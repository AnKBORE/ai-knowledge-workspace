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

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$dependencies['permissions']->peutAilleurs($dependencies['auth']->id(), 'document.deposer')) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$title = trim((string) ($_POST['title'] ?? ''));
if ($title === '') {
    http_response_code(422);
    echo json_encode(['error' => 'A title is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$upload = $_FILES['file'] ?? null;
if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = is_array($upload) ? (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite du serveur (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite du formulaire.',
        UPLOAD_ERR_PARTIAL => "L'envoi a été interrompu.",
        UPLOAD_ERR_NO_FILE => 'Aucun fichier sélectionné.',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
        UPLOAD_ERR_CANT_WRITE => "Impossible d'écrire sur le disque.",
        UPLOAD_ERR_EXTENSION => "Envoi bloqué par une extension PHP.",
    ];
    http_response_code(422);
    echo json_encode(['error' => $messages[$code] ?? ('Erreur d\'envoi (' . $code . ').')], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $tmp = (string) $upload['tmp_name'];
    $originalName = (string) ($upload['name'] ?? 'document');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // 1. Extract the text BEFORE moving the uploaded file.
    if (in_array($extension, ['mp3', 'wav', 'm4a', 'ogg', 'webm', 'aac', 'flac'], true)) {
        $content = $dependencies['transcription']->transcribe($tmp);
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
        throw new \RuntimeException('No text could be extracted from the file.');
    }

    // 2. Store the file and create the document + first immutable version.
    $result = $dependencies['documents_service']->createDocument(
        $title,
        $dependencies['espaces']->firstActive(),
        $dependencies['auth']->id(),
        $upload,
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
        ['version' => 1, 'chunks' => $chunks],
    );

    echo json_encode([
        'title' => $title,
        'chunks' => $chunks,
        'version' => 1,
        'sha256' => substr($result['sha256'], 0, 12),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    $debug = getenv('APP_DEBUG') === '1';
    echo json_encode(['error' => $debug ? $e->getMessage() : 'Indexing failed.'], JSON_UNESCAPED_UNICODE);
}
