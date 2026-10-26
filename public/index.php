<?php

declare(strict_types=1);

use App\Controllers\AiAssistantController;
use App\Core\View;

/**
 * Front controller — single entry point of the application.
 *
 *   GET  /          -> workspace UI (upload + chat)
 *   POST /api/ask   -> AI knowledge assistant ({"question": "..."})
 */

try {
    $dependencies = require dirname(__DIR__) . '/bootstrap.php';
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Application bootstrap failed: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');

// JSON API (used by the workspace UI, and available under Apache rewriting).
if ($method === 'POST' && $path === '/api/ask') {
    $controller = new AiAssistantController($dependencies['rag']);
    $input = json_decode((string) file_get_contents('php://input'), true);
    $input = is_array($input) ? $input : [];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($controller->ask($input), JSON_UNESCAPED_UNICODE);
    exit;
}

// Workspace UI (any normal GET).
if ($method === 'GET') {
    $documents = $dependencies['pdo']
        ->query('SELECT id, title, filename FROM documents ORDER BY id DESC LIMIT 20')
        ->fetchAll();

    header('Content-Type: text/html; charset=utf-8');
    $view = new View(dirname(__DIR__) . '/views');
    echo $view->render('ai/index', ['title' => 'Assistant', 'documents' => $documents]);
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Not found.'], JSON_UNESCAPED_UNICODE);
