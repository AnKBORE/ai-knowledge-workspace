<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Mooré proverbs (40, KABORÉ Timothée / MooreBurkina) used as a cultural
 * reference in Mooré translation prompts.
 *
 * The model is told these are style references: it must not invent or insert
 * a proverb that is absent from the source text.
 */
final class Proverbes
{
    /** @var list<array{n: int, mo: string, fr: string}>|null */
    private static ?array $items = null;

    /** @return list<array{n: int, mo: string, fr: string}> */
    public static function all(): array
    {
        if (self::$items !== null) {
            return self::$items;
        }

        $path = dirname(__DIR__, 2) . '/data/proverbes_moore.json';
        if (!is_file($path)) {
            return self::$items = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return self::$items = is_array($decoded) ? array_values($decoded) : [];
    }

    public static function count(): int
    {
        return count(self::all());
    }

    /**
     * Compact style reference for a Mooré prompt (defaults to 8 proverbs).
     */
    public static function prompt(int $limit = 8): string
    {
        $items = array_slice(self::all(), 0, $limit);

        if ($items === []) {
            return '';
        }

        $lines = [];
        foreach ($items as $proverb) {
            $lines[] = '- ' . $proverb['mo'] . ' — ' . $proverb['fr'];
        }

        return "\n\nMooré proverbs (style reference only: never insert one unless the source contains it):\n"
            . implode("\n", $lines);
    }
}
