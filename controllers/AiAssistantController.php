<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Ai\RagService;

/**
 * HTTP entry point for the AI assistant (JSON in / JSON out).
 *
 * Request:  {"question": "Which training projects were carried out in 2025?"}
 * Response: {"answer": "..."}
 */
final class AiAssistantController
{
    public function __construct(private readonly RagService $rag)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function ask(array $input): array
    {
        $question = trim((string) ($input['question'] ?? ''));

        if ($question === '') {
            return ['error' => 'A question is required.'];
        }

        return ['answer' => $this->rag->query($question)];
    }
}
