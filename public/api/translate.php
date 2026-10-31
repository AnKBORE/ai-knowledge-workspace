<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/translate.php
 *
 * JSON {text, from, to} — translates between fr / en / mo (Mooré) with
 * Muse Spark Open. Every translation is journaled.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];
$P = app_i18n($lang)['translate'];

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

$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];

$text = trim((string) ($input['text'] ?? ''));
$from = (string) ($input['from'] ?? '');
$to = (string) ($input['to'] ?? '');

$languages = ['fr' => $P['lang_fr'], 'en' => $P['lang_en'], 'mo' => 'Mooré (Mossi, Burkina Faso)'];

if ($text === '') {
    http_response_code(422);
    echo json_encode(['error' => $P['text_required']], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($from === $to || !isset($languages[$from], $languages[$to])) {
    http_response_code(422);
    echo json_encode(['error' => $P['same_lang']], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Mooré is low-resource: inject a curated linguistic reference (SIL/Peace
    // Corps sources) + real extracts from the user's own indexed documents.
    $system = 'You are a professional translator for French, English and Mooré (Mossi, Burkina Faso). '
        . 'Translate the user\'s text faithfully from %s into %s. '
        . 'Keep names, figures and dates exact. Output ONLY the final translation, verbatim and decisive: '
        . 'no comments, no notes, no alternatives, no uncertainty markers ("probably", "it seems", "perhaps"), '
        . 'no reasoning, no quotation marks around the whole text. If the source is ambiguous, commit to the '
        . 'single most likely meaning.';

    $system .= \App\Services\Ai\Glossaire::promptFor($to);
    $system .= \App\Services\Ai\LexiqueMoore::promptFor($text, $from, $to);

    if ($from === 'mo' || $to === 'mo') {
        $system .= "\n\n" . \App\Services\Ai\MooreReference::prompt();
        $moore = $dependencies['pdo']->query(
            "SELECT SUBSTRING(k.content, 1, 400) FROM document_chunks k
             JOIN documents d ON d.id = k.document_id AND d.supprime_le IS NULL
             WHERE k.content REGEXP 'ɩ|ʋ|ẽ|ũ|ã|õ|ã|ʋ' ORDER BY k.id DESC LIMIT 3"
        )->fetchAll(PDO::FETCH_COLUMN);
        $system .= "\n\nGenuine Mooré reference style (from real documents):\n"
            . implode("\n", array_map(static fn ($t) => '- ' . trim((string) $t), $moore ?: []));
        $system .= "\nUse standard Mossi orthography with its special characters (ɩ ʋ õ ã ũ ñ).";
    }

    $translation = $dependencies['meta']->chat(
        $text,
        [['role' => 'system', 'content' => sprintf($system, $languages[$from], $languages[$to])]],
        null,
        0.1,
    );

    // Length-ratio guard: an incomplete or distorted translation is retried once.
    $ratio = strlen($text) > 0 ? strlen($translation) / strlen($text) : 1.0;
    if (trim($translation) === '' || $ratio < 0.35 || $ratio > 3.5) {
        $repair = sprintf($system, $languages[$from], $languages[$to])
            . "\n\nYour previous attempt was rejected (incomplete or distorted). Translate the ENTIRE source faithfully, clause by clause; keep the same length and level of detail.";
        $retry = $dependencies['meta']->chat($text, [['role' => 'system', 'content' => $repair]], null, 0.1);
        if (trim($retry) !== '') {
            $translation = $retry;
        }
    }

    $dependencies['journal']->log(
        $dependencies['auth']->id(),
        'traduction',
        null,
        null,
        'succes',
        ['de' => $from, 'vers' => $to, 'longueur' => mb_strlen($text)],
    );

    echo json_encode(['translation' => trim($translation)], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => getenv('APP_DEBUG') === '1' ? $e->getMessage() : $T['internal_error']], JSON_UNESCAPED_UNICODE);
}
