<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/upload.php
 *
 * Multipart form with `title` and `file`. Extracts text from the file
 * (text / PDF / scanned image / audio), creates an immutable version
 * (SHA-256, stored outside the web root) and indexes the content.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

// Local whisper.cpp transcription can take several minutes on CPU —
// lift the default 30 s PHP limit for this endpoint.
set_time_limit(0);
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => $T['auth_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.deposer')) {
    http_response_code(403);
    echo json_encode(['error' => $T['permission_denied']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => $T['method_post']], JSON_UNESCAPED_UNICODE);
    exit;
}

$title = trim((string) ($_POST['title'] ?? ''));
if ($title === '') {
    http_response_code(422);
    echo json_encode(['error' => $T['title_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

$categorieId = isset($_POST['categorie_id']) && $_POST['categorie_id'] !== ''
    ? (int) $_POST['categorie_id']
    : 0;

if ($categorieId < 1 || $dependencies['categorie_model']->find($categorieId) === null) {
    http_response_code(422);
    echo json_encode(['error' => $T['category_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

$upload = $_FILES['file'] ?? null;
if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = is_array($upload) ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
    $messages = [
        UPLOAD_ERR_INI_SIZE => $T['file_ini_size'] . ' (upload_max_filesize = ' . ini_get('upload_max_filesize') . ', post_max_size = ' . ini_get('post_max_size') . ').',
        UPLOAD_ERR_FORM_SIZE => $T['file_form_size'],
        UPLOAD_ERR_PARTIAL => $T['file_partial'],
        UPLOAD_ERR_NO_FILE => $T['file_none'],
        UPLOAD_ERR_NO_TMP_DIR => $T['file_no_tmp'],
        UPLOAD_ERR_CANT_WRITE => $T['file_cant_write'],
        UPLOAD_ERR_EXTENSION => $T['file_extension'],
    ];
    http_response_code(422);
    echo json_encode(['error' => $messages[$code] ?? ($T['internal_error'] . ' (' . $code . ').')], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $tmp = (string) $upload['tmp_name'];
    $originalName = (string) ($upload['name'] ?? 'document');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // 1. Extract the text BEFORE moving the uploaded file.
    $audioExtensions = ['mp3', 'wav', 'm4a', 'ogg', 'webm', 'aac', 'flac'];

    if (in_array($extension, $audioExtensions, true)) {
        $maxAudio = (int) ($dependencies['config']['audio_max_seconds'] ?? 600);
        $splitter = new \App\Services\AudioSplitter();

        // $tmp has no extension (phpXXXX.tmp) — work on a copy named with the
        // real extension so duration() and split() can recognise the format.
        $workPath = $tmp . '.' . $extension;
        copy($tmp, $workPath);
        $duration = $splitter->duration($workPath);

        if ($duration !== null && $duration > $maxAudio) {
            // Long audio: split into ≤$maxAudio segments (WAV/MP3), transcribe
            // each one and concatenate — the file is never rejected outright.
            $parts = $splitter->split($workPath, $maxAudio);

            if ($parts === []) {
                // Format the splitter cannot cut (m4a/ogg/…): keep the guard.
                http_response_code(422);
                echo json_encode(['error' => sprintf($T['audio_too_long'], intdiv($maxAudio, 60))], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $segments = [];
            foreach ($parts as $i => $partPath) {
                $segments[] = '[Segment ' . ($i + 1) . ' / ' . count($parts) . "]\n"
                    . $dependencies['transcription']->transcribe($partPath);
                if (!in_array($partPath, [$tmp, $workPath], true)) {
                    @unlink($partPath);
                }
            }
            @unlink($workPath);
            $content = implode("\n\n", $segments);
        } else {
            $content = $dependencies['transcription']->transcribe($workPath);
        }
        if (file_exists($workPath)) {
            @unlink($workPath);
        }
    } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'tif', 'tiff', 'webp'], true)) {
        $content = $dependencies['vision']->extractText($tmp);
    } elseif ($extension === 'pdf') {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new \RuntimeException('PDF extraction requires smalot/pdfparser.');
        }
        $parser = new \Smalot\PdfParser\Parser();
        $content = $parser->parseFile($tmp)->getText();
    } else {
        $content = (string) file_get_contents($tmp);
    }

    if (trim($content) === '') {
        throw new \RuntimeException($T['file_empty']);
    }

    // Optional anonymisation before indexing and before any API call.
    if (getenv('ANONYMISATION') === '1') {
        $content = (new \App\Services\Anonymiseur())->anonymiser($content);
    }

    // 2. Store the file and create the document + first immutable version.
    $result = $dependencies['documents_service']->createDocument(
        $title,
        $dependencies['espaces']->firstActive(),
        $dependencies['auth']->id(),
        $upload,
        $categorieId,
    );

    // 3. Index the extracted text, attached to the version.
    $chunks = count($dependencies['rag']->chunk($content));
    $dependencies['rag']->indexDocument($result['document_id'], $content, $result['version_id']);

    $dependencies['journal']->log(
        $dependencies['auth']->id(),
        'document.depot',
        'document',
        $result['document_id'],
        'succes',
        [
            'titre' => $title,
            'version' => 1,
            'chunks' => $chunks,
            'sha256' => substr($result['sha256'], 0, 16),
            'taille_octets' => (int) ($upload['size'] ?? 0),
        ],
    );

    echo json_encode([
        'title' => $title,
        'chunks' => $chunks,
        'version' => 1,
        'sha256' => substr($result['sha256'], 0, 12),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    $message = $e->getMessage();

    if (str_contains($message, 'Audio too long')) {
        $error = sprintf($T['audio_too_long'], intdiv(($dependencies['config']['audio_max_seconds'] ?? 600), 60));
    } elseif (str_contains($message, 'Media could not be processed')) {
        $error = $T['media_failed'];
    } else {
        $error = getenv('APP_DEBUG') === '1' ? $message : $T['upload_failed'];
    }

    http_response_code(500);
    echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE);
}
