<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/upload.php
 *
 * Multipart form with `title` and `file`. Extracts text from the file
 * (text / PDF / scanned image / audio), creates an immutable version
 * (SHA-256, stored outside the web root) and indexes the content.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

// Local whisper.cpp transcription can take several minutes on CPU —
// lift the default 30 s PHP limit for this endpoint.
set_time_limit(0);
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => $T['auth_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.deposer')) {
    http_response_code(403);
    echo json_encode(['error' => $T['permission_denied']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => $T['method_post']], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!$dependencies['csrf']->validateRequest()) {
    http_response_code(419);
    echo json_encode(['error' => $T['csrf'] ?? 'Session expirée. Rechargez la page.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$title = trim((string) ($_POST['title'] ?? ''));
if ($title === '') {
    http_response_code(422);
    echo json_encode(['error' => $T['title_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

$categorieId = isset($_POST['categorie_id']) && $_POST['categorie_id'] !== ''
    ? (int) $_POST['categorie_id']
    : 0;

if ($categorieId < 1 || $dependencies['categorie_model']->find($categorieId) === null) {
    http_response_code(422);
    echo json_encode(['error' => $T['category_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

$upload = $_FILES['file'] ?? null;
if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = is_array($upload) ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
    $messages = [
        UPLOAD_ERR_INI_SIZE => $T['file_ini_size'] . ' (upload_max_filesize = ' . ini_get('upload_max_filesize') . ', post_max_size = ' . ini_get('post_max_size') . ').',
        UPLOAD_ERR_FORM_SIZE => $T['file_form_size'],
        UPLOAD_ERR_PARTIAL => $T['file_partial'],
        UPLOAD_ERR_NO_FILE => $T['file_none'],
        UPLOAD_ERR_NO_TMP_DIR => $T['file_no_tmp'],
        UPLOAD_ERR_CANT_WRITE => $T['file_cant_write'],
        UPLOAD_ERR_EXTENSION => $T['file_extension'],
    ];
    http_response_code(422);
    echo json_encode(['error' => $messages[$code] ?? ($T['internal_error'] . ' (' . $code . ').')], JSON_UNESCAPED_UNICODE);
    exit;
}

$ingestion = $dependencies['ingestion'];

try {
    $tmp = (string) $upload['tmp_name'];
    $originalName = (string) ($upload['name'] ?? 'document');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Slow files (audio, image, scanned PDF) need minutes of ASR/OCR: queue
    // them for a detached worker and answer immediately (202 + job id).
    if ($ingestion->isSlow($tmp, $extension)) {
        $queueDir = $dependencies['root'] . '/storage/cache/ingestion';
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0775, true);
        }
        $queued = $queueDir . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($tmp, $queued)) {
            throw new \RuntimeException('Could not queue the upload.');
        }

        $jobId = $dependencies['ingestion_jobs']->create([
            'utilisateur_id' => (int) $dependencies['auth']->id(),
            'titre' => $title,
            'categorie_id' => $categorieId,
            'nom_original' => $originalName,
            'type_mime' => (string) ($upload['type'] ?? ''),
            'taille_octets' => (int) ($upload['size'] ?? 0),
            'chemin_tmp' => $queued,
            'langue' => $lang,
        ]);
        \App\Services\BackgroundWorker::spawn($dependencies['root'] . '/tools/ingest_worker.php', [(string) $jobId]);

        http_response_code(202);
        echo json_encode(['job_id' => $jobId, 'status' => 'en_attente', 'title' => $title], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Fast files (text, PDF with a text layer): processed synchronously.
    $content = $ingestion->extract($tmp, $extension);
    $result = $ingestion->finalize($title, $categorieId, (int) $dependencies['auth']->id(), $upload, $content);

    echo json_encode([
        'title' => $title,
        'chunks' => $result['chunks'],
        'version' => 1,
        'sha256' => substr($result['sha256'], 0, 12),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(str_contains($e->getMessage(), 'File empty or unreadable') ? 422 : 500);
    echo json_encode(['error' => $ingestion->errorMessage($e, $T)], JSON_UNESCAPED_UNICODE);
}
