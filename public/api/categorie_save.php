<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/categorie_save.php
 *
 * Creates or updates a document category (CRUD side of the category module).
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('X-Content-Type-Options: nosniff');

/**
 * Emit a JSON response — or, for a native (non-AJAX) form submission,
 * redirect back to the categories page so the browser never shows raw JSON.
 * url() cannot be used here: it resolves its base from THIS script (/api/…),
 * so the app root is derived from the script location instead.
 */
$respond = static function (array $data, int $code = 200) use ($dependencies): never {
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
        // Native submission: carry the message via session flash, then reload.
        $flash = isset($data['error'])
            ? ['type' => 'error', 'text' => (string) $data['error']]
            : ['type' => 'success', 'text' => (string) ($data['nom'] ?? $data['deleted'] ?? '') . ' — OK'];
        $dependencies['session']->set('flash', $flash);
        $appBase = rtrim(str_replace('\\', '/', dirname(dirname(str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME'])))), '/');
        header('Location: ' . $appBase . '/categories');
        exit;
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

if (!$dependencies['auth']->check()) {
    $respond(['error' => $T['auth_required']], 401);
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.deposer')) {
    $respond(['error' => $T['permission_denied']], 403);
}

if (!$dependencies['csrf']->validate($_POST['_csrf'] ?? null)) {
    $respond(['error' => $T['session_expired']], 419);
}

$nom = trim((string) ($_POST['nom'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$couleur = trim((string) ($_POST['couleur'] ?? '#4a63c8'));
$categorieId = (int) ($_POST['categorie_id'] ?? 0);

if ($nom === '') {
    $respond(['error' => $T['category_name']], 422);
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

    $respond(['id' => $id, 'nom' => $nom]);
} catch (\PDOException $e) {
    // Duplicate slug (unique key uq_categorie_slug) → friendly 422.
    $isDuplicate = $e->getCode() === '23000' || str_contains($e->getMessage(), 'uq_categorie_slug');
    $respond(['error' => $isDuplicate
        ? $T['category_exists']
        : (getenv('APP_DEBUG') === '1' ? $e->getMessage() : $T['internal_error'])], $isDuplicate ? 422 : 500);
} catch (\Throwable $e) {
    $respond(['error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : $T['internal_error']], 500);
}
