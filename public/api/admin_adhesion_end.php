<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/admin_adhesion_end.php
 *
 * Ends a membership now (`fin_le = today`). Time-bounded access already exists
 * in the schema and is enforced by PermissionService, but nothing exposed it:
 * a mission could be given to someone without any way to take it back.
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

if (!$dependencies['permissions']->peutAilleurs($userId, 'adhesion.gerer')) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/admin_adhesion_end']);
    $respond(['error' => $T['permission_denied']]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/admin_adhesion_end']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.']);
}

// Sensitive action: the actor's own password is required.
if (!verifier_mot_de_passe_admin($dependencies, 'adhesion.fin')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 403);
}

$adhesionId = (int) ($_POST['adhesion_id'] ?? 0);

if ($adhesionId < 1) {
    $respond(['error' => $isFr ? 'Adhésion invalide.' : 'Invalid membership.']);
}

$stmt = $dependencies['pdo']->prepare('SELECT id, utilisateur_id, espace_id FROM adhesion WHERE id = :id');
$stmt->execute(['id' => $adhesionId]);
$adhesion = $stmt->fetch(PDO::FETCH_ASSOC);

if ($adhesion === false) {
    $respond(['error' => $isFr ? 'Adhésion inconnue.' : 'Unknown membership.']);
}

// `fin_le` is an INCLUSIVE bound (the permission query uses `fin_le >= CURRENT_DATE`),
// so ending the membership today would keep the access until this evening.
// Yesterday is the correct value for an immediate revocation.
$stmt = $dependencies['pdo']->prepare('UPDATE adhesion SET fin_le = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) WHERE id = :id');
$stmt->execute(['id' => $adhesionId]);

$dependencies['journal']->log($userId, 'adhesion.fin', 'adhesion', $adhesionId, 'succes', [
    'utilisateur_id' => (int) $adhesion['utilisateur_id'],
    'espace_id' => (int) $adhesion['espace_id'],
]);

$respond(['message' => $isFr ? 'Adhésion terminée.' : 'Membership ended.']);
