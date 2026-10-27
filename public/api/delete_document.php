<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/delete_document.php
 *
 * Request:  {"document_id": 3}
 * Response: {"deleted": 3}
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.supprimer')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];
$documentId = (int) ($input['document_id'] ?? 0);

if ($documentId < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'A document_id is required.'], JSON_UNESCAPED_UNICODE);
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
