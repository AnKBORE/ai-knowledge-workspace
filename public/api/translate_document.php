<?php

declare(strict_types=1);

/**
 * Protected endpoint — GET /api/translate_document.php?document_id=N&to=LANG&segment=I
 *
 * Returns the translated segment I of the document's indexed text (its audio
 * transcript for recordings), plus the total segment count. Segments are
 * translated on demand and cached in `document_translations`, so the audio
 * modal can stream the translation progressively while the file plays.
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

$documentId = (int) ($_GET['document_id'] ?? 0);
$to = (string) ($_GET['to'] ?? '');
$mode = (string) ($_GET['mode'] ?? 'text');
$hasSegment = isset($_GET['segment']);
$segment = (int) ($_GET['segment'] ?? 0);

$languages = array_keys(\App\Services\Ai\DocumentTranslationService::LANGUAGES);

$document = $documentId > 0 ? $dependencies['document_model']->find($documentId) : null;
if ($document === null) {
    http_response_code(404);
    echo json_encode(['error' => $T['document_not_found']], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($to, $languages, true) || $segment < 0) {
    http_response_code(422);
    echo json_encode(['error' => $T['invalid_input'] ?? 'Invalid request.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$version = $dependencies['version_model']->latest($documentId);
if ($version === null) {
    http_response_code(404);
    echo json_encode(['error' => $T['document_not_found']], JSON_UNESCAPED_UNICODE);
    exit;
}
$versionId = (int) $version['id'];

/* ---------- Timed mode: whisper timestamps → karaoke-style subtitles -------- */

if ($mode === 'timed') {
    $path = $dependencies['storage_path'] . '/' . (string) $version['fichier'];
    $mime = (string) ($version['type_mime'] ?? '');
    $isAudio = str_starts_with($mime, 'audio/')
        || in_array(strtolower((string) pathinfo((string) $version['fichier'], PATHINFO_EXTENSION)),
            ['wav', 'mp3', 'm4a', 'ogg', 'oga', 'webm', 'flac', 'aac'], true);

    if (!$isAudio || !is_file($path)) {
        echo json_encode(['error' => 'timed_unavailable'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $timed = $dependencies['doc_translations']->timedSegments($documentId, $versionId, $path);
    } catch (\Throwable $e) {
        echo json_encode(['error' => 'timed_unavailable'
            . (getenv('APP_DEBUG') === '1' ? ': ' . $e->getMessage() : '')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $total = count($timed);

    // Probe: return the timing map so the UI can lay out the subtitle lines.
    if (!$hasSegment) {
        echo json_encode([
            'mode' => 'timed',
            'total' => $total,
            'timings' => array_map(static fn ($s) => ['start' => $s['start'], 'end' => $s['end']], $timed),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($segment < 0 || $segment >= $total) {
        http_response_code(422);
        echo json_encode(['error' => $T['invalid_input'], 'total' => $total], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $translation = $dependencies['doc_translations']->translateTimedSegment($documentId, $versionId, $to, $segment, $timed);
        if ($translation === '') {
            http_response_code(502);
            echo json_encode(['error' => $T['internal_error']], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $dependencies['journal']->log(
            $dependencies['auth']->id(),
            'traduction',
            'document',
            $documentId,
            'succes',
            ['vers' => $to, 'segment' => $segment, 'total' => $total, 'mode' => 'timed'],
        );

        echo json_encode([
            'segment' => $segment,
            'total' => $total,
            'start' => $timed[$segment]['start'],
            'end' => $timed[$segment]['end'],
            'translation' => $translation,
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(502);
        echo json_encode(['error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : $T['internal_error']], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$service = $dependencies['doc_translations'];
$segments = $service->segments($documentId, $versionId);
$total = count($segments);

if ($total === 0) {
    http_response_code(422);
    echo json_encode(['error' => $T['no_text'] ?? 'This document has no indexed text.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($segment >= $total) {
    http_response_code(422);
    echo json_encode(['error' => $T['invalid_input'] ?? 'Invalid request.', 'total' => $total], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $translation = $service->translateSegment($documentId, $versionId, $to, $segment);

    if ($translation === '') {
        http_response_code(502);
        echo json_encode(['error' => $T['internal_error']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $dependencies['journal']->log(
        $dependencies['auth']->id(),
        'traduction',
        'document',
        $documentId,
        'succes',
        ['vers' => $to, 'segment' => $segment, 'total' => $total],
    );

    echo json_encode([
        'segment' => $segment,
        'total' => $total,
        'translation' => $translation,
        'title' => (string) ($document['title'] ?? ''),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(502);
    echo json_encode(['error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : $T['internal_error']], JSON_UNESCAPED_UNICODE);
}
