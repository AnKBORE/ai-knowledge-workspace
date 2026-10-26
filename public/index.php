<?php

declare(strict_types=1);

use App\Controllers\AiAssistantController;
use App\Controllers\AuthController;
use App\Core\View;

/**
 * Front controller — single entry point of the application.
 *
 *   GET  /login   -> login form
 *   POST /login   -> authenticate
 *   POST /logout  -> sign out
 *   GET  /        -> workspace UI (authenticated only)
 *   POST /api/ask -> AI knowledge assistant (authenticated only)
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

$view = new View(dirname(__DIR__) . '/views');
$auth = $dependencies['auth'];
$csrf = $dependencies['csrf'];
$authController = new AuthController($dependencies['auth_service'], $csrf, $view);

// Login form.
if ($method === 'GET' && $path === '/login') {
    if ($auth->check()) {
        header('Location: ' . url('/'));
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo $authController->showLogin();
    exit;
}

// Login submit.
if ($method === 'POST' && $path === '/login') {
    if (!$csrf->validate($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        header('Content-Type: text/html; charset=utf-8');
        echo $authController->showLogin('Session expirée. Rechargez la page et réessayez.');
        exit;
    }

    if ($authController->login($_POST)) {
        header('Location: ' . url('/'));
        exit;
    }

    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    echo $authController->showLogin('Identifiants invalides.');
    exit;
}

// Logout.
if ($method === 'POST' && $path === '/logout') {
    if ($csrf->validate($_POST['_csrf'] ?? null)) {
        $authController->logout();
    }

    header('Location: ' . url('/login'));
    exit;
}

// Everything below requires an authenticated user.
if (!$auth->check()) {
    header('Location: ' . url('/login'));
    exit;
}

// JSON API (also available under Apache rewriting).
if ($method === 'POST' && $path === '/api/ask') {
    $controller = new AiAssistantController($dependencies['rag']);
    $input = json_decode((string) file_get_contents('php://input'), true);
    $input = is_array($input) ? $input : [];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($controller->ask($input), JSON_UNESCAPED_UNICODE);
    exit;
}

// Workspace pages: each section is its own navigable page.
if ($method === 'GET') {
    $pages = [
        '/' => ['view' => 'ai/question', 'title' => 'Question'],
        '/tache' => ['view' => 'ai/task', 'title' => 'Tâche agentique'],
        '/ajouter' => ['view' => 'ai/upload', 'title' => 'Ajouter un document'],
        '/documents' => ['view' => 'ai/documents', 'title' => 'Documents indexés'],
    ];

    if (!isset($pages[$path])) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Not found.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $page = $pages[$path];
    $documents = $dependencies['document_model']->listRecent();

    header('Content-Type: text/html; charset=utf-8');
    echo $view->render($page['view'], [
        'title' => $page['title'],
        'documents' => $documents,
        'currentUser' => $auth->user(),
        'csrf' => $csrf,
    ], 'app');
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Not found.'], JSON_UNESCAPED_UNICODE);
