<?php

declare(strict_types=1);

use App\Services\Ai\RagService;

/**
 * CLI — index a document into the knowledge base.
 *
 * Usage:
 *   php tools/index_document.php "Title" /path/to/file.txt
 *
 * Supports plain text (.txt, .md). PDF text extraction requires a parser
 * (e.g. smalot/pdfparser) to be added in phase 1.
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

$content = file_get_contents($file);
if ($content === false) {
    fwrite(STDERR, "Could not read: {$file}\n");
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
