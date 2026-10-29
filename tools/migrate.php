<?php

declare(strict_types=1);

/**
 * Minimal migration runner (self-contained — no Composer needed).
 *
 * Usage:
 *   php tools/migrate.php migrate     # apply pending migrations
 *   php tools/migrate.php status      # list applied / pending
 *   php tools/migrate.php rollback    # roll back the last applied migration
 *   php tools/migrate.php fresh       # roll back all, then re-apply (dev only)
 */

require dirname(__DIR__) . '/core/Env.php';

\App\Core\Env::load(dirname(__DIR__) . '/.env');

function migrationName(string $file): string
{
    return basename($file, '.up.sql');
}

function runSql(PDO $pdo, string $file): void
{
    $sql = file_get_contents($file);
    if ($sql !== false && trim($sql) !== '') {
        $pdo->exec($sql);
    }
}

/** @return list<string> */
function appliedList(PDO $pdo): array
{
    return $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
}

/** @param list<string> $upFiles */
function applyPending(PDO $pdo, array $upFiles): int
{
    $applied = appliedList($pdo);
    $count = 0;

    foreach ($upFiles as $file) {
        $migration = migrationName($file);
        if (in_array($migration, $applied, true)) {
            continue;
        }
        runSql($pdo, $file);
        $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)')->execute([$migration]);
        $count++;
        echo "  applied: {$migration}\n";
    }

    return $count;
}

$database = getenv('DB_DATABASE') ?: 'ai_knowledge_workspace';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$server = sprintf('mysql:host=%s;port=%s;charset=%s', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '3306', $charset);
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    // Create the database on first install (fresh clone), then connect to it.
    $pdo = new PDO($server, getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '', $options);
    if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
        throw new PDOException('Invalid DB_DATABASE name: ' . $database);
    }
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$database}`");
} catch (PDOException $e) {
    fwrite(STDERR, "Database connection failed. Check DB_* settings in .env and that MySQL is running.\n" . $e->getMessage() . "\n");
    exit(1);
}

$dir = dirname(__DIR__) . '/migrations';

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration VARCHAR(255) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$upFiles = glob($dir . '/*.up.sql') ?: [];
sort($upFiles, SORT_STRING);

$command = $argv[1] ?? 'migrate';

switch ($command) {
    case 'migrate':
        $count = applyPending($pdo, $upFiles);
        echo $count === 0 ? "Nothing to migrate.\n" : "Migrated {$count} migration(s).\n";
        break;

    case 'status':
        foreach ($upFiles as $file) {
            $migration = migrationName($file);
            $mark = in_array($migration, appliedList($pdo), true) ? 'applied ' : 'pending';
            printf("  [%s] %s\n", $mark, $migration);
        }
        break;

    case 'rollback':
        $last = $pdo->query('SELECT migration FROM migrations ORDER BY id DESC LIMIT 1')->fetchColumn();
        if ($last === false) {
            echo "Nothing to roll back.\n";
            break;
        }
        $down = $dir . '/' . $last . '.down.sql';
        if (is_file($down)) {
            runSql($pdo, $down);
        }
        $pdo->prepare('DELETE FROM migrations WHERE migration = ?')->execute([$last]);
        echo "Rolled back: {$last}\n";
        break;

    case 'fresh':
        $applied = $pdo->query('SELECT migration FROM migrations ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($applied as $migration) {
            $down = $dir . '/' . $migration . '.down.sql';
            if (is_file($down)) {
                runSql($pdo, $down);
                echo "  rolled back: {$migration}\n";
            }
        }
        $pdo->exec('DELETE FROM migrations');
        echo "All migrations rolled back.\n";
        $count = applyPending($pdo, $upFiles);
        echo "Re-applied {$count} migration(s).\n";
        break;

    default:
        fwrite(STDERR, "Unknown command: {$command}\n");
        fwrite(STDERR, "Usage: php tools/migrate.php [migrate|status|rollback|fresh]\n");
        exit(1);
}
