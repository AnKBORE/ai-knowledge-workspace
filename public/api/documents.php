<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/documents.php
 *
 * Returns the recent documents as JSON so the workspace can refresh the list
 * after an upload without a full page reload.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$documents = $dependencies['document_model']->listRecent();

echo json_encode(['documents' => $documents], JSON_UNESCAPED_UNICODE);
