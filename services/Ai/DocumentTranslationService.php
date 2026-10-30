<?php

declare(strict_types=1);

namespace App\Services\Ai;

use PDO;

/**
 * Segment-by-segment document translation — powers the live audio translation
 * in the documents modal (the transcript streams in while the audio plays) and
 * the PDF/DOCX export of a full translation.
 *
 * Source text is rebuilt from `document_chunks` (which overlap by design), the
 * appended "[Traduction — …]" tail is stripped, and the text is re-split into
 * ~700-char sentence-aligned segments. Each translated segment is cached in
 * `document_translations` so replays and exports cost zero model calls.
 */
final class DocumentTranslationService
{
    public const LANGUAGES = ['fr' => 'French', 'en' => 'English', 'mo' => 'Mooré (Mossi, Burkina Faso)'];

    /** Timed-segment cache keys are offset to never collide with text segments. */
    private const TIMED_OFFSET = 1_000_000;

    public function __construct(
        private readonly PDO $pdo,
        private readonly MetaClient $client,
        private readonly ?string $transcriptCacheDir = null,
    ) {
    }

    /**
     * Rebuild the document's extracted text and split it into translation
     * segments. Returns an empty list when the document has no indexed text.
     *
     * @return list<string>
     */
    public function segments(int $documentId, int $versionId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT content FROM document_chunks
             WHERE document_id = :doc AND version_document_id = :ver
             ORDER BY chunk_index'
        );
        $stmt->execute(['doc' => $documentId, 'ver' => $versionId]);
        $chunks = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($chunks === []) {
            return [];
        }

        $text = $this->mergeChunks(array_map('strval', $chunks));

