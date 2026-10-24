<?php

declare(strict_types=1);

use App\Controllers\AiAssistantController;
use App\Core\Router;

/**
 * Front controller — single entry point of the application.
 *
 * Routes:
 *   GET  /          -> application status / endpoints
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

$router = new Router();

$router->add('GET', '/', static function () use ($dependencies): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'app' => 'AI Knowledge Workspace',
        'status' => 'ok',
        'endpoints' => [
            'POST /api/ask' => 'Ask the knowledge assistant',
        ],
    ], JSON_UNESCAPED_UNICODE);
});

$router->add('POST', '/api/ask', static function () use ($dependencies): void {
    $controller = new AiAssistantController($dependencies['rag']);
    $input = json_decode((string) file_get_contents('php://input'), true);
    $input = is_array($input) ? $input : [];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($controller->ask($input), JSON_UNESCAPED_UNICODE);
});

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, $path);
