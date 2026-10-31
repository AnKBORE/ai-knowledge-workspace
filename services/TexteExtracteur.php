<?php

declare(strict_types=1);

namespace App\Services;

use Smalot\PdfParser\Parser;
use ZipArchive;

/**
 * Extracts plain text from an uploaded correction.
 *
 * Reviewers send what they actually work with — a PDF or a Word document —
 * so the correction pipeline reads those natively instead of forcing them to
 * convert to .txt first.
 */
final class TexteExtracteur
{
    /** Extensions accepted for a correction. */
    public const EXTENSIONS = ['txt', 'md', 'csv', 'pdf', 'docx'];

    public function fromFile(string $path, string $extension): string
    {
        $extension = strtolower($extension);

        $text = match ($extension) {
            'pdf' => $this->fromPdf($path),
            'docx' => $this->fromDocx($path),
            default => (string) file_get_contents($path),
        };

        // Normalise the whitespace so the stored correction is predictable.
        $text = (string) preg_replace("/\r\n?/u", "\n", $text);
        $text = (string) preg_replace("/[ \t]+\n/u", "\n", $text);
        $text = (string) preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }

    private function fromPdf(string $path): string
    {
        try {
            return (new Parser())->parseFile($path)->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * A .docx is a zip whose word/document.xml holds the paragraphs.
     */
    private function fromDocx(string $path): string
    {
        if (!class_exists(ZipArchive::class)) {
            return '';
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === '') {
            return '';
        }

        // Paragraphs become newlines, runs are joined, remaining tags dropped.
        $xml = str_replace(['</w:p>', '<w:br/>', '<w:br />'], "\n", $xml);
        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return (string) preg_replace("/[ \t]+/u", ' ', $text);
    }
}
