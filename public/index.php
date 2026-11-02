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
        '/administration' => ['view' => 'ai/administration'],
        '/espaces' => ['view' => 'ai/espaces'],
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

    // The list is scoped like retrieval: a member only sees the documents they
    // are allowed to read, otherwise the permission model would only apply
    // inside the RAG.
    $visibleDocuments = $dependencies['permissions']->documentsVisibles((int) $auth->id());
    // Changing a document's workspace or confidentiality is an administrative act.
    $canControlDocuments = $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'espace.administrer');
    $documentsFlash = $dependencies['session']->get('documents_flash');
    $dependencies['session']->remove('documents_flash');

    $espaceActif = (int) $dependencies['session']->get('espace_actif', 0);
    // The active workspace name is shown in the topbar on every page.
    $espaceActifNom = '';
    if ($espaceActif > 0) {
        $espaceRow = $dependencies['espaces']->find($espaceActif);
        $espaceActifNom = $espaceRow === null ? '' : (string) ($espaceRow['nom'] ?? '');
        if ($espaceActifNom === '') {
            // The workspace no longer exists: clear the stale selection.
            $dependencies['session']->set('espace_actif', 0);
            $espaceActif = 0;
        }
    }

    // Explicit filter from the document list: it wins over the active workspace.
    $filtreEspace = $path === '/documents' ? (int) ($_GET['espace'] ?? 0) : 0;

    $documents = $search !== ''
        ? $dependencies['document_model']->search($search, 200, $visibleDocuments)
        : $dependencies['document_model']->listRecent(200, 0, $visibleDocuments);

    // Working inside a workspace scopes the list to that workspace.
    if ($filtreEspace > 0 && $documents !== []) {
        $documents = array_values(array_filter(
            $documents,
            static fn (array $doc): bool => in_array(
                $filtreEspace,
                array_map('intval', array_filter(explode(',', (string) ($doc['espaces'] ?? '')))),
                true
            )
        ));
    } elseif (($espaceActif ?? 0) > 0 && $documents !== []) {
        $documents = array_values(array_filter(
            $documents,
            static function (array $doc) use ($espaceActif): bool {
                $espaces = array_map('intval', array_filter(explode(',', (string) ($doc['espaces'] ?? ''))));

                return in_array((int) $espaceActif, $espaces, true);
            }
        ));
    }

    $totalDocuments = $search !== ''
        ? count($documents)
        : (is_array($visibleDocuments) ? count($documents) : $dependencies['document_model']->count());
    $totalPages = max(1, (int) ceil($totalDocuments / $perPage));
    // The audit journal carries IP addresses and actor names: it is reserved
    // to `journal.lire`, exactly like its CSV export and its purge.
    $journalDenied = $path === '/journal'
        && !$dependencies['permissions']->peutAilleurs((int) $auth->id(), 'journal.lire');

    if ($journalDenied) {
        http_response_code(403);
        $dependencies['journal']->log((int) $auth->id(), 'securite.refus', 'securite', null, 'refus', ['raison' => 'journal_interdit', 'chemin' => '/journal']);
    }

    // Categories are managed by whoever may deposit or delete documents: the
    // page offers exactly those two actions, so anyone else gets a clean refusal
    // instead of buttons that would answer 403.
    $categoriesDenied = $path === '/categories'
        && !$dependencies['permissions']->peutAilleurs((int) $auth->id(), 'document.deposer')
        && !$dependencies['permissions']->peutAilleurs((int) $auth->id(), 'document.supprimer');

    if ($categoriesDenied) {
        http_response_code(403);
        $dependencies['journal']->log((int) $auth->id(), 'securite.refus', 'securite', null, 'refus', ['raison' => 'categories_interdites', 'chemin' => '/categories']);
    }

    // The sidebar follows the permissions: a member never sees an entry they
    // would only get a 403 on.
    $navPerms = [
        0 => true,
        1 => $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'assistant.tacher'),
        2 => true,
        3 => $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'document.consulter'),
        4 => $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'document.deposer'),
        5 => $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'document.supprimer'),
        6 => $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'journal.lire'),
        7 => true,
        8 => $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'compte.inviter')
            || $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'espace.creer')
            || $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'adhesion.gerer'),
        9 => true,
    ];

    $estAdminGlobal = in_array(
        (string) ($dependencies['permissions']->roleGlobal((int) $auth->id()) ?? ''),
        ['administrateur_technique', 'administrateur_fonctionnel'],
        true
    );

    // Names for the workspace badges on the document list. Only the spaces the
    // user can enter are resolved: a visible document always belongs to one.
    $espacesNoms = [];

    if ($path === '/documents') {
        foreach ($dependencies['espaces']->pourUtilisateur((int) $auth->id(), $estAdminGlobal) as $espaceBadge) {
            $espacesNoms[(int) $espaceBadge['id']] = (string) $espaceBadge['nom'];
        }
    }

    // Only the workspaces where the user may actually deposit are offered.
    $espacesDepot = [];

    if ($path === '/ajouter') {
        foreach ($dependencies['espaces']->pourUtilisateur((int) $auth->id(), $estAdminGlobal) as $espaceDispo) {
            if ($dependencies['permissions']->peut((int) $auth->id(), 'document.deposer', (int) $espaceDispo['id'])) {
                $espacesDepot[] = $espaceDispo;
            }
        }
    }

    $mesEspaces = $path === '/espaces'
        ? $dependencies['espaces']->pourUtilisateur((int) $auth->id(), $estAdminGlobal)
        : [];
    // Administration: each form has its own permission, so the page is open to
    // anyone who can do at least one of them.
    $canInvite = $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'compte.inviter');
    $canCreateSpace = $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'espace.creer');
    $canManageAdhesion = $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'adhesion.gerer');
    $canArchive = $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'espace.archiver');
    // Only the protected account can hand the protection over from the interface.
    $estSuperActeur = $dependencies['users']->estCompteSuper((int) $auth->id());
    $nbSuperAdmins = count($dependencies['users']->superAdmins());

    $adminDenied = $path === '/administration' && !($canInvite || $canCreateSpace || $canManageAdhesion);

    if ($adminDenied) {
        http_response_code(403);
    }

    $adminData = $path === '/administration' && !$adminDenied;
    $adminUsers = $adminData ? $dependencies['users']->all() : [];
    $adminEspaces = $adminData ? $dependencies['espaces']->all() : [];
    $adminAdhesions = $adminData ? $dependencies['adhesions']->all() : [];
    $adminFlash = $dependencies['session']->get('admin_flash');
    $dependencies['session']->remove('admin_flash');
    $entries = $path === '/journal' && !$journalDenied ? $dependencies['journal']->recent() : [];
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
        'journalDenied' => $journalDenied ?? false,
        'adminUsers' => $adminUsers ?? [],
        'adminEspaces' => ($adminEspaces ?? []) !== []
            ? $adminEspaces
            : (($canControlDocuments ?? false) ? $dependencies['espaces']->all() : []),
        'adminAdhesions' => $adminAdhesions ?? [],
        'adminFlash' => $adminFlash ?? null,
        'canInvite' => $canInvite ?? false,
        'canCreateSpace' => $canCreateSpace ?? false,
        'canManageAdhesion' => $canManageAdhesion ?? false,
        'canArchive' => $canArchive ?? false,
        'estSuperActeur' => $estSuperActeur ?? false,
        'nbSuperAdmins' => $nbSuperAdmins ?? 0,
        'navPerms' => $navPerms ?? [],
        'canControlDocuments' => $canControlDocuments ?? false,
        'documentsFlash' => $documentsFlash ?? null,
        'categoriesDenied' => $categoriesDenied ?? false,
        'mesEspaces' => $mesEspaces ?? [],
        'espaceActif' => $espaceActif ?? null,
        'espacesDepot' => $espacesDepot ?? [],
        'espacesNoms' => $espacesNoms ?? [],
        'filtreEspace' => $filtreEspace ?? 0,
        'canPublish' => $dependencies['permissions']->peutAilleurs((int) $auth->id(), 'espace.administrer'),
        'espaceActifNom' => $espaceActifNom ?? '',
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
