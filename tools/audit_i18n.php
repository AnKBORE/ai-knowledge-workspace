<?php

declare(strict_types=1);

/**
 * i18n parity: every key must exist in both languages.
 *
 * A key present in only one dictionary is a page that shows "Undefined array
 * key" to a user in that language — it happened twice (categories, journal),
 * and nothing caught it before a browser did.
 *
 * Usage: php tools/audit_i18n.php
 */

require dirname(__DIR__) . '/core/i18n.php';

/**
 * @return list<string>
 */
function collectKeys(array $tree, string $prefix = ''): array
{
    $keys = [];

    foreach ($tree as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

        if (is_array($value)) {
            foreach (collectKeys($value, $path) as $child) {
                $keys[] = $child;
            }

            continue;
        }

        $keys[] = $path;
    }

    return $keys;
}

$fr = collectKeys(app_i18n('fr'));
$en = collectKeys(app_i18n('en'));

$onlyFr = array_values(array_diff($fr, $en));
$onlyEn = array_values(array_diff($en, $fr));

echo 'clés françaises : ' . count($fr) . "\n";
echo 'clés anglaises  : ' . count($en) . "\n\n";

$defects = 0;

if ($onlyFr !== []) {
    $defects += count($onlyFr);
    echo '== présentes en français seulement ==' . "\n";

    foreach ($onlyFr as $key) {
        echo '  ' . $key . "\n";
    }

    echo "\n";
}

if ($onlyEn !== []) {
    $defects += count($onlyEn);
    echo '== présentes en anglais seulement ==' . "\n";

    foreach ($onlyEn as $key) {
        echo '  ' . $key . "\n";
    }

    echo "\n";
}

if ($defects === 0) {
    echo "Parité i18n : aucune clé manquante.\n";
    exit(0);
}

echo 'Parité i18n : ' . $defects . " clé(s) manquante(s).\n";
exit(1);
