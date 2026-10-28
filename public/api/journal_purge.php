<?php

declare(strict_types=1);

use App\Validators\PolitiqueMotDePasse;

/**
 * Protected endpoint — POST /api/journal_purge.php
 *
 * Purges audit entries within a period. Restricted to technical
 * administrators (journal.lire) and requires the account password.
 *
 * Request:  {"date_debut": "2026-01-01", "date_fin": "2026-01-31", "password": "..."}
 * Response: {"deleted": 42}
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

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'journal.lire')) {
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
$dateDebut = trim((string) ($input['date_debut'] ?? ''));
$dateFin = trim((string) ($input['date_fin'] ?? ''));
$password = (string) ($input['password'] ?? '');
$userId = (int) $dependencies['auth']->id();

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut) !== 1) {
    http_response_code(422);
    echo json_encode(['error' => $T['date_debut_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($dateFin !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin) !== 1) {
    http_response_code(422);
    echo json_encode(['error' => $T['date_fin_format']], JSON_UNESCAPED_UNICODE);
    exit;
}

$maxEnd = date('Y-m-d', strtotime('-10 days'));

if ($dateFin === '') {
    $dateFin = $maxEnd;
} elseif ($dateFin > $maxEnd) {
    http_response_code(422);
    echo json_encode(['error' => $T['purge_limit'] . ' (' . $maxEnd . ').'], JSON_UNESCAPED_UNICODE);
    exit;
}

$from = $dateDebut . ' 00:00:00';
$to = $dateFin . ' 23:59:59';

if ($from > $to) {
    http_response_code(422);
    echo json_encode(['error' => $T['date_order']], JSON_UNESCAPED_UNICODE);
    exit;
}

$hash = $dependencies['users']->motDePasseHash($userId);
if ($hash === null || !PolitiqueMotDePasse::verify($password, $hash)) {
    $dependencies['journal']->log($userId, 'journal.purge', 'journal', null, 'refus', ['raison' => 'mot_de_passe_incorrect']);
    http_response_code(403);
    echo json_encode(['error' => $T['wrong_password']], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $deleted = $dependencies['journal']->purge($from, $to);
} catch (Throwable $e) {
    http_response_code(500);
    $debug = getenv('APP_DEBUG') === '1';
    echo json_encode(['error' => $debug ? $e->getMessage() : 'Purge failed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$dependencies['journal']->log($userId, 'journal.purge', 'journal', null, 'succes', [
    'periode' => $dateDebut . ' → ' . ($dateFin !== '' ? $dateFin : 'aujourd\'hui'),
    'entrees_supprimees' => $deleted,
]);

echo json_encode(['deleted' => $deleted], JSON_UNESCAPED_UNICODE);
