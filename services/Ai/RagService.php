<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\PermissionService;
use PDO;

/**
 * Retrieval-Augmented Generation (RAG) pipeline.
 *
 * Indexing: document -> chunks -> embeddings -> stored in the database.
 * Querying:  question -> embedding -> similar chunks -> grounded answer.
 * Tasking:   instruction -> retrieval -> structured draft (agentic).
 *
 * Anti-hallucination: prompts forbid inventing facts and require citing the
 * numbered excerpts; temperature is kept low (0.2) for precise, stable output.
 */
final class RagService
{
    public function __construct(
        private readonly MetaClient $client,
        private readonly EmbeddingService $embeddings,
        private readonly PDO $db,
        private readonly ?PermissionService $permissions = null,
        private readonly string $lang = 'en',
    ) {
    }

    /**
     * Split a text into small, overlapping chunks.
     *
     * @return list<string>
     */
    public function chunk(string $text, int $size = 800, int $overlap = 100): array
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $length = mb_strlen($text);

        if ($length === 0) {
            return [];
        }

        $chunks = [];
        $start = 0;

        while ($start < $length) {
            $end = min($start + $size, $length);

            // Break at a word boundary instead of mid-word: the excerpt stays
            // readable and the next chunk keeps the full word. The boundary is
            // only used when a space sits in the last 40% of the slice.
            if ($end < $length) {
                $lastSpace = mb_strrpos(mb_substr($text, $start, $size), ' ');
                if ($lastSpace !== false && $lastSpace > (int) ($size * 0.6)) {
                    $end = $start + $lastSpace;
                }
            }

            $chunks[] = rtrim(mb_substr($text, $start, $end - $start));
            if ($end >= $length) {
                break;
            }

            // $end - $start >= 60% of $size > $overlap, so progress is guaranteed.
            $start = max(0, $end - $overlap);
        }

