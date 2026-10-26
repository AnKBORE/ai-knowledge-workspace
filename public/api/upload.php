<?php

declare(strict_types=1);

/**
 * Public endpoint — POST /api/upload.php
 *
 * Multipart form with `title` and `file`. Extracts text from the file
 * (text / PDF / scanned image / audio), indexes it, and returns JSON.
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

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
    http_response_code(422);
    echo json_encode(['error' => 'A valid file is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmp = (string) $upload['tmp_name'];
$originalName = (string) ($upload['name'] ?? 'document');

try {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $audioExtensions = ['mp3', 'wav', 'm4a', 'ogg', 'webm', 'aac', 'flac'];
    $imageExtensions = ['png', 'jpg', 'jpeg', 'tif', 'tiff', 'webp'];

    if (in_array($extension, $audioExtensions, true)) {
        $content = $dependencies['transcription']->transcribe($tmp);
    } elseif (in_array($extension, $imageExtensions, true)) {
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

    $pdo = $dependencies['pdo'];
    $stmt = $pdo->prepare('INSERT INTO documents (title, filename, mime_type) VALUES (:title, :filename, :mime_type)');
    $stmt->execute([
        'title' => $title,
        'filename' => $originalName,
        'mime_type' => (string) ($upload['type'] ?? ''),
    ]);

    $documentId = (int) $pdo->lastInsertId();
    $chunks = count($dependencies['rag']->chunk($content));
    $dependencies['rag']->indexDocument($documentId, $content);

    echo json_encode(['title' => $title, 'chunks' => $chunks], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Indexing failed.'], JSON_UNESCAPED_UNICODE);
}
