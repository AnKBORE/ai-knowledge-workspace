<?php

declare(strict_types=1);

/**
 * State coherence audit: the numbers the documentation states about the live
 * database must be the numbers the database holds.
 *
 * Two things are measured, and both are printed:
 *   - how many claims were verified (so the coverage is explicit);
 *   - how many numbers could not be verified automatically, because they are a
 *     duration, a score or a narrative figure.
 *
 * A "all figures match" line means every *checkable* claim was checked, never
 * that every number in the file was.
 */

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/core/Env.php';

\App\Core\Env::load($root . '/.env');

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_DATABASE')
    ),
    getenv('DB_USERNAME') ?: 'root',
    getenv('DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$one = static fn (string $sql): int => (int) $pdo->query($sql)->fetchColumn();

$truth = [
    'documents' => $one('SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL'),
    'chunks' => $one('SELECT COUNT(*) FROM document_chunks'),
    'espaces' => $one('SELECT COUNT(*) FROM espace'),
    'comptes' => $one('SELECT COUNT(*) FROM utilisateur WHERE supprime_le IS NULL'),
    'proteges' => $one('SELECT COUNT(*) FROM utilisateur WHERE compte_super = 1'),
    'adhesions' => $one('SELECT COUNT(*) FROM adhesion'),
    'migrations' => $one('SELECT COUNT(*) FROM migrations'),
    'corrections' => $one('SELECT COUNT(*) FROM correction_document'),
    'versions' => $one('SELECT COUNT(*) FROM version_document'),
    'categories' => $one('SELECT COUNT(*) FROM categorie'),
];

// A document row in the state table: | # | Title | Type | lang | Spaces | Chunks | Proof |
$documents = [];

foreach ($pdo->query('SELECT d.id, d.langue, (SELECT COUNT(*) FROM document_chunks c WHERE c.document_id = d.id) AS chunks FROM documents d WHERE d.supprime_le IS NULL ORDER BY d.id') as $row) {
    $documents[(int) $row['id']] = [
        'langue' => (string) $row['langue'],
        'chunks' => (int) $row['chunks'],
    ];
}

$nouns = [
    'documents' => 'documents',
    'chunks' => 'chunks',
    'workspaces' => 'espaces',
    'workspace' => 'espaces',
    'espaces' => 'espaces',
    'accounts' => 'comptes',
    'comptes' => 'comptes',
    'memberships' => 'adhesions',
    'adhésions' => 'adhesions',
    'adhesions' => 'adhesions',
    'migrations' => 'migrations',
    'corrections' => 'corrections',
    'versions' => 'versions',
    'categories' => 'categories',
    'catégories' => 'categories',
];

$files = ['docs/ETAT_DE_DEMO.md', 'docs/ETAT_DE_DEMO.fr.md'];
$problems = [];
$verified = 0;
$excluded = 0;
$numbers = 0;

foreach ($files as $file) {
    $lines = file($root . '/' . $file, FILE_IGNORE_NEW_LINES) ?: [];

    foreach ($lines as $index => $line) {
        $numbers += preg_match_all('/\b\d{1,4}\b/', $line);

        // A claim is scoped only when the line is genuinely about one workspace
        // or one profile — not merely because it mentions the word. Excluding
        // whole lines on a keyword silenced 100 % of the document once.
        $scoped = preg_match('/^\|\s*\*\*(?:Général|Chantier test)\*\*/u', $line) === 1
            || preg_match('/(?:member|membre|anatole|visibles?|sees|voit)[^.]{0,40}\d{1,3}\s+(?:documents|chunks)/iu', $line) === 1;

        $historical = preg_match('/removed|deleted|supprim|renamed|remplac|announced|P-\d+/i', $line) === 1;

        if ($scoped || $historical) {
            $excluded += preg_match_all('/\d{1,3}\s+(?:' . implode('|', array_map(static fn (string $n): string => preg_quote($n, '/'), array_keys($nouns))) . ')\b/iu', $line);

            continue;
        }

        // 1. "N noun" claims.
        foreach ($nouns as $noun => $key) {
            if (preg_match_all('/(\d{1,3})\s+' . preg_quote($noun, '/') . '\b/iu', $line, $m) === 0) {
                continue;
            }

            foreach ($m[1] as $claimed) {
                $verified++;

                if ((int) $claimed !== $truth[$key]) {
                    $problems[] = sprintf(
                        '%s:%d | annonce %s %s, la base dit %d',
                        $file,
                        $index + 1,
                        $claimed,
                        $noun,
                        $truth[$key]
                    );
                }
            }
        }

        // 2. A document row: its chunk count and its language.
        if (preg_match('/^\|\s*(\d+)\s*\|([^|]+)\|([^|]+)\|([^|]*)\|([^|]*)\|\s*(\d+)\s*\|/', $line, $m) === 1) {
            $documentId = (int) $m[1];

            if (isset($documents[$documentId])) {
                $verified++;
                $claimedChunks = (int) $m[6];

                if ($claimedChunks !== $documents[$documentId]['chunks']) {
                    $problems[] = sprintf(
                        '%s:%d | document %d annonce %d chunk(s), la base dit %d',
                        $file,
                        $index + 1,
                        $documentId,
                        $claimedChunks,
                        $documents[$documentId]['chunks']
                    );
                }

                $claimedLang = trim($m[4], " *`\t");

                if ($claimedLang !== '' && $claimedLang !== $documents[$documentId]['langue']) {
                    $problems[] = sprintf(
                        '%s:%d | document %d annoncé en « %s », la base dit « %s »',
                        $file,
                        $index + 1,
                        $documentId,
                        $claimedLang,
                        $documents[$documentId]['langue']
                    );
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------
// The figures about the test suite: config/app.php is the single source, and
// every page or document that quotes them must agree with it.
// ---------------------------------------------------------------------------
$config = require $root . '/config/app.php';
$expectedTests = (int) ($config['tests']['count'] ?? 0);
$expectedAssertions = (int) ($config['tests']['assertions'] ?? 0);

$published = array_merge(
    glob($root . '/views/*.php') ?: [],
    glob($root . '/views/*/*.php') ?: [],
    glob($root . '/*.md') ?: [],
    glob($root . '/docs/*.md') ?: []
);

// The build journal and the defect register record what was true at a date:
// their figures are history, not claims about the present.
$published = array_values(array_filter(
    $published,
    static fn (string $file): bool => preg_match('/JOURNAL_DE_CONSTRUCTION|REGISTRE_PROBLEMES/', $file) !== 1
));

$suiteClaims = 0;

foreach ($published as $file) {
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $file);
    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];

    foreach ($lines as $index => $line) {
        if (preg_match('/removed|deleted|supprim|renamed|remplac|announced|P-\d+/i', $line)) {
            continue;
        }

        if (preg_match_all('/(\d{2,4})\s+(?:automated\s+|automatisés\s+)?tests\b/i', $line, $m) > 0) {
            foreach ($m[1] as $claimed) {
                $suiteClaims++;

                if ((int) $claimed !== $expectedTests) {
                    $problems[] = sprintf(
                        '%s:%d | annonce %s tests, la configuration dit %d',
                        $relative,
                        $index + 1,
                        $claimed,
                        $expectedTests
                    );
                }
            }
        }

        if (preg_match_all('/(\d{2,4})\s+assertions\b/i', $line, $m) > 0) {
            foreach ($m[1] as $claimed) {
                $suiteClaims++;

                if ((int) $claimed !== $expectedAssertions) {
                    $problems[] = sprintf(
                        '%s:%d | annonce %s assertions, la configuration dit %d',
                        $relative,
                        $index + 1,
                        $claimed,
                        $expectedAssertions
                    );
                }
            }
        }
    }
}

echo "=== VALEURS RÉELLES (base) ===\n\n";

foreach ($truth as $key => $value) {
    printf("  %-12s %d\n", $key, $value);
}

printf("\n  suite de tests (config/app.php) : %d tests, %d assertions\n", $expectedTests, $expectedAssertions);

echo "\n=== AFFIRMATIONS NUMÉRIQUES ===\n\n";
printf("  vérifiées              : %d\n", $verified);
printf("  exclues (espace/profil/histoire) : %d\n", $excluded);
printf("  vérifiables au total   : %d\n", $verified + $excluded);
printf("  nombres dans le document : %d\n", $numbers);
printf(
    "  non vérifiables        : %d (dates, durées, scores, montants)\n",
    $numbers - $verified - $excluded
);
printf("  chiffres de la suite cités (pages + docs) : %d\n", $suiteClaims);

if ($problems === []) {
    echo "\n  Toutes les affirmations vérifiables correspondent.\n";
    exit(0);
}

echo "\n  " . count($problems) . " écart(s) :\n";

foreach ($problems as $problem) {
    echo '    - ' . $problem . "\n";
}

exit(1);
