<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_espace_save.php
 *
 * Creates a collaborative space. Reserved to `espace.creer`.
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
            'text' => (string) ($data['error'] ?? $data['message'] ?? ($isFr ? 'Espace créé.' : 'Space created.')),
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

if (!$dependencies['permissions']->peutAilleurs($userId, 'espace.creer')) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_espace_save']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_espace_save']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

// Sensitive action: the actor's own password is required.
if (!verifier_mot_de_passe_admin($dependencies, 'espace.creer')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 422);
}

$nom = trim((string) ($_POST['nom'] ?? ''));
$visibilite = (string) ($_POST['visibilite'] ?? 'restreint');

if ($nom === '') {
    $respond(['error' => $isFr ? "Le nom de l'espace est obligatoire." : 'The space name is required.'], 422);
}

if (!in_array($visibilite, ['restreint', 'public_interne'], true)) {
    $respond(['error' => $isFr ? 'Visibilité inconnue.' : 'Unknown visibility.'], 422);
}

try {
    $espaceId = $dependencies['espaces']->create($nom, null, $visibilite);
} catch (Throwable $e) {
    $respond(['error' => $isFr ? 'Création impossible.' : 'Creation failed.'], 500);
}

// The creator joins as manager, otherwise nobody could administer the space.
try {
    $dependencies['adhesions']->create($espaceId, $userId, 'responsable');
} catch (Throwable $e) {
    // Non blocking: the space exists and can be joined from the same page.
}

$dependencies['journal']->log($userId, 'espace.creation', 'espace', $espaceId, 'succes', [
    'nom' => $nom,
    'visibilite' => $visibilite,
]);

$respond([
    'id' => $espaceId,
    'message' => $isFr ? 'Espace créé.' : 'Space created.',
]);
