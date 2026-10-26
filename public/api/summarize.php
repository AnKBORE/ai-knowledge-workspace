<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/summarize.php
 *
 * Request:  {"document_id": 3}
 * Response: {"summary": "...", "document_id": 3, "sources": [...]}
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
$documentId = (int) ($input['document_id'] ?? 0);

if ($documentId < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'A document_id is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = $dependencies['rag']->summarize($documentId);

    $dependencies['journal']->log(
        $dependencies['auth']->id(),
        'document.resume',
        'document',
        $documentId,
        'succes',
    );

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $debug = getenv('APP_DEBUG') === '1';
    echo json_encode(['error' => $debug ? $e->getMessage() : 'Summarization failed.'], JSON_UNESCAPED_UNICODE);
}
