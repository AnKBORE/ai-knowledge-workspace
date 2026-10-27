<?php

declare(strict_types=1);

use App\Controllers\AiAssistantController;

/**
 * Public endpoint — POST /api/ask.php
 *
 * Request:  {"question": "..."}
 * Response: {"answer": "..."} or {"error": "..."}
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];
$mode = (string) ($input['mode'] ?? 'answer');
$required = $mode === 'task' ? 'assistant.tacher' : 'assistant.interroger';

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), $required)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $controller = new AiAssistantController($dependencies['rag']);
    $response = $mode === 'task' ? $controller->task($input) : $controller->ask($input);

    $dependencies['journal']->log(
        $dependencies['auth']->id(),
        'assistant.question',
        'document',
        null,
        'succes',
        [
            'mode' => $mode,
            'question' => mb_substr(
                $mode === 'task' ? (string) ($input['instruction'] ?? '') : (string) ($input['question'] ?? ''),
                0,
                200,
            ),
            'sources' => count($response['sources'] ?? []),
        ],
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $debug = getenv('APP_DEBUG') === '1';
    echo json_encode(['error' => $debug ? $e->getMessage() : 'Internal server error.'], JSON_UNESCAPED_UNICODE);
}
