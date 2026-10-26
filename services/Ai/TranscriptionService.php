<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Turns audio recordings (meetings, voice notes) into text with Muse Voice Transcribe.
 */
final class TranscriptionService
{
    public function __construct(private readonly MetaClient $client)
    {
    }

    /**
     * Transcribe a local audio file and return the recognised text.
     */
    public function transcribe(string $audioPath): string
    {
        if (!is_file($audioPath)) {
            throw new \RuntimeException('Audio file not found: ' . $audioPath);
        }

        return trim($this->client->transcribe($audioPath));
    }
}
