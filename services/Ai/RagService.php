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

    /** @var array<string, list<float>> decoded embedding cache (per process) */
    private static array $vectorCache = [];

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
        self::$vectorCache = []; // new chunks make cached vectors stale

        // Replace, never stack: a re-index must not leave stale chunks from
        // previous versions searchable (wrong stats, outdated matches).
        $this->db->prepare('DELETE FROM document_chunks WHERE document_id = :id')
            ->execute(['id' => $documentId]);

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
    public function answer(string $question, int $topK = 6, ?int $userId = null, ?int $espaceId = null): array
    {
        // An explicit question about a document the user may not consult is
        // refused up front: no retrieval, no off-topic answer, no leak.
        if ($this->inaccessibleDocumentScope($question, $userId) !== null) {
            return [
                'answer' => $this->msg('not_accessible'),
                'sources' => [],
                'refused' => true,
            ];
        }

        // Catalogue intent: "list all documents" cannot be answered from chunk
        // retrieval — the top-K only sees fragments of the dominant document.
        // Feed the permission-scoped document catalogue to the model instead.
        if ($this->isCatalogQuestion($question)) {
            // The catalogue lists metadata; corrections are declared on the
            // extracts rather than substituted, so the dates stay intact.
            $chunks = $this->annotateCorrections($this->catalogChunks($userId, $espaceId));
            if ($chunks === []) {
                return [
                    'answer' => $this->msg('no_documents'),
                    'sources' => [],
                ];
            }

                $this->openQuestion = $this->isOpenQuestion($question);
        $this->translationRequested = $this->wantsTranslation($question);
            $system = $this->answerPrompt() . "\n\n" . $this->labeledContext($chunks);
            $answer = $this->client->chat($question, [['role' => 'system', 'content' => $system]], null, 0.2);
            $answer = $this->stripStrayNotFound($answer);
        $sources = $this->sourcesFor($answer, $chunks);

            return ['answer' => $answer, 'sources' => $this->translateSources($sources, $question)];
        }

        $chunks = $this->retrieveSimilar($this->embeddings->embed($question), $topK, $question, $userId, $espaceId);

        if ($chunks === []) {
            return [
                'answer' => $this->msg('no_documents'),
                'sources' => [],
            ];
        }

        // A weak best score means the workspace holds nothing about this question.
        // Answering anyway produces a confident sentence about the wrong subject,
        // which is the worst outcome for a tool whose claim is grounding.
        // Measured: relevant matches score 0.53 and above, off-topic ones 0.17 and below.
        if ((float) ($chunks[0]['score'] ?? 0.0) < 0.25) {
            return [
                'answer' => $this->msg($espaceId === null ? 'no_documents' : 'not_in_workspace'),
                'sources' => [],
                'refused' => true,
            ];
        }

            $this->openQuestion = $this->isOpenQuestion($question);
        $this->translationRequested = $this->wantsTranslation($question);
            $system = $this->answerPrompt() . "\n\n" . $this->labeledContext($chunks);
        $answer = $this->client->chat($question, [['role' => 'system', 'content' => $system]], null, 0.2);

        // Recall retry: a "not found" answer may hide a retrieval miss —
        // widen top-K once before conceding.
        if ($this->looksNotFound($answer)) {
            $wider = $this->retrieveSimilar($this->embeddings->embed($question), $topK * 2, $question, $userId, $espaceId);
            if (count($wider) > count($chunks)) {
                $chunks = $wider;
                    $this->openQuestion = $this->isOpenQuestion($question);
        $this->translationRequested = $this->wantsTranslation($question);
            $system = $this->answerPrompt() . "\n\n" . $this->labeledContext($chunks);
                $answer = $this->client->chat($question, [['role' => 'system', 'content' => $system]], null, 0.2);
            }
        }

        $answer = $this->stripStrayNotFound($answer);
        $sources = $this->sourcesFor($answer, $chunks);

        return ['answer' => $answer, 'sources' => $this->translateSources($sources, $question)];
    }

    /**
     * The "nothing found" sentence in every supported UI language.
     *
     * The model is asked to answer in the QUESTION's language, so it may return
     * the French variant even when the interface is in English. Detection must
     * therefore accept both, otherwise the recall retry never triggers.
     *
     * @return list<string>
     */
    private bool $openQuestion = false;

    private bool $translationRequested = false;

    private ?string $requestedLangue = null;

    private function notFoundPhrases(): array
    {
        return [
            'This information is not in the provided documents.',
            'Cette information ne figure pas dans les documents fournis.',
        ];
    }

    /**
     * The model sometimes appends the "nothing found" sentence as a caveat at
     * the end of an answer that is otherwise perfectly grounded. The prompt
     * forbids it; the code enforces it.
     */
    private function stripStrayNotFound(string $answer): string
    {
        $trimmed = trim($answer);

        foreach ($this->notFoundPhrases() as $phrase) {
            if ($trimmed === $phrase) {
                return $trimmed; // a genuine "not found" answer
            }

            $answer = str_replace([$phrase, "\u{00AB} " . $phrase . " \u{00BB}", '« ' . $phrase . ' »'], '', $answer);
        }

        $answer = preg_replace("/\n{3,}/", "\n\n", $answer) ?? $answer;

        return trim($answer);
    }
    private function looksNotFound(string $answer): bool
    {
        foreach ($this->notFoundPhrases() as $phrase) {
            if (str_contains($answer, $phrase)) {
                return true;
            }
        }

        return false;
    }
    private function notFoundPhrase(): string
    {
        return $this->lang === 'en'
            ? 'This information is not in the provided documents.'
            : 'Cette information ne figure pas dans les documents fournis.';
    }

    /**
     * Agentic task — plan → retrieve → draft → verify → revise.
     *
     *  1. Plan: the model decomposes the instruction into targeted search
     *     queries, knowing the titles of the documents the user may access.
     *  2. Retrieve: each query runs through the permission-scoped retriever;
     *     results are merged and de-duplicated.
     *  3. Draft: the document is written from the merged, numbered extracts.
     *  4. Verify: every sourced claim is checked against its cited extract.
     *  5. Revise: unsupported claims are corrected or removed (one pass).
     *
     * Each step is reported in `steps` so the reasoning is auditable in the UI.
     *
     * @return array{draft: string, sources: list<array<string, mixed>>, steps: list<array<string, mixed>>}
     */
    public function task(string $instruction, int $topK = 8, ?int $userId = null, array $documentIds = [], ?int $espaceId = null): array
    {
        $steps = [];

        $queries = $this->planQueries($instruction, $userId);
        $steps[] = ['step' => 'plan', 'queries' => $queries];

        $merged = [];
        $trace = [];
        foreach ($queries as $query) {
            $found = $this->retrieveSimilar($this->embeddings->embed($query), max(3, intdiv($topK, 2)), $query, $userId, $espaceId);
            $new = 0;
            foreach ($found as $chunk) {
                $key = ($chunk['document_id'] ?? 0) . '|' . ($chunk['version_id'] ?? 0) . '|' . ($chunk['chunk_index'] ?? md5((string) $chunk['content']));
                if (!isset($merged[$key]) || $merged[$key]['score'] < $chunk['score']) {
                    $new += isset($merged[$key]) ? 0 : 1;
                    $merged[$key] = $chunk;
                }
            }
            $trace[] = ['query' => $query, 'found' => count($found), 'new' => $new];
        }
        $chunks = array_values($merged);
        usort($chunks, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $chunks = array_slice($chunks, 0, $topK + 4);
        $steps[] = [
            'step' => 'retrieve',
            'queries' => $trace,
            'extracts' => count($chunks),
            'documents' => count(array_unique(array_column($chunks, 'document_id'))),
        ];

        // Scope: documents chosen in the form, otherwise a document named in the
        // instruction. Everything outside it is discarded, so the draft can only
        // cite the sources the user asked for.
        $scopeIds = [];
        $allowedScope = $userId === null ? true : $this->accessibleDocumentIds($userId);

        // The workspace shown at the top of the page scopes the task too:
        // a document filed elsewhere must not be pulled in silently.
        if ($espaceId !== null && $espaceId > 0) {
            $inSpace = $this->documentIdsInEspace($espaceId);

            $allowedScope = $allowedScope === true
                ? $inSpace
                : array_intersect_key($allowedScope, $inSpace);
        }

        foreach ($documentIds as $candidate) {
            $candidate = (int) $candidate;

            if ($candidate > 0 && ($allowedScope === true || isset($allowedScope[$candidate]))) {
                $scopeIds[] = $candidate;
            }
        }

        // Documents explicitly requested but all forbidden: refuse instead of
        // silently answering from unrelated sources.
        if ($documentIds !== [] && $scopeIds === []) {
            $steps[] = ['step' => 'scope', 'refused' => 'document_ids_inaccessible'];

            return [
                'draft' => $this->msg('not_accessible'),
                'sources' => [],
                'steps' => $steps,
                'refused' => true,
            ];
        }

        if ($scopeIds === []) {
            // An instruction naming a forbidden document is refused too.
            if ($this->inaccessibleDocumentScope($instruction, $userId) !== null) {
                $steps[] = ['step' => 'scope', 'refused' => 'document_inaccessible'];

                return [
                    'draft' => $this->msg('not_accessible'),
                    'sources' => [],
                    'steps' => $steps,
                    'refused' => true,
                ];
            }

            $detected = $this->documentScope($instruction, $userId);

            if ($detected !== null) {
                $scopeIds[] = $detected;
            }
        }

        $scopeNote = '';

        if ($scopeIds !== []) {
            $scoped = array_values(array_filter(
                $chunks,
                static fn (array $chunk): bool => in_array((int) ($chunk['document_id'] ?? 0), $scopeIds, true)
            ));

            if ($scoped === []) {
                foreach ($scopeIds as $scopeId) {
                    $scoped = array_merge($scoped, $this->chunksForDocument($scopeId, $topK));
                }
            }

            if ($scoped !== []) {
                $chunks = $scoped;
            }

            $titles = [];

            foreach ($scopeIds as $scopeId) {
                $stmt = $this->db->prepare('SELECT title FROM documents WHERE id = ?');
                $stmt->execute([$scopeId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row !== false && (string) ($row['title'] ?? '') !== '') {
                    $titles[] = '« ' . (string) $row['title'] . ' »';
                }
            }

            $scopeNote = "SCOPE: cette tâche doit s'appuyer UNIQUEMENT sur "
                . (count($titles) > 1 ? 'les documents ' : 'le document ')
                . implode(', ', $titles)
                . ". N'utilise aucun autre document, même si un extrait voisin semble pertinent.\n\n";
        }

        $steps[] = [
            'step' => 'scope',
            'document_id' => $scopeIds === [] ? null : $scopeIds[0],
            'document_ids' => $scopeIds,
            'extracts' => count($chunks),
        ];


        if ($chunks === []) {
            return ['draft' => $this->msg('no_chunks'), 'sources' => [], 'steps' => $steps];
        }

        $context = $scopeNote . $this->labeledContext($chunks);
        $draft = $this->client->chat($instruction, [['role' => 'system', 'content' => $this->taskPrompt() . "\n\n" . $context]], null, 0.2);
        $steps[] = ['step' => 'draft', 'citations' => count($this->citedChunks($draft, $chunks))];

        $verdict = $this->verifyDraft($draft, $context);
        $revised = false;
        if ($verdict['unsupported'] !== []) {
            $revision = $this->reviseDraft($instruction, $draft, $verdict['unsupported'], $context);
            if ($revision !== '') {
                $draft = $revision;
                $revised = true;
            }
        }
        $steps[] = [
            'step' => 'verify',
            'checked' => $verdict['checked'],
            'unsupported' => count($verdict['unsupported']),
            'revised' => $revised,
            'available' => $verdict['available'],
        ];

        // Conformity: a grounded answer can still be off-topic.
        $conformity = $this->checkConformity($instruction, $draft);
        $conformityRevised = false;

        if (!$conformity['conforme']) {
            $fixed = $this->reviseForConformity($instruction, $draft, $conformity['raison'], $context);

            if ($fixed !== '') {
                $draft = $fixed;
                $conformityRevised = true;
                $conformity = $this->checkConformity($instruction, $draft);
            }
        }

        $steps[] = [
            'step' => 'conformity',
            'conforme' => $conformity['conforme'],
            'raison' => $conformity['raison'],
            'revised' => $conformityRevised,
            'available' => $conformity['available'],
        ];

        $sources = $this->sourcesFor($draft, $chunks);

        return ['draft' => $draft, 'sources' => $this->translateSources($sources, $instruction), 'steps' => $steps];
    }

    /**
     * Planning step: 2–4 targeted search queries for the instruction. The
     * original instruction is always kept; any planner failure degrades to it.
     *
     * @return list<string>
     */
    /**
     * Conformity step — does the draft actually ANSWER the instruction?
     *
     * The verify step only checks that claims are supported by the extracts: an
     * answer can be perfectly grounded and still off-topic (for instance
     * describing the project when the instruction asked for a risk analysis).
     * This check is cheap, runs once, and never blocks: a parsing failure is
     * treated as "conforme".
     *
     * @return array{conforme: bool, raison: string, available: bool}
     */
    private function checkConformity(string $instruction, string $draft): array
    {
        try {
            $raw = $this->client->chat(
                "INSTRUCTION:\n" . $instruction . "\n\nRÉPONSE PRODUITE:\n" . mb_substr($draft, 0, 6000),
                [['role' => 'system', 'content' =>
                    "Tu évalues si une RÉPONSE répond à une INSTRUCTION.\n"
                    . "Réponds UNIQUEMENT par un objet JSON : {\"conforme\": true|false, \"raison\": \"...\"}\n"
                    . "conforme = false si : la réponse est hors sujet ; elle décrit au lieu d'analyser ou de proposer ; elle ignore une partie explicitement demandée ; elle produit un document administratif alors qu'une simple réponse était attendue.\n"
                    . "conforme = true si elle traite l'instruction, même imparfaitement.\n"
                    . "La raison tient en une phrase, dans la langue de l'instruction."]],
                null,
                0.0,
            );

            $clean = trim((string) preg_replace('/^```(?:json)?|```$/m', '', trim($raw)));
            $data = json_decode($clean, true);

            if (is_array($data) && array_key_exists('conforme', $data)) {
                return [
                    'conforme' => (bool) $data['conforme'],
                    'raison' => (string) ($data['raison'] ?? ''),
                    'available' => true,
                ];
            }
        } catch (\Throwable) {
            // fall through
        }

        return ['conforme' => true, 'raison' => '', 'available' => false];
    }

    /**
     * Rewrite the draft so it answers the instruction, keeping every grounding
     * rule intact.
     */
    private function reviseForConformity(string $instruction, string $draft, string $reason, string $context): string
    {
        try {
            return $this->client->chat(
                "INSTRUCTION:\n" . $instruction
                    . "\n\nRAISON DU REJET:\n" . $reason
                    . "\n\nBROUILLON À CORRIGER:\n" . $draft
                    . "\n\nEXTRAITS:\n" . $context,
                [['role' => 'system', 'content' =>
                    $this->taskPrompt()
                    . "\n\nLe brouillon ci-dessus a été rejeté : il ne répond pas à l'instruction. "
                    . "Réécris-le afin qu'il y réponde réellement, en respectant toutes les règles. "
                    . "Ne commente pas la correction, ne t'excuse pas : produis directement le texte corrigé."]],
                null,
                0.2,
            );
        } catch (\Throwable) {
            return '';
        }
    }
    private function planQueries(string $instruction, ?int $userId): array
    {
        $queries = [$instruction];
        try {
            $titles = array_slice(array_map(
                static fn (array $chunk): string => '- ' . $chunk['title'],
                $this->catalogChunks($userId),
            ), 0, 60);

            $raw = $this->client->chat(
                "INSTRUCTION:\n" . $instruction . "\n\nAVAILABLE DOCUMENTS:\n" . ($titles !== [] ? implode("\n", $titles) : '(none)'),
                [['role' => 'system', 'content' =>
                    "You plan the research for a document-drafting agent. Break the INSTRUCTION into 2 to 4 short, "
                    . "complementary search queries that together retrieve everything needed from the knowledge base. "
                    . "Use the key terms and document titles that are likely to appear in the documents (the documents may be in French: "
                    . "when the instruction is in another language, write the queries in French). "
                    . 'Reply ONLY with JSON: {"queries": ["...", "..."]}']],
                null,
                0.0,
            );
            $plan = $this->decodeJson($raw);
            foreach ((array) ($plan['queries'] ?? []) as $query) {
                if (is_string($query) && trim($query) !== '') {
                    $queries[] = mb_substr(trim($query), 0, 200);
                }
            }
        } catch (\Throwable) {
        }

        return array_slice(array_values(array_unique($queries)), 0, 5);
    }

    /**
     * Verification step: which sourced claims of the draft are not supported by
     * the extract they cite? Proposals explicitly presented as suggestions are
     * out of scope. A verifier failure is reported, never blocking.
     *
     * @return array{checked: int, unsupported: list<string>, available: bool}
     */
    private function verifyDraft(string $draft, string $context): array
    {
        try {
            $raw = $this->client->chat(
                "EXTRACTS:\n" . $context . "\n\nDRAFT:\n" . $draft,
                [['role' => 'system', 'content' =>
                    'You are a strict fact-checker. Check every factual statement of the DRAFT that cites an extract [n] '
                    . 'or states a specific fact (figure, date, name, title, quotation, rule) against the EXTRACTS. '
                    . 'Ignore content explicitly presented as a proposal or suggestion. '
                    . 'A statement is unsupported if the cited extract does not say it, contradicts it, or if the number cited is wrong. '
                    . 'Reply ONLY with JSON: {"checked": <number of statements checked>, "unsupported": ["<statement> — <why>", ...]}']],
                null,
                0.0,
            );
            $verdict = $this->decodeJson($raw);
            if (!isset($verdict['checked'])) {
                return ['checked' => 0, 'unsupported' => [], 'available' => false];
            }

            return [
                'checked' => (int) $verdict['checked'],
                'unsupported' => array_values(array_filter(
                    array_map(static fn ($item): string => is_string($item) ? trim($item) : '', (array) ($verdict['unsupported'] ?? [])),
                    static fn (string $item): bool => $item !== '',
                )),
                'available' => true,
            ];
        } catch (\Throwable) {
            return ['checked' => 0, 'unsupported' => [], 'available' => false];
        }
    }

    /**
     * Revision step: correct or remove the unsupported claims, keep the rest.
     *
     * @param list<string> $unsupported
     */
    private function reviseDraft(string $instruction, string $draft, array $unsupported, string $context): string
    {
        try {
            return trim($this->client->chat(
                "INSTRUCTION:\n" . $instruction . "\n\nDRAFT:\n" . $draft . "\n\nUNSUPPORTED STATEMENTS:\n- " . implode("\n- ", $unsupported),
                [['role' => 'system', 'content' => $this->taskPrompt() . "\n\n" . $context
                    . "\n\nREVISION: rewrite the DRAFT so that each UNSUPPORTED STATEMENT is corrected to match the extracts or removed. "
                    . 'Keep everything else unchanged: structure, language, formatting and the [n] citations. Output only the revised document.']],
                null,
                0.1,
            ));
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Decode the first JSON object of a model reply (tolerates code fences).
     *
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        if (preg_match('/\{.*\}/s', $raw, $match) !== 1) {
            return [];
        }
        $decoded = json_decode($match[0], true);

        return is_array($decoded) ? $decoded : [];
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
        // Optional (one extra LLM call per answer): META_TRANSLATE_SOURCES=0
        // to keep original excerpts and save latency + credits.
        if ($sources === [] || getenv('META_TRANSLATE_SOURCES') === '0') {
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
                    "Tu es un traducteur. Traduis chaque extrait numéroté dans la langue de la QUESTION posée (y compris le mooré si la question est en mooré).\n"
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
    /**
     * What the document actually IS, derived from its file extension. A meeting
     * recording is first an audio file transcribed in French; its Mooré
     * corrected version is a translation, not the document's own language.
     */
    /** Pick the wording matching the interface language. */
    private function t(string $fr, string $en): string
    {
        return $this->lang === 'fr' ? $fr : $en;
    }

    /**
     * A date must be written the way the language writes it: "31 octobre 2026"
     * in French, "31 October 2026" in English — never a raw ISO string in the
     * middle of a sentence. The value itself is never altered.
     */
    private function formatDate(string $iso): string
    {
        $iso = substr(trim($iso), 0, 10);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m) !== 1) {
            return $iso;
        }

        $monthsFr = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $monthsEn = [1 => 'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];

        $day = (int) $m[3];
        $month = (int) $m[2];
        $year = $m[1];

        return $this->lang === 'fr'
            ? $day . ' ' . $monthsFr[$month] . ' ' . $year
            : $day . ' ' . $monthsEn[$month] . ' ' . $year;
    }
    private function documentNature(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'wav', 'mp3', 'm4a', 'ogg', 'flac', 'aac', 'webm' => $this->t('enregistrement audio (transcrit)', 'audio recording (transcribed)'),
            'pdf' => $this->t('document PDF', 'PDF document'),
            'docx', 'doc' => $this->t('document Word', 'Word document'),
            'jpeg', 'jpg', 'png', 'webp', 'gif' => $this->t('image / scan (OCR)', 'image / scan (OCR)'),
            'csv' => $this->t('fichier CSV', 'CSV file'),
            'md' => $this->t('texte Markdown', 'Markdown text'),
            default => $this->t('document texte', 'text document'),
        };
    }
    private function catalogChunks(?int $userId, ?int $espaceId = null): array
    {
        $allowed = $userId === null ? true : $this->accessibleDocumentIds($userId);

        // Same scope as the search: the workspace shown at the top of the
        // page decides which documents may appear in a catalogue answer.
        if ($espaceId !== null && $espaceId > 0) {
            $inSpace = $this->documentIdsInEspace($espaceId);

            $allowed = $allowed === true
                ? $inSpace
                : array_intersect_key($allowed, $inSpace);
        }

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
                'content' => $this->t('Titre', 'Title') . ' : ' . trim((string) $row['title'])
                    . ' · ' . $this->t('Catégorie', 'Category') . ' : ' . $categorie
                    . ' · ' . $this->t('Enregistré le', 'Registered on') . ' : '
                    . $this->formatDate((string) ($row['created_at'] ?? ''))
                    . ' · ' . $this->t('Fichier', 'File') . ' : ' . (string) ($row['filename'] ?? ''),
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

        $hasCorrection = false;

        foreach ($chunks as $index => $chunk) {
            $label = (string) ($chunk['title'] ?? 'Document');

            if (isset($chunk['correction'])) {
                $hasCorrection = true;
                $corr = $chunk['correction'];
                $label .= sprintf(
                    ' · VERSION CORRIGÉE %s du %s',
                    mb_strtoupper((string) ($corr['langue'] ?? '')),
                    mb_substr((string) ($corr['date'] ?? ''), 0, 10)
                );
            }

            $parts[] = sprintf(
                "[Extrait %d — %s]\n%s",
                $index + 1,
                $label,
                (string) $chunk['content'],
            );
        }

        if ($hasCorrection) {
            array_unshift(
                $parts,
                'NOTE: an extract marked « VERSION CORRIGÉE » is the validated reference for that document. '
                . 'Always prefer it over any other extract of the same document, and say so when you cite it.'
            );
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Open requests ("summarise", "tell me about", "explain", "what does it
     * say") deserve a developed answer; a closed question ("how many", "which
     * one", "when") deserves one sentence. Deciding this on the server is more
     * reliable than hoping the model classifies the question the same way every
     * time.
     */
    /**
     * Language explicitly named in the question.
     *
     * When the user writes "parle-moi de Meeting6 en mooré", the requested
     * language outranks the document's identity: the answer must present the
     * Mooré version, not describe the French audio.
     */
    /**
     * Language explicitly named in the question.
     *
     * "Parle-moi de Meeting6 en mooré" must present the Mooré version; without
     * a named language the document's identity comes first. Only an explicit
     * request counts — a bare mention of a language is not a preference.
     */
    private function requestedLanguage(string $question): ?string
    {
        $q = mb_strtolower($question);

        $names = [
            'mo' => '(?:moor[ée]|moore|mor[ée]|mossi)',
            'fr' => '(?:fran[çc]ais|french|francophone)',
            'en' => '(?:anglais|english|anglo)',
        ];

        foreach ($names as $code => $alt) {
            $pattern = '/(?:en|in|vers|into|version|tradui\w*|traduis\w*|traduction)\s+'
                . '(?:la\s+|le\s+|l\')?' . $alt . '\b/u';

            if (preg_match($pattern, $q) === 1) {
                return $code;
            }
        }

        return null;
    }
    /**
     * "Translate it" is an instruction, not a topic: the answer must contain a
     * translation, not a description of the document.
     */
    private function wantsTranslation(string $question): bool
    {
        return preg_match('/\b(tradui[st]\w*|traduisez|traduire|traduction|translate|translation)\b/iu', $question) === 1;
    }
    private function isOpenQuestion(string $question): bool
    {
        return preg_match(
            '/\b(r[ée]sum\w*|synth[èe]se|synth[ée]tise\w*|parle[- ]moi|pr[ée]sente\w*|explique\w*|expliquer'
            . '|d[ée]cri\w*|d[ée]taille\w*|analyse\w*|compare\w*|commentaire|comment\w*'
            . '|que dit|que contient|de quoi parle|quels? sont les points|points? cl[ée]s?'
            . '|summary|summari[sz]e|tell me about|overview|explain|describe|compare|what does .{0,40}(say|contain))\b/iu',
            $question
        ) === 1;
    }
    private function answerPrompt(): string
    {
        $translationNote = $this->translationRequested
            ? ' · TRADUCTION DEMANDÉE : applique la règle 4h et fournis la traduction.'
            : '';

        $mode = $this->openQuestion
            ? 'OUVERTE — applique 6b : réponse développée et structurée (150 mots max).'
            : 'FERMÉE — applique 6a : réponse directe en une à trois phrases, sans énumération.';

        $langRule = '5. Réponds dans la langue de la question : anglais → anglais, mooré → mooré, coréen → coréen, etc. Si la question est en mooré, réponds entièrement en mooré courant en gardant les données exactes (chiffres, dates, noms) telles quelles. Cette règle prime sur toute autre considération de langue.';
        return <<<PROMPT
Tu es un assistant documentaire expert, rigoureux et professionnel.

RÈGLES ABSOLUES :
1. Réponds UNIQUEMENT à partir des extraits fournis. N'invente aucun fait, chiffre, date, nom ni citation.
2. Si l'information demandée n'est pas présente dans les extraits, réponds exactement par cette phrase, TRADUITE dans la langue de la question (et non celle de l'interface) — cette phrase REMPLACE toute la réponse : ne l'ajoute jamais à la fin d'une réponse déjà rédigée, et ne l'emploie que si la réponse entière est absente : « Cette information ne figure pas dans les documents fournis. » / « This information is not in the provided documents. »
2b. Si la question filtre par PÉRIODE (mois, année) et qu'aucun document ne correspond : ne dis pas que l'information est absente (les dates SONT dans les extraits). Réponds en une ou deux phrases : « Aucun document n'a été enregistré en <période>. » puis, seulement pour situer, l'étendue réelle des dates présente dans les extraits. N'énumère PAS les autres documents : ce n'est pas demandé.
3. Commence par une réponse DIRECTE à la question, formulée avec tes propres mots : synthétise, ne recopie pas l'extrait mot pour mot (sauf pour les citations exactes placées entre guillemets).
4. Conserve les données exactes (chiffres, dates, noms, intitulés) telles quelles, sans les arrondir.
4a. EXPRIME les dates dans la langue de la réponse : « 31 octobre 2026 » en français, « 31 October 2026 » en anglais. N'écris jamais une date au format ISO (2026-10-31) dans une phrase, sauf si la question le demande expressément. La valeur reste identique.
4b. Quand la réponse cite un titre, un en-tête ou un intitulé rédigé dans une autre langue que la question, donne d'abord sa traduction dans la langue de la réponse, suivie de l'original entre parenthèses. Exemple (question en anglais, titre en français) : "Death notice and fraternal communion" ("Avis de décès et communion fraternelle").
4c. Mets en gras (**…**) la réponse directe à la question ainsi que les données clés citées (titres, dates, chiffres, noms propres).
4d. Toute citation littérale entre guillemets DOIT être traduite dans la langue de la réponse ; l'original ne peut être gardé qu'entre parenthèses juste après. Aucun passage de la réponse ne doit rester dans une autre langue que celle de la question.
4h. Si la question demande de TRADUIRE (« traduis-le », « traduit le », « translate it », « traduis ce document ») : tu dois PRODUIRE LA TRADUCTION, et non décrire le document. Structure : (a) une ligne indiquant la source utilisée (texte original ou version corrigée, avec sa langue et sa date) ; (b) une rubrique « Traduction : » suivie de la traduction complète du passage disponible, dans la langue de la question ; (c) les données exactes (noms, chiffres, dates) conservées. Si le contenu est déjà dans la langue demandée, dis-le en une phrase et recopie-le.
4g. Si la question NOMME UNE LANGUE (« en mooré », « en anglais », « en français », « in English »…), CETTE LANGUE DÉSIGNE LE CONTENU À PRÉSENTER, et non la langue de ta réponse : tu réponds toujours dans la langue de la question (règle 5), tu présentes la version écrite dans la langue demandée, et tu cites le passage dans sa langue d'origine. Si cette version est une traduction corrigée, dis-le en une courte mention (nature et langue d'origine du document, date de la correction), puis donne le contenu — sans développer l'identité du document ni proposer les autres langues.
4f. Pour DÉCRIRE un document (« parle-moi de X », « présente X », « qu'est-ce que X ») : commence par son identité RÉELLE — sa NATURE (enregistrement audio, PDF, scan, Word…) et sa LANGUE D'ORIGINE — puis, séparément, les versions corrigées disponibles en précisant qu'il s'agit de TRADUCTIONS, avec leur langue et leur date. Ne présente JAMAIS le texte d'une version corrigée comme le contenu d'origine, ni sa langue comme la langue du document.
4e. N'emploie jamais un mot ou un passage visiblement tronqué en bord d'extrait : si un extrait se termine au milieu d'une phrase ou d'un mot, ignore cette fin plutôt que de la citer.
5b. Les étiquettes « [Extrait n — Titre] » font partie des informations fournies : une question portant sur un titre, un nom de document ou la présence d'un document DOIT être répondue à partir de ces titres.
$langRule
6. RÈGLE DE FORME, selon le type de question (le mode est indiqué plus bas) :
   6a. QUESTION FERMÉE (combien, quel, quand, qui, où, oui/non, une valeur précise) :
        la première phrase EST la réponse (un chiffre, un titre, une date, une valeur). Pas de préambule, pas de reformulation, pas de conclusion, pas de conseil. Une à trois phrases maximum. Aucune puce sauf si plusieurs éléments sont demandés. Aucune information non demandée.
   6b. QUESTION OUVERTE (résume, parle-moi de, explique, décris, que dit, compare, points clés) :
        réponse développée mais structurée : une phrase d'ensemble, puis 3 à 6 points clés en puces, puis une phrase de clôture seulement si elle apporte un fait. 150 mots maximum. Toujours uniquement à partir des extraits, sans remplissage ni généralités.
   Dans les deux cas : termine par « Sources : [1], [2] » en citant uniquement les numéros réellement utilisés.
7. Ton neutre et précis, sans remplissage ni formule de politesse. En mode 6a, la brièveté est une exigence : si deux formulations répondent, garde la plus courte.

MODE DE CETTE QUESTION : {$mode}{$translationNote}
PROMPT;
    }

    private function taskPrompt(): string
    {
        $langRule = "7. Rédige le document dans la langue de l'instruction : anglais → document en anglais, mooré → document en mooré, coréen → coréen, etc. Si l'instruction est en mooré, rédige entièrement en mooré courant en gardant les données exactes telles quelles.";

        return <<<PROMPT
Tu es un rédacteur professionnel expérimenté. Pour les leçons et supports de cours, tu es spécialiste de l'Approche Par les Compétences / Pédagogie de l'Intégration (APC/PI).

Mission : à partir des extraits fournis, rédige le document demandé (note, rapport, compte rendu, courrier, synthèse, leçon…).

RÈGLES :
1. Les extraits sont l'OSSATURE : reprends fidèlement les faits, intitulés, chiffres, dates et noms qu'ils contiennent, en citant les extraits [1], [2]…
2. Tu peux développer ce que le type de document exige (mise en forme, transitions, recommandations, activités pédagogiques…), comme une PROPOSITION cohérente avec les extraits.
3. N'invente aucun fait factuel externe (noms de personnes, chiffres, dates, références) non justifié par les extraits.
4. Distingue clairement ce qui provient des extraits (cité) de ce qui est proposé.
4b. Mets en gras (**…**) le titre du document, les intitulés de sections et les données clés (dates, chiffres, noms propres).
4c. Toute citation littérale entre guillemets DOIT être traduite dans la langue de rédaction ; l'original ne peut être gardé qu'entre parenthèses juste après. Aucun passage du document ne doit rester dans une autre langue que celle de l'instruction.
5. Structure ADAPTÉE au type de document demandé — n'impose jamais une structure étrangère au genre :
   - note / mémo : titre, destinataires et objet, points essentiels, actions ou recommandations ;
   - rapport / synthèse : titre, contexte, constats, analyse, conclusions et recommandations ;
   - compte rendu : titre, date et participants (si connus), points abordés, décisions, suites à donner ;
   - courrier : objet, formule d'appel, corps, formule de politesse ;
   - leçon / cours (APC/PI) : identification (classe, discipline, durée), objectifs d'apprentissage, prérequis, déroulement détaillé (théorie/pratique), exercices, évaluation ;
   - autre : la structure professionnelle usuelle pour ce genre.
   Termine toujours par « Sources : [1], [2]… ».
6. Ton professionnel, document prêt à l'emploi.

7. MODE RÉPONSE OUVERTE — si l'instruction est une QUESTION ou une ANALYSE (elle se termine par « ? », ou commence par pourquoi / comment / quel / quels / analyse / compare / explique / évalue / propose) :
   - Ne fabrique PAS un document administratif (pas de « objet », pas de destinataires, pas de formule de politesse).
   - Produis une réponse ouverte, développée et structurée :
     a) une synthèse de 2 à 3 phrases qui répond directement ;
     b) 3 à 6 sections ou points, chacune avec un titre court en gras et les faits cités [n] ;
     c) une conclusion opérationnelle de 1 à 2 phrases (ce qu'il faut retenir ou faire).
   - 400 mots maximum. Aucune généralité : chaque affirmation vient des extraits.
   - Termine par « Sources : [1], [2]… ».

8. IDENTITÉ DES DOCUMENTS — quand tu décris ou cites un document : donne sa NATURE réelle (enregistrement audio transcrit, PDF, scan, Word…) et sa LANGUE D'ORIGINE. Une version corrigée est une TRADUCTION : présente-la comme telle, avec sa langue et sa date. Ne présente jamais le texte d'une version corrigée comme le contenu d'origine du document.

9. LANGUE DEMANDÉE — si l'instruction nomme une langue (« en mooré », « en anglais »), cette langue désigne le CONTENU à utiliser ; la rédaction reste dans la langue de l'instruction.

10. TRADUCTION — si l'instruction demande de traduire, produis la TRADUCTION effective, pas une description : indique la source utilisée, puis la traduction complète du passage disponible, dans la langue demandée.

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

    /**
     * A validated correction is the reliable reference for its document.
     *
     * When a document has one, its raw chunks are replaced by the corrected
     * text (one entry per document) and the provenance is attached, so the
     * answer can cite "version corrigée v3 (MOORÉ)". Documents without a
     * correction are left untouched.
     *
     * @param list<array<string, mixed>> $chunks
     * @return list<array<string, mixed>>
     */
    private function preferCorrections(array $chunks): array
    {
        if ($chunks === []) {
            return $chunks;
        }

        $ids = [];

        foreach ($chunks as $chunk) {
            $id = (int) ($chunk['document_id'] ?? 0);

            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        if ($ids === []) {
            return $chunks;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            // No SQL comparison between c.langue and d.langue: the two tables
            // were created with different collations, which made MySQL raise
            // "Illegal mix of collations". The preference is decided in PHP.
            $stmt = $this->db->prepare(
                "SELECT c.id, c.document_id, c.langue, c.texte_corrige, c.nom_fichier, c.cree_le,
                        d.title, d.langue AS doc_langue, d.filename, d.created_at,
                        cat.nom AS categorie
                 FROM correction_document c
                 JOIN documents d ON d.id = c.document_id
                 LEFT JOIN categorie cat ON cat.id = d.categorie_id
                 WHERE c.document_id IN ($placeholders) AND c.statut = 'valide'
                 ORDER BY c.id DESC"
            );
            $stmt->execute(array_keys($ids));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return $chunks;
        }

        if ($rows === []) {
            return $chunks;
        }

        // Rows arrive newest first. Keep one per document: a correction written
        // in the document's own language wins, otherwise the most recent one.
        $best = [];

        foreach ($rows as $row) {
            $documentId = (int) $row['document_id'];
            $candidate = $best[$documentId] ?? null;

            if ($candidate === null) {
                $best[$documentId] = $row;
                continue;
            }

            // An explicitly requested language outranks every other rule.
            if ($this->requestedLangue !== null) {
                $candidateWanted = (string) $candidate['langue'] === $this->requestedLangue;
                $rowWanted = (string) $row['langue'] === $this->requestedLangue;

                if ($rowWanted && !$candidateWanted) {
                    $best[$documentId] = $row;
                }

                continue;
            }

            $candidateMatches = (string) $candidate['langue'] === (string) ($candidate['doc_langue'] ?? '');
            $rowMatches = (string) $row['langue'] === (string) ($row['doc_langue'] ?? '');

            if ($rowMatches && !$candidateMatches) {
                $best[$documentId] = $row;
            }
        }

        // Every valid corrected version of the document, not only the one chosen
        // for the extract: a document may have MO, EN and FR corrections.
        $available = [];

        foreach ($rows as $row) {
            $available[(int) $row['document_id']][] = mb_strtoupper((string) $row['langue'])
                . ' (' . $this->formatDate((string) $row['cree_le']) . ')';
        }
        $out = [];
        $done = [];

        foreach ($chunks as $chunk) {
            $documentId = (int) ($chunk['document_id'] ?? 0);

            if (!isset($best[$documentId])) {
                $out[] = $chunk;
                continue;
            }

            if (isset($done[$documentId])) {
                continue; // one entry per corrected document
            }

            $done[$documentId] = true;
            $row = $best[$documentId];

            $title = (string) ($row['title'] ?? ($chunk['title'] ?? 'Document'));
            $filename = (string) ($row['filename'] ?? '');
            $categorie = (string) ($row['categorie'] ?? '');
            $correctionLangue = (string) $row['langue'];
            $correctionDate = (string) $row['cree_le'];

            // The document's IDENTITY is never replaced by its correction: a
            // meeting recording stays an audio file transcribed in French, and
            // the corrected text is announced as a translation. Without this,
            // describing the document produced a purely Mooré description of a
            // French audio file.
            $out[] = [
                'document_id' => $documentId,
                'version_id' => (int) ($chunk['version_id'] ?? 0),
                'chunk_index' => 0,
                'title' => $title,
                'content' => $this->t('Titre', 'Title') . ' : ' . $title
                    . ' · ' . $this->t('Nature', 'Type') . ' : ' . $this->documentNature($filename)
                    . ' · ' . $this->t('Langue d\'origine', 'Original language') . ' : ' . mb_strtoupper((string) ($row['doc_langue'] ?? ''))
                    . ($categorie !== '' ? ' · ' . $this->t('Catégorie', 'Category') . ' : ' . $categorie : '')
                    . ' · ' . $this->t('Enregistré le', 'Registered on') . ' : ' . $this->formatDate((string) ($row['created_at'] ?? ''))
                    . (isset($available[$documentId])
                        ? ' · ' . $this->t('Versions corrigées', 'Corrected versions') . ' : ' . implode(', ', array_unique($available[$documentId]))
                        : '')
                    . ' · ' . $this->t('Fichier', 'File') . ' : ' . $filename,
                'score' => (float) ($chunk['score'] ?? 1.0),
            ];

            // The original text must stay available: dropping it made "translate
            // it" and "what does the original say" impossible to answer.
            $originalText = trim((string) ($chunk['content'] ?? ''));

            if ($originalText !== '' && $originalText !== trim((string) $row['texte_corrige'])) {
                $out[] = [
                    'document_id' => $documentId,
                    'version_id' => (int) ($chunk['version_id'] ?? 0),
                    'chunk_index' => 2,
                    'title' => $title,
                    'content' => 'Texte original ('
                        . mb_strtoupper((string) ($row['doc_langue'] ?? '')) . ') : ' . $originalText,
                    'score' => (float) ($chunk['score'] ?? 1.0),
                ];
            }
            $out[] = [
                'document_id' => $documentId,
                'version_id' => (int) ($chunk['version_id'] ?? 0),
                'chunk_index' => 1,
                'title' => $title,
                'content' => $this->t('Version corrigée', 'Corrected version') . ' ' . mb_strtoupper($correctionLangue)
                    . ' ' . $this->t('du', 'of') . ' ' . $this->formatDate($correctionDate) . ' — '
                    . $this->t(
                        'traduction corrigée, et non la langue d\'origine du document',
                        'a corrected translation, not the document\'s original language'
                    ) . ' :' . "\n"
                    . trim((string) $row['texte_corrige']),
                'score' => (float) ($chunk['score'] ?? 1.0),
                'correction' => [
                    'id' => (int) $row['id'],
                    'langue' => $correctionLangue,
                    'date' => $correctionDate,
                    'fichier' => (string) ($row['nom_fichier'] ?? ''),
                ],
            ];
        }

        // Stable sort: the corrected version written in the requested language
        // comes first, so the model focuses on it.
        if ($this->requestedLangue !== null) {
            $wanted = $this->requestedLangue;

            usort($out, static function (array $a, array $b) use ($wanted): int {
                $aWanted = isset($a['correction']['langue']) && (string) $a['correction']['langue'] === $wanted ? 0 : 1;
                $bWanted = isset($b['correction']['langue']) && (string) $b['correction']['langue'] === $wanted ? 0 : 1;

                return $aWanted <=> $bWanted;
            });
        }

        return $out;
    }

    /**
     * Corrections whose text matches the query, as ready-to-use context chunks.
     *
     * Lexical matching only (the corpus is small and corrections are prose):
     * every distinctive term of the question is OR-ed, then the number of
     * matched terms orders the results.
     *
     * @return list<array<string, mixed>>
     */
    private function correctionHits(string $query, ?int $userId, int $limit = 3): array
    {
        if (trim($query) === '' || $limit < 1) {
            return [];
        }

        $tokens = array_values(array_filter(
            array_unique($this->tokens($query)),
            static fn (string $token): bool => mb_strlen($token) >= 5
        ));

        if ($tokens === []) {
            return [];
        }

        $tokens = array_slice($tokens, 0, 5);

        $where = [];
        $params = [];

        foreach ($tokens as $index => $token) {
            $where[] = "c.texte_corrige LIKE :t$index";
            $params["t$index"] = '%' . $token . '%';
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT c.id, c.document_id, c.langue, c.texte_corrige, c.nom_fichier, c.cree_le, d.title
                 FROM correction_document c
                 JOIN documents d ON d.id = c.document_id AND d.supprime_le IS NULL
                 WHERE c.statut = 'valide' AND (" . implode(' OR ', $where) . ")
                 ORDER BY c.id DESC
                 LIMIT 25"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }

        if ($rows === []) {
            return [];
        }

        // Permission scope, when the user is not an unrestricted reader.
        $allowed = $userId === null ? true : $this->accessibleDocumentIds($userId);

        $scored = [];

        foreach ($rows as $row) {
            $documentId = (int) $row['document_id'];

            // accessibleDocumentIds() returns a map documentId => true.
            if (is_array($allowed) && !isset($allowed[$documentId])) {
                continue;
            }

            $haystack = mb_strtolower((string) $row['texte_corrige']);
            $score = 0;

            foreach ($tokens as $token) {
                if (mb_strpos($haystack, $token) !== false) {
                    $score++;
                }
            }

            if ($score === 0) {
                continue;
            }

            $scored[] = [$score, $row];
        }

        if ($scored === []) {
            return [];
        }

        usort($scored, static fn (array $a, array $b): int => $b[0] <=> $a[0]);

        // Every valid corrected version of the document, not only the one chosen
        // for the extract: a document may have MO, EN and FR corrections.
        $available = [];

        foreach ($rows as $row) {
            $available[(int) $row['document_id']][] = mb_strtoupper((string) $row['langue'])
                . ' (' . $this->formatDate((string) $row['cree_le']) . ')';
        }
        $out = [];

        foreach (array_slice($scored, 0, $limit) as [$score, $row]) {
            $out[] = [
                'document_id' => (int) $row['document_id'],
                'version_id' => 0,
                'chunk_index' => 0,
                'title' => (string) ($row['title'] ?? 'Document'),
                'content' => trim((string) $row['texte_corrige']),
                'score' => 1.0 + ($score / 10),
                'correction' => [
                    'id' => (int) $row['id'],
                    'langue' => (string) $row['langue'],
                    'date' => (string) $row['cree_le'],
                    'fichier' => (string) ($row['nom_fichier'] ?? ''),
                ],
            ];
        }

        return $out;
    }

    /**
     * Sources for an answer: the extracts it actually cited, or — when the
     * model forgot or was cut off — the top extracts it was given. A grounded
     * answer must never look sourceless in the UI.
     *
     * @param list<array<string, mixed>> $chunks
     * @return list<array<string, mixed>>
     */
    private function sourcesFor(string $text, array $chunks): array
    {
        $cited = $this->citedChunks($text, $chunks);

        if ($cited === [] && $chunks !== []) {
            $cited = array_slice($chunks, 0, 3);
        }

        return $this->sources($cited);
    }
    /**
     * Catalogue extracts are metadata lines (title, category, date, file) used to
     * LIST documents — replacing them with a corrected text would destroy the
     * very dates the question is about. So the correction is not substituted
     * here: it is *declared* on the extract (marker in the text + provenance
     * metadata), so the model knows a validated version exists and the source
     * panel can show the "corrected version" badge and its download link.
     *
     * @param list<array<string, mixed>> $chunks
     * @return list<array<string, mixed>>
     */
    private function annotateCorrections(array $chunks): array
    {
        if ($chunks === []) {
            return $chunks;
        }

        $ids = [];

        foreach ($chunks as $chunk) {
            $id = (int) ($chunk['document_id'] ?? 0);

            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        if ($ids === []) {
            return $chunks;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            $stmt = $this->db->prepare(
                "SELECT c.id, c.document_id, c.langue, c.nom_fichier, c.cree_le, d.langue AS doc_langue
                 FROM correction_document c
                 JOIN documents d ON d.id = c.document_id
                 WHERE c.document_id IN ($placeholders) AND c.statut = 'valide'
                 ORDER BY c.id DESC"
            );
            $stmt->execute(array_keys($ids));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return $chunks;
        }

        if ($rows === []) {
            return $chunks;
        }

        // One correction per document: its own language first, else the newest.
        $best = [];

        foreach ($rows as $row) {
            $documentId = (int) $row['document_id'];
            $candidate = $best[$documentId] ?? null;

            if ($candidate === null) {
                $best[$documentId] = $row;
                continue;
            }

            // An explicitly requested language outranks every other rule.
            if ($this->requestedLangue !== null) {
                $candidateWanted = (string) $candidate['langue'] === $this->requestedLangue;
                $rowWanted = (string) $row['langue'] === $this->requestedLangue;

                if ($rowWanted && !$candidateWanted) {
                    $best[$documentId] = $row;
                }

                continue;
            }

            $candidateMatches = (string) $candidate['langue'] === (string) ($candidate['doc_langue'] ?? '');
            $rowMatches = (string) $row['langue'] === (string) ($row['doc_langue'] ?? '');

            if ($rowMatches && !$candidateMatches) {
                $best[$documentId] = $row;
            }
        }

        foreach ($chunks as &$chunk) {
            $documentId = (int) ($chunk['document_id'] ?? 0);

            if (!isset($best[$documentId])) {
                continue;
            }

            $row = $best[$documentId];
            $chunk['correction'] = [
                'id' => (int) $row['id'],
                'langue' => (string) $row['langue'],
                'date' => (string) $row['cree_le'],
                'fichier' => (string) ($row['nom_fichier'] ?? ''),
            ];

            $chunk['content'] = rtrim((string) $chunk['content'])
                . ' · ' . $this->t('version corrigée', 'corrected version') . ' '
                . mb_strtoupper((string) $row['langue'])
                . ' ' . $this->t('du', 'of') . ' ' . $this->formatDate((string) $row['cree_le']);
        }
        unset($chunk);

        return $chunks;
    }
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
                'corrige' => isset($chunk['correction']),
                'correction' => $chunk['correction'] ?? null,
            ];
        }, $chunks, array_keys($chunks));
    }

    /**
     * @return list<array{content: string, title: string, score: float}>
     */
    /**
     * A document explicitly named in the instruction ("à base de Meeting6",
     * "d'après Meeting6", "from Meeting6") restricts the whole task to that
     * document. Without this, the planner searched the entire corpus and the
     * draft quoted an unrelated project matrix.
     */
    private function documentScope(string $instruction, ?int $userId): ?int
    {
        try {
            $stmt = $this->db->query('SELECT id, title FROM documents WHERE supprime_le IS NULL');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return null;
        }

        $allowed = $userId === null ? true : $this->accessibleDocumentIds($userId);
        $best = null;
        $bestLength = 0;

        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));

            if ($title === '' || mb_strlen($title) < 3) {
                continue;
            }

            if (preg_match('/\b' . preg_quote($title, '/') . '\b/iu', $instruction) !== 1) {
                continue;
            }

            $id = (int) $row['id'];

            if (is_array($allowed) && !isset($allowed[$id])) {
                continue;
            }

            if (mb_strlen($title) > $bestLength) {
                $best = $id;
                $bestLength = mb_strlen($title);
            }
        }

        return $best;
    }

    /**
     * Best document title named in the text that the user may NOT consult.
     * Unlike documentScope() — which silently skips inaccessible documents —
     * this detects an explicit question about a forbidden document so the
     * answer can be an explicit access refusal instead of an off-topic reply.
     */
    private function inaccessibleDocumentScope(string $text, ?int $userId): ?int
    {
        if ($userId === null) {
            return null;
        }

        try {
            $stmt = $this->db->query('SELECT id, title FROM documents WHERE supprime_le IS NULL');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return null;
        }

        $allowed = $this->accessibleDocumentIds($userId);

        if ($allowed === true) {
            return null;
        }

        $best = null;
        $bestLength = 0;

        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));

            if ($title === '' || mb_strlen($title) < 3) {
                continue;
            }

            if (preg_match('/\b' . preg_quote($title, '/') . '\b/iu', $text) !== 1) {
                continue;
            }

            $id = (int) $row['id'];

            if (isset($allowed[$id])) {
                continue;
            }

            if (mb_strlen($title) > $bestLength) {
                $best = $id;
                $bestLength = mb_strlen($title);
            }
        }

        return $best;
    }
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
                'document_id' => $documentId,
                'score' => 1.0,
            ];
        }

        return $this->preferCorrections($chunks);
    }

    /**
     * Retrieve the most relevant chunks for a query (exposed for tooling and tests).
     *
     * @return list<array{content: string, title: string, score: float}>
     */
    public function search(string $query, int $topK = 6, ?int $userId = null, ?int $espaceId = null): array
    {
        return $this->retrieveSimilar($this->embeddings->embed($query), $topK, $query, $userId, $espaceId);
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
    private function retrieveSimilar(array $queryVector, int $topK, string $query = '', ?int $userId = null, ?int $espaceId = null): array
    {
        $allowed = $userId === null ? true : $this->accessibleDocumentIds($userId);

        // The workspace shown at the top of the page scopes the search: a user
        // who switches workspace expects the assistant to answer from it, not
        // from every document their memberships open elsewhere.
        if ($espaceId !== null && $espaceId > 0) {
            $inSpace = $this->documentIdsInEspace($espaceId);

            $allowed = $allowed === true
                ? $inSpace
                : array_intersect_key($allowed, $inSpace);
        }

        if ($allowed === []) {
            return [];
        }

        $rows = $this->db->query(
            'SELECT c.document_id, c.version_document_id, c.chunk_index, c.content, c.embedding, d.title
             FROM document_chunks c
             JOIN documents d ON d.id = c.document_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        // Decoded-vector cache: avoid re-parsing every chunk's JSON embedding
        // on each query (built-in server keeps this process across requests).
        $vectorCache = &self::$vectorCache;

        $scored = [];
        foreach ($rows as $row) {
            if (is_array($allowed) && !isset($allowed[(int) $row['document_id']])) {
                continue;
            }
            $key = $row['document_id'] . '|' . (int) ($row['version_document_id'] ?? 0) . '|' . $row['chunk_index'];
            $vector = $vectorCache[$key] ?? null;
            if ($vector === null) {
                $vector = json_decode((string) $row['embedding'], true);
                if (is_array($vector)) {
                    if (count($vectorCache) > 50000) {
                        $vectorCache = [];
                    }
                    $vectorCache[$key] = $vector;
                }
            }
            if (!is_array($vector)) {
                continue;
            }

            $semantic = $this->embeddings->similarity($queryVector, $vector);
            $lexical = $query !== '' ? $this->lexicalScore($query, (string) $row['content']) : 0.0;

            // Title boost: when the question names a document ("the audio",
            // "the announcement"…), its chunks must rank first — a generic
            // wording otherwise drowns under higher-scoring documents.
            $titleHit = $query !== '' ? $this->lexicalScore($query, (string) ($row['title'] ?? '')) : 0.0;

            $scored[] = [
                'document_id' => (int) $row['document_id'],
                'version_id' => (int) ($row['version_document_id'] ?? 0),
                'chunk_index' => (int) $row['chunk_index'],
                'content' => (string) $row['content'],
                'title' => (string) ($row['title'] ?? 'Document'),
                'score' => 0.6 * $semantic + 0.4 * $lexical + $titleHit,
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $top = array_slice($scored, 0, $topK);

        // Neighbor expansion: a heading (e.g. the title of an announcement) often
        // sits in the chunk just before/after the best-scoring one. Enrich each
        // hit with its adjacent chunks from the same version so the model sees
        // the full passage instead of a truncated fragment.
        $expanded = $this->expandWithNeighbors($top);

        // Validated corrections are not part of the chunk index (the raw text
        // must stay searchable), so they are looked up separately: a correction
        // matching the question must be able to surface the document even when
        // its raw chunks do not.
        $hits = $this->correctionHits($query, $userId, 3);

        if ($hits !== []) {
            $known = [];

            foreach ($expanded as $chunk) {
                $known[(int) ($chunk['document_id'] ?? 0)] = true;
            }

            foreach (array_reverse($hits) as $hit) {
                $hitId = (int) $hit['document_id'];
                if (!isset($known[$hitId])) {
                    array_unshift($expanded, $hit);
                    $known[$hitId] = true;   // one entry per corrected document
                }
            }
        }

        // A language named in the question decides which corrected version
        // is presented first.
        $this->requestedLangue = $this->requestedLanguage($query);

        return $this->preferCorrections($expanded);
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
                'version_id' => $item['version_id'],
                'chunk_index' => $item['chunk_index'],
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
    /**
     * Document ids filed in one workspace, as a set.
     *
     * The link table is authoritative; the primary column is kept for documents
     * that predate it.
     *
     * @return array<int, bool>
     */
    private function documentIdsInEspace(int $espaceId): array
    {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT d.id
               FROM documents d
          LEFT JOIN document_espace de ON de.document_id = d.id
              WHERE d.supprime_le IS NULL
                AND (de.espace_id = :espace OR (de.document_id IS NULL AND d.espace_id = :espace2))'
        );
        $stmt->execute(['espace' => $espaceId, 'espace2' => $espaceId]);

        $set = [];

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $set[(int) $id] = true;
        }

        return $set;
    }
    private function accessibleDocumentIds(int $userId): array|bool
    {
        if ($this->permissions === null) {
            return true;
        }

        // Share the rule with the document list and the download endpoint.
        return $this->permissions->documentsVisibles($userId);
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
                'not_in_workspace' => "Je ne trouve pas cette information dans l'espace de travail courant. Changez d'espace, ou déposez le document qui la contient.",
            ],
            'en' => [
                'no_documents' => 'This information is not in the provided documents. Index a document first.',
                'no_chunks' => 'No relevant document was found. Index a document first.',
                'no_content' => 'No indexed content for this document.',
                'not_accessible' => 'This document is not accessible with your permissions.',
                'not_in_workspace' => 'I cannot find this information in the current workspace. Switch workspace, or deposit the document that contains it.',
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
