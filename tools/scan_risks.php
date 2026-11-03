<?php
/**
 * Static risk scan: the patterns that produce real defects, not style opinions.
 */
$root = dirname(__DIR__);
$skip = ['vendor', 'tests', '.git', 'storage'];

$findings = [];
$files = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    $path = $file->getPathname();
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);

    foreach ($skip as $dir) {
        if (str_contains($rel, $dir . DIRECTORY_SEPARATOR)) {
            continue 2;
        }
    }

    if (!str_ends_with($path, '.php')) {
        continue;
    }

    $files++;
    $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];

    foreach ($lines as $i => $line) {
        $n = $i + 1;

        // 1. SQL construit par interpolation : injection possible.
        if (preg_match('/(?:->query|->exec|->prepare)\s*\(\s*["\']/', $line) === 1
            && preg_match('/\$[a-zA-Z_]/', $line) === 1
            && preg_match('/\.\s*\$|"\s*\.\s*\$|\{\$/', $line) === 1) {
            $findings[] = ['S1 SQL interpolé', $rel, $n, trim($line)];
        }

        // 2. Sortie de debug laissée dans le code.
        if (preg_match('/\b(var_dump|print_r|dd|dump)\s*\(/', $line) === 1) {
            $findings[] = ['S2 debug', $rel, $n, trim($line)];
        }

        // 3. Sortie directe sans échappement dans une vue.
        if (str_starts_with($rel, 'views/') && preg_match('/<\?=\s*\$[a-zA-Z_]+(\[|\s*\?>)/', $line) === 1) {
            $findings[] = ['S3 echo non échappé', $rel, $n, trim($line)];
        }

        // 4. Secret en dur.
        if (preg_match('/(api[_-]?key|secret|password|token)\s*=\s*["\'][^"\']{12,}["\']/i', $line) === 1
            && !str_contains($line, 'getenv')) {
            $findings[] = ['S4 secret en dur', $rel, $n, trim($line)];
        }

        // 5. Marqueur de travail inachevé.
        if (preg_match('/\b(TODO|FIXME|XXX|HACK)\b/', $line) === 1) {
            $findings[] = ['S5 inachevé', $rel, $n, trim($line)];
        }
    }
}

echo 'fichiers PHP analysés : ' . $files . "\n\n";

if ($findings === []) {
    echo "Aucun motif à risque.\n";
    exit(0);
}

$byType = [];

foreach ($findings as $f) {
    $byType[$f[0]][] = $f;
}

foreach ($byType as $type => $items) {
    echo '=== ' . $type . ' (' . count($items) . ") ===\n";

    foreach (array_slice($items, 0, 8) as $item) {
        echo '  ' . $item[1] . ':' . $item[2] . '  ' . mb_substr($item[3], 0, 90) . "\n";
    }

    echo "\n";
}