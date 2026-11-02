<?php

declare(strict_types=1);

/**
 * Prove the provenance chain of a document.
 *
 * The claim: when the indexed document is oral, every text derivative (chunks,
 * translations, corrections) still points at the original recording, whose bytes
 * are unchanged. This command verifies it instead of asserting it.
 *
 * Usage:
 *   php tools/prove_provenance.php [document-id]
 *
 * Exit code 0 when the chain is intact, 1 otherwise.
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

$storage = dirname(__DIR__) . '/storage/files';
$documentId = (int) ($argv[1] ?? 30);

$document = $pdo->prepare('SELECT id, title, filename, mime_type, created_at FROM documents WHERE id = :id');
$document->execute(['id' => $documentId]);
$doc = $document->fetch();

if ($doc === false) {
    fwrite(STDERR, "Document {$documentId} introuvable.\n");
    exit(2);
}

$failures = 0;
$check = static function (bool $ok, string $label, string $detail = '') use (&$failures): void {
    if (!$ok) {
        $failures++;
    }

    printf("  %s %-46s %s\n", $ok ? 'OK  ' : 'KO  ', $label, $detail);
};

echo "\nDocument #{$doc['id']} — {$doc['title']}\n";
echo "  fichier d'origine : {$doc['filename']} ({$doc['mime_type']})\n";
echo "  versé le          : {$doc['created_at']}\n\n";

// --- 1. Versions : octets et empreinte -------------------------------------
$versions = $pdo->prepare('SELECT * FROM version_document WHERE document_id = :id ORDER BY numero');
$versions->execute(['id' => $documentId]);
$rows = $versions->fetchAll();

$check($rows !== [], 'au moins une version', count($rows) . ' version(s)');

foreach ($rows as $version) {
    $path = $storage . '/' . $version['fichier'];
    $exists = is_file($path);
    $recomputed = $exists ? hash_file('sha256', $path) : '';
    $stored = (string) $version['empreinte'];

    $check(
        $exists && $recomputed === $stored,
        "v{$version['numero']} empreinte identique",
        $exists ? substr($recomputed, 0, 16) . '… ' . number_format((int) $version['taille_octets']) . ' o' : 'fichier absent'
    );
}

// --- 2. Transcription (chunks) ---------------------------------------------
$chunks = $pdo->prepare(
    'SELECT COUNT(*) AS n, MIN(SUBSTRING(content, 1, 80)) AS extrait
     FROM document_chunks WHERE document_id = :id'
);
$chunks->execute(['id' => $documentId]);
$chunkRow = $chunks->fetch();

$check((int) $chunkRow['n'] > 0, 'transcription indexée', (int) $chunkRow['n'] . ' chunk(s)');

if ((int) $chunkRow['n'] > 0) {
    echo '       extrait : ' . trim(preg_replace('/\s+/', ' ', (string) $chunkRow['extrait'])) . "…\n";
}

// --- 3. Traductions : liées à une version ----------------------------------
$translations = $pdo->prepare(
    'SELECT lang, COUNT(*) AS n, SUM(version_document_id IS NULL OR version_document_id = 0) AS orphelines
     FROM document_translations WHERE document_id = :id GROUP BY lang'
);
$translations->execute(['id' => $documentId]);

foreach ($translations->fetchAll() as $translation) {
    $check(
        (int) $translation['orphelines'] === 0,
        "traductions {$translation['lang']} liées à une version",
        (int) $translation['n'] . ' segment(s)'
    );
}

// --- 4. Corrections : source conservée et version liée ---------------------
$corrections = $pdo->prepare('SELECT * FROM correction_document WHERE document_id = :id ORDER BY id');
$corrections->execute(['id' => $documentId]);
$correctionRows = $corrections->fetchAll();

foreach ($correctionRows as $correction) {
    $versionOk = $correction['version_document_id'] !== null && (int) $correction['version_document_id'] > 0;
    $sourceOk = $correction['texte_source'] !== null && trim((string) $correction['texte_source']) !== '';
    $correctedOk = trim((string) $correction['texte_corrige']) !== '';

    $check(
        $versionOk,
        "correction #{$correction['id']} liée à une version",
        $versionOk ? 'version ' . $correction['version_document_id'] : 'version NULL'
    );
    $check(
        $sourceOk && $correctedOk,
        "correction #{$correction['id']} conserve l'original",
        'source ' . mb_strlen((string) $correction['texte_source']) . ' car. · corrigé '
        . mb_strlen((string) $correction['texte_corrige']) . ' car.'
    );
}

if ($correctionRows === []) {
    echo "  --   aucune correction sur ce document            (la chaîne reste valide)\n";
}

// --- Verdict ---------------------------------------------------------------
echo "\n";
echo $failures === 0
    ? "Chaîne intacte : tout dérivé remonte à l'enregistrement d'origine, octets vérifiés.\n\n"
    : $failures . " maillon(s) rompu(s).\n\n";

exit($failures === 0 ? 0 : 1);
