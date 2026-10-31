<?php
/**
 * Rolling live-transcription endpoint for the meeting recorder.
 * Receives short audio slices (WAV 16 kHz), returns transcript text,
 * optionally translated into `to` (fr|en|mo).
 *
 * Precision strategy (real-time):
 *   1. rolling context: the previous translation is passed back so boundary
 *      ambiguity is resolved instead of re-translated;
 *   2. domain glossary: professional terms stay consistent across slices;
 *   3. strict prompt: translate only, keep names/numbers/dates/acronyms,
 *      never invent content, no commentary;
 *   4. length-ratio check: a suspicious output (too short or too long for the
 *      source) is retried once with an explicit repair instruction.
 */
$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Services\Ai\Glossaire;
use App\Services\Ai\LexiqueMoore;
use App\Services\Ai\MooreReference;

header('Content-Type: application/json; charset=utf-8');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'auth_required']);
    exit;
}
if (!$dependencies['csrf']->validateRequest()) {
    http_response_code(419);
    echo json_encode(['error' => 'csrf']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['audio']) || !is_uploaded_file($_FILES['audio']['tmp_name'])) {
    http_response_code(422);
    echo json_encode(['error' => 'audio_required']);
    exit;
}
if ((int) $_FILES['audio']['size'] > 6 * 1024 * 1024 || (int) $_FILES['audio']['size'] < 4000) {
    echo json_encode(['text' => '']);
    exit;
}

$tmp = tempnam(sys_get_temp_dir(), 'akw_live_') . '.wav';
move_uploaded_file($_FILES['audio']['tmp_name'], $tmp);

$from = preg_match('/^(fr|en|mo)$/', (string) ($_POST['from'] ?? '')) ? (string) $_POST['from'] : null;

try {
    $text = trim($dependencies['transcription']->transcribe($tmp, $from));
} catch (\Throwable) {
    $text = '';
}
@unlink($tmp);

// Never display ASR "no speech"/refusal replies or sound-effect descriptions
// (*bruits…*, (musique)…) as if they were speech.
if ($text !== '' && (\App\Services\Ai\MetaClient::isNotATranscript($text)
    || preg_match('/\b(no|not any)\s+(audible\s+)?(speech|audio|voice|sound)\b[^.]{0,60}\b(detected|found|provided|heard|present)/iu', $text)
    || preg_match('/^\s*[\*\(\[]/u', $text)
    || preg_match('/\b(bruits?|musique|applaudissements?|sous[- ]?titrage)\b/iu', $text))) {
    $text = '';
}

$to = (string) ($_POST['to'] ?? '');
$langs = ['fr' => 'French', 'en' => 'English', 'mo' => 'Mooré'];

/**
 * Translate one slice with the strict prompt; returns '' on failure.
 */
$translateSlice = static function (string $source, string $target, ?string $sourceLang, string $context, bool $repair) use ($dependencies, $langs): string {
    $moore = $target === 'mo' || $sourceLang === 'mo' || (bool) preg_match('/[ɩʋɛɔãẽĩõũ]/u', $source);

    $srcNote = $sourceLang !== null
        ? ' The source language is ' . $langs[$sourceLang] . '.'
            . ($sourceLang === 'mo' ? ' The slice is an imperfect phonetic rendering of Mooré speech (foreign-looking words are misheard Mooré): reconstruct the intended Mooré words first, then translate.' : '')
        : '';

    $ctxNote = $context !== ''
        ? "\n\nThe previous translated excerpt (for continuity ONLY — do not repeat it, do not paraphrase it):\n" . $context
        : '';

    $repairNote = $repair
        ? "\n\nYour previous attempt was rejected (incomplete or distorted). Translate the ENTIRE source faithfully, sentence by sentence; keep the same length and level of detail."
        : '';

    $system = 'You are a professional translator for French, English and Mooré (Mossi, Burkina Faso).'
        . ' Translate the live-transcribed audio slice into ' . $langs[$target] . '.'
        . $srcNote
        . "\n\nRules:"
        . "\n- Output ONLY the translation — no commentary, no quotes, no language name."
        . "\n- Keep every number, acronym and proper noun unchanged; translate month names and date formats into the target language."
        . "\n- Do not add, summarize or omit information; translate every clause."
        . "\n- The slice may start or end mid-word: translate the complete words only and ignore the partial fragment."
        . "\n- The text may contain ASR mishearings: silently reconstruct the probable words first, then translate."
        . Glossaire::promptFor($target)
        . LexiqueMoore::promptFor($source, $sourceLang ?? 'fr', $target)
        . $ctxNote
        . $repairNote
        . ($moore ? "\n\n" . MooreReference::prompt() . "\n\n" . Proverbes::prompt(4) : '');

    return trim($dependencies['meta']->chat($source, [['role' => 'system', 'content' => $system]], null, 0.1));
};

if ($text !== '' && isset($langs[$to])) {
    $context = trim(mb_substr((string) ($_POST['ctx'] ?? ''), 0, 1000));

    try {
        $translation = $translateSlice($text, $to, $from, $context, false);

        // Length-ratio guard: a translation far shorter/longer than the source
        // usually means an omission or a drift — retry once with a repair note.
        $ratio = strlen($text) > 0 ? strlen($translation) / strlen($text) : 1.0;
        if ($translation === '' || $ratio < 0.35 || $ratio > 3.5) {
            $retry = $translateSlice($text, $to, $from, $context, true);
            if ($retry !== '') {
                $translation = $retry;
            }
        }

        if ($translation !== '') {
            $text = $translation;
        }
    } catch (\Throwable) { /* keep raw transcript */ }
}

echo json_encode(['text' => $text], JSON_UNESCAPED_UNICODE);
