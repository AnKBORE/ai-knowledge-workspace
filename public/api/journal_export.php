<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/journal_export.php
 *
 * Exports the audit journal as CSV (append-only trail, no API cost).
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['auth_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'journal.lire')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['permission_denied']], JSON_UNESCAPED_UNICODE);
    exit;
}

$entries = $dependencies['journal']->recent(1000);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="journal-' . date('Ymd-His') . '.csv"');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Acteur', 'Action', 'Objet', 'Resultat', 'IP', 'Details'], ',', '"', '');

foreach ($entries as $entry) {
    fputcsv($output, [
        (string) ($entry['horodatage'] ?? ''),
        trim((string) ($entry['prenom'] ?? '') . ' ' . (string) ($entry['nom'] ?? '')),
        (string) ($entry['action'] ?? ''),
        trim((string) ($entry['objet_type'] ?? '') . ($entry['objet_id'] !== null ? ' #' . (int) $entry['objet_id'] : '')),
        (string) ($entry['resultat'] ?? ''),
        (string) ($entry['adresse_ip'] ?? ''),
        (string) ($entry['details'] ?? ''),
    ], ',', '"', '');
}

fclose($output);
