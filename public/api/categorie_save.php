<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/categorie_save.php
 *
 * Creates or updates a document category (CRUD side of the category module).
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.creer')) {
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

$nom = trim((string) ($_POST['nom'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$couleur = trim((string) ($_POST['couleur'] ?? '#4a63c8'));
$categorieId = (int) ($_POST['categorie_id'] ?? 0);

if ($nom === '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Le nom de la catégorie est requis.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $couleur)) {
    $couleur = '#4a63c8';
}

try {
    if ($categorieId > 0 && $dependencies['categorie_model']->find($categorieId) !== null) {
        $dependencies['categorie_model']->update($categorieId, $nom, $description !== '' ? $description : null, $couleur);
        $dependencies['journal']->log($dependencies['auth']->id(), 'categorie.modification', 'categorie', $categorieId, 'succes', ['nom' => $nom]);
        $id = $categorieId;
    } else {
        $id = $dependencies['categorie_model']->create($nom, $description !== '' ? $description : null, $couleur);
        $dependencies['journal']->log($dependencies['auth']->id(), 'categorie.creation', 'categorie', $id, 'succes', ['nom' => $nom]);
    }

    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['id' => $id, 'nom' => $nom], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : 'Enregistrement impossible.'], JSON_UNESCAPED_UNICODE);
}
