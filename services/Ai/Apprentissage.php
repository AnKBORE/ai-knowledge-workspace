<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Correction;

/**
 * Learning layer — "the system enriches its vocabulary and expressions from
 * human corrections".
 *
 * This is retrieval-based learning, not fine-tuning: validated corrections are
 * indexed, and the ones closest to the text being processed are injected into
 * the prompt as style examples. The model reuses the approved phrasing, so the
 * output drifts towards the vocabulary the organisation actually uses.
 */
final class Apprentissage
{
    public function __construct(private readonly Correction $corrections)
    {
    }

    public function count(): int
    {
        return $this->corrections->count();
    }

    /**
     * Few-shot block built from corrections sharing vocabulary with $text.
     * Returns '' when nothing relevant was learned yet.
     */
    public function exemples(string $text, string $langue, int $limit = 3): string
    {
        if ($limit < 1 || $text === '') {
            return '';
        }

        $corrections = $this->corrections->validForLangue($langue, 200);

        if ($corrections === []) {
            return '';
        }

        $needles = $this->keywords($text);
        if ($needles === []) {
            return '';
        }

        $scored = [];
        foreach ($corrections as $row) {
            $haystack = mb_strtolower((string) ($row['texte_corrige'] ?? '') . ' ' . (string) ($row['texte_source'] ?? ''));
            $score = 0;
            foreach ($needles as $needle) {
                if (mb_strpos($haystack, $needle) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scored[] = [$score, (string) ($row['texte_corrige'] ?? '')];
            }
        }

        if ($scored === []) {
            return '';
        }

        usort($scored, static fn (array $a, array $b): int => $b[0] <=> $a[0]);
        $examples = [];
        foreach (array_slice($scored, 0, $limit) as [, $corrected]) {
            $corrected = trim(preg_replace('/\s+/u', ' ', $corrected) ?? '');
            if ($corrected === '') {
                continue;
            }
            $examples[] = '- ' . mb_substr($corrected, 0, 600);
        }

        if ($examples === []) {
            return '';
        }

        return "\n\nValidated phrasings from this organisation (human-corrected, reuse their vocabulary and tone):\n"
            . implode("\n", $examples)
            . "\nFollow this style when the content is similar; never invent facts from it.";
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
        $stop = ['the', 'and', 'for', 'with', 'that', 'this', 'les', 'des', 'une', 'que', 'qui', 'dans', 'pour', 'avec', 'est', 'sont', 'sur', 'pas', 'plus', 'aux', 'par', 'nous', 'vous'];

        $out = [];
        foreach ($words as $word) {
            if (mb_strlen($word) < 5 || in_array($word, $stop, true)) {
                continue;
            }
            $out[$word] = true;
        }

        return array_slice(array_keys($out), 0, 60);
    }
}
