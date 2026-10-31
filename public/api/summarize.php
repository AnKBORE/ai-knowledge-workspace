<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/summarize.php
 *
 * Request:  {"document_id": 3}
 * Response: {"summary": "...", "document_id": 3, "sources": [...]}
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

$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];
$documentId = (int) ($input['document_id'] ?? 0);

if ($documentId < 1) {
    http_response_code(422);
    echo json_encode(['error' => $T['document_id_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $document = $dependencies['document_model']->find($documentId);
    $version = $dependencies['version_model']->latest($documentId);
    $owner = $document !== null && ($document['proprietaire_id'] ?? null) !== null
        ? $dependencies['users']->findById((int) $document['proprietaire_id'])
        : null;
    $deposant = $owner !== null
        ? trim((string) ($owner['prenom'] ?? '') . ' ' . (string) ($owner['nom'] ?? ''))
        : 'Inconnu';

    $result = $dependencies['rag']->summarize($documentId, 20, $dependencies['auth']->id());

    $dependencies['journal']->log(
        $dependencies['auth']->id(),
        'document.resume',
        'document',
        $documentId,
        'succes',
        [
            'titre' => (string) ($document['title'] ?? ''),
            'version' => (int) ($version['numero'] ?? 0),
            'deposant' => $deposant,
        ],
    );

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    $debug = getenv('APP_DEBUG') === '1';
    echo json_encode(['error' => $debug ? $e->getMessage() : $T['summarize_failed']], JSON_UNESCAPED_UNICODE);
}
