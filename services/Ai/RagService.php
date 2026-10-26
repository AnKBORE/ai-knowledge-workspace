<?php

declare(strict_types=1);

namespace App\Services\Ai;

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
        $step = max(1, $size - $overlap);

        for ($start = 0; $start < $length; $start += $step) {
            $chunks[] = mb_substr($text, $start, $size);
            if ($start + $size >= $length) {
                break;
            }
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
     */
    public function query(string $question, int $topK = 6): string
    {
        return (string) $this->answer($question, $topK)['answer'];
    }

    /**
     * Answer a question grounded in the indexed documents, with cited sources.
     *
     * @return array{answer: string, sources: list<array{title: string, excerpt: string}>}
     */
    public function answer(string $question, int $topK = 6): array
    {
        $chunks = $this->retrieveSimilar($this->embeddings->embed($question), $topK);

        if ($chunks === []) {
            return [
                'answer' => "Cette information ne figure pas dans les documents fournis. Indexez d'abord un document.",
                'sources' => [],
            ];
        }

        $system = $this->answerPrompt() . "\n\n" . $this->labeledContext($chunks);
        $answer = $this->client->chat($question, [['role' => 'system', 'content' => $system]], null, 0.2);

        return ['answer' => $answer, 'sources' => $this->sources($chunks)];
    }

    /**
     * Agentic task: draft a structured document (note, report) grounded in the
     * knowledge base.
     *
     * @return array{draft: string, sources: list<array{title: string, excerpt: string}>}
     */
    public function task(string $instruction, int $topK = 8): array
    {
        $chunks = $this->retrieveSimilar($this->embeddings->embed($instruction), $topK);

        if ($chunks === []) {
            return [
                'draft' => "Aucun document pertinent n'a été trouvé. Indexez d'abord un document.",
                'sources' => [],
            ];
        }

        $system = $this->taskPrompt() . "\n\n" . $this->labeledContext($chunks);
        $draft = $this->client->chat($instruction, [['role' => 'system', 'content' => $system]], null, 0.2);

        return ['draft' => $draft, 'sources' => $this->sources($chunks)];
    }

    /**
     * Summarize a document from its indexed chunks.
     *
     * @return array{summary: string, document_id: int, sources: list<array{title: string, excerpt: string}>}
     */
    public function summarize(int $documentId, int $maxChunks = 20): array
    {
        $chunks = $this->chunksForDocument($documentId, $maxChunks);

        if ($chunks === []) {
            return [
                'summary' => 'Aucun contenu indexé pour ce document.',
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
        return <<<'PROMPT'
Tu es un assistant documentaire expert, rigoureux et professionnel.

RÈGLES ABSOLUES :
1. Réponds UNIQUEMENT à partir des extraits fournis. N'invente aucun fait, chiffre, date, nom ni citation.
2. Si l'information demandée n'est pas présente dans les extraits, réponds exactement : « Cette information ne figure pas dans les documents fournis. »
3. Commence par une réponse DIRECTE à la question, formulée avec tes propres mots : synthétise, ne recopie pas l'extrait mot pour mot (sauf pour les citations exactes placées entre guillemets).
4. Conserve les données exactes (chiffres, dates, noms, intitulés) telles quelles, sans les arrondir.
5. Réponds dans la langue de la question.
6. Structure :
   - une phrase de synthèse qui répond directement ;
   - les points clés sous forme de liste à puces ;
   - termine par « Sources : [1], [2] » en citant les numéros réellement utilisés.
7. Ton neutre et précis, sans remplissage ni formule de politesse.
PROMPT;
    }

    private function taskPrompt(): string
    {
        return <<<'PROMPT'
Tu es un rédacteur pédagogique expérimenté, spécialiste de l'Approche Par les Compétences / Pédagogie de l'Intégration (APC/PI).

Mission : à partir du programme fourni dans les extraits, rédige le document demandé (leçon détaillée, note, rapport…).

RÈGLES :
1. Le programme est l'OSSATURE : reprends fidèlement la classe, la discipline, l'intitulé de la leçon, le contenu officiel et l'approche pédagogique, en citant les extraits [1], [2]…
2. Tu DOIS développer le contenu pédagogique demandé : objectifs d'apprentissage, prérequis, déroulement (théorie et pratique), activités, exercices et critères d'évaluation. Ce développement est une PROPOSITION pédagogique cohérente avec le programme.
3. N'invente aucun fait factuel externe (noms de personnes, chiffres, références bibliographiques) non justifié par les extraits ou par une pratique pédagogique standard.
4. Distingue clairement ce qui provient du programme (cité) de ce qui est proposé.
5. Structure :
   - titre et identification (classe, discipline, durée) ;
   - objectifs d'apprentissage ;
   - prérequis ;
   - déroulement détaillé (séances, théorie/pratique) ;
   - exercices / activité pratique ;
   - évaluation ;
   - sources [1], [2]…
6. Ton professionnel, prêt à être utilisé en classe.
PROMPT;
    }

    private function summaryPrompt(): string
    {
        return <<<'PROMPT'
Tu résumes un document de façon fidèle, précise et professionnelle.

RÈGLES :
1. Reprends les faits, chiffres, dates et noms exacts.
2. Structure : une phrase de synthèse, puis 3 à 6 points clés.
3. N'invente rien et n'ajoute aucune interprétation.
4. Rédige dans la langue du document.
PROMPT;
    }

    /**
     * @param list<array{content: string, title: string, score: float}> $chunks
     * @return list<array{title: string, excerpt: string}>
     */
    private function sources(array $chunks): array
    {
        return array_map(static function (array $chunk): array {
            return [
                'title' => (string) ($chunk['title'] ?? 'Document'),
                'excerpt' => mb_substr((string) ($chunk['content'] ?? ''), 0, 240),
            ];
        }, $chunks);
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
     * Retrieve the chunks most similar to the query vector.
     *
     * Cosine similarity is computed in PHP for portability. In phase 2 this
     * step can be replaced by a dedicated vector store or a MySQL vector index.
     *
     * @param list<float> $queryVector
     * @return list<array{content: string, title: string, score: float}>
     */
    private function retrieveSimilar(array $queryVector, int $topK): array
    {
        $rows = $this->db->query(
            'SELECT c.content, c.embedding, d.title
             FROM document_chunks c
             JOIN documents d ON d.id = c.document_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        $scored = [];
        foreach ($rows as $row) {
            $vector = json_decode((string) $row['embedding'], true);
            if (!is_array($vector)) {
                continue;
            }
            $scored[] = [
                'content' => (string) $row['content'],
                'title' => (string) ($row['title'] ?? 'Document'),
                'score' => $this->embeddings->similarity($queryVector, $vector),
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $topK);
    }
}
