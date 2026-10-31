<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/correction_save.php
 *
 * Stores a human-corrected version of a document and feeds the learning layer.
 *
 * Accepted input (multipart or urlencoded):
 *   document_id     int    required
 *   langue          fr|en|mo   required
 *   texte_corrige   string    required when no file is sent
 *   fichier         file      optional: audio (transcribed) or plain text
 *
 * Response: {"id": 12, "langue": "mo", "chars": 480, "appris": 3}
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$respond = static function (int $code, array $body): never {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
};

if (!$dependencies['auth']->check()) {
    $respond(401, ['error' => $T['auth_required'] ?? 'Authentication required.']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(405, ['error' => $T['method_post'] ?? 'POST required.']);
}

if (!$dependencies['csrf']->validateRequest()) {
    $respond(419, ['error' => $T['csrf'] ?? 'Session expirée. Rechargez la page.']);
}

$userId = (int) $dependencies['auth']->id();

if (!$dependencies['permissions']->peutAilleurs($userId, 'document.deposer')) {
    $respond(403, ['error' => $T['permission_denied'] ?? 'Permission denied.']);
}

if (!$dependencies['rate_limiter']->allow('correction:' . $userId, 30, 300)) {
    http_response_code(429);
    header('Retry-After: 60');
    $respond(429, ['error' => $T['rate_limited'] ?? 'Trop de requêtes.']);
}

$documentId = (int) ($_POST['document_id'] ?? 0);
$langue = (string) ($_POST['langue'] ?? '');
$texteCorrige = trim((string) ($_POST['texte_corrige'] ?? ''));

if ($documentId < 1) {
    $respond(422, ['error' => 'document_id is required.']);
}

if (!in_array($langue, ['fr', 'en', 'mo'], true)) {
    $respond(422, ['error' => 'langue must be fr, en or mo.']);
}

// The document must exist and not be deleted.
$stmt = $dependencies['pdo']->prepare(
    'SELECT id, title, langue FROM documents WHERE id = :id AND supprime_le IS NULL'
);
$stmt->execute(['id' => $documentId]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if ($document === false) {
    $respond(404, ['error' => 'Document not found.']);
}

$storedFile = null;
$filePath = null;

// A corrected audio file is transcribed; a text file is read verbatim.
if (isset($_FILES['fichier']) && is_uploaded_file($_FILES['fichier']['tmp_name'])) {
    if ((int) $_FILES['fichier']['size'] > 24 * 1024 * 1024) {
        $respond(413, ['error' => 'File too large (24 MB max).']);
    }

    $original = (string) ($_FILES['fichier']['name'] ?? 'correction.txt');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    // A correction is a text file: the reviewer rewrites the transcript.
    if (!in_array($ext, ['txt', 'md', 'csv'], true)) {
        @unlink($_FILES['fichier']['tmp_name']);
        $respond(422, ['error' => 'A correction must be a text file (.txt, .md, .csv).']);
    }

    $tmp = tempnam(sys_get_temp_dir(), 'akw_corr_');
    move_uploaded_file($_FILES['fichier']['tmp_name'], $tmp);

    try {
        if ($texteCorrige === '') {
            $texteCorrige = trim((string) file_get_contents($tmp));
        }
    } finally {
        @unlink($tmp);
    }
}

if ($texteCorrige === '') {
    $respond(422, ['error' => 'A corrected text or a corrected file is required.']);
}

// Original transcript of the document: the pair (source -> corrected) is what
// the learning layer reuses.
$stmt = $dependencies['pdo']->prepare(
    'SELECT content FROM document_chunks WHERE document_id = :id ORDER BY chunk_index'
);
$stmt->execute(['id' => $documentId]);
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
$texteSource = trim(implode("\n\n", array_map(static fn ($r): string => trim((string) $r), $rows)));

$version = $dependencies['correction_model']->countForDocument($documentId) + 1;
$base = (string) pathinfo((string) ($document['title'] ?? 'document'), PATHINFO_FILENAME);
$slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $base), '_'));
$slug = $slug !== '' ? $slug : 'document';
$nomFichier = $slug . '_corrige_v' . $version . '.txt';

try {
    $correctionId = $dependencies['correction_model']->create(
        $documentId,
        $langue,
        $texteCorrige,
        $texteSource !== '' ? $texteSource : null,
        null,
        $userId,
        $nomFichier
    );

    $dependencies['journal']->log($userId, 'document.correction', 'document', $documentId, 'succes', [
        'langue' => $langue,
        'caracteres' => mb_strlen($texteCorrige),
    ]);

    $respond(201, [
        'id' => $correctionId,
        'langue' => $langue,
        'chars' => mb_strlen($texteCorrige),
        'nom_fichier' => $nomFichier,
        'version' => $version,
        'appris' => $dependencies['apprentissage']->count(),
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    $respond(500, [
        'error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : ($T['internal_error'] ?? 'Internal error.'),
    ]);
}
