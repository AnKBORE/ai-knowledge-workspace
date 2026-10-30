<?php
// Import Mooré→French lexicon PDFs into moore_lexicon.
$d = require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/vendor/autoload.php';

$files = [
    ['appellations_moore_fr.pdf', 'fr'],
    ['appellations_moore_en.pdf', 'en'],
];
$ins = $d['pdo']->prepare(
    'INSERT IGNORE INTO moore_lexicon (headword, variant, pos, gloss_fr, gloss_en) VALUES (?,?,?,?,?)'
);
$total = 0;
foreach ($files as [$file, $lang]) {
    $path = dirname(__DIR__) . '/storage/moore_sources/' . $file;
    if (!is_file($path)) { echo "skip $file\n"; continue; }
    $pdf = (new Smalot\PdfParser\Parser())->parseFile($path);
    $text = $pdf->getText();
    $n = 0;
    foreach (preg_split('/[\r\n]+/', $text) as $line) {
        $line = trim($line);
        if ($line === '' || mb_strlen($line) < 5 || mb_strlen($line) > 300) continue;
        // Separators: em-dash, en-dash, colon, tab, 3+ spaces
        if (!preg_match('/^(.{2,80}?)[\s]*[—–:\-][\s]*(.{3,220})$/u', $line, $m) &&
            !preg_match('/^(.{2,80}?)\s{3,}(.{3,220})$/u', $line, $m)) continue;
        $mo = trim($m[1]); $gl = trim($m[2]);
        if ($mo === '' || $gl === '' || is_numeric($mo)) continue;
        $ins->execute([
            mb_substr($mo, 0, 120), null, null,
            $lang === 'fr' ? $gl : null,
            $lang === 'en' ? $gl : null,
        ]);
        $n++;
    }
    echo "$file: $n entrées\n";
    $total += $n;
}
echo "TOTAL: $total | en base: ",
    $d['pdo']->query('SELECT COUNT(*) c FROM moore_lexicon')->fetch()['c'], "\n";
