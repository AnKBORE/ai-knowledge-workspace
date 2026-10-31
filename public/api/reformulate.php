<?php

declare(strict_types=1);

use App\Services\Ai\MooreReference;

/**
 * Protected endpoint — POST /api/reformulate.php
 *
 * Produces a different, better-phrased version of a recording. The AUDIO is the
 * source of truth: when the request carries `audio`, the model re-listens to it
 * instead of rewording a translation (which would lose the original wording).
 *
 * Two shapes are accepted:
 *   multipart: audio=@file, from=mo|fr|en (optional), to=mo|fr|en (optional), previous=...
 *   JSON:      {"text": "...", "lang": "fr", "previous": "..."}
 *
 * Response: {"text": "...", "transcript": "...", "translated": true|false}
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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => $T['method_post']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['csrf']->validateRequest()) {
    http_response_code(419);
    echo json_encode(['error' => $T['csrf'] ?? 'Session expirée. Rechargez la page.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) $dependencies['auth']->id();

if (!$dependencies['rate_limiter']->allow('reformulate:' . $userId, 30, 300)) {
    http_response_code(429);
    header('Retry-After: 60');
    echo json_encode(['error' => $T['rate_limited'] ?? 'Trop de requêtes, réessayez dans une minute.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$langs = ['fr' => 'French', 'en' => 'English', 'mo' => 'Mooré (Mossi, Burkina Faso)'];
$allowed = static fn (string $v): bool => in_array($v, ['fr', 'en', 'mo'], true);

$from = (string) ($_POST['from'] ?? '');
$to = (string) ($_POST['to'] ?? '');
$previous = trim((string) ($_POST['previous'] ?? ''));
$inputText = '';
$audioPath = null;

if (isset($_FILES['audio']) && is_uploaded_file($_FILES['audio']['tmp_name'])) {
    if ((int) $_FILES['audio']['size'] > 24 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['error' => 'Audio too large (24 MB max).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $audioPath = tempnam(sys_get_temp_dir(), 'akw_ref_') . '.wav';
    move_uploaded_file($_FILES['audio']['tmp_name'], $audioPath);
} else {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $payload = is_array($payload) ? $payload : [];
    $inputText = trim((string) ($payload['text'] ?? ''));
    $previous = $previous !== '' ? $previous : trim((string) ($payload['previous'] ?? ''));
    $to = $to !== '' ? $to : (string) ($payload['to'] ?? '');
    $from = $from !== '' ? $from : (string) ($payload['lang'] ?? '');
}

$from = $allowed($from) ? $from : '';
$to = $allowed($to) ? $to : '';

if ($audioPath === null && $inputText === '') {
    http_response_code(422);
    echo json_encode(['error' => $T['text_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $translated = false;
    $transcript = $inputText;

    // 1. Always start from the audio when we have it.
    if ($audioPath !== null) {
        try {
            $transcript = trim($dependencies['transcription']->transcribe($audioPath, $from !== '' ? $from : null));
        } finally {
            @unlink($audioPath);
        }

        if ($transcript === '') {
            http_response_code(422);
            echo json_encode(['error' => 'No speech recognised in the recording.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 2. Decide the output language: translate only when one was requested.
    $wantsTranslation = $to !== '' && $to !== $from;
    $targetLabel = $langs[$wantsTranslation ? $to : ($from !== '' ? $from : 'fr')] ?? 'the same language as the source';

    $system = 'You rewrite a spoken recording so it reads well, keeping its meaning strictly identical.'
        . ' Write it in ' . $targetLabel . ', in clear, natural, well-punctuated prose.'
        . "\n\nRules:"
        . "\n- Output ONLY the rewritten text — no commentary, no title, no language name."
        . "\n- Keep every fact, name, number, date and acronym unchanged."
        . "\n- Do not add, remove or summarise any information."
        . "\n- Remove hesitations and repetitions, fix the punctuation, keep the speaker's intent."
        . "\n- This text comes from speech recognition: silently repair obvious mishearings first."
        . "\n- Produce a genuinely different sentence structure from the version you are given.";

    if ($previous !== '' && $previous !== $transcript) {
        $system .= "\n\nA first version already exists:\n" . mb_substr($previous, 0, 4000)
            . "\n\nWrite a DIFFERENT and better-phrased version of the same recording.";
    }

    if ($wantsTranslation) {
        $system .= "\n\nThis is also a translation task: render the meaning in the target language, do not copy the source wording.";
    }

    if (($wantsTranslation ? $to : $from) === 'mo') {
        $system .= "\n\n" . MooreReference::prompt();
    }

    $out = trim($dependencies['meta']->chat($transcript, [['role' => 'system', 'content' => $system]], null, 0.7));
    $translated = $wantsTranslation;

    $dependencies['journal']->log($userId, 'reformulation', null, null, 'succes', [
        'source' => $audioPath !== null ? 'audio' : 'texte',
        'de' => $from,
        'vers' => $to,
        'longueur' => mb_strlen($transcript),
    ]);

    echo json_encode([
        'text' => $out !== '' ? $out : $transcript,
        'transcript' => $transcript,
        'translated' => $translated,
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : $T['internal_error']],
        JSON_UNESCAPED_UNICODE
    );
}
