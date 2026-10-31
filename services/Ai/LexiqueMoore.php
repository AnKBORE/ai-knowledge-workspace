<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Mooré lexicon loaded from `data/lexique_moore.json` (SIL/Webonary thematic
 * word lists: body, food, plants, animals — 700+ entries).
 *
 * Used to inject concrete, verified vocabulary into translation prompts so the
 * model does not invent everyday nouns.
 */
final class LexiqueMoore
{
    /** @var list<array{mo: string, en: string, fr: string, theme: string}>|null */
    private static ?array $entries = null;

    /** @return list<array{mo: string, en: string, fr: string, theme: string}> */
    public static function entries(): array
    {
        if (self::$entries !== null) {
            return self::$entries;
        }

        $path = dirname(__DIR__, 2) . '/data/lexique_moore.json';
        if (!is_file($path)) {
            return self::$entries = [];
        }

        $raw = file_get_contents($path);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        return self::$entries = is_array($decoded)
            ? array_values(array_filter($decoded, static fn ($row): bool => is_array($row) && isset($row['mo'])))
            : [];
    }

    public static function count(): int
    {
        return count(self::entries());
    }

    /**
     * Find lexicon terms present in the source text and render a compact
     * "term → translation" block for the prompt.
     */
    public static function promptFor(string $text, string $from, string $to): string
    {
        $sourceField = in_array($from, ['fr', 'en', 'mo'], true) ? $from : 'fr';
        $targetField = in_array($to, ['fr', 'en', 'mo'], true) ? $to : 'fr';

        if ($sourceField === $targetField) {
            return '';
        }

        $matches = [];
        $entries = self::entries();

        foreach ($entries as $entry) {
            $term = trim((string) ($entry[$sourceField] ?? ''));
            $translation = trim((string) ($entry[$targetField] ?? ''));

            if ($term === '' || $translation === '' || $sourceField === 'mo' && $translation === '') {
                continue;
            }

            $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($term, '/') . '(?![\p{L}\p{N}])/iu';

            if (preg_match($pattern, $text) === 1) {
                $key = mb_strtolower($term);
                if (!isset($matches[$key])) {
                    $matches[$key] = $term . ' → ' . $translation;
                }
            }
        }

        if ($matches === []) {
            return '';
        }

        // Longest terms first (most specific), capped for prompt size.
        $values = array_values($matches);
        usort($values, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return "\n\nVerified vocabulary from the SIL/Mooré lexicon (use these exact words when they appear):\n- "
            . implode("\n- ", array_slice($values, 0, 30));
    }
}
