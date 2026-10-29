<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/upload_status.php?job_id=N
 *
 * Progress of a background ingestion job. Only the user who queued the job can
 * read it (no job enumeration across accounts).
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$T = app_i18n((string) $dependencies['session']->get('lang', 'en'))['api'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => $T['auth_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

$job = $dependencies['ingestion_jobs']->find((int) ($_GET['job_id'] ?? 0));

if ($job === null || (int) $job['utilisateur_id'] !== (int) $dependencies['auth']->id()) {
    http_response_code(404);
    echo json_encode(['error' => $T['job_not_found']], JSON_UNESCAPED_UNICODE);
    exit;
}

// A worker killed mid-way (crash, reboot) would leave the job pending forever.
if (in_array($job['statut'], ['en_attente', 'en_cours'], true) && (int) $job['elapsed'] > 1800) {
    $dependencies['ingestion_jobs']->fail((int) $job['id'], $T['job_stalled']);
    $job = $dependencies['ingestion_jobs']->find((int) $job['id']);
}

echo json_encode([
    'job_id' => (int) $job['id'],
    'status' => (string) $job['statut'],
    'title' => (string) $job['titre'],
    'chunks' => $job['chunks'] !== null ? (int) $job['chunks'] : null,
    'error' => $job['statut'] === 'echec' ? (string) $job['erreur'] : null,
    'elapsed' => max(0, (int) $job['elapsed']),
], JSON_UNESCAPED_UNICODE);
