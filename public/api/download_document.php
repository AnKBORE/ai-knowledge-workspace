<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/download_document.php?document_id=N[&version=V]
 *
 * Streams the stored file and records a detailed `document.consultation`
 * entry (title, version, depositor, file name, size, fingerprint).
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['auth_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.consulter')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['permission_denied']], JSON_UNESCAPED_UNICODE);
    exit;
}

$documentId = (int) ($_GET['document_id'] ?? 0);
$versionNumero = isset($_GET['version']) && $_GET['version'] !== '' ? (int) $_GET['version'] : null;

$document = $documentId > 0 ? $dependencies['document_model']->find($documentId) : null;
if ($document === null) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['document_not_found']], JSON_UNESCAPED_UNICODE);
    exit;
}

$version = $versionNumero !== null
    ? $dependencies['version_model']->findByNumero($documentId, $versionNumero)
    : $dependencies['version_model']->latest($documentId);

if ($version === null) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['document_not_found']], JSON_UNESCAPED_UNICODE);
    exit;
}

$path = $dependencies['storage_path'] . '/' . (string) $version['fichier'];
if (!is_file($path)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['download_failed']], JSON_UNESCAPED_UNICODE);
    exit;
}

$owner = $document['proprietaire_id'] !== null
    ? $dependencies['users']->findById((int) $document['proprietaire_id'])
    : null;
$ownerName = $owner !== null
    ? trim((string) ($owner['prenom'] ?? '') . ' ' . (string) ($owner['nom'] ?? ''))
    : 'Inconnu';

$dependencies['journal']->log(
    $dependencies['auth']->id(),
    'document.consultation',
    'document',
    $documentId,
    'succes',
    [
        'titre' => (string) $document['title'],
        'version' => (int) $version['numero'],
        'deposant' => $ownerName,
        'fichier' => (string) $version['nom_original'],
        'taille_octets' => (int) $version['taille_octets'],
        'empreinte' => substr((string) $version['empreinte'], 0, 16),
    ],
);

$filename = (string) ($version['nom_original'] ?? ('document-' . $documentId));
$mime = (string) ($version['type_mime'] ?? '');

header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . (string) filesize($path));

readfile($path);
