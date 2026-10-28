<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/delete_document.php
 *
 * Request:  {"document_id": 3}
 * Response: {"deleted": 3}
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => $T['auth_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.supprimer')) {
    http_response_code(403);
    echo json_encode(['error' => $T['permission_denied']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => $T['method_post']], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];
$documentId = (int) ($input['document_id'] ?? 0);
$password = (string) ($input['password'] ?? '');

if ($documentId < 1) {
    http_response_code(422);
    echo json_encode(['error' => $T['document_id_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) $dependencies['auth']->id();
$hash = $dependencies['users']->motDePasseHash($userId);
if ($hash === null || !\App\Validators\PolitiqueMotDePasse::verify($password, $hash)) {
    $dependencies['journal']->log($userId, 'document.suppression', 'document', $documentId, 'refus', ['raison' => 'mot_de_passe_incorrect']);
    http_response_code(403);
    echo json_encode(['error' => $T['wrong_password']], JSON_UNESCAPED_UNICODE);
    exit;
}

$document = $dependencies['document_model']->find($documentId);
$version = $dependencies['version_model']->latest($documentId);
$owner = $document !== null && ($document['proprietaire_id'] ?? null) !== null
    ? $dependencies['users']->findById((int) $document['proprietaire_id'])
    : null;
$deposant = $owner !== null
    ? trim((string) ($owner['prenom'] ?? '') . ' ' . (string) ($owner['nom'] ?? ''))
    : 'Inconnu';

$dependencies['document_model']->softDelete($documentId);

$dependencies['journal']->log(
    $dependencies['auth']->id(),
    'document.suppression',
    'document',
    $documentId,
    'succes',
    [
        'titre' => (string) ($document['title'] ?? ''),
        'version' => (int) ($version['numero'] ?? 0),
        'deposant' => $deposant,
    ],
);

echo json_encode(['deleted' => $documentId], JSON_UNESCAPED_UNICODE);
