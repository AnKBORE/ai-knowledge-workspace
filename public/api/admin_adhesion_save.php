<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_adhesion_save.php
 *
 * Adds a user to a collaborative space with a local role. Reserved to
 * `adhesion.gerer`. This is what makes a space collaborative: without it, a
 * member can only ever see what they uploaded themselves.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];
$isFr = $lang === 'fr';

header('X-Content-Type-Options: nosniff');

$respond = static function (array $data, int $code = 200) use ($dependencies, $isFr): never {
    $isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if (!$isXhr) {
        $ok = !isset($data['error']);
        $dependencies['session']->set('admin_flash', [
            'type' => $ok ? 'success' : 'error',
            'text' => (string) ($data['error'] ?? $data['message'] ?? ($isFr ? 'Membre ajouté.' : 'Member added.')),
        ]);
        header('Location: ' . rtrim(str_replace('\\', '/', dirname(dirname((string) $_SERVER['SCRIPT_NAME']))), '/') . '/administration');
        exit;
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

if (!$dependencies['auth']->check()) {
    $respond(['error' => $T['auth_required']], 401);
}

$userId = (int) $dependencies['auth']->id();

if (!$dependencies['permissions']->peutAilleurs($userId, 'adhesion.gerer')) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_adhesion_save']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_adhesion_save']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

$utilisateurId = (int) ($_POST['utilisateur_id'] ?? 0);
$espaceId = (int) ($_POST['espace_id'] ?? 0);
$roleLocal = (string) ($_POST['role_local'] ?? 'lecteur');

if ($utilisateurId < 1 || $espaceId < 1) {
    $respond(['error' => $isFr ? 'Choisissez un utilisateur et un espace.' : 'Choose a user and a space.'], 422);
}

if (!in_array($roleLocal, ['lecteur', 'contributeur', 'animateur', 'responsable'], true)) {
    $respond(['error' => $isFr ? 'Rôle local inconnu.' : 'Unknown local role.'], 422);
}

if ($dependencies['users']->findById($utilisateurId) === null) {
    $respond(['error' => $isFr ? 'Utilisateur inconnu.' : 'Unknown user.'], 404);
}

if ($dependencies['espaces']->find($espaceId) === null) {
    $respond(['error' => $isFr ? 'Espace inconnu.' : 'Unknown space.'], 404);
}

if ($dependencies['adhesions']->find($espaceId, $utilisateurId) !== null) {
    $respond(['error' => $isFr ? 'Cet utilisateur est déjà membre de cet espace.' : 'This user is already a member of that space.'], 409);
}

try {
    $adhesionId = $dependencies['adhesions']->create($espaceId, $utilisateurId, $roleLocal);
} catch (Throwable $e) {
    $respond(['error' => $isFr ? 'Ajout impossible.' : 'Could not add the member.'], 500);
}

$dependencies['journal']->log($userId, 'adhesion.creation', 'adhesion', $adhesionId, 'succes', [
    'utilisateur_id' => $utilisateurId,
    'espace_id' => $espaceId,
    'role_local' => $roleLocal,
]);

$respond([
    'id' => $adhesionId,
    'message' => $isFr ? 'Membre ajouté.' : 'Member added.',
]);
