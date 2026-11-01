<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/documents.php
 *
 * Returns the recent documents as JSON so the workspace can refresh the list
 * after an upload without a full page reload.
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

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.consulter')) {
    http_response_code(403);
    $dependencies['journal']->log((int) $dependencies['auth']->id(), 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => mb_substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 180)]);
    echo json_encode(['error' => $T['permission_denied']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => $T['method_get']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Same scope as the page and the retrieval engine.
$visible = $dependencies['permissions']->documentsVisibles((int) $dependencies['auth']->id());
$documents = $dependencies['document_model']->listRecent(20, 0, $visible);

echo json_encode(['documents' => $documents], JSON_UNESCAPED_UNICODE);
