<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_user_save.php
 *
 * Creates a user account. Reserved to `compte.inviter` (technical and
 * functional administrators). The permission existed in the RBAC matrix but no
 * screen exercised it, so accounts could only be created from the command line.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];
$isFr = $lang === 'fr';

header('X-Content-Type-Options: nosniff');

/**
 * JSON for an AJAX call, redirect back to /administration for a native form.
 */
$respond = static function (array $data, int $code = 200) use ($dependencies, $isFr): never {
    $isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if (!$isXhr) {
        $ok = !isset($data['error']);
        $dependencies['session']->set('admin_flash', [
            'type' => $ok ? 'success' : 'error',
            'text' => (string) ($data['error'] ?? $data['message'] ?? ($isFr ? 'Compte créé.' : 'Account created.')),
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
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_user_save']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_user_save']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

$courriel = strtolower(trim((string) ($_POST['courriel'] ?? '')));
$nom = trim((string) ($_POST['nom'] ?? ''));
$prenom = trim((string) ($_POST['prenom'] ?? ''));
$motDePasse = (string) ($_POST['mot_de_passe'] ?? '');
$roleGlobal = (string) ($_POST['role_global'] ?? 'membre');

if (!filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
    $respond(['error' => $isFr ? 'Adresse e-mail invalide.' : 'Invalid e-mail address.'], 422);
}

if ($nom === '' || $prenom === '') {
    $respond(['error' => $isFr ? 'Nom et prénom sont obligatoires.' : 'Last and first name are required.'], 422);
}

if (mb_strlen($motDePasse) < 12) {
    $respond(['error' => $isFr ? 'Le mot de passe doit contenir au moins 12 caractères.' : 'The password must be at least 12 characters.'], 422);
}

if (!in_array($roleGlobal, ['membre', 'administrateur_fonctionnel', 'administrateur_technique'], true)) {
    $respond(['error' => $isFr ? 'Rôle inconnu.' : 'Unknown role.'], 422);
}

if ($dependencies['users']->courrielPris($courriel, 0)) {
    $respond(['error' => $isFr ? 'Cette adresse est déjà utilisée.' : 'This address is already in use.'], 409);
}

$hash = password_hash($motDePasse, PASSWORD_ARGON2ID);

try {
    $newId = $dependencies['users']->create($courriel, $nom, $prenom, $hash, $roleGlobal);
} catch (Throwable $e) {
    $respond(['error' => $isFr ? 'Création impossible.' : 'Creation failed.'], 500);
}

$dependencies['journal']->log($userId, 'compte.creation', 'utilisateur', $newId, 'succes', [
    'courriel' => $courriel,
    'role_global' => $roleGlobal,
]);

$respond([
    'id' => $newId,
    'message' => $isFr ? 'Compte créé.' : 'Account created.',
]);
