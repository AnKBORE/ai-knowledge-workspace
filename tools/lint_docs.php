<?php

declare(strict_types=1);

/**
 * Documentation linter.
 *
 * Checks the Markdown files for defects that a reader (or a jury) would notice:
 * headings glued to the previous line, tables with an inconsistent column count,
 * leftover template placeholders, and generator artefacts.
 *
 * Usage: php tools/lint_docs.php
 * Exit code 0 = clean, 1 = issues found.
 */

$root = dirname(__DIR__);
$files = array_merge(glob($root . '/README*.md'), glob($root . '/docs/*.md'));

$issues = [];

foreach ($files as $file) {
    $name = str_replace($root . '/', '', $file);
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $inCode = false;
    $expectedCols = null;

    foreach ($lines as $i => $line) {
        $n = $i + 1;
        $trim = trim($line);

        if (preg_match('/^```/', $trim)) {
            $inCode = !$inCode;
            $expectedCols = null;
            continue;
        }

        if ($inCode) {
            continue;
        }

        // Remove inline code spans: a backslash inside `App\Core\` is legitimate.
        $outside = preg_replace('/`[^`]*`/', '', $line);

        // 1. Backslash-escaped markdown characters outside code spans.
        if (preg_match('/\\\\[`*_\[\]#]/', $outside)) {
            $issues[] = "$name:$n  échappement parasite : " . mb_substr($trim, 0, 70);
        }

        // 2. Template residue (real placeholders, not prose about TODO markers).
        foreach (['%d', '{$', '$P[', '*(à compléter)*', '*(earlier)*'] as $needle) {
            if (str_contains($line, $needle)) {
                $issues[] = "$name:$n  résidu « $needle » : " . mb_substr($trim, 0, 70);
            }
        }

        // 3. Heading glued to the previous paragraph.
        if (str_starts_with($trim, '#') && $i > 0) {
            $prev = trim($lines[$i - 1]);

            if ($prev !== '' && !str_starts_with($prev, '#') && !preg_match('/^([-*]|\d+\.|>|\||:::)/', $prev)) {
                $issues[] = "$name:$n  titre collé à la ligne précédente : " . mb_substr($trim, 0, 50);
            }
        }

        // 4. Table column count, counting only unescaped pipes.
        if (str_starts_with($trim, '|')) {
            $cols = substr_count(preg_replace('/\\\\\|/', '', $trim), '|');

            if (preg_match('/^\|[\s:|-]+\|/', $trim)) {
                $expectedCols = $cols;
            } elseif ($expectedCols !== null && $cols !== $expectedCols) {
                $issues[] = "$name:$n  tableau : $cols colonnes au lieu de $expectedCols";
            } elseif ($expectedCols === null) {
                $expectedCols = $cols;
            }
        } else {
            $expectedCols = null;
        }
    }

    if (preg_match_all("/\n{4,}/", implode("\n", $lines), $m)) {
        $issues[] = "$name  " . count($m[0]) . " bloc(s) de lignes vides excessives";
    }
}

if ($issues === []) {
    echo "Documentation : aucun defaut structurel\n";
    exit(0);
}

echo count($issues) . " defaut(s) :\n";

foreach ($issues as $issue) {
    echo "  $issue\n";
}

exit(1);
