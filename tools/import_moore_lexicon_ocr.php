<?php
// OCR scanned Faso Bibles lexicon PDFs via MetaClient::describeImages (Muse
// vision), parse Mooré→gloss pairs, insert into moore_lexicon.
// Usage: php tools/import_moore_lexicon_ocr.php [maxImages] [batchSize]
$d = require dirname(__DIR__) . '/bootstrap.php';
require dirname(__DIR__) . '/vendor/autoload.php';

$maxImages = isset($argv[1]) ? (int) $argv[1] : 0;   // 0 = all
$batch = isset($argv[2]) ? max(1, (int) $argv[2]) : 8;

$ins = $d['pdo']->prepare(
    'INSERT IGNORE INTO moore_lexicon (headword, variant, pos, gloss_fr, gloss_en) VALUES (?,?,?,?,?)'
);
$files = [
    ['appellations_moore_fr.pdf', 'fr'],
    ['appellations_moore_en.pdf', 'en'],
];

$client = $d['meta'] ?? null;
if ($client === null) { echo "client IA introuvable\n"; exit(1); }
$total = 0;

foreach ($files as [$file, $lang]) {
    $path = dirname(__DIR__) . '/storage/moore_sources/' . $file;
    if (!is_file($path)) { echo "skip $file\n"; continue; }

    $pdf = (new Smalot\PdfParser\Parser())->parseFile($path);
    $images = array_values(array_filter(
        $pdf->getObjectsByType('XObject', 'Image'),
        static fn ($im) => str_starts_with($im->getContent(), "\xFF\xD8\xFF"),
    ));
    if ($maxImages > 0) $images = array_slice($images, 0, $maxImages);
    echo "$file: ", count($images), " images à OCR\n";

    $n = 0;
    foreach (array_chunk($images, $batch) as $chunk) {
        $paths = [];
        foreach ($chunk as $im) {
            $b = tempnam(sys_get_temp_dir(), 'akw_ocr_');
            file_put_contents($b . '.jpg', $im->getContent());
            $paths[] = $b . '.jpg';
            $b && file_put_contents($b, '');
        }
        try {
            $texts = $client->describeImages($paths);
        } catch (\Throwable $e) {
            echo "  batch error: ", $e->getMessage(), "\n"; $texts = [];
        } finally {
            foreach ($paths as $p) { @unlink($p); @unlink(substr($p, 0, -4)); }
        }
        foreach ($texts as $text) {
            foreach (preg_split('/[\r\n]+/', (string) $text) as $line) {
                $line = trim($line);
                if ($line === '' || mb_strlen($line) < 5 || mb_strlen($line) > 300) continue;
                if (!preg_match('/^(.{2,80}?)\s*[—–:\-]\s*(.{3,220})$/u', $line, $m) &&
                    !preg_match('/^(.{2,80}?)\s{2,}(.{3,220})$/u', $line, $m)) continue;
                $mo = trim($m[1]); $gl = trim($m[2]);
                if ($mo === '' || $gl === '' || is_numeric($mo)) continue;
                $ins->execute([mb_substr($mo, 0, 120), null, null,
                    $lang === 'fr' ? $gl : null, $lang === 'en' ? $gl : null]);
                $n++;
            }
        }
        echo "  ", $n, " entrées\n";
    }
    $total += $n;
}
echo "TOTAL: $total | en base: ",
    $d['pdo']->query('SELECT COUNT(*) c FROM moore_lexicon')->fetch()['c'], "\n";
