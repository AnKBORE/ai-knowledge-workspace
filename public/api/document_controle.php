<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/document_controle.php
 *
 * The administration changes where a document lives and who may read it. The
 * deposit sets a default; this is the deliberate override, so it requires the
 * administrator password and is journaled with the previous values.
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
        $dependencies['session']->set('documents_flash', [
            'type' => isset($data['error']) ? 'error' : 'success',
            'text' => (string) ($data['error'] ?? $data['message'] ?? ''),
        ]);
        header('Location: ' . rtrim(str_replace('\\', '/', dirname(dirname((string) $_SERVER['SCRIPT_NAME']))), '/') . '/documents');
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

// Diffusion is an organisational decision: the global administrators hold it.
if (!$dependencies['permissions']->peutAilleurs($userId, 'espace.administrer')) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/document_controle']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/document_controle']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

if (!verifier_mot_de_passe_admin($dependencies, 'document.controle')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 422);
}

$documentId = (int) ($_POST['document_id'] ?? 0);
$espaceId = (int) ($_POST['espace_id'] ?? 0);
$confidentialite = (string) ($_POST['confidentialite'] ?? 'restreint');

if ($documentId < 1) {
    $respond(['error' => $isFr ? 'Document invalide.' : 'Invalid document.'], 422);
}

if (!in_array($confidentialite, ['public_interne', 'restreint', 'prive'], true)) {
    $respond(['error' => $isFr ? 'Confidentialité invalide.' : 'Invalid confidentiality.'], 422);
}

$document = $dependencies['document_model']->find($documentId);

if ($document === null) {
    $respond(['error' => $T['document_not_found']], 404);
}

// A space is optional: a document without a workspace is owner-only.
if ($espaceId > 0 && $dependencies['espaces']->find($espaceId) === null) {
    $respond(['error' => $isFr ? 'Espace inconnu.' : 'Unknown workspace.'], 404);
}

$avant = [
    'espace_id' => $document['espace_id'] === null ? null : (int) $document['espace_id'],
    'confidentialite' => (string) ($document['confidentialite'] ?? 'restreint'),
];

$dependencies['document_model']->updateControle($documentId, $espaceId > 0 ? $espaceId : null, $confidentialite);

$dependencies['journal']->log($userId, 'document.controle', 'document', $documentId, 'succes', [
    'avant' => $avant,
    'apres' => ['espace_id' => $espaceId > 0 ? $espaceId : null, 'confidentialite' => $confidentialite],
]);

$respond(['message' => $isFr
    ? 'Espace et confidentialité mis à jour.'
    : 'Workspace and confidentiality updated.']);
