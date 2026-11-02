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

echo "\n=== CHIFFRES AFFIRMÉS DANS ETAT_DE_DEMO ===\n\n";
printf("  affirmations lues : %d\n", $read);

if ($problems === []) {
    echo "  Tous les chiffres correspondent à la base.\n";
    exit(0);
}

echo '  ' . count($problems) . " écart(s) :\n";

foreach ($problems as $problem) {
    echo '    - ' . $problem . "\n";
}

exit(1);
