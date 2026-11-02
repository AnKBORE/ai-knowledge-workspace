<?php

declare(strict_types=1);

/**
 * Numeric coherence audit.
 *
 * The reference audit checks that what the documentation *names* exists. This
 * one checks that the numbers the documentation *states* are true right now,
 * measured against the database and against the test suite.
 *
 * It is deliberately strict on ETAT_DE_DEMO, which claims to describe the live
 * state, and tolerant on the register and the journal, where wrong numbers are
 * recorded history rather than claims.
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

$one = static function (PDO $pdo, string $sql): int {
    return (int) $pdo->query($sql)->fetchColumn();
};

$truth = [
    'documents' => $one($pdo, 'SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL'),
    'chunks' => $one($pdo, 'SELECT COUNT(*) FROM document_chunks'),
    'espaces' => $one($pdo, 'SELECT COUNT(*) FROM espace'),
    'comptes' => $one($pdo, 'SELECT COUNT(*) FROM utilisateur WHERE supprime_le IS NULL'),
    'proteges' => $one($pdo, 'SELECT COUNT(*) FROM utilisateur WHERE compte_super = 1'),
    'adhesions' => $one($pdo, 'SELECT COUNT(*) FROM adhesion'),
    'migrations' => $one($pdo, 'SELECT COUNT(*) FROM migrations'),
    'corrections' => $one($pdo, 'SELECT COUNT(*) FROM correction_document'),
];

echo "=== VALEURS REELLES (base) ===\n\n";

foreach ($truth as $key => $value) {
    printf("  %-14s %d\n", $key, $value);
}

// ---------------------------------------------------------------------------
// Claims: a number followed by a noun, in the state document.
// ---------------------------------------------------------------------------
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
    'migrations' => 'migrations',
    'corrections' => 'corrections',
];

$files = ['docs/ETAT_DE_DEMO.md', 'docs/ETAT_DE_DEMO.fr.md'];
$problems = [];
$read = 0;

foreach ($files as $file) {
    $path = $root . '/' . $file;
    $lines = file($path, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $index => $line) {
        foreach ($nouns as $noun => $key) {
            $pattern = '/(\d{1,3})\s+' . preg_quote($noun, '/') . '\b/iu';

            if (!preg_match_all($pattern, $line, $m)) {
                continue;
            }

            foreach ($m[1] as $claimed) {
                $read++;
                $value = (int) $claimed;

                // A line recording what an older state said is history, and a
                // line scoped to one profile or one workspace states a count
                // that is not the global one: both are skipped.
                if (preg_match('/removed|deleted|supprim|renamed|remplac|announced|P-\d+/i', $line)) {
                    continue;
                }

                if (preg_match('/Chantier test|Général|member|membre|anatole|workspace/iu', $line)) {
                    continue;
                }

                if ($value !== $truth[$key]) {
                    $problems[] = sprintf(
                        '%s:%d | annonce %d %s, la base dit %d',
                        $file,
                        $index + 1,
                        $value,
                        $noun,
                        $truth[$key]
                    );
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------
// The figures published about the test suite: config/app.php is the single
// source, and every page or document that quotes them must agree with it.
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
// their figures are history, not claims about the present. Current figures live
// in the README, the architecture, the guide, the demo state and the pages.
$published = array_values(array_filter(
    $published,
    static fn (string $file): bool => !preg_match('/JOURNAL_DE_CONSTRUCTION|REGISTRE_PROBLEMES/', $file)
));

$claims = 0;

foreach ($published as $file) {
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $file);
    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];

    foreach ($lines as $index => $line) {
        if (preg_match('/removed|deleted|supprim|renamed|remplac|announced|P-\d+|back to green/i', $line)) {
            continue;
        }

        if (preg_match_all('/(\d{2,4})\s+(?:automated\s+|automatisés\s+)?tests\b/i', $line, $m)) {
            foreach ($m[1] as $claimed) {
                $claims++;

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

        if (preg_match_all('/(\d{2,4})\s+assertions\b/i', $line, $m)) {
            foreach ($m[1] as $claimed) {
                $claims++;

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

echo "\n=== CHIFFRES AFFIRMÉS DANS ETAT_DE_DEMO ===\n\n";
printf("  affirmations lues : %d\n", $read);

echo "\n=== CHIFFRES DE LA SUITE DE TESTS ===\n\n";
printf("  configuration : %d tests, %d assertions\n", $expectedTests, $expectedAssertions);
printf("  affirmations lues : %d (pages et documents)\n", $claims);

if ($problems === []) {
    echo "\n  Tous les chiffres correspondent.\n";
    exit(0);
}

echo "\n  " . count($problems) . " écart(s) :\n";

foreach ($problems as $problem) {
    echo '    - ' . $problem . "\n";
}

exit(1);
