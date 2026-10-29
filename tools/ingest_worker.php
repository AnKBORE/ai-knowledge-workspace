<?php

declare(strict_types=1);

/**
 * Background ingestion worker — processes one queued upload (audio, image,
 * scanned PDF): extraction (ASR / OCR), storage, indexing, audit.
 *
 * Usage (spawned automatically by public/api/upload.php):
 *   php tools/ingest_worker.php <job_id>
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$dependencies = require dirname(__DIR__) . '/bootstrap.php';
set_time_limit(0);

$jobId = (int) ($argv[1] ?? 0);
$jobs = $dependencies['ingestion_jobs'];
$job = $jobId > 0 ? $jobs->find($jobId) : null;

// claim() is atomic: a job already taken (or finished) is left untouched.
if ($job === null || !$jobs->claim($jobId)) {
    fwrite(STDERR, "Job {$jobId} not found or already processed.\n");
    exit(1);
}

$T = app_i18n((string) $job['langue'])['api'];
$path = (string) $job['chemin_tmp'];
$extension = strtolower(pathinfo((string) $job['nom_original'], PATHINFO_EXTENSION));

try {
    $content = $dependencies['ingestion']->extract($path, $extension);

    $result = $dependencies['ingestion']->finalize(
        (string) $job['titre'],
        (int) $job['categorie_id'],
        (int) $job['utilisateur_id'],
        [
            'tmp_name' => $path,
            'name' => (string) $job['nom_original'],
            'type' => (string) $job['type_mime'],
            'size' => (int) $job['taille_octets'],
            'error' => UPLOAD_ERR_OK,
        ],
        $content,
    );

    $jobs->complete($jobId, $result['document_id'], $result['chunks']);
    echo "Job {$jobId}: document #{$result['document_id']} indexed ({$result['chunks']} chunks).\n";
} catch (\Throwable $e) {
    error_log('[ingest_worker] job ' . $jobId . ' failed: ' . $e->getMessage());
    $jobs->fail($jobId, $dependencies['ingestion']->errorMessage($e, $T));
    $dependencies['journal']->log((int) $job['utilisateur_id'], 'document.depot', 'document', null, 'echec', [
        'titre' => (string) $job['titre'],
        'erreur' => mb_substr($e->getMessage(), 0, 200),
    ]);
    exit(1);
} finally {
    @unlink($path);
}
