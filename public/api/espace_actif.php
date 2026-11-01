<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/espace_actif.php
 *
 * Chooses the workspace the user is currently working in. The choice is stored
 * in the session and scopes the document list and the deposits. Choosing 0
 * clears it (the user then sees everything they are allowed to see).
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');

if (!$dependencies['auth']->check()) {
    header('Location: ' . rtrim(str_replace('\\', '/', dirname(dirname((string) $_SERVER['SCRIPT_NAME']))), '/') . '/login');
    exit;
}

$userId = (int) $dependencies['auth']->id();
$appBase = rtrim(str_replace('\\', '/', dirname(dirname((string) $_SERVER['SCRIPT_NAME']))), '/');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/espace_actif']);
    header('Location: ' . $appBase . '/espaces');
    exit;
}

$espaceId = (int) ($_POST['espace_id'] ?? 0);

if ($espaceId < 1) {
    $dependencies['session']->remove('espace_actif');
    header('Location: ' . $appBase . '/documents');
    exit;
}

$espace = $dependencies['espaces']->find($espaceId);

// A user may only enter a space they can consult: their own memberships, the
// internal public spaces, or any space for a global administrator.
$canEnter = $espace !== null
    && $dependencies['permissions']->peut($userId, 'espace.consulter', $espaceId);

if (!$canEnter) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'espace_interdit', 'espace_id' => $espaceId]);
    header('Location: ' . $appBase . '/espaces');
    exit;
}

$dependencies['session']->set('espace_actif', $espaceId);

$dependencies['journal']->log($userId, 'espace.actif', 'espace', $espaceId, 'succes', [
    'nom' => (string) ($espace['nom'] ?? ''),
]);

header('Location: ' . $appBase . '/documents');
