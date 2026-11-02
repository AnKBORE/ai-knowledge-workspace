<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_espace_statut.php
 *
 * Opens or closes a collaborative space (`actif` / `archive`). A closed space
 * no longer accepts deposits; its documents stay readable, because closing a
 * piece of work does not mean erasing it.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];
$isFr = $lang === 'fr';

header('X-Content-Type-Options: nosniff');

$respond = static function (array $data) use ($dependencies, $isFr): never {
    $isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if (!$isXhr) {
        $dependencies['session']->set('admin_flash', [
            'type' => isset($data['error']) ? 'error' : 'success',
            'text' => (string) ($data['error'] ?? $data['message'] ?? ''),
        ]);
        header('Location: ' . rtrim(str_replace('\\', '/', dirname(dirname((string) $_SERVER['SCRIPT_NAME']))), '/') . '/administration');
        exit;
    }

    http_response_code(isset($data['error']) ? 422 : 200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
};

if (!$dependencies['auth']->check()) {
    $respond(['error' => $T['auth_required']]);
}

$userId = (int) $dependencies['auth']->id();

if (!$dependencies['permissions']->peutAilleurs($userId, 'espace.archiver')) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_espace_statut']);
    $respond(['error' => $T['permission_denied']]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_espace_statut']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.']);
}

// Sensitive action: the actor's own password is required.
if (!verifier_mot_de_passe_admin($dependencies, 'espace.archiver')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 403);
}

$espaceId = (int) ($_POST['espace_id'] ?? 0);
$statut = (string) ($_POST['statut'] ?? '');

if ($espaceId < 1 || !in_array($statut, ['actif', 'archive'], true)) {
    $respond(['error' => $isFr ? 'Espace ou statut invalide.' : 'Invalid space or status.']);
}

$espace = $dependencies['espaces']->find($espaceId);

if ($espace === null) {
    $respond(['error' => $isFr ? 'Espace inconnu.' : 'Unknown space.']);
}

// A closed space still holds live documents: reopening must stay possible, so
// only the two states are allowed and both are reversible.
$stmt = $dependencies['pdo']->prepare('UPDATE espace SET statut = :statut WHERE id = :id');
$stmt->execute(['statut' => $statut, 'id' => $espaceId]);

$dependencies['journal']->log($userId, 'espace.statut', 'espace', $espaceId, 'succes', [
    'statut' => $statut,
    'nom' => (string) ($espace['nom'] ?? ''),
]);

$respond([
    'id' => $espaceId,
    'statut' => $statut,
    'message' => $statut === 'archive'
        ? ($isFr ? 'Espace clos : plus aucun dépôt accepté.' : 'Space closed: no further deposits.')
        : ($isFr ? 'Espace réouvert.' : 'Space reopened.'),
]);
