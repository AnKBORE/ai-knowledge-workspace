<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_user_statut.php
 *
 * Suspends or reinstates an account. The Super Admin is the bootstrap account
 * and can never be suspended: the model refuses it, this endpoint reports it.
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
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_user_statut']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_user_statut']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

if (!verifier_mot_de_passe_admin($dependencies, 'compte.statut')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 422);
}

$targetId = (int) ($_POST['utilisateur_id'] ?? 0);
$statut = (string) ($_POST['statut'] ?? '');

if ($targetId < 1 || !in_array($statut, ['actif', 'suspendu'], true)) {
    $respond(['error' => $isFr ? 'Compte ou statut invalide.' : 'Invalid account or status.'], 422);
}

if (compte_super_protege($dependencies, $targetId)) {
    $dependencies['journal']->log($userId, 'securite.refus', 'utilisateur', $targetId, 'refus', ['raison' => 'super_admin_protege']);

    $respond(['error' => $isFr
        ? 'Le Super Admin est le compte d\'amorçage : il ne peut pas être suspendu.'
        : 'The Super Admin is the bootstrap account: it cannot be suspended.'], 403);
}

try {
    $dependencies['users']->changerStatut($targetId, $statut);
} catch (Throwable $e) {
    $respond(['error' => $isFr ? 'Modification impossible.' : 'Change failed.'], 422);
}

$dependencies['journal']->log($userId, $statut === 'suspendu' ? 'compte.suspension' : 'compte.reactivation', 'utilisateur', $targetId, 'succes', [
    'statut' => $statut,
]);

$respond(['message' => $statut === 'suspendu'
    ? ($isFr ? 'Compte suspendu.' : 'Account suspended.')
    : ($isFr ? 'Compte réactivé.' : 'Account reinstated.')]);
