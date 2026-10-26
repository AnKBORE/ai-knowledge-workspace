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

    public function chat(string $prompt, array $context = [], ?string $model = null): string
    {
        return 'Grounded answer from context.';
    }

    public function transcribe(string $audioPath): string
    {
        return 'transcribed meeting text';
    }

    public function describeImage(string $imagePath): string
    {
        return 'scanned document text';
    }
}