        return $chunks;
    }

    /**
     * Index a document: split it, embed each chunk, and store it.
     *
     * When a version id is given, chunks are attached to that immutable version.
     */
    public function indexDocument(int $documentId, string $content, ?int $versionId = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO document_chunks (document_id, version_document_id, chunk_index, content, embedding)
             VALUES (:document_id, :version_id, :chunk_index, :content, :embedding)'
        );

        foreach ($this->chunk($content) as $index => $chunk) {
            $stmt->execute([
                'document_id' => $documentId,
                'version_id' => $versionId,
                'chunk_index' => $index,
                'content' => $chunk,
                'embedding' => json_encode($this->embeddings->embed($chunk), JSON_THROW_ON_ERROR),
            ]);
        }
    }

    /**
     * Answer a question grounded in the indexed documents (plain string).
     *
     * When $userId is provided, only the documents that user may consult are
     * searched (RBAC scoping).
     */
    public function query(string $question, int $topK = 6, ?int $userId = null): string
    {
        return (string) $this->answer($question, $topK, $userId)['answer'];
    }

    /**
     * Answer a question grounded in the indexed documents, with cited sources.
     *
     * @return array{answer: string, sources: list<array{title: string, excerpt: string}>}
     */
    public function answer(string $question, int $topK = 6, ?int $userId = null): array
    {
        // Catalogue intent: "list all documents" cannot be answered from chunk
        // retrieval — the top-K only sees fragments of the dominant document.
        // Feed the permission-scoped document catalogue to the model instead.
        if ($this->isCatalogQuestion($question)) {
            $chunks = $this->catalogChunks($userId);
            if ($chunks === []) {
                return [
                    'answer' => $this->msg('no_documents'),
                    'sources' => [],
                ];
            }

            $system = $this->answerPrompt() . "\n\n" . $this->labeledContext($chunks);
            $answer = $this->client->chat($question, [['role' => 'system', 'content' => $system]], null, 0.2);
            $sources = $this->sources($this->citedChunks($answer, $chunks));

            return ['answer' => $answer, 'sources' => $this->translateSources($sources, $question)];
        }

        $chunks = $this->retrieveSimilar($this->embeddings->embed($question), $topK, $question, $userId);

        if ($chunks === []) {
            return [
                'answer' => $this->msg('no_documents'),
                'sources' => [],
            ];
        }

        $system = $this->answerPrompt() . "\n\n" . $this->labeledContext($chunks);
        $answer = $this->client->chat($question, [['role' => 'system', 'content' => $system]], null, 0.2);
        $sources = $this->sources($this->citedChunks($answer, $chunks));

        return ['answer' => $answer, 'sources' => $this->translateSources($sources, $question)];
    }

    /**
     * Agentic task: draft a structured document (note, report) grounded in the
     * knowledge base.
     *
     * @return array{draft: string, sources: list<array{title: string, excerpt: string}>}
     */
    public function task(string $instruction, int $topK = 8, ?int $userId = null): array
    {
        $chunks = $this->retrieveSimilar($this->embeddings->embed($instruction), $topK, $instruction, $userId);

        if ($chunks === []) {
            return [
                'draft' => $this->msg('no_chunks'),
                'sources' => [],
            ];
        }

        $system = $this->taskPrompt() . "\n\n" . $this->labeledContext($chunks);
        $draft = $this->client->chat($instruction, [['role' => 'system', 'content' => $system]], null, 0.2);
        $sources = $this->sources($this->citedChunks($draft, $chunks));

        return ['draft' => $draft, 'sources' => $this->translateSources($sources, $instruction)];
    }

    /**
     * Summarize a document from its indexed chunks.
     *
     * @return array{summary: string, document_id: int, sources: list<array{title: string, excerpt: string}>}
     */
    public function summarize(int $documentId, int $maxChunks = 20, ?int $userId = null): array
    {
        if ($userId !== null && !$this->canConsult($documentId, $userId)) {
            return [
                'summary' => $this->msg('not_accessible'),
                'document_id' => $documentId,
                'sources' => [],
            ];
        }

        $chunks = $this->chunksForDocument($documentId, $maxChunks);

        if ($chunks === []) {
            return [
                'summary' => $this->msg('no_content'),
                'document_id' => $documentId,
                'sources' => [],
            ];
        }

        $system = $this->summaryPrompt() . "\n\n" . $this->labeledContext($chunks);
        $summary = $this->client->chat('Résume ce document.', [['role' => 'system', 'content' => $system]], null, 0.2);

        return [
            'summary' => $summary,
            'document_id' => $documentId,
            'sources' => $this->sources($chunks),
        ];
    }

    /**
     * Translate the displayed source excerpts into the language of the query.
     * One extra model call covering all excerpts; any failure keeps the
     * originals so the feature degrades silently rather than breaking answers.
     *
     * @param list<array{num: int, title: string, excerpt: string}> $sources
     * @return list<array{num: int, title: string, excerpt: string}>
     */
    private function translateSources(array $sources, string $query): array
    {
        if ($sources === []) {
            return $sources;
        }

        $numbered = [];
        foreach ($sources as $source) {
            $numbered[] = '[' . $source['num'] . '] ' . $source['excerpt'];
        }

        try {
            $translation = $this->client->chat(
                "QUESTION:\n" . $query
                    . "\n\nEXTRAIT(S):\n" . implode("\n", $numbered),
                [['role' => 'system', 'content' =>
                    "Tu es un traducteur. Traduis chaque extrait numéroté dans la langue de la QUESTION posée.\n"
                    . "RÈGLES :\n"
                    . "1. Garde le numéro [n] devant chaque extrait, une ligne par extrait.\n"
                    . "2. Traduction fidèle, sans ajout ni résumé. Conserve chiffres, dates et noms propres exacts.\n"
                    . "3. Si un extrait est déjà dans la langue de la question, recopie-le tel quel.\n"
                    . "4. Ne renvoie que les extraits traduits, aucune phrase d'introduction."]],
                null,
                0.1,
            );

            preg_match_all('/\[(\d+)\]\s*(.+)/', $translation, $matches, PREG_SET_ORDER);
            $byNum = [];
            foreach ($matches as $m) {
                $byNum[(int) $m[1]] = trim($m[2]);
            }

            foreach ($sources as &$source) {
                if (isset($byNum[$source['num']]) && $byNum[$source['num']] !== '') {
                    $source['excerpt'] = $byNum[$source['num']];
                }
            }
            unset($source);

            return $sources;
        } catch (\Throwable) {
            return $sources;
        }
    }

    /**
     * Detect questions about the document collection itself
     * ("list all documents", "quels sont les titres des documents enregistrés").
     */
    private function isCatalogQuestion(string $question): bool
    {
        return preg_match(
            '/\b(list|liste|tous|toutes|all|quels?|which|combien|how many|montre|affiche|enumerate|show)\b.{0,60}\b(documents?|fichiers?|files?|titres?|titles?|intitulés?)\b'
            . '|\b(documents?|fichiers?|files?)\b.{0,40}\b(enregistr|index|stock|disponibles?|available|présents?|stored)\b/iu',
            $question
        ) === 1;
    }

    /**
     * One pseudo-extract per accessible document, so the model can enumerate
     * the real titles (catalogue questions). Scoped by the same permission
     * rules as chunk retrieval.
     *
     * @return list<array{document_id: int, content: string, title: string, score: float}>
     */
    private function catalogChunks(?int $userId): array
    {
        $allowed = $userId === null ? true : $this->accessibleDocumentIds($userId);
        if ($allowed === []) {
            return [];
        }

        $stmt = $this->db->query(
            'SELECT d.id, d.title, d.filename, d.created_at, c.nom AS categorie
             FROM documents d
             LEFT JOIN categorie c ON c.id = d.categorie_id
             WHERE d.supprime_le IS NULL
             ORDER BY d.title'
        );

        $chunks = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (is_array($allowed) && !isset($allowed[(int) $row['id']])) {
                continue;
            }
            $categorie = ($row['categorie'] ?? '') !== '' ? (string) $row['categorie'] : '—';
            $chunks[] = [
                'document_id' => (int) $row['id'],
                'content' => 'Titre : ' . trim((string) $row['title'])
                    . ' · Catégorie : ' . $categorie
                    . ' · Enregistré le : ' . substr((string) ($row['created_at'] ?? ''), 0, 10)
                    . ' · Fichier : ' . (string) ($row['filename'] ?? ''),
                'title' => (string) ($row['title'] ?? 'Document'),
                'score' => 1.0,
            ];
        }

        return $chunks;
    }

    /**
     * Keep only the chunks the model actually cited ("Sources : [3]"),
     * renumbered so the displayed sources match the citation numbers.
     *
     * @param list<array<string, mixed>> $chunks
     * @return list<array<string, mixed>>
     */
    private function citedChunks(string $text, array $chunks): array
    {
        preg_match_all('/\[(\d+)\]/', $text, $matches);
        $cited = array_values(array_unique(array_map('intval', $matches[1])));
        $cited = array_filter($cited, static fn (int $n): bool => $n >= 1 && $n <= count($chunks));
        sort($cited);

        // No citation at all → nothing was found/used: show no sources rather
        // than dumping the whole top-K (e.g. a "not in the documents" answer).
        if ($cited === []) {
            return [];
        }

        return array_map(static function (int $n) use ($chunks): array {
            $chunk = $chunks[$n - 1];
            $chunk['num'] = $n; // keep the original extract number for display
            return $chunk;
        }, $cited);
    }

    /**
     * Build the numbered context the model must ground itself in.
     *
     * @param list<array{content: string, title: string, score: float}> $chunks
     */
    private function labeledContext(array $chunks): string
    {
        $parts = [];

        foreach ($chunks as $index => $chunk) {
            $parts[] = sprintf(
                "[Extrait %d — %s]\n%s",
                $index + 1,
                (string) ($chunk['title'] ?? 'Document'),
                (string) $chunk['content'],
            );
        }

        return implode("\n\n---\n\n", $parts);
    }

    private function answerPrompt(): string
    {
        $langRule = '5. Réponds dans la langue de la question : si la question est posée en anglais, réponds en anglais ; en coréen, en coréen, etc. Cette règle prime sur toute autre considération de langue.';
        $notFound = $this->lang === 'en'
            ? 'This information is not in the provided documents.'
            : 'Cette information ne figure pas dans les documents fournis.';

        return <<<PROMPT
Tu es un assistant documentaire expert, rigoureux et professionnel.

RÈGLES ABSOLUES :
1. Réponds UNIQUEMENT à partir des extraits fournis. N'invente aucun fait, chiffre, date, nom ni citation.
2. Si l'information demandée n'est pas présente dans les extraits, réponds exactement : « {$notFound} »
3. Commence par une réponse DIRECTE à la question, formulée avec tes propres mots : synthétise, ne recopie pas l'extrait mot pour mot (sauf pour les citations exactes placées entre guillemets).
4. Conserve les données exactes (chiffres, dates, noms, intitulés) telles quelles, sans les arrondir.
4b. Quand la réponse cite un titre, un en-tête ou un intitulé rédigé dans une autre langue que la question, donne d'abord sa traduction dans la langue de la réponse, suivie de l'original entre parenthèses. Exemple (question en anglais, titre en français) : "Death notice and fraternal communion" ("Avis de décès et communion fraternelle").
4c. Mets en gras (**…**) la réponse directe à la question ainsi que les données clés citées (titres, dates, chiffres, noms propres).
4d. Toute citation littérale entre guillemets DOIT être traduite dans la langue de la réponse ; l'original ne peut être gardé qu'entre parenthèses juste après. Aucun passage de la réponse ne doit rester dans une autre langue que celle de la question.
4e. N'emploie jamais un mot ou un passage visiblement tronqué en bord d'extrait : si un extrait se termine au milieu d'une phrase ou d'un mot, ignore cette fin plutôt que de la citer.
5b. Les étiquettes « [Extrait n — Titre] » font partie des informations fournies : une question portant sur un titre, un nom de document ou la présence d'un document DOIT être répondue à partir de ces titres.
$langRule
6. Structure :
   - une phrase de synthèse qui répond directement ;
   - les points clés sous forme de liste à puces ;
   - termine par « Sources : [1], [2] » en citant les numéros réellement utilisés.
7. Ton neutre et précis, sans remplissage ni formule de politesse.
PROMPT;
    }

    private function taskPrompt(): string
    {
        $langRule = "7. Rédige le document dans la langue de l'instruction : instruction en anglais → document en anglais ; en coréen → en coréen, etc.";

        return <<<PROMPT
Tu es un rédacteur pédagogique expérimenté, spécialiste de l'Approche Par les Compétences / Pédagogie de l'Intégration (APC/PI).

Mission : à partir du programme fourni dans les extraits, rédige le document demandé (leçon détaillée, note, rapport…).

RÈGLES :
1. Le programme est l'OSSATURE : reprends fidèlement la classe, la discipline, l'intitulé de la leçon, le contenu officiel et l'approche pédagogique, en citant les extraits [1], [2]…
2. Tu DOIS développer le contenu pédagogique demandé : objectifs d'apprentissage, prérequis, déroulement (théorie et pratique), activités, exercices et critères d'évaluation. Ce développement est une PROPOSITION pédagogique cohérente avec le programme.
3. N'invente aucun fait factuel externe (noms de personnes, chiffres, références bibliographiques) non justifié par les extraits ou par une pratique pédagogique standard.
4. Distingue clairement ce qui provient du programme (cité) de ce qui est proposé.
4b. Mets en gras (**…**) le titre du document, les intitulés de sections et les données clés (dates, chiffres, noms propres).
4c. Toute citation littérale entre guillemets DOIT être traduite dans la langue de rédaction ; l'original ne peut être gardé qu'entre parenthèses juste après. Aucun passage du document ne doit rester dans une autre langue que celle de l'instruction.
5. Structure :
   - titre et identification (classe, discipline, durée) ;
   - objectifs d'apprentissage ;
   - prérequis ;
   - déroulement détaillé (séances, théorie/pratique) ;
   - exercices / activité pratique ;
   - évaluation ;
   - sources [1], [2]…
6. Ton professionnel, prêt à être utilisé en classe.
$langRule
PROMPT;
    }

    private function summaryPrompt(): string
    {
        $langRule = $this->lang === 'en'
            ? '4. Write the summary in English.'
            : '4. Rédige le résumé en français.';

        return <<<PROMPT
Tu résumes un document de façon fidèle, précise et professionnelle.

RÈGLES :
1. Reprends les faits, chiffres, dates et noms exacts.
2. Structure : une phrase de synthèse, puis 3 à 6 points clés.
3. N'invente rien et n'ajoute aucune interprétation.
$langRule
PROMPT;
    }

    /**
     * @param list<array{content: string, title: string, score: float}> $chunks
     * @return list<array{title: string, excerpt: string}>
     */
    private function sources(array $chunks): array
    {
        // Keep the exact retrieval order: each entry matches the numbered
        // "[Extrait n — Titre]" of the prompt context, so the "[3]" citations
        // of the model point at the right source. No dedupe here — two chunks
        // of the same document are two distinct numbered extracts.
        return array_map(static function (array $chunk, int $i): array {
            return [
                'num' => (int) ($chunk['num'] ?? $i + 1),
                'document_id' => (int) ($chunk['document_id'] ?? 0),
                'title' => (string) ($chunk['title'] ?? 'Document'),
                'excerpt' => mb_substr((string) ($chunk['content'] ?? ''), 0, 240),
            ];
        }, $chunks, array_keys($chunks));
    }

    /**
     * @return list<array{content: string, title: string, score: float}>
     */
    private function chunksForDocument(int $documentId, int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.content, d.title
             FROM document_chunks c
             JOIN documents d ON d.id = c.document_id
             WHERE c.document_id = :document
             ORDER BY c.chunk_index
             LIMIT :limit'
        );
        $stmt->bindValue('document', $documentId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $chunks = [];

        foreach ($rows as $row) {
            $chunks[] = [
                'content' => (string) $row['content'],
                'title' => (string) ($row['title'] ?? 'Document'),
                'score' => 1.0,
            ];
        }

        return $chunks;
    }

    /**
     * Retrieve the most relevant chunks for a query (exposed for tooling and tests).
     *
     * @return list<array{content: string, title: string, score: float}>
     */
    public function search(string $query, int $topK = 6, ?int $userId = null): array
    {
        return $this->retrieveSimilar($this->embeddings->embed($query), $topK, $query, $userId);
    }

    /**
     * Retrieve the chunks most similar to the query (hybrid ranking).
     *
     * The final score blends the semantic similarity (cosine) with a lexical
     * overlap score, which keeps precision high with the local hashing
     * embeddings and stays correct when an API embedding is configured.
     *
     * @param list<float> $queryVector
     * @return list<array{content: string, title: string, score: float}>
     */
    private function retrieveSimilar(array $queryVector, int $topK, string $query = '', ?int $userId = null): array
    {
        $allowed = $userId === null ? true : $this->accessibleDocumentIds($userId);

        if ($allowed === []) {
            return [];
        }

        $rows = $this->db->query(
            'SELECT c.document_id, c.version_document_id, c.chunk_index, c.content, c.embedding, d.title
             FROM document_chunks c
             JOIN documents d ON d.id = c.document_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        $scored = [];
        foreach ($rows as $row) {
            if (is_array($allowed) && !isset($allowed[(int) $row['document_id']])) {
                continue;
            }
            $vector = json_decode((string) $row['embedding'], true);
            if (!is_array($vector)) {
                continue;
            }

            $semantic = $this->embeddings->similarity($queryVector, $vector);
            $lexical = $query !== '' ? $this->lexicalScore($query, (string) $row['content']) : 0.0;

            $scored[] = [
                'document_id' => (int) $row['document_id'],
                'version_id' => (int) ($row['version_document_id'] ?? 0),
                'chunk_index' => (int) $row['chunk_index'],
                'content' => (string) $row['content'],
                'title' => (string) ($row['title'] ?? 'Document'),
                'score' => 0.6 * $semantic + 0.4 * $lexical,
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $top = array_slice($scored, 0, $topK);

        // Neighbor expansion: a heading (e.g. the title of an announcement) often
        // sits in the chunk just before/after the best-scoring one. Enrich each
        // hit with its adjacent chunks from the same version so the model sees
        // the full passage instead of a truncated fragment.
        return $this->expandWithNeighbors($top);
    }

    /**
     * Merge each top chunk with its previous/next siblings (same document version).
     *
     * @param list<array<string, mixed>> $top
     * @return list<array{content: string, title: string, score: float}>
     */
    private function expandWithNeighbors(array $top): array
    {
        if ($top === []) {
            return [];
        }

        $wanted = [];
        $seen = [];
        foreach ($top as $item) {
            $base = $item['document_id'] . '|' . $item['version_id'] . '|';
            $seen[$base . $item['chunk_index']] = true;
            // ±1 neighbours + the version's first chunk: headings/titles of the
            // document live at the start and are otherwise never retrieved.
            foreach ([$item['chunk_index'] - 1, $item['chunk_index'] + 1, 0] as $i) {
                $wanted[$base . $i] = true;
            }
        }
        $wanted = array_diff_key($wanted, $seen);

        if ($wanted === []) {
            return array_map(static fn (array $i): array => [
                'document_id' => $i['document_id'],
                'content' => $i['content'],
                'title' => $i['title'],
                'score' => $i['score'],
            ], $top);
        }

        $docIds = array_values(array_unique(array_map(static fn (array $i): int => $i['document_id'], $top)));
        $placeholders = implode(',', array_fill(0, count($docIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT document_id, version_document_id, chunk_index, content
             FROM document_chunks
             WHERE document_id IN ($placeholders)"
        );
        $stmt->execute($docIds);

        $neighbors = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = $row['document_id'] . '|' . (int) ($row['version_document_id'] ?? 0) . '|' . $row['chunk_index'];
            if (isset($wanted[$key])) {
                $neighbors[$key] = (string) $row['content'];
            }
        }

        return array_map(static function (array $item) use ($neighbors): array {
            $base = $item['document_id'] . '|' . $item['version_id'] . '|';
            $first = $neighbors[$base . '0'] ?? null;
            $before = $neighbors[$base . ($item['chunk_index'] - 1)] ?? null;
            $after = $neighbors[$base . ($item['chunk_index'] + 1)] ?? null;

            $content = $item['content'];
            if ($before !== null) {
                $content = $before . ' ' . $content;
            }
            if ($after !== null) {
                $content .= ' ' . $after;
            }
            // Prepend the document opening when it isn't already included and
            // the hit isn't the first chunk — the heading usually lives there.
            if ($first !== null && $item['chunk_index'] > 1) {
                $content = $first . ' […] ' . $content;
            }

            return [
                'document_id' => $item['document_id'],
                'content' => $content,
                'title' => $item['title'],
                'score' => $item['score'],
            ];
        }, $top);
    }

    /**
     * Documents the user may consult for retrieval/summarization.
     *
     * Rules (aligned with PermissionService):
     *  - global administrators see every non-deleted document of their tenant;
     *  - the owner always sees their own documents;
     *  - `prive` documents are owner-only;
     *  - a document attached to a space is visible when the space is
     *    `public_interne` or the user holds `document.consulter` on it.
     *
     * @return true|array<int, true> `true` = no restriction, map of ids otherwise
     */
    private function accessibleDocumentIds(int $userId): array|bool
    {
        if ($this->permissions === null) {
            return true;
        }

        $actor = $this->permissions->compteActif($userId);
        if ($actor === null) {
            return [];
        }

        $tenantId = (int) $actor['tenant_id'];
        $isAdmin = in_array($actor['role_global'], ['administrateur_technique', 'administrateur_fonctionnel'], true);

        $stmt = $this->db->prepare(
            'SELECT id, espace_id, confidentialite, proprietaire_id
             FROM documents
             WHERE tenant_id = :tenant AND supprime_le IS NULL'
        );
        $stmt->execute(['tenant' => $tenantId]);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($isAdmin) {
            $all = [];
            foreach ($documents as $doc) {
                $all[(int) $doc['id']] = true;
            }

            return $all;
        }

        $publicSpaces = [];
        $stmt = $this->db->prepare(
            'SELECT id FROM espace
             WHERE tenant_id = :tenant AND visibilite = \'public_interne\' AND supprime_le IS NULL'
        );
        $stmt->execute(['tenant' => $tenantId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $spaceId) {
            $publicSpaces[(int) $spaceId] = true;
        }

        $spaceCache = [];
        $allowed = [];
        foreach ($documents as $doc) {
            $docId = (int) $doc['id'];
            $spaceId = $doc['espace_id'] === null ? null : (int) $doc['espace_id'];

            if ((int) ($doc['proprietaire_id'] ?? 0) === $userId) {
                $allowed[$docId] = true;
                continue;
            }

            if ($spaceId === null || $doc['confidentialite'] === 'prive') {
                continue;
            }

            if (isset($publicSpaces[$spaceId])) {
                $allowed[$docId] = true;
                continue;
            }

            $spaceCache[$spaceId] ??= $this->permissions->peut($userId, 'document.consulter', $spaceId);
            if ($spaceCache[$spaceId]) {
                $allowed[$docId] = true;
            }
        }

        return $allowed;
    }

    /**
     * Whether the user may consult one specific document.
     */
    private function canConsult(int $documentId, int $userId): bool
    {
        $allowed = $this->accessibleDocumentIds($userId);

        return $allowed === true || isset($allowed[$documentId]);
    }

    /**
     * Localized fallback message.
     */
    private function msg(string $key): string
    {
        $messages = [
            'fr' => [
                'no_documents' => "Cette information ne figure pas dans les documents fournis. Indexez d'abord un document.",
                'no_chunks' => "Aucun document pertinent n'a été trouvé. Indexez d'abord un document.",
                'no_content' => 'Aucun contenu indexé pour ce document.',
                'not_accessible' => "Ce document n'est pas accessible avec vos permissions.",
            ],
            'en' => [
                'no_documents' => 'This information is not in the provided documents. Index a document first.',
                'no_chunks' => 'No relevant document was found. Index a document first.',
                'no_content' => 'No indexed content for this document.',
                'not_accessible' => 'This document is not accessible with your permissions.',
            ],
        ];

        return $messages[$this->lang][$key] ?? $messages['fr'][$key] ?? $key;
    }

    /**
     * Lexical overlap between the query and a chunk (0..1), stop words removed.
     */
    private function lexicalScore(string $query, string $content): float
    {
        $queryTokens = $this->tokens($query);
        $contentTokens = $this->tokens($content);

        if ($queryTokens === [] || $contentTokens === []) {
            return 0.0;
        }

        $contentCounts = array_count_values($contentTokens);
        $matches = 0;

        foreach ($queryTokens as $token) {
            if (isset($contentCounts[$token])) {
                $matches++;
            }
        }

        return $matches / count($queryTokens);
    }

    /**
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;
        $tokens = preg_split('/\s+/u', trim($text)) ?: [];

        $stopWords = [
            'le', 'la', 'les', 'de', 'des', 'du', 'un', 'une', 'et', 'ou', 'en', 'a', 'au', 'aux',
            'ce', 'ces', 'que', 'qui', 'quel', 'quels', 'quelle', 'quelles', 'est', 'sont',
            'pour', 'dans', 'sur', 'avec', 'par', 'il', 'elle', 'ils', 'elles', 'on', 'nous',
            'vous', 'je', 'tu', 'ne', 'pas', 'plus', 'son', 'sa', 'ses', 'leur', 'leurs',
            'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'with', 'is', 'are',
            'what', 'which', 'how', 'why', 'this', 'that', 'from', 'by', 'at', 'be', 'as',
        ];

        return array_values(array_filter(
            $tokens,
            static fn (string $token): bool => $token !== ''
                && mb_strlen($token) > 1
                && !in_array($token, $stopWords, true),
        ));
    }
}
