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
    /**
     * Wrap raw PDF bitmap samples in a PNG.
     *
     * A Flate-encoded image in a PDF is not an image file: it is the pixel data
     * itself. This builds a valid PNG (8-bit grey or RGB) around it, which the
     * vision call can then read. Anything less usual - 1-bit scans, CMYK,
     * indexed palettes - returns null and the caller reports it.
     *
     * @param array<string, mixed> $details the image dictionary
     */
    private function rawBitmapToPng(string $raw, array $details): ?string
    {
        $width = (int) ($details['Width'] ?? 0);
        $height = (int) ($details['Height'] ?? 0);
        $bits = (int) ($details['BitsPerComponent'] ?? 8);
        $space = $details['ColorSpace'] ?? '';

        if (is_array($space)) {
            $space = (string) (end($space) ?: '');
        }

        $space = (string) $space;
        $channels = match ($space) {
            'DeviceRGB', 'RGB' => 3,
            'DeviceGray', 'G' => 1,
            default => 0,
        };

        // Guard: 8 bits only, sane dimensions, and the samples must match.
        if ($channels === 0 || $bits !== 8 || $width < 8 || $height < 8 || $width * $height > 12_000_000) {
            return null;
        }

        $stride = $width * $channels;

        if (strlen($raw) < $stride * $height) {
            return null;
        }

        // One filter byte (0 = none) per scanline, then the samples.
        $scanlines = '';

        for ($y = 0; $y < $height; $y++) {
            $scanlines .= "\x00" . substr($raw, $y * $stride, $stride);
        }

        $chunk = static function (string $type, string $data): string {
            return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        };

        $ihdr = pack('N2', $width, $height) . chr(8) . chr($channels === 3 ? 2 : 0) . "\x00\x00\x00";

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', $ihdr)
            . $chunk('IDAT', gzcompress($scanlines, 6))
            . $chunk('IEND', '');
    }
    public function extractTextFromScannedPdf(\Smalot\PdfParser\Document $pdf): string
    {
        $all = $pdf->getObjectsByType('XObject', 'Image');

        if ($all === []) {
            throw new \RuntimeException('Scanned PDF has no readable image');
        }

        $paths = [];
        $skipped = 0;

        foreach ($all as $image) {
            $content = (string) $image->getContent();
            $details = $image->getDetails();

            // JPEG streams are valid image files as they are.
            if (str_starts_with($content, "\xFF\xD8\xFF")) {
                $base = tempnam(sys_get_temp_dir(), 'akw_ocr_');
                file_put_contents($base . '.jpg', $content);
                $paths[$base] = $base . '.jpg';

                continue;
            }

            // Flate-encoded bitmaps arrive as raw samples: wrap them in a PNG.
            $png = $this->rawBitmapToPng($content, $details);

            if ($png === null) {
                $skipped++;

                continue;
            }

            $base = tempnam(sys_get_temp_dir(), 'akw_ocr_');
            file_put_contents($base . '.png', $png);
            $paths[$base] = $base . '.png';
        }

        if ($paths === []) {
            // Everything was skipped: say which case it is, instead of returning
            // an empty string the caller would report as "file empty".
            throw new \RuntimeException(
                $skipped > 0
                    ? 'Scanned PDF images are not convertible (unsupported colour space or depth)'
                    : 'Scanned PDF has no readable image'
            );
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
