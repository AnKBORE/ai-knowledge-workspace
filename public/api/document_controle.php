<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/document_controle.php
 *
 * The administration changes where a document lives and who may read it. The
 * deposit sets a default; this is the deliberate override, so it requires the
 * administrator password and is journaled with the previous values.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];
$isFr = $lang === 'fr';

header('X-Content-Type-Options: nosniff');

$respond = static function (array $data, int $code = 200) use ($dependencies, $isFr): never {
    $isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if (!$isXhr) {
        $dependencies['session']->set('documents_flash', [
            'type' => isset($data['error']) ? 'error' : 'success',
            'text' => (string) ($data['error'] ?? $data['message'] ?? ''),
        ]);
        header('Location: ' . rtrim(str_replace('\\', '/', dirname(dirname((string) $_SERVER['SCRIPT_NAME']))), '/') . '/documents');
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

$userId = (int) $dependencies['auth']->id();

// Diffusion is an organisational decision: the global administrators hold it.
if (!$dependencies['permissions']->peutAilleurs($userId, 'espace.administrer')) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'permission', 'chemin' => '/api/document_controle']);
    $respond(['error' => $T['permission_denied']], 403);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    $respond(['error' => 'Method not allowed. Use POST.'], 405);
}

if (!$dependencies['csrf']->validateRequest()) {
    $dependencies['journal']->log($userId, 'securite.refus', 'securite', null, 'refus', ['raison' => 'csrf', 'chemin' => '/api/document_controle']);
    $respond(['error' => $T['csrf'] ?? 'Session expirée.'], 419);
}

if (!verifier_mot_de_passe_admin($dependencies, 'document.controle')) {
    $respond(['error' => $isFr ? 'Mot de passe administrateur incorrect.' : 'Incorrect administrator password.'], 422);
}

$documentId = (int) ($_POST['document_id'] ?? 0);
$confidentialite = (string) ($_POST['confidentialite'] ?? 'restreint');

// A document may be filed in several workspaces: the posted list replaces the
// whole set, so unchecking a workspace detaches the document from it.
$espaceIds = [];

foreach ((array) ($_POST['espace_ids'] ?? []) as $espaceCandidat) {
    $espaceCandidat = (int) $espaceCandidat;

    if ($espaceCandidat > 0) {
        $espaceIds[] = $espaceCandidat;
    }
}

$espaceIds = array_values(array_unique($espaceIds));

if ($documentId < 1) {
    $respond(['error' => $isFr ? 'Document invalide.' : 'Invalid document.'], 422);
}

if (!in_array($confidentialite, ['public_interne', 'restreint', 'prive'], true)) {
    $respond(['error' => $isFr ? 'Confidentialité invalide.' : 'Invalid confidentiality.'], 422);
}

$document = $dependencies['document_model']->find($documentId);

if ($document === null) {
    $respond(['error' => $T['document_not_found']], 404);
}

// A document without any workspace is owner-only: the empty list is valid.
$espacesConnus = [];

foreach ($dependencies['espaces']->all() as $espaceExistant) {
    $espacesConnus[] = (int) $espaceExistant['id'];
}

foreach ($espaceIds as $espaceCandidat) {
    if (!in_array($espaceCandidat, $espacesConnus, true)) {
        $respond(['error' => $isFr ? 'Espace inconnu.' : 'Unknown workspace.'], 404);
    }
}

$avant = [
    'espaces' => $dependencies['document_model']->espacesDe($documentId),
    'confidentialite' => (string) ($document['confidentialite'] ?? 'restreint'),
];

$dependencies['document_model']->remplacerEspaces($documentId, $espaceIds);
$dependencies['document_model']->updateConfidentialite($documentId, $confidentialite);

$dependencies['journal']->log($userId, 'document.controle', 'document', $documentId, 'succes', [
    'avant' => $avant,
    'apres' => ['espaces' => $espaceIds, 'confidentialite' => $confidentialite],
]);

$respond(['message' => $isFr
    ? 'Espace et confidentialité mis à jour.'
    : 'Workspace and confidentiality updated.']);
