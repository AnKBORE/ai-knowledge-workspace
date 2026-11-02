<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/correction_download.php?id=N
 *
 * Streams a validated correction as a UTF-8 text file, so a cited "corrected
 * version" can actually be downloaded from the sources list.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['auth_required'] ?? 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) $dependencies['auth']->id();

if (!$dependencies['permissions']->peutAilleurs($userId, 'document.consulter')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['permission_denied'] ?? 'Permission denied.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id < 1) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'id is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $dependencies['pdo']->prepare(
    'SELECT c.id, c.document_id, c.langue, c.texte_corrige, c.nom_fichier, c.fichier, c.cree_le, d.title
     FROM correction_document c
     JOIN documents d ON d.id = c.document_id
     WHERE c.id = :id AND c.statut = \'valide\''
);
$stmt->execute(['id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Correction not found.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// The correction belongs to a document: that document must be readable.
if (!$dependencies['permissions']->peutConsulterDocument($userId, (int) $row['document_id'])) {
    $dependencies['journal']->log($userId, 'securite.refus', 'document', (int) $row['document_id'], 'refus', ['raison' => 'correction_hors_perimetre']);
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Forbidden.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim((string) ($row['nom_fichier'] ?? ''));

if ($name === '') {
    $base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', (string) ($row['title'] ?? 'document')), '_'));
    $name = ($base !== '' ? $base : 'document') . '_corrige_' . $id . '.txt';
}

// Keep only the file name, never a path.
$name = (string) preg_replace('/[^A-Za-z0-9._\-\x{00C0}-\x{024F}]+/u', '_', basename($name));

$mime = match (strtolower((string) pathinfo($name, PATHINFO_EXTENSION))) {
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'pdf' => 'application/pdf',
    default => 'text/plain; charset=utf-8',
};

$body = (string) $row['texte_corrige'];

// The reviewer's own file wins: it is served byte for byte, keeping its
// real format (Word, PDF…). Generation is only a fallback for text-only
// corrections.
$stored = trim((string) ($row['fichier'] ?? ''));
$storedPath = $stored !== ''
    ? rtrim((string) $dependencies['storage_path'], '/') . '/' . ltrim($stored, '/')
    : '';

if ($storedPath !== '' && is_file($storedPath)) {
    $size = (int) filesize($storedPath);

    $dependencies['journal']->log($userId, 'document.correction.telechargement', 'document', null, 'succes', [
        'correction_id' => $id,
        'langue' => (string) $row['langue'],
        'source' => 'fichier',
    ]);

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . (string) $size);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($storedPath);
    exit;
}

$dependencies['journal']->log($userId, 'document.correction.telechargement', 'document', null, 'succes', [
    'correction_id' => $id,
    'langue' => (string) $row['langue'],
]);

// Honour the stored extension: a correction saved as .docx must download as
// a real Word document, not as plain text.
$ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
$title = (string) ($row['title'] ?? 'Correction');

try {
    if ($ext === 'docx') {
        $payload = (new \App\Services\ExportService())->toDocx($title, $body);
        $type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    } elseif ($ext === 'pdf') {
        $payload = (new \App\Services\ExportService())->toPdf($title, $body);
        $type = 'application/pdf';
    } else {
        $payload = $body;
        $type = 'text/plain; charset=utf-8';
    }
} catch (\Throwable $e) {
    // Never fail the download: fall back to plain text with a .txt name.
    $payload = $body;
    $type = 'text/plain; charset=utf-8';
    $name = (string) pathinfo($name, PATHINFO_FILENAME) . '.txt';
}

header('Content-Type: ' . $type);
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Length: ' . (string) strlen($payload));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

echo $payload;
