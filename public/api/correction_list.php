<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/correction_list.php?document_id=N
 *
 * Lists the validated corrections of one document so they can be reviewed and
 * removed. No approval workflow: whoever uploads a correction (the author or a
 * native-speaker reviewer) is trusted by definition.
 *
 * Response: {"corrections":[{"id":3,"langue":"fr","extrait":"…","chars":420,
 *            "auteur":"…","cree_le":"2026-10-31 15:20","peut_supprimer":true}]}
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => $T['auth_required'] ?? 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) $dependencies['auth']->id();

if (!$dependencies['permissions']->peutAilleurs($userId, 'document.consulter')) {
    http_response_code(403);
    echo json_encode(['error' => $T['permission_denied'] ?? 'Permission denied.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$documentId = (int) ($_GET['document_id'] ?? 0);

if ($documentId < 1) {
    http_response_code(422);
    echo json_encode(['error' => 'document_id is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$canDeleteAny = $dependencies['permissions']->peutAilleurs($userId, 'document.supprimer');

$stmt = $dependencies['pdo']->prepare(
    "SELECT c.id, c.langue, c.texte_corrige, c.auteur_id, c.cree_le,
            TRIM(CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, ''))) AS auteur
     FROM correction_document c
     LEFT JOIN utilisateur u ON u.id = c.auteur_id
     WHERE c.document_id = :id AND c.statut = 'valide'
     ORDER BY c.id DESC"
);
$stmt->execute(['id' => $documentId]);

$out = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $text = trim((string) $row['texte_corrige']);
    $out[] = [
        'id' => (int) $row['id'],
        'langue' => (string) $row['langue'],
        'extrait' => mb_substr($text, 0, 180) . (mb_strlen($text) > 180 ? '…' : ''),
        'chars' => mb_strlen($text),
        'auteur' => (string) ($row['auteur'] ?? '—'),
        'cree_le' => (string) $row['cree_le'],
        'peut_supprimer' => $canDeleteAny || (int) $row['auteur_id'] === $userId,
    ];
}

$dependencies['journal']->log($userId, 'document.correction.consultation', 'document', $documentId, 'succes', [
    'corrections' => count($out),
]);

echo json_encode([
    'corrections' => $out,
    'total' => count($out),
    'appris' => $dependencies['apprentissage']->count(),
], JSON_UNESCAPED_UNICODE);
