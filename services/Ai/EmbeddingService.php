<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Vector helpers used by the RAG pipeline.
 *
 * By default it uses a local, dependency-free embedding (hashing trick), so the
 * retrieval works without a dedicated embeddings endpoint. Set
 * META_EMBEDDING_MODE=api to use the Meta embeddings endpoint instead.
 */
final class EmbeddingService
{
    public function __construct(
        private readonly MetaClient $client,
        private readonly string $mode = 'local',
        private readonly int $dimensions = 256,
    ) {
    }

    /**
     * Embed a single piece of text into a numeric vector.
     *
     * @return list<float>
     */
    public function embed(string $text): array
    {
        if ($this->mode === 'api') {
            $vector = $this->client->embed($text);
            if ($vector !== []) {
                return $vector;
            }
        }

        return $this->localEmbed($text);
    }

    /**
     * Deterministic local embedding using the hashing trick (bag of words).
     *
     * @return list<float>
     */
    private function localEmbed(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $vector[crc32($token) % $this->dimensions] += 1.0;
        }

        $norm = 0.0;
        foreach ($vector as $value) {
            $norm += $value * $value;
        }
        $norm = sqrt($norm);

        if ($norm > 0.0) {
            foreach ($vector as $index => $value) {
                $vector[$index] = $value / $norm;
            }
        }

        return $vector;
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
