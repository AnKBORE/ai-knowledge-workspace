<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_user_role.php
 *
 * Changes an account's global role. The Super Admin can never be demoted: it is
 * the bootstrap account, the only way back in after a wipe.
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
        $dependencies['session']->set('admin_flash', [
            'type' => isset($data['error']) ? 'error' : 'success',
            'text' => (string) ($data['error'] ?? $data['message'] ?? ''),
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

if (!$dependencies['permissions']->peutAilleurs($userId, 'compte.inviter')) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_user_role']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_user_role']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

if (!verifier_mot_de_passe_admin($dependencies, 'compte.role')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 422);
}

$targetId = (int) ($_POST['utilisateur_id'] ?? 0);
$role = (string) ($_POST['role_global'] ?? '');

if ($targetId < 1 || !in_array($role, ['membre', 'administrateur_fonctionnel', 'administrateur_technique'], true)) {
    $respond(['error' => $isFr ? 'Compte ou rôle invalide.' : 'Invalid account or role.'], 422);
}

if (compte_super_protege($dependencies, $targetId) && $role !== 'administrateur_technique') {
    $dependencies['journal']->log($userId, 'securite.refus', 'utilisateur', $targetId, 'refus', ['raison' => 'super_admin_protege']);

    $respond(['error' => $isFr
        ? 'Le Super Admin est le compte d\'amorçage : il ne peut pas être rétrogradé.'
        : 'The Super Admin is the bootstrap account: it cannot be demoted.'], 403);
}

try {
    $dependencies['users']->changerRole($targetId, $role);
} catch (Throwable $e) {
    $respond(['error' => $isFr ? 'Modification impossible.' : 'Change failed.'], 422);
}

$dependencies['journal']->log($userId, 'compte.role', 'utilisateur', $targetId, 'succes', ['role_global' => $role]);

$respond(['message' => $isFr ? 'Rôle modifié.' : 'Role changed.']);
