<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Correction;

/**
 * Learning layer — the system enriches its vocabulary and expressions from the
 * corrections uploaded by authors and native-speaker reviewers.
 *
 * Each uploaded correction is already trusted (whoever uploads it masters the
 * language or owns the document), so no approval step is involved: it becomes
 * usable immediately and can be removed at any time from the documents page.
 *
 * This is retrieval-based learning, not fine-tuning. The scoring is an IDF
 * weighted overlap: a term that appears in few corrections (e.g. "vicariat")
 * weighs far more than a generic one ("projet"), so the examples injected in
 * the prompt are the ones sharing the *specific* vocabulary of the text.
 */
final class Apprentissage
{
    /** Hard ceilings, so a large corpus never floods the prompt. */
    private const SCAN_LIMIT = 500;
    private const MAX_EXAMPLES = 6;
    private const CHAR_BUDGET = 2600;
    private const EXCERPT_CHARS = 480;

    /** @var list<string> */
    private const STOP_WORDS = [
        'the', 'and', 'for', 'with', 'that', 'this', 'from', 'have', 'they', 'will',
        'les', 'des', 'une', 'que', 'qui', 'dans', 'pour', 'avec', 'est', 'sont',
        'sur', 'pas', 'plus', 'aux', 'par', 'nous', 'vous', 'mais', 'leur', 'cette',
        'leur', 'tout', 'tous', 'elle', 'ils', 'elles', 'aussi', 'comme', 'sans',
    ];

    public function __construct(private readonly Correction $corrections)
    {
    }

    public function count(): int
    {
        return $this->corrections->count();
    }

    /**
     * Few-shot block built from the corrections closest to $text.
     * Returns '' when nothing relevant has been learned yet.
     */
    public function exemples(string $text, string $langue, int $maxExamples = self::MAX_EXAMPLES): string
    {
        if ($text === '' || $maxExamples < 1) {
            return '';
        }

        $rows = $this->corrections->validForLangue($langue, self::SCAN_LIMIT);

        if ($rows === []) {
            return '';
        }

        $needles = $this->keywords($text);

        if ($needles === []) {
            return '';
        }

        $total = count($rows);

        // Document frequency of every queried term across the correction corpus.
        $corpus = [];
        $df = [];

        foreach ($rows as $index => $row) {
            $corpus[$index] = mb_strtolower(
                (string) ($row['texte_corrige'] ?? '') . ' ' . (string) ($row['texte_source'] ?? '')
            );

            foreach ($needles as $needle) {
                if (mb_strpos($corpus[$index], $needle) !== false) {
                    $df[$needle] = ($df[$needle] ?? 0) + 1;
                }
            }
        }

        // Score: IDF-weighted overlap + a small recency bonus (rows are id DESC).
        $scored = [];

        foreach ($rows as $index => $row) {
            $score = 0.0;
            $hits = 0;

            foreach ($needles as $needle) {
                if (mb_strpos($corpus[$index], $needle) === false) {
                    continue;
                }

                $score += log((1 + $total) / (1 + ($df[$needle] ?? 1)));
                $hits++;
            }

            if ($hits === 0) {
                continue;
            }

            $recency = 1.0 - ($index / max(1, $total));
            $score += $recency * 0.35;

            // A correction sharing several specific terms is worth more.
            $score *= 1 + (min($hits, 6) / 20);

            $scored[] = [
                'score' => $score,
                'document_id' => (int) ($row['document_id'] ?? 0),
                'corrected' => trim((string) ($row['texte_corrige'] ?? '')),
            ];
        }

        if ($scored === []) {
            return '';
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        // One example per document (a single document must not dominate), and
        // never the same wording twice.
        $picked = [];
        $seenDocs = [];
        $seenText = [];
        $budget = self::CHAR_BUDGET;

        foreach ($scored as $candidate) {
            if (count($picked) >= $maxExamples || $budget <= 0) {
                break;
            }

            $corrected = $candidate['corrected'];

            if ($corrected === '') {
                continue;
            }

            if (isset($seenDocs[$candidate['document_id']]) && $candidate['document_id'] > 0) {
                continue;
            }

            $signature = mb_substr(mb_strtolower($corrected), 0, 90);

            if (isset($seenText[$signature])) {
                continue;
            }

            $excerpt = $this->excerpt($corrected, $needles, self::EXCERPT_CHARS);

            if ($excerpt === '') {
                continue;
            }

            if (mb_strlen($excerpt) > $budget) {
                $excerpt = mb_substr($excerpt, 0, $budget) . '…';
            }

            $budget -= mb_strlen($excerpt);
            $picked[] = $excerpt;
            $seenText[$signature] = true;

            if ($candidate['document_id'] > 0) {
                $seenDocs[$candidate['document_id']] = true;
            }
        }

        if ($picked === []) {
            return '';
        }

        return "\n\nValidated phrasings from this organisation (human-corrected, reuse their vocabulary and tone):\n- "
            . implode("\n- ", $picked)
            . "\nFollow this style when the content is similar; never invent facts from it.";
    }

    /**
     * Keep the part of the correction around the first matching term, so the
     * example stays short and relevant.
     *
     * @param list<string> $needles
     */
    private function excerpt(string $corrected, array $needles, int $length): string
    {
        $flat = trim((string) preg_replace('/\s+/u', ' ', $corrected));

        if (mb_strlen($flat) <= $length) {
            return $flat;
        }

        $lower = mb_strtolower($flat);
        $position = null;

        foreach ($needles as $needle) {
            $found = mb_strpos($lower, $needle);

            if ($found !== false && ($position === null || $found < $position)) {
                $position = $found;
            }
        }

        $start = $position === null ? 0 : max(0, $position - (int) ($length / 3));

        return '…' . mb_substr($flat, $start, $length) . '…';
    }

    /**
     * Content words of a text, used for lexical matching.
     *
     * @return list<string>
     */
    private function keywords(string $text): array
    {
        $clean = mb_strtolower((string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text));
        $words = preg_split('/\s+/u', $clean) ?: [];

        $out = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 5 || in_array($word, self::STOP_WORDS, true)) {
                continue;
            }

            $out[$word] = true;
        }

        return array_slice(array_keys($out), 0, 60);
    }
}
