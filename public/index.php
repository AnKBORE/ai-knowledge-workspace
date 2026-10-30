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

$uiLang = (string) $dependencies['session']->get('lang', 'en');
$apiT = app_i18n($uiLang)['api'];
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
    echo $authController->showLogin(null, $uiLang);
    exit;
}

// Login submit.
if ($method === 'POST' && $path === '/login') {
    if (!$csrf->validate($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        header('Content-Type: text/html; charset=utf-8');
        echo $authController->showLogin($apiT['session_expired'], $uiLang);
        exit;
    }

    if ($authController->login($_POST)) {
        header('Location: ' . url('/'));
        exit;
    }

    http_response_code(401);
    header('Content-Type: text/html; charset=utf-8');
    echo $authController->showLogin($apiT['invalid_credentials'], $uiLang);
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

// Language switch — persisted in session, applied to the whole app.
// (/presentation handles its own ?lang= to stay shareable without a session redirect.)
if ($method === 'GET' && isset($_GET['lang']) && $path !== '/presentation') {
    $lang = $_GET['lang'] === 'fr' ? 'fr' : 'en';
    $dependencies['session']->set('lang', $lang);
    header('Location: ' . url($path));
    exit;
}

// Public presentation page (bilingual, no authentication required).
if ($method === 'GET' && $path === '/presentation') {
    $lang = (($_GET['lang'] ?? 'en') === 'fr') ? 'fr' : 'en';
    if (isset($_GET['lang'])) {
        $dependencies['session']->set('lang', $lang);
    }

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
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode((string) file_get_contents('php://input'), true);
    $input = is_array($input) ? $input : [];
    $mode = (string) ($input['mode'] ?? 'answer');
    $required = $mode === 'task' ? 'assistant.tacher' : 'assistant.interroger';

    if (!$dependencies['permissions']->peutAilleurs($auth->id(), $required)) {
        http_response_code(403);
        echo json_encode(['error' => $apiT['permission_denied']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $controller = new AiAssistantController($dependencies['rag'], $uiLang);
    $response = $mode === 'task'
        ? $controller->task($input, $auth->id())
        : $controller->ask($input, $auth->id());

    $dependencies['journal']->log(
        $auth->id(),
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
    exit;
}

// Profile update — requires the current password as confirmation.
if ($method === 'POST' && $path === '/mon-profil') {
    $session = $dependencies['session'];
    $flash = static function (string $type, string $text) use ($session): void {
        $session->set('profil_flash', ['type' => $type, 'text' => $text]);
    };

    if (!$csrf->validate($_POST['_csrf'] ?? null)) {
        $flash('error', $apiT['session_expired']);
        header('Location: ' . url('/mon-profil'));
        exit;
    }

    $userId = (int) $auth->id();
    $prenom = trim((string) ($_POST['prenom'] ?? ''));
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $courriel = strtolower(trim((string) ($_POST['courriel'] ?? '')));
    $motDePasse = (string) ($_POST['mot_de_passe_actuel'] ?? '');
    $users = $dependencies['users'];

    $hash = $users->motDePasseHash($userId);
    if ($hash === null || !\App\Validators\PolitiqueMotDePasse::verify($motDePasse, $hash)) {
        $dependencies['journal']->log($userId, 'profil.maj', 'utilisateur', $userId, 'refus', ['raison' => 'mot_de_passe_incorrect']);
        $flash('error', $apiT['profil_wrong_password']);
    } elseif ($prenom === '' || $nom === '') {
        $flash('error', $apiT['profil_name_required']);
    } elseif (filter_var($courriel, FILTER_VALIDATE_EMAIL) === false) {
        $flash('error', $apiT['profil_email_invalid']);
    } elseif ($users->courrielPris($courriel, $userId)) {
        $flash('error', $apiT['profil_email_taken']);
    } else {
        $users->updateProfil($userId, $prenom, $nom, $courriel);
        $auth->login((array) $users->findById($userId));
        $dependencies['journal']->log($userId, 'profil.maj', 'utilisateur', $userId, 'succes');
        $flash('success', $apiT['profil_saved']);
    }

    header('Location: ' . url('/mon-profil'));
    exit;
}

// Password change — requires the current password and the password policy.
if ($method === 'POST' && $path === '/mon-profil/mot-de-passe') {
    $session = $dependencies['session'];
    $flash = static function (string $type, string $text) use ($session): void {
        $session->set('profil_flash', ['type' => $type, 'text' => $text]);
    };

    if (!$csrf->validate($_POST['_csrf'] ?? null)) {
        $flash('error', $apiT['session_expired']);
        header('Location: ' . url('/mon-profil'));
        exit;
    }

    $userId = (int) $auth->id();
    $users = $dependencies['users'];
    $actuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
    $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation_mot_de_passe'] ?? '');

    $hash = $users->motDePasseHash($userId);
    $policyErrors = \App\Validators\PolitiqueMotDePasse::validate($nouveau, $uiLang);

    if ($hash === null || !\App\Validators\PolitiqueMotDePasse::verify($actuel, $hash)) {
        $dependencies['journal']->log($userId, 'profil.mot_de_passe', 'utilisateur', $userId, 'refus', ['raison' => 'mot_de_passe_incorrect']);
        $flash('error', $apiT['pwd_current_wrong']);
    } elseif ($policyErrors !== []) {
        $flash('error', implode(' ', $policyErrors));
    } elseif ($nouveau !== $confirmation) {
        $flash('error', $apiT['pwd_mismatch']);
    } elseif ($nouveau === $actuel) {
        $flash('error', $apiT['pwd_same']);
    } else {
        $users->updateMotDePasse($userId, \App\Validators\PolitiqueMotDePasse::hash($nouveau));
        $dependencies['journal']->log($userId, 'profil.mot_de_passe', 'utilisateur', $userId, 'succes');
        $flash('success', $apiT['pwd_saved']);
    }

    header('Location: ' . url('/mon-profil'));
    exit;
}

// Workspace pages: each section is its own navigable page.
if ($method === 'GET') {
    $lang = (string) $dependencies['session']->get('lang', 'en');
    $pageTitles = app_i18n($lang)['pages'];
    $pages = [
        '/' => ['view' => 'ai/question'],
        '/tache' => ['view' => 'ai/task'],
        '/traduire' => ['view' => 'ai/translate'],
        '/ajouter' => ['view' => 'ai/upload'],
        '/documents' => ['view' => 'ai/documents'],
        '/journal' => ['view' => 'ai/journal'],
        '/mon-profil' => ['view' => 'ai/profil'],
        '/categories' => ['view' => 'ai/categories'],
    ];

    foreach ($pages as $pagePath => &$pageDef) {
        $pageDef['title'] = $pageTitles[$pagePath] ?? $pageDef['view'];
    }
    unset($pageDef);

    if (!isset($pages[$path])) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => app_i18n($uiLang)['pages']['__not_found__'] ?? ($uiLang === 'en' ? 'Not found.' : 'Introuvable.')], JSON_UNESCAPED_UNICODE);
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
    $flashMessage = $dependencies['session']->get('profil_flash');
    $dependencies['session']->remove('profil_flash');

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
        'categories' => in_array($path, ['/ajouter', '/categories'], true) ? $dependencies['categorie_model']->listAll() : [],
        'versions' => $path === '/documents' ? $dependencies['version_model']->allByDocument() : [],
        'currentUser' => $auth->user(),
        'csrf' => $csrf,
        'flash' => $flashMessage,
        'stats' => $path === '/' ? [
            'documents' => $totalDocuments,
            'chunks' => (int) $dependencies['pdo']->query(
                'SELECT COUNT(*) FROM document_chunks c
                 JOIN documents d ON d.id = c.document_id
                 WHERE d.supprime_le IS NULL'
            )->fetchColumn(),
            'categories' => $dependencies['categorie_model']->count(),
        ] : null,
        'lang' => $lang,
    ], 'app');
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => app_i18n($uiLang)['pages']['__not_found__'] ?? ($uiLang === 'en' ? 'Not found.' : 'Introuvable.')], JSON_UNESCAPED_UNICODE);
