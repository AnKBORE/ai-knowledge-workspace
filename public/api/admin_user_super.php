<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_user_super.php
 *
 * Moves the bootstrap protection (the ★ Super Admin) to another account. Only
 * the current Super Admin may do it, and only with their own password: the
 * action changes who holds the single protected account of the installation.
 *
 * The command line keeps the same capability as a recovery path when the
 * protected account is locked out.
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

// Only the protected account can hand the protection over.
if (!compte_super_protege($dependencies, $userId)) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'pas_super_admin', 'chemin' => '/api/admin_user_super']);
    $respond(['error' => $isFr
        ? 'Seul le Super Admin actuel peut transférer la protection.'
        : 'Only the current Super Admin can transfer the protection.'], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_user_super']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

if (!verifier_mot_de_passe_admin($dependencies, 'compte.super')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 422);
}

$targetId = (int) ($_POST['utilisateur_id'] ?? 0);
$cible = $targetId > 0 ? $dependencies['users']->findById($targetId) : null;

if ($cible === null) {
    $respond(['error' => $isFr ? 'Compte inconnu.' : 'Unknown account.'], 404);
}

if ($targetId === $userId) {
    $respond(['error' => $isFr
        ? 'Ce compte est déjà le Super Admin.'
        : 'This account is already the Super Admin.'], 422);
}

if (($cible['statut'] ?? 'actif') !== 'actif') {
    $respond(['error' => $isFr
        ? 'Le compte doit être actif pour recevoir la protection.'
        : 'The account must be active to receive the protection.'], 422);
}

$ancien = $dependencies['users']->compteSuperId();
$ancienRole = (string) ($cible['role_global'] ?? 'membre');

$dependencies['users']->designerSuperAdmin($targetId);

$dependencies['journal']->log($userId, 'compte.super_transfert', 'utilisateur', $targetId, 'succes', [
    'avant' => $ancien,
    'apres' => $targetId,
    'ancien_role' => $ancienRole,
    'source' => 'interface',
]);

$respond([
    'id' => $targetId,
    'message' => $isFr
        ? 'Protection transférée. Ce compte est désormais le Super Admin.'
        : 'Protection transferred. This account is now the Super Admin.',
]);
