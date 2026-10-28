<?php

declare(strict_types=1);

/**
 * Protected endpoint — POST /api/export.php
 *
 * Request:  {"title": "...", "content": "...", "format": "pdf"|"docx"}
 * Response: the generated file (Content-Disposition: attachment).
 */

$dependencies = require dirname(__DIR__, 2) . '/bootstrap.php';
$lang = (string) $dependencies['session']->get('lang', 'en');
$T = app_i18n($lang)['api'];

if (!$dependencies['auth']->check()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['auth_required']], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['method_post']], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
$input = is_array($input) ? $input : [];

$title = trim((string) ($input['title'] ?? 'Résultat'));
$content = (string) ($input['content'] ?? '');
$format = strtolower((string) ($input['format'] ?? 'pdf'));

if (trim($content) === '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $T['export_content']], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($format !== 'docx') {
    $format = 'pdf';
}

try {
    $exporter = new \App\Services\ExportService();
    $bytes = $format === 'docx'
        ? $exporter->toDocx($title, $content)
        : $exporter->toPdf($title, $content);

    $slug = preg_replace('/[^a-z0-9]+/i', '-', $title) ?? 'resultat';
    $slug = trim(mb_strtolower($slug), '-') ?: 'resultat';
    $filename = $slug . '-' . date('Ymd-His') . '.' . $format;

    header('Content-Type: ' . ($format === 'docx'
        ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        : 'application/pdf'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($bytes));
    header('X-Content-Type-Options: nosniff');

    echo $bytes;
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $debug = getenv('APP_DEBUG') === '1';
    echo json_encode(['error' => $debug ? $e->getMessage() : $T['export_failed']], JSON_UNESCAPED_UNICODE);
}
