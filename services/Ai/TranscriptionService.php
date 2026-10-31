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
    public function transcribe(string $audioPath, ?string $lang = null): string
    {
        if (!is_file($audioPath)) {
            throw new \RuntimeException('Audio file not found: ' . $audioPath);
        }

        return trim($this->client->transcribe($audioPath, $lang));
    }

    /**
     * Translate a transcript into a target language (e.g. a Mooré transcript →
     * French) so both versions can be indexed and searched. Returns '' on
     * failure — translation is best-effort, never blocking.
     */
    public function translateTranscript(string $text, string $targetLanguage): string
    {
        try {
            return trim($this->client->chat($text, [['role' => 'system', 'content' => sprintf(
                'You are a professional translator for French, English and Mooré (Mossi, Burkina Faso). '
                . 'Translate this audio transcript faithfully into %s. The transcript comes from '
                . 'automatic speech recognition and may contain mishearings: silently reconstruct the '
                . 'intended words phonetically using the reference below (e.g. "Ibe kibali kye" ≈ '
                . '"Yibeoog kibaré", "f zak ramba" ≈ "fo zak rãmba"), then translate the reconstructed '
                . 'text. Keep names, figures, dates and speaker markers exact. If the transcript is '
                . 'already written in %s, return it unchanged. Output ONLY the final translation, '
                . 'decisively: never show the reconstruction, never add notes, alternatives or '
                . 'uncertainty markers — commit to the single most likely meaning.'
                . "\n\n" . MooreReference::prompt(),
                $targetLanguage, $targetLanguage,
            )]], null, 0.1));
        } catch (\Throwable) {
            return '';
        }
    }
}
