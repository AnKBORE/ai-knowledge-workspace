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
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

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

// Public presentation page (bilingual, no authentication required).
if ($method === 'GET' && $path === '/presentation') {
    $lang = (($_GET['lang'] ?? 'fr') === 'en') ? 'en' : 'fr';

    header('Content-Type: text/html; charset=utf-8');
    echo $view->render('public/landing', [
        'title' => $lang === 'fr'
            ? 'Agentic Knowledge Workspace — présentation'
            : 'Agentic Knowledge Workspace — presentation',
        'lang' => $lang,
    ], 'public');
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
        '/journal' => ['view' => 'ai/journal', 'title' => "Journal d'audit"],
        '/mon-profil' => ['view' => 'ai/profil', 'title' => 'Mon profil'],
    ];

    if (!isset($pages[$path])) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Not found.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $page = $pages[$path];
    $pageNum = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $search = trim((string) ($_GET['q'] ?? ''));

    $documents = $search !== ''
        ? $dependencies['document_model']->search($search, 200)
        : $dependencies['document_model']->listRecent(200, 0);

    $totalDocuments = $search !== ''
        ? $dependencies['document_model']->countSearch($search)
        : $dependencies['document_model']->count();
    $totalPages = max(1, (int) ceil($totalDocuments / $perPage));
    $entries = $path === '/journal' ? $dependencies['journal']->recent() : [];

    header('Content-Type: text/html; charset=utf-8');
    echo $view->render($page['view'], [
        'title' => $page['title'],
        'documents' => $documents,
        'pageNum' => $pageNum,
        'totalPages' => $totalPages,
        'totalDocuments' => $totalDocuments,
        'search' => $search,
        'entries' => $entries,
        'memberships' => $path === '/mon-profil' ? $dependencies['adhesions']->listForUser((int) $auth->id()) : [],
        'versions' => $path === '/documents' ? $dependencies['version_model']->allByDocument() : [],
        'currentUser' => $auth->user(),
        'csrf' => $csrf,
    ], 'app');
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Not found.'], JSON_UNESCAPED_UNICODE);
