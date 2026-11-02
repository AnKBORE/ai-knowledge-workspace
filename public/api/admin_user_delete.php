<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_user_delete.php
 *
 * Soft-deletes an account. The Super Admin is the bootstrap account and can
 * never be deleted: that is the whole point of the flag.
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
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_user_delete']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_user_delete']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

if (!verifier_mot_de_passe_admin($dependencies, 'compte.supprimer')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 422);
}

$targetId = (int) ($_POST['utilisateur_id'] ?? 0);

if ($targetId < 1) {
    $respond(['error' => $isFr ? 'Compte invalide.' : 'Invalid account.'], 422);
}

if ($targetId === $userId) {
    $respond(['error' => $isFr ? 'Vous ne pouvez pas supprimer votre propre compte.' : 'You cannot delete your own account.'], 422);
}

if (compte_super_protege($dependencies, $targetId)) {
    $dependencies['journal']->log($userId, 'securite.refus', 'utilisateur', $targetId, 'refus', ['raison' => 'super_admin_protege']);

    $respond(['error' => $isFr
        ? 'Le Super Admin est le compte d\'amorçage : il ne peut pas être supprimé.'
        : 'The Super Admin is the bootstrap account: it cannot be deleted.'], 403);
}

try {
    $dependencies['users']->supprimerLogique($targetId);
} catch (Throwable $e) {
    $respond(['error' => $isFr ? 'Suppression impossible.' : 'Deletion failed.'], 422);
}

$dependencies['journal']->log($userId, 'compte.suppression', 'utilisateur', $targetId, 'succes', []);

$respond(['message' => $isFr ? 'Compte supprimé.' : 'Account deleted.']);
