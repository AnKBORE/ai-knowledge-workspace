<?php

declare(strict_types=1);

use App\Services\Ai\RagService;

/**
 * CLI — index a document into the knowledge base.
 *
 * Usage:
 *   php tools/index_document.php "Title" /path/to/file.txt
 *   php tools/index_document.php "Title" /path/to/file.pdf
 *
 * Supported inputs:
 *   - text (.txt, .md)        -> read directly
 *   - PDF (.pdf)              -> smalot/pdfparser
 *   - audio (wav/mp3/m4a)     -> Muse Voice Transcribe
 *   - image / scan (png/jpg)  -> SAM 3.1 vision
 */

$dependencies = require dirname(__DIR__) . '/bootstrap.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/index_document.php \"Title\" /path/to/file.txt\n");
    exit(1);
}

$title = $argv[1];
$file = $argv[2];

if (!is_file($file)) {
    fwrite(STDERR, "File not found: {$file}\n");
    exit(1);
}

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

$audioExtensions = ['mp3', 'wav', 'm4a', 'ogg', 'webm', 'aac', 'flac'];
$imageExtensions = ['png', 'jpg', 'jpeg', 'tif', 'tiff', 'webp'];

if (in_array($extension, $audioExtensions, true)) {
    $content = $dependencies['transcription']->transcribe($file);
} elseif (in_array($extension, $imageExtensions, true)) {
    $content = $dependencies['vision']->extractText($file);
} elseif ($extension === 'pdf') {
    if (!class_exists(\Smalot\PdfParser\Parser::class)) {
        fwrite(STDERR, "PDF extraction requires smalot/pdfparser. Run `composer install` first.\n");
        exit(1);
    }
    $parser = new \Smalot\PdfParser\Parser();
    $content = $parser->parseFile($file)->getText();
} else {
    $content = file_get_contents($file);
    if ($content === false) {
        fwrite(STDERR, "Could not read: {$file}\n");
        exit(1);
    }
}

if (trim($content) === '') {
    fwrite(STDERR, "No text could be extracted from: {$file}\n");
    exit(1);
}

/** @var RagService $rag */
$rag = $dependencies['rag'];
$pdo = $dependencies['pdo'];

$mime = function_exists('mime_content_type') ? mime_content_type($file) : null;

$stmt = $pdo->prepare(
    'INSERT INTO documents (title, filename, mime_type) VALUES (:title, :filename, :mime_type)'
);
$stmt->execute([
    'title' => $title,
    'filename' => basename($file),
    'mime_type' => $mime ?: null,
]);

$documentId = (int) $pdo->lastInsertId();
$chunkCount = count($rag->chunk($content));

$rag->indexDocument($documentId, $content);

echo "Indexed document #{$documentId} \"{$title}\" ({$chunkCount} chunks).\n";
