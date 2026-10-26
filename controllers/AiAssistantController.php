<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Ai\RagService;

/**
 * HTTP entry point for the AI assistant (JSON in / JSON out).
 *
 * ask  : {"question": "..."}       -> {"answer": "...", "sources": [...]}
 * task : {"instruction": "..."}    -> {"draft": "...", "sources": [...]}
 */
final class AiAssistantController
{
    public function __construct(private readonly RagService $rag)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function ask(array $input): array
    {
        $question = trim((string) ($input['question'] ?? ''));

        if ($question === '') {
            return ['error' => 'A question is required.'];
        }

        return $this->rag->answer($question);
    }

    /**
     * Agentic task: draft a document from the knowledge base.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function task(array $input): array
    {
        $instruction = trim((string) ($input['instruction'] ?? ''));

        if ($instruction === '') {
            return ['error' => 'An instruction is required.'];
        }

        return $this->rag->task($instruction);
    }
}
