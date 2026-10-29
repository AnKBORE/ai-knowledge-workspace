<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Extracts text from scanned documents (paper records, forms, tables) with SAM 3.1.
 */
final class VisionService
{
    public function __construct(private readonly MetaClient $client)
    {
    }

    /**
     * Extract text from a local image file (a scan or a photo of a form).
     */
    public function extractText(string $imagePath): string
    {
        if (!is_file($imagePath)) {
            throw new \RuntimeException('Image file not found: ' . $imagePath);
        }

        return trim($this->client->describeImage($imagePath));
    }

    /**
     * OCR fallback for scanned PDFs (no text layer): each embedded JPEG page
     * image is extracted and read by the vision model, then concatenated with
     * page markers. Returns '' when the PDF holds no usable image.
     */
    public function extractTextFromScannedPdf(\Smalot\PdfParser\Document $pdf): string
    {
        $images = array_values(array_filter(
            $pdf->getObjectsByType('XObject', 'Image'),
            // JPEG streams (DCTDecode) are valid image files as-is.
            static fn ($image) => str_starts_with($image->getContent(), "\xFF\xD8\xFF"),
        ));

        $paths = [];
        foreach ($images as $image) {
            $base = tempnam(sys_get_temp_dir(), 'akw_ocr_');
            file_put_contents($base . '.jpg', $image->getContent());
            $paths[$base] = $base . '.jpg';
        }

        try {
            $texts = $this->client->describeImages(array_values($paths));
        } finally {
            foreach ($paths as $base => $path) {
                @unlink($path);
                @unlink($base);
            }
        }

        $pages = [];
        foreach ($texts as $i => $text) {
            if (trim($text) !== '') {
                $pages[] = '[Page ' . ($i + 1) . ' / ' . count($texts) . "]\n" . trim($text);
            }
        }

        return implode("\n\n", $pages);
    }
}
