<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Extracts text from scanned documents (paper records, forms, tables) with SAM 3.1.
 */
final class VisionService
{
    public function __construct(private readonly MetaClient $client)
    {
    }

    /**
     * Extract text from a local image file (a scan or a photo of a form).
     */
    public function extractText(string $imagePath): string
    {
        if (!is_file($imagePath)) {
            throw new \RuntimeException('Image file not found: ' . $imagePath);
        }

        return trim($this->client->describeImage($imagePath));
    }
}