        // An audio transcript may carry an appended machine translation block —
        // translating it again would duplicate content in the target language.
        $text = (string) preg_replace('/\R\[[^\]\r\n]*(?:Traduction|Translation)[^\]\r\n]*\][\s\S]*$/u', '', $text);
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        return $this->splitSegments($text);
    }

    /**
     * Translate segment $index of the document into $lang ('fr'|'en'|'mo'),
     * using the SQL cache when available. Returns the translated text.
     */
    public function translateSegment(int $documentId, int $versionId, string $lang, int $index): string
    {
        $cached = $this->cached($documentId, $versionId, $lang, $index);
        if ($cached !== null) {
            return $cached;
        }

        $segments = $this->segments($documentId, $versionId);
        if (!isset($segments[$index])) {
            return '';
        }

        $translation = $this->translateText($segments[$index], $lang);

        if ($translation !== '') {
            $ins = $this->pdo->prepare(
                'INSERT IGNORE INTO document_translations
                    (document_id, version_document_id, lang, segment_index, translation)
                 VALUES (:doc, :ver, :lang, :seg, :txt)'
            );
            $ins->execute([
                'doc' => $documentId, 'ver' => $versionId,
                'lang' => $lang, 'seg' => $index, 'txt' => $translation,
            ]);
        }

        return $translation;
    }

    /**
     * All translated segments for (document, version, lang), translated on
     * demand in order — used by the export endpoint. Returns '' entries for
     * segments that could not be translated.
     *
     * @return list<string>
     */
    public function translateAll(int $documentId, int $versionId, string $lang): array
    {
        $segments = $this->segments($documentId, $versionId);
        $out = [];

        foreach (array_keys($segments) as $i) {
            try {
                $out[] = $this->translateSegment($documentId, $versionId, $lang, (int) $i);
            } catch (\Throwable) {
                $out[] = '';
            }
        }

        return $out;
    }

    /**
     * Timestamped speech segments for an audio document — whisper.cpp -oj,
     * cached as JSON so the timing pass runs only once per version.
     *
     * @return list<array{start:int, end:int, text:string}>
     */
    public function timedSegments(int $documentId, int $versionId, string $audioPath): array
    {
        if ($this->transcriptCacheDir === null) {
            throw new \RuntimeException('No transcript cache directory configured.');
        }

        @mkdir($this->transcriptCacheDir, 0777, true);
        $base = $this->transcriptCacheDir . '/' . $documentId . '_' . $versionId;
        $merged = $base . '.segments.json';

        if (is_file($merged)) {
            $segs = json_decode((string) file_get_contents($merged), true);
            if (is_array($segs)) {
                return $segs;
            }
        }

        // whisper-cli writes the timed JSON straight to the cache path.
        $segs = $this->client->transcribeTimedLocal($audioPath, $base);
        $segs = $this->mergeTinySegments($segs);

        file_put_contents($merged, json_encode($segs, JSON_UNESCAPED_UNICODE));

        return $segs;
    }

    /**
     * Translate timed segment $index; cached under a namespaced index so it
     * never collides with the plain text-segment translations.
     */
    public function translateTimedSegment(int $documentId, int $versionId, string $lang, int $index, array $segments): string
    {
        if (!isset($segments[$index])) {
            return '';
        }

        $cacheIndex = self::TIMED_OFFSET + $index;
        $cached = $this->cached($documentId, $versionId, $lang, $cacheIndex);
        if ($cached !== null) {
            return $cached;
        }

        $translation = $this->translateText($segments[$index]['text'], $lang);

        if ($translation !== '') {
            $ins = $this->pdo->prepare(
                'INSERT IGNORE INTO document_translations
                    (document_id, version_document_id, lang, segment_index, translation)
                 VALUES (:doc, :ver, :lang, :seg, :txt)'
            );
            $ins->execute([
                'doc' => $documentId, 'ver' => $versionId,
                'lang' => $lang, 'seg' => $cacheIndex, 'txt' => $translation,
            ]);
        }

        return $translation;
    }

    /** Merge ultra-short whisper segments (< 600 ms) into the next one. */
    private function mergeTinySegments(array $segments): array
    {
        $out = [];
        foreach ($segments as $seg) {
            if ($out !== [] && ($seg['end'] - $seg['start']) < 600 && mb_strlen($seg['text']) < 12) {
                $last = count($out) - 1;
                $out[$last]['end'] = max($out[$last]['end'], $seg['end']);
                $out[$last]['text'] .= ' ' . $seg['text'];
            } else {
                $out[] = $seg;
            }
        }
        return $out;
    }

    private function translateText(string $source, string $lang): string
    {
        $needsMoore = $lang === 'mo' || $this->looksMoore($source);

        $system = sprintf(
            'You are a professional translator for French, English and Mooré (Mossi, Burkina Faso). '
            . 'Translate this audio-transcript excerpt faithfully into %s. The text may contain ASR '
            . 'mishearings: silently reconstruct the intended words when they are phonetically close, '
            . 'then translate. Keep names, figures and dates exact. Output ONLY the final translation, '
            . 'decisively: no comments, no notes, no alternatives, no uncertainty markers — commit to '
            . 'the single most likely meaning.%s',
            self::LANGUAGES[$lang] ?? $lang,
            $needsMoore ? "\n\n" . MooreReference::prompt() : '',
        );

        return trim($this->client->chat($source, [['role' => 'system', 'content' => $system]], null, 0.1));
    }

    private function cached(int $documentId, int $versionId, string $lang, int $index): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT translation FROM document_translations
             WHERE document_id = :doc AND version_document_id = :ver
               AND lang = :lang AND segment_index = :seg'
        );
        $stmt->execute(['doc' => $documentId, 'ver' => $versionId, 'lang' => $lang, 'seg' => $index]);
        $row = $stmt->fetch(PDO::FETCH_COLUMN);

        return $row === false ? null : (string) $row;
    }

    /** Merge overlapping chunks back into the original continuous text. */
    private function mergeChunks(array $chunks): string
    {
        $text = array_shift($chunks) ?? '';

        foreach ($chunks as $chunk) {
            $overlap = min(200, min(strlen($text), strlen($chunk)));
            $added = false;
            for ($k = $overlap; $k >= 20; $k -= 5) {
                if (substr($text, -$k) === substr($chunk, 0, $k)) {
                    $text .= substr($chunk, $k);
                    $added = true;
                    break;
                }
            }
            if (!$added) {
                $text .= ' ' . $chunk;
            }
        }

        return $text;
    }

    /** @return list<string> */
    private function splitSegments(string $text, int $size = 700): array
    {
        $segments = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $size, $length);
            if ($end < $length) {
                $window = mb_substr($text, $start, $size);
                // Prefer a sentence end, then any space — never mid-word.
                $cut = max(
                    mb_strrpos($window, '. ') ?: -1,
                    mb_strrpos($window, '! ') ?: -1,
                    mb_strrpos($window, '? ') ?: -1,
                    mb_strrpos($window, "\n") ?: -1,
                );
                if ($cut > (int) ($size * 0.5)) {
                    $end = $start + $cut + 1;
                } else {
                    $space = mb_strrpos($window, ' ');
                    if ($space !== false && $space > (int) ($size * 0.6)) {
                        $end = $start + $space;
                    }
                }
            }
            $seg = trim(mb_substr($text, $start, $end - $start));
            if ($seg !== '') {
                $segments[] = $seg;
            }
            $start = $end;
        }

        return $segments;
    }

    private function looksMoore(string $text): bool
    {
        return preg_match('/[ɩʋɛɔãẽĩõũñ]/u', $text) === 1
            || preg_match('/\b(kibar|rãmba|yibeoog|wẽnd|tõnd|yãmb|bãmb|sõma|laafi|goma|kẽnga|zaka)\b/iu', $text) === 1;
    }
}
