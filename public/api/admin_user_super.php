<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_user_super.php
 *
 * Adds an account to the set of protected Super Admins, or removes one. Any
 * protected account may do it, with their own password. The last protected
 * account can never be removed: the installation must always keep a way back in.
 *
 * The command line keeps the same capability as a recovery path.
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

// Only a protected account manages the set.
if (!compte_super_protege($dependencies, $userId)) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'pas_super_admin', 'chemin' => '/api/admin_user_super']);
    $respond(['error' => $isFr
        ? 'Seul un Super Admin peut gérer la liste des comptes protégés.'
        : 'Only a Super Admin can manage the protected accounts.'], 403);
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

$action = (string) ($_POST['action'] ?? 'grant');
$targetId = (int) ($_POST['utilisateur_id'] ?? 0);
$cible = $targetId > 0 ? $dependencies['users']->findById($targetId) : null;

if ($cible === null) {
    $respond(['error' => $isFr ? 'Compte inconnu.' : 'Unknown account.'], 404);
}

if (!in_array($action, ['grant', 'revoke'], true)) {
    $respond(['error' => $isFr ? 'Action inconnue.' : 'Unknown action.'], 422);
}

$estProtege = $dependencies['users']->estCompteSuper($targetId);

if ($action === 'grant') {
    if ($estProtege) {
        $respond(['error' => $isFr
            ? 'Ce compte est déjà protégé.'
            : 'This account is already protected.'], 422);
    }

    if (($cible['statut'] ?? 'actif') !== 'actif') {
        $respond(['error' => $isFr
            ? 'Le compte doit être actif pour recevoir la protection.'
            : 'The account must be active to receive the protection.'], 422);
    }

    $ancienRole = (string) ($cible['role_global'] ?? 'membre');
    $dependencies['users']->accorderSuperAdmin($targetId);

    $dependencies['journal']->log($userId, 'compte.super_ajout', 'utilisateur', $targetId, 'succes', [
        'ancien_role' => $ancienRole,
        'source' => 'interface',
    ]);

    $respond([
        'id' => $targetId,
        'message' => $isFr
            ? 'Compte protégé ajouté.'
            : 'Protected account added.',
    ]);
}

// Revocation: never the last one.
try {
    $dependencies['users']->retirerSuperAdmin($targetId);
} catch (RuntimeException $e) {
    $dependencies['journal']->log($userId, 'securite.refus', 'utilisateur', $targetId, 'refus', ['raison' => 'dernier_super_admin']);

    $respond(['error' => $isFr
        ? 'Impossible : il doit toujours rester au moins un compte protégé.'
        : 'Impossible: at least one protected account must always remain.'], 409);
}

$dependencies['journal']->log($userId, 'compte.super_retrait', 'utilisateur', $targetId, 'succes', [
    'source' => 'interface',
]);

$respond([
    'id' => $targetId,
    'message' => $isFr ? 'Protection retirée.' : 'Protection removed.',
]);
