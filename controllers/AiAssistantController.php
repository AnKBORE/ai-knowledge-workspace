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
    public function __construct(
        private readonly RagService $rag,
        private readonly string $lang = 'en',
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function ask(array $input, ?int $userId = null): array
    {
        $question = trim((string) ($input['question'] ?? ''));

        if ($question === '') {
            return ['error' => app_i18n($this->lang)['api']['question_required']];
        }

        return $this->rag->answer($question, 6, $userId);
    }

    /**
     * Agentic task: draft a document from the knowledge base.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function task(array $input, ?int $userId = null): array
    {
        $instruction = trim((string) ($input['instruction'] ?? ''));

        if ($instruction === '') {
            return ['error' => app_i18n($this->lang)['api']['instruction_required']];
        }

        return $this->rag->task($instruction, 8, $userId);
    }
}
