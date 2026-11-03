<?php

declare(strict_types=1);

/**
 * Exhaustive coherence audit of the documentation against the repository.
 *
 * Checks, with no sampling:
 *   1. every backticked file path exists somewhere in the repository;
 *   2. every route cited as `/something` is handled by public/index.php;
 *   3. every environment variable cited is documented in .env.example,
 *      including the commented optional declarations;
 *   4. every capture marked as collected exists in docs/PREUVES.
 *
 * Prints only failures, plus the counts of what was actually checked.
 */

$root = dirname(__DIR__);

$failures = [];
$checked = ['files' => 0, 'routes' => 0, 'env' => 0, 'captures' => 0];

// ---------------------------------------------------------------------------
// 1. Index of every file name in the repository (basename => paths).
// ---------------------------------------------------------------------------
$byName = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());

    if (str_contains($path, '.git' . DIRECTORY_SEPARATOR) || str_contains($path, 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    $byName[$file->getFilename()][] = $path;
}

// ---------------------------------------------------------------------------
// 2. Routes handled by the front controller.
// ---------------------------------------------------------------------------
$index = (string) file_get_contents($root . '/public/index.php');
$routes = [];

// Two forms coexist: the direct comparison `$path === '/login'` and the view
// map `'/tache' => ['view' => 'ai/task']` that the controller resolves later.
if (preg_match_all("/'\\/([a-z0-9\-]+)'\\s*=>/", $index, $m)) {
    foreach ($m[1] as $route) {
        $routes[] = '/' . $route;
    }
}

if (preg_match_all("/\\\$path === '(\/[a-z0-9\-]*)'/", $index, $m)) {
    foreach ($m[1] as $route) {
        $routes[] = $route;
    }
}

$routes = array_values(array_unique($routes));

// ---------------------------------------------------------------------------
// 3. Environment variables documented for operators — active OR commented.
// ---------------------------------------------------------------------------
$example = (string) file_get_contents($root . '/.env.example');
preg_match_all('/^#?\s*([A-Z][A-Z0-9_]+)=/m', $example, $m);
$documented = array_flip($m[1] ?? []);

// ---------------------------------------------------------------------------
$docs = array_merge(glob($root . '/*.md') ?: [], glob($root . '/docs/*.md') ?: []);

foreach ($docs as $doc) {
    $name = str_replace($root . DIRECTORY_SEPARATOR, '', $doc);
    $text = (string) file_get_contents($doc);

    // ---- 1. File paths.
    if (preg_match_all('/`([A-Za-z0-9_\/.\-]+\.(?:php|md|js|css|sql|ps1|json|pem))`/', $text, $m)) {
        foreach (array_unique($m[1]) as $path) {
            if ($path === '.fr.md') {
                continue;
            }

            $checked['files']++;

            if (isset($byName[basename($path)])) {
                continue;
            }

            // A line that records a deletion or a rename cites a file that no
            // longer exists: that is history, not a broken reference.
            $line = '';

            if (preg_match('/^.*' . preg_quote($path, '/') . '.*$/m', $text, $lineMatch)) {
                $line = $lineMatch[0];
            }

            if (preg_match('/removed|deleted|supprim|renamed|remplac/i', $line)) {
                continue;
            }

            $failures[] = $name . ' | fichier cité introuvable : ' . $path;
        }
    }

    // ---- 2. Routes.
    if (preg_match_all('/`(\/[a-z][a-z0-9\-]*)`/', $text, $m)) {
        foreach (array_unique($m[1]) as $route) {
            $checked['routes']++;

            if (in_array($route, $routes, true)) {
                continue;
            }

            $failures[] = $name . ' | route citée non routée : ' . $route;
        }
    }

    // ---- 3. Environment variables.
    if (preg_match_all('/`((?:META|APP|DB|ADMIN|SMOKE|PHP)_[A-Z0-9_]+)`/', $text, $m)) {
        foreach (array_unique($m[1]) as $var) {
            $checked['env']++;

            if (!isset($documented[$var])) {
                $failures[] = $name . ' | variable non documentée : ' . $var;
            }
        }
    }
}

// ---------------------------------------------------------------------------
// 3 bis. The README announces a register count: it must be the real one.
// ---------------------------------------------------------------------------
$registerRows = 0;
$registerFile = $root . '/docs/REGISTRE_PROBLEMES.md';

if (is_file($registerFile)) {
    foreach (file($registerFile, FILE_IGNORE_NEW_LINES) ?: [] as $registerLine) {
        if (str_starts_with($registerLine, '| P-')) {
            $registerRows++;
        }
    }
}

foreach (['README.md', 'README.fr.md'] as $readme) {
    $readmePath = $root . '/' . $readme;

    if (!is_file($readmePath)) {
        continue;
    }

    $readmeText = (string) file_get_contents($readmePath);

    if (preg_match('/(?:Issue register|Registre des problèmes) \((\d+) entr/', $readmeText, $labelMatch) === 1) {
        $checked['register'] = ($checked['register'] ?? 0) + 1;

        if ((int) $labelMatch[1] !== $registerRows) {
            $failures[] = $readme . ' | le libellé du registre annonce ' . $labelMatch[1]
                . ' entrée(s), le registre en contient ' . $registerRows;
        }
    }
}

// ---------------------------------------------------------------------------
// 4. Captures.
// ---------------------------------------------------------------------------
$preuves = (string) file_get_contents($root . '/docs/PREUVES/README.md');

if (preg_match_all('/`([0-9]{2}[a-z]?-[a-z0-9\-]+\.png)`/', $preuves, $m)) {
    foreach (array_unique($m[1]) as $png) {
        $checked['captures']++;
        $marked = str_contains($preuves, '`' . $png . '` ✅');

        if ($marked && !is_file($root . '/docs/PREUVES/' . $png)) {
            $failures[] = 'docs/PREUVES/README.md | capture marquée ✅ mais absente : ' . $png;
        }
    }
}

// ---------------------------------------------------------------------------
echo "=== AUDIT EXHAUSTIF DES REFERENCES ===\n\n";
printf("  références de fichiers : %d\n", $checked['files']);
printf("  routes citées          : %d\n", $checked['routes']);
printf("  variables citées       : %d\n", $checked['env']);
printf("  captures listées       : %d\n", $checked['captures']);
printf("  libellés de registre   : %d\n", $checked['register'] ?? 0);
printf("  routes routées         : %d\n", count($routes));
printf("  variables documentées  : %d\n\n", count($documented));

if ($failures === []) {
    echo "  Aucune référence cassée.\n";
    exit(0);
}

echo '  ' . count($failures) . " problème(s) :\n";

foreach ($failures as $failure) {
    echo '    - ' . $failure . "\n";
}

exit(1);
