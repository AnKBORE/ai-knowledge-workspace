<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/categorie_delete.php
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
        $flash = isset($data['error'])
            ? ['type' => 'error', 'text' => (string) $data['error']]
            : ['type' => 'success', 'text' => 'OK'];
        $dependencies['session']->set('profil_flash', $flash);
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

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.supprimer')) {
    $respond(['error' => $T['permission_denied']], 403);
}

if (!$dependencies['csrf']->validate($_POST['_csrf'] ?? null)) {
    $respond(['error' => $T['session_expired']], 419);
}

$categorieId = (int) ($_POST['categorie_id'] ?? 0);
$categorie = $categorieId > 0 ? $dependencies['categorie_model']->find($categorieId) : null;

if ($categorie === null) {
    $respond(['error' => $T['document_not_found']], 404);
}

$dependencies['categorie_model']->delete($categorieId);
$dependencies['journal']->log($dependencies['auth']->id(), 'categorie.suppression', 'categorie', $categorieId, 'succes', ['nom' => (string) $categorie['nom']]);

$respond(['deleted' => $categorieId]);
