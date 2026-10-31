<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\MetaClient;

/**
 * Fake Meta client: fixed embeddings, answer and media extraction, no network.
 */
final class FakeMetaClient extends MetaClient
{
    public function __construct()
    {
        parent::__construct([]);
    }

    public function embed(string $text): array
    {
        return [1.0, 0.0, 0.0];
    }

    public function chat(string $prompt, array $context = [], ?string $model = null, ?float $temperature = null): string
    {
        // Translation calls get a translator system prompt — echo the numbered
        // extracts back so sources keep their original excerpts.
        $system = (string) ($context[0]['content'] ?? '');
        if (str_contains($system, 'traducteur')) {
            return (string) preg_replace('/^QUESTION:.*\n\nEXTRAIT\(S\):\n/s', '', $prompt);
        }

        // Answer/draft calls: cite every provided extract like the real model
        // does ("Sources : [1], [2]…"), so citedChunks keeps them all.
        preg_match_all('/\[Extrait (\d+)/', $system, $m);
        if ($m[1] !== []) {
            $refs = implode(', ', array_map(static fn (string $n): string => "[$n]", $m[1]));
            return 'Grounded answer from context. Sources : ' . $refs;
        }

        return 'Grounded answer from context.';
    }

    public function transcribe(string $audioPath, ?string $lang = null): string
    {
        return 'transcribed meeting text';
    }

    /** @return array{text: string, segments: list<array<string, mixed>>, language: string} */
    public function transcribeTimedLocal(string $audioPath, string $outputBase): array
    {
        return ['text' => 'transcribed meeting text', 'segments' => [], 'language' => 'fr'];
    }

    public function describeImage(string $imagePath): string
    {
        return 'scanned document text';
    }

    /** @return list<string> */
    public function describeImages(array $imagePaths): array
    {
        return array_fill(0, count($imagePaths), 'scanned document text');
    }
}
