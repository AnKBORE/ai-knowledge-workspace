<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Espace;
use App\Services\Ai\RagService;
use App\Services\Ai\TranscriptionService;
use App\Services\Ai\VisionService;

/**
 * Document ingestion pipeline shared by the synchronous upload endpoint and the
 * background worker: text extraction (text / PDF / scanned PDF / image / audio),
 * immutable storage, indexing and audit logging.
 */
final class IngestionService
{
    public const AUDIO = ['mp3', 'wav', 'm4a', 'ogg', 'webm', 'aac', 'flac'];
    public const IMAGE = ['png', 'jpg', 'jpeg', 'tif', 'tiff', 'webp'];

    /** @var array<string, \Smalot\PdfParser\Document> parsed PDFs, by path */
    private array $pdfCache = [];

    public function __construct(
        private readonly TranscriptionService $transcription,
        private readonly VisionService $vision,
        private readonly DocumentService $documents,
        private readonly Espace $espaces,
        private readonly RagService $rag,
        private readonly JournalService $journal,
        private readonly int $audioMaxSeconds = 600,
    ) {
    }

    /**
     * Slow files need AI calls (ASR / OCR) that can take minutes: they are
     * processed in the background instead of blocking the HTTP request.
     */
    public function isSlow(string $path, string $extension): bool
    {
        if (in_array($extension, self::AUDIO, true) || in_array($extension, self::IMAGE, true)) {
            return true;
        }

        return $extension === 'pdf' && trim($this->pdf($path)->getText()) === '';
    }

    /**
     * Extract the indexable text of a file. Throws on empty/unreadable content.
     */
    public function extract(string $path, string $extension): string
    {
        if (in_array($extension, self::AUDIO, true)) {
            $content = $this->extractAudio($path, $extension);
        } elseif (in_array($extension, self::IMAGE, true)) {
            $content = $this->vision->extractText($path);
        } elseif ($extension === 'pdf') {
            $pdf = $this->pdf($path);
            $content = $pdf->getText();
            // Scanned PDF (images only, no text layer): OCR each page image.
            if (trim($content) === '') {
                $content = $this->vision->extractTextFromScannedPdf($pdf);
            }
        } else {
            $content = (string) file_get_contents($path);
        }

        if (trim($content) === '') {
            throw new \RuntimeException('File empty or unreadable');
        }

        // Optional anonymisation before indexing and before any API call.
        if (getenv('ANONYMISATION') === '1') {
            $content = (new Anonymiseur())->anonymiser($content);
        }

        return $content;
    }

    /**
     * Store the file, create the document + first immutable version, index the
     * text and write the audit entry.
     *
     * @param array{tmp_name: string, name: string, type?: string, size?: int, error?: int} $upload
     * @return array{document_id: int, chunks: int, sha256: string}
     */
    public function finalize(string $title, int $categorieId, int $userId, array $upload, string $content): array
    {
        $result = $this->documents->createDocument($title, $this->espaces->firstActive(), $userId, $upload, $categorieId);

        $chunks = count($this->rag->chunk($content));
        $this->rag->indexDocument($result['document_id'], $content, $result['version_id']);

        $this->journal->log($userId, 'document.depot', 'document', $result['document_id'], 'succes', [
            'titre' => $title,
            'version' => 1,
            'chunks' => $chunks,
            'sha256' => substr($result['sha256'], 0, 16),
            'taille_octets' => (int) ($upload['size'] ?? 0),
        ]);

        return ['document_id' => $result['document_id'], 'chunks' => $chunks, 'sha256' => $result['sha256']];
    }

    /**
     * Map an ingestion exception to a localized, user-facing message.
     *
     * @param array<string, string> $T API dictionary (app_i18n($lang)['api'])
     */
    public function errorMessage(\Throwable $e, array $T): string
    {
        $message = $e->getMessage();

        return match (true) {
            str_contains($message, 'Audio too long') => sprintf($T['audio_too_long'], intdiv($this->audioMaxSeconds, 60)),
            str_contains($message, 'Media could not be processed') => $T['media_failed'],
            str_contains($message, 'Transcription refused') => $T['transcription_refused'],
            str_contains($message, 'File empty or unreadable') => $T['file_empty'],
            default => getenv('APP_DEBUG') === '1' ? $message : $T['upload_failed'],
        };
    }

    private function extractAudio(string $path, string $extension): string
    {
        $splitter = new AudioSplitter();

        // Uploaded temp files have no extension (phpXXXX.tmp) — work on a copy
        // named with the real extension so duration() and split() recognise it.
        $workPath = $path . '.' . $extension;
        copy($path, $workPath);

        try {
            $duration = $splitter->duration($workPath);
            if ($duration === null || $duration <= $this->audioMaxSeconds) {
                return $this->transcription->transcribe($workPath);
            }

            // Long audio: split into ≤ max segments (WAV/MP3), transcribe each
            // one and concatenate — the file is never rejected outright.
            $parts = $splitter->split($workPath, $this->audioMaxSeconds);
            if ($parts === []) {
                // Format the splitter cannot cut (m4a/ogg/…): keep the guard.
                throw new \RuntimeException('Audio too long');
            }

            $segments = [];
            foreach ($parts as $i => $partPath) {
                $segments[] = '[Segment ' . ($i + 1) . ' / ' . count($parts) . "]\n"
                    . $this->transcription->transcribe($partPath);
                if (!in_array($partPath, [$path, $workPath], true)) {
                    @unlink($partPath);
                }
            }

            return implode("\n\n", $segments);
        } finally {
            @unlink($workPath);
        }
    }

    private function pdf(string $path): \Smalot\PdfParser\Document
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new \RuntimeException('PDF extraction requires smalot/pdfparser.');
        }

        return $this->pdfCache[$path] ??= (new \Smalot\PdfParser\Parser())->parseFile($path);
    }
}
