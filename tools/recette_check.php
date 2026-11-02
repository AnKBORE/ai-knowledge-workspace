<?php

declare(strict_types=1);

/**
 * Snapshot for the acceptance run.
 *
 * Prints the state a reviewer needs after each manual step: row counts, the
 * latest journal entries, and the stored files. Read-only.
 *
 * Usage:
 *   php tools/recette_check.php
 */

require dirname(__DIR__) . '/core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_DATABASE') ?: 'ai_knowledge_workspace',
        getenv('DB_CHARSET') ?: 'utf8mb4',
    ),
    getenv('DB_USERNAME') ?: 'root',
    getenv('DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);

$one = static function (PDO $pdo, string $sql): int {
    return (int) $pdo->query($sql)->fetchColumn();
};

$counts = [
    'migrations' => $one($pdo, 'SELECT COUNT(*) FROM migrations'),
    'comptes' => $one($pdo, 'SELECT COUNT(*) FROM utilisateur WHERE supprime_le IS NULL'),
    'protégés' => $one($pdo, 'SELECT COUNT(*) FROM utilisateur WHERE compte_super = 1'),
    'espaces' => $one($pdo, 'SELECT COUNT(*) FROM espace'),
    'adhésions' => $one($pdo, 'SELECT COUNT(*) FROM adhesion'),
    'documents' => $one($pdo, 'SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL'),
    'versions' => $one($pdo, 'SELECT COUNT(*) FROM version_document'),
    'chunks' => $one($pdo, 'SELECT COUNT(*) FROM document_chunks'),
    'traductions' => $one($pdo, 'SELECT COUNT(*) FROM document_translations'),
    'corrections' => $one($pdo, 'SELECT COUNT(*) FROM correction_document'),
    'catégories' => $one($pdo, 'SELECT COUNT(*) FROM categorie'),
    'journal' => $one($pdo, 'SELECT COUNT(*) FROM journal'),
];

echo "\n=== ÉTAT ===\n\n";

foreach ($counts as $label => $value) {
    printf("  %-14s %s\n", $label, $value);
}

// Fichiers stockés
$files = glob(dirname(__DIR__) . '/storage/files/*');
$files = array_filter($files, static fn (string $f): bool => is_file($f) && basename($f) !== '.gitkeep');
$bytes = 0;

foreach ($files as $file) {
    $bytes += (int) filesize($file);
}

printf("  %-14s %d (%s Mo)\n", 'fichiers', count($files), number_format($bytes / 1048576, 1, ',', ' '));

// Dernières entrées du journal
echo "\n=== DERNIÈRES ACTIONS ===\n\n";

$entries = $pdo->query(
    'SELECT j.id, j.action, j.resultat, j.objet_type, j.objet_id, j.horodatage, u.courriel
     FROM journal j LEFT JOIN utilisateur u ON u.id = j.acteur_id
     ORDER BY j.id DESC LIMIT 8'
)->fetchAll();

if ($entries === []) {
    echo "  (aucune)\n";
}

foreach (array_reverse($entries) as $e) {
    printf(
        "  %s  %-28s %-8s %s%s  %s\n",
        substr((string) $e['horodatage'], 11, 8),
        $e['action'],
        $e['resultat'],
        $e['objet_type'] ?? '-',
        $e['objet_id'] === null ? '' : '#' . $e['objet_id'],
        $e['courriel'] ?? 'anonyme',
    );
}

echo "\n";
