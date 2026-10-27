<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/categorie_delete.php
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.supprimer')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Permission denied.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['csrf']->validate($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Session expirée. Rechargez la page.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$categorieId = (int) ($_POST['categorie_id'] ?? 0);
$categorie = $categorieId > 0 ? $dependencies['categorie_model']->find($categorieId) : null;

if ($categorie === null) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Catégorie introuvable.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$dependencies['categorie_model']->delete($categorieId);
$dependencies['journal']->log($dependencies['auth']->id(), 'categorie.suppression', 'categorie', $categorieId, 'succes', ['nom' => (string) $categorie['nom']]);

echo json_encode(['deleted' => $categorieId], JSON_UNESCAPED_UNICODE);
