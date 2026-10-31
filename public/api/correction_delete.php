<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/correction_delete.php
 *
 * Removes a correction so it stops feeding the learning layer. Allowed for its
 * author, or for anyone holding document.supprimer.
 *
 * Request:  {"id": 3}
 * Response: {"deleted": 3, "appris": 2}
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$fail = static function (int $code, string $message): never {
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
};

if (!$dependencies['auth']->check()) {
    $fail(401, $T['auth_required'] ?? 'Authentication required.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $fail(405, $T['method_post'] ?? 'POST required.');
}

if (!$dependencies['csrf']->validateRequest()) {
    $fail(419, $T['csrf'] ?? 'Session expirée. Rechargez la page.');
}

$userId = (int) $dependencies['auth']->id();

$payload = json_decode((string) file_get_contents('php://input'), true);
$payload = is_array($payload) ? $payload : [];
$id = (int) ($payload['id'] ?? $_POST['id'] ?? 0);

if ($id < 1) {
    $fail(422, 'id is required.');
}

$stmt = $dependencies['pdo']->prepare(
    'SELECT id, document_id, auteur_id, langue FROM correction_document WHERE id = :id'
);
$stmt->execute(['id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    $fail(404, 'Correction not found.');
}

$isAuthor = (int) ($row['auteur_id'] ?? 0) === $userId;
$canDeleteAny = $dependencies['permissions']->peutAilleurs($userId, 'document.supprimer');

if (!$isAuthor && !$canDeleteAny) {
    $fail(403, $T['permission_denied'] ?? 'Permission denied.');
}

try {
    $dependencies['pdo']->prepare('DELETE FROM correction_document WHERE id = :id')->execute(['id' => $id]);

    $dependencies['journal']->log($userId, 'document.correction.suppression', 'document', (int) $row['document_id'], 'succes', [
        'correction_id' => $id,
        'langue' => (string) $row['langue'],
    ]);

    echo json_encode([
        'deleted' => $id,
        'appris' => $dependencies['apprentissage']->count(),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    $fail(500, getenv('APP_DEBUG') === '1' ? $e->getMessage() : ($T['internal_error'] ?? 'Internal error.'));
}
