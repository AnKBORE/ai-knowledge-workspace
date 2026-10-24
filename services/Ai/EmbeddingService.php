<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Vector helpers used by the RAG pipeline.
 */
final class EmbeddingService
{
    public function __construct(private readonly MetaClient $client)
    {
    }

    /**
     * Embed a single piece of text into a numeric vector.
     *
     * @return list<float>
     */
    public function embed(string $text): array
    {
        return $this->client->embed($text);
    }

    /**
     * Cosine similarity between two vectors (1 = identical, 0 = orthogonal).
     *
     * @param list<float> $a
     * @param list<float> $b
     */
    public function similarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = min(count($a), count($b));
        for ($i = 0; $i < $count; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
