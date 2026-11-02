<?php

declare(strict_types=1);

/**
 * Repère les textes français codés en dur dans les vues et les scripts.
 *
 * Un texte est signalé quand il contient un accent français ou un mot français
 * courant, et que la ligne n'est pas déjà une traduction conditionnelle
 * ($isFr / $lang === 'fr' / app_i18n).
 *
 * Usage : php tools/scan_french_ui.php
 */

$root = dirname(__DIR__);

$targets = array_merge(
    glob($root . '/views/**/*.php') ?: [],
    glob($root . '/views/*.php') ?: [],
    glob($root . '/views/*/*.php') ?: [],
    glob($root . '/public/assets/js/*.js') ?: [],
);

$targets = array_values(array_unique($targets));
sort($targets);

$accents = '/[àâäéèêëîïôöùûüçœÀÂÄÉÈÊËÎÏÔÖÙÛÜÇŒ]/u';

$mots = [
    'le ', 'la ', 'les ', 'des ', 'une ', 'du ', 'pour ', 'avec ', 'votre ', 'vous ',
    'nous ', 'sur ', 'dans ', 'est ', 'sont ', 'pas ', 'plus ', 'aucun', 'ajouter',
    'supprimer', 'enregistrer', 'annuler', 'fermer', 'créer', 'modifier', 'rechercher',
    'chargement', 'erreur', 'succès', 'veuillez', 'sélectionner', 'mot de passe',
    'courriel', 'aucune', 'terminé', 'en cours', 'échec',
];

// Lignes qui sont déjà des traductions : on ne les signale pas.
$dejaTraduit = [
    '$isFr', "=== 'fr'", "== 'fr'", '$lang', 'app_i18n', "'fr' =>", '"fr" =>',
    'aria-label', // traité à part plus bas
];

$trouves = [];
$total = 0;

foreach ($targets as $file) {
    $rel = str_replace($root . '/', '', $file);
    $lines = file($file, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $i => $line) {
        $trim = trim($line);

        if ($trim === '' || str_starts_with($trim, '//') || str_starts_with($trim, '*')) {
            continue;
        }

        $estTraduction = false;

        foreach ($dejaTraduit as $marqueur) {
            if (str_contains($line, $marqueur)) {
                $estTraduction = true;
                break;
            }
        }

        // Une ligne de tableau de traduction : 'cle' => 'texte',
        if (preg_match('/^\s*\'[a-z0-9_]+\'\s*=>/', $line)) {
            $estTraduction = true;
        }

        if ($estTraduction) {
            continue;
        }

        // Un commentaire en fin de ligne ne compte pas
        $code = preg_replace('~//.*$~', '', $line);

        $accentsTrouves = preg_match($accents, $code) === 1;
        $motsTrouves = false;

        foreach ($mots as $mot) {
            if (str_contains(mb_strtolower($code), $mot)) {
                $motsTrouves = true;
                break;
            }
        }

        if (!$accentsTrouves && !$motsTrouves) {
            continue;
        }

        $total++;
        $trouves[$rel][] = ['ligne' => $i + 1, 'texte' => trim($trim)];
    }
}

echo "\n=== TEXTES FRANÇAIS CODÉS EN DUR ===\n\n";
echo '  fichiers analysés : ' . count($targets) . "\n";

if ($trouves === []) {
    echo "  aucun texte français non conditionnel\n\n";
    exit(0);
}

echo '  lignes signalées : ' . $total . ' dans ' . count($trouves) . " fichier(s)\n\n";

foreach ($trouves as $file => $lignes) {
    echo '— ' . $file . ' (' . count($lignes) . ")\n";

    foreach ($lignes as $l) {
        $texte = mb_strlen($l['texte']) > 120 ? mb_substr($l['texte'], 0, 120) . '…' : $l['texte'];
        echo '    ' . str_pad((string) $l['ligne'], 4, ' ', STR_PAD_LEFT) . '  ' . $texte . "\n";
    }

    echo "\n";
}
