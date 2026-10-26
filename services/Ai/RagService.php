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
     */
    public function indexDocument(int $documentId, string $content): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO document_chunks (document_id, chunk_index, content, embedding)
             VALUES (:document_id, :chunk_index, :content, :embedding)'
        );

        foreach ($this->chunk($content) as $index => $chunk) {
            $stmt->execute([
                'document_id' => $documentId,
                'chunk_index' => $index,
                'content' => $chunk,
                'embedding' => json_encode($this->embeddings->embed($chunk), JSON_THROW_ON_ERROR),
            ]);
        }
    }

    /**
     * Answer a question grounded in the indexed documents (plain string).
     */
    public function query(string $question, int $topK = 4): string
    {
        return (string) $this->answer($question, $topK)['answer'];
    }

    /**
     * Answer a question grounded in the indexed documents, with cited sources.
     *
     * @return array{answer: string, sources: list<array{title: string, excerpt: string}>}
     */
    public function answer(string $question, int $topK = 4): array
    {
        $chunks = $this->retrieveSimilar($this->embeddings->embed($question), $topK);

        if ($chunks === []) {
            return ['answer' => 'No relevant document found. Index documents first.', 'sources' => []];
        }

        $system = "You are a knowledge assistant. Answer using ONLY the excerpts below.\n"
            . "If the answer is not present, say so explicitly. Keep the answer concise and factual.\n\n"
            . implode("\n\n---\n\n", array_map(static fn (array $c): string => (string) $c['content'], $chunks));

        $answer = $this->client->chat($question, [['role' => 'system', 'content' => $system]]);

        return ['answer' => $answer, 'sources' => $this->sources($chunks)];
    }

    /**
     * Agentic task: draft a structured document (note, report) grounded in the
     * knowledge base.
     *
     * @return array{draft: string, sources: list<array{title: string, excerpt: string}>}
     */
    public function task(string $instruction, int $topK = 5): array
    {
        $chunks = $this->retrieveSimilar($this->embeddings->embed($instruction), $topK);

        if ($chunks === []) {
            return ['draft' => 'No relevant document found. Index documents first.', 'sources' => []];
        }

        $system = "You draft documents for an organization, using ONLY the excerpts below.\n"
            . "Produce a well-structured draft with a title and short sections (bullet points when useful).\n"
            . "Do not invent facts. If something is missing, add a short 'Information manquante' note.\n\n"
            . implode("\n\n---\n\n", array_map(static fn (array $c): string => (string) $c['content'], $chunks));

        $draft = $this->client->chat($instruction, [['role' => 'system', 'content' => $system]]);

        return ['draft' => $draft, 'sources' => $this->sources($chunks)];
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
