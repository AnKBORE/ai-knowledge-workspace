<?php

declare(strict_types=1);

use App\Services\Ai\MooreReference;

/**
 * Protected endpoint — POST /api/reformulate.php
 *
 * Reword a transcript faithfully (same language, no new facts). Used by the
 * recorder result panel when the first wording reads badly.
 *
 * Request:  {"text": "...", "lang": "fr", "previous": "..."}
 * Response: {"text": "..."}
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

if (!$dependencies['rate_limiter']->allow('reformulate:' . $userId, 20, 300)) {
    http_response_code(429);
    header('Retry-After: 60');
    echo json_encode(['error' => $T['rate_limited'] ?? 'Trop de requêtes, réessayez dans une minute.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];

$text = trim((string) ($input['text'] ?? ''));
$previous = trim((string) ($input['previous'] ?? ''));
$sourceLang = (string) ($input['lang'] ?? '');

if ($text === '') {
    http_response_code(422);
    echo json_encode(['error' => $T['text_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

$langs = ['fr' => 'French', 'en' => 'English', 'mo' => 'Mooré (Mossi, Burkina Faso)'];
$label = $langs[$sourceLang] ?? 'the same language as the text';

try {
    $system = 'You reword a transcript while keeping its meaning strictly identical.'
        . ' Write it again in ' . $label . ', in clear, natural and well-punctuated prose.'
        . "\n\nRules:"
        . "\n- Output ONLY the rewritten transcript — no commentary, no title, no language name."
        . "\n- Keep every fact, name, number, date and acronym unchanged."
        . "\n- Do not add, remove or summarise any information."
        . "\n- Fix hesitations, repetitions and mis-punctuation; keep the speaker's intent."
        . "\n- Make it genuinely different from the given version: new sentence structure, not a copy.";

    if ($previous !== '') {
        $system .= "\n\nThe current wording is:\n" . $previous . "\n\nProduce a DIFFERENT and better-phrased version.";
    }

    if ($sourceLang === 'mo') {
        $system .= "\n\n" . MooreReference::prompt();
    }

    $reformulated = trim($dependencies['meta']->chat($text, [['role' => 'system', 'content' => $system]], null, 0.7));

    $dependencies['journal']->log($userId, 'reformulation', null, null, 'succes', [
        'longueur' => mb_strlen($text),
    ]);

    echo json_encode(['text' => $reformulated !== '' ? $reformulated : $text], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : $T['internal_error']],
        JSON_UNESCAPED_UNICODE
    );
}
