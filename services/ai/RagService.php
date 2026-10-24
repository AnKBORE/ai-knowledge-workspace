<?php

declare(strict_types=1);

namespace App\Services\Ai;

use PDO;

/**
 * Retrieval-Augmented Generation (RAG) pipeline.
 *
 * Indexing: document -> chunks -> embeddings -> stored in the database.
 * Querying:  question -> embedding -> similar chunks -> grounded answer.
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
     * Answer a question grounded in the indexed documents.
     */
    public function query(string $question, int $topK = 4): string
    {
        $chunks = $this->retrieveSimilar($this->embeddings->embed($question), $topK);

        if ($chunks === []) {
            return 'No relevant document found. Index documents first.';
        }

        $system = "You are a knowledge assistant. Answer using ONLY the excerpts below.\n\n"
            . implode("\n\n---\n\n", $chunks);

        return $this->client->chat($question, [['role' => 'system', 'content' => $system]]);
    }

    /**
     * Retrieve the chunks most similar to the query vector.
     *
     * Cosine similarity is computed in PHP for portability. In phase 2 this
     * step can be replaced by a dedicated vector store or a MySQL vector index.
     *
     * @param list<float> $queryVector
     * @return list<string>
     */
    private function retrieveSimilar(array $queryVector, int $topK): array
    {
        $rows = $this->db->query('SELECT content, embedding FROM document_chunks')->fetchAll(PDO::FETCH_ASSOC);

        $scored = [];
        foreach ($rows as $row) {
            $vector = json_decode((string) $row['embedding'], true);
            if (!is_array($vector)) {
                continue;
            }
            $scored[] = [
                'content' => (string) $row['content'],
                'score' => $this->embeddings->similarity($queryVector, $vector),
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_column(array_slice($scored, 0, $topK), 'content');
    }
}
