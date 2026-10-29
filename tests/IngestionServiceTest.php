<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\Document;
use App\Models\Espace;
use App\Models\IngestionJob;
use App\Models\VersionDocument;
use App\Services\Ai\EmbeddingService;
use App\Services\Ai\RagService;
use App\Services\Ai\TranscriptionService;
use App\Services\Ai\VisionService;
use App\Services\DocumentService;
use App\Services\IngestionService;
use App\Services\JournalService;
use App\Services\PermissionService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Ingestion pipeline: sync/background routing, extraction, localized errors,
 * atomic job claiming (SQLite in memory, fake AI client).
 */
final class IngestionServiceTest extends TestCase
{
    private PDO $pdo;
    private IngestionService $service;
    /** @var list<string> */
    private array $files = [];

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/core/i18n.php';
    }

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $client = new FakeMetaClient();
        $this->service = new IngestionService(
            new TranscriptionService($client),
            new VisionService($client),
            new DocumentService(new Document($this->pdo), new VersionDocument($this->pdo), sys_get_temp_dir()),
            new Espace($this->pdo),
            new RagService($client, new EmbeddingService($client, 'local'), $this->pdo, new PermissionService($this->pdo)),
            new JournalService($this->pdo),
            600,
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function file(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ing_');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }

    public function testAudioAndImagesAreProcessedInBackground(): void
    {
        $path = $this->file('binary');

        foreach (['mp3', 'wav', 'm4a', 'png', 'jpg', 'tiff'] as $extension) {
            self::assertTrue($this->service->isSlow($path, $extension), $extension);
        }
    }

    public function testTextFilesAreProcessedSynchronously(): void
    {
        $path = $this->file('Procedure for onboarding new staff.');

        self::assertFalse($this->service->isSlow($path, 'txt'));
        self::assertFalse($this->service->isSlow($path, 'md'));
    }

    public function testExtractsTextAndImageContent(): void
    {
        self::assertSame('Meeting minutes', $this->service->extract($this->file('Meeting minutes'), 'txt'));
        self::assertSame('scanned document text', $this->service->extract($this->file('img'), 'png'));
    }

    public function testEmptyFileIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File empty or unreadable');

        $this->service->extract($this->file("  \n "), 'txt');
    }

    public function testErrorsAreMappedToLocalizedMessages(): void
    {
        $en = app_i18n('en')['api'];
        $fr = app_i18n('fr')['api'];

        self::assertSame($en['file_empty'], $this->service->errorMessage(new \RuntimeException('File empty or unreadable'), $en));
        self::assertSame($fr['transcription_refused'], $this->service->errorMessage(new \RuntimeException('Transcription refused by the model'), $fr));
        self::assertSame(sprintf($en['audio_too_long'], 10), $this->service->errorMessage(new \RuntimeException('Audio too long'), $en));
    }

    public function testInternalErrorsAreNotLeakedWithoutDebug(): void
    {
        $en = app_i18n('en')['api'];
        putenv('APP_DEBUG=0');

        self::assertSame($en['upload_failed'], $this->service->errorMessage(new \RuntimeException('SQLSTATE secret detail'), $en));
    }

    public function testAssistantRepliesAreNeverTakenForTranscripts(): void
    {
        $notTranscripts = [
            "I understand you'd like help with transcription, but I don't have access to audio to transcribe directly — I can only work with text you share here.",
            "I can't provide a verbatim transcript of these sung lyrics as they appear to be a copyrighted song.",
            "Je ne peux pas transcrire cet enregistrement.",
        ];
        foreach ($notTranscripts as $reply) {
            self::assertTrue(\App\Services\Ai\MetaClient::isNotATranscript($reply), $reply);
        }

        self::assertFalse(\App\Services\Ai\MetaClient::isNotATranscript(
            'Compte rendu de la réunion du conseil paroissial du 12 octobre. Le budget est fixé à 500 000 francs CFA.'
        ));
    }

    public function testJobCanOnlyBeClaimedOnce(): void
    {
        $this->pdo->exec("CREATE TABLE ingestion_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT, utilisateur_id INTEGER, titre TEXT, categorie_id INTEGER,
            nom_original TEXT, type_mime TEXT, taille_octets INTEGER, chemin_tmp TEXT, langue TEXT,
            statut TEXT NOT NULL DEFAULT 'en_attente', erreur TEXT NULL, document_id INTEGER NULL, chunks INTEGER NULL
        )");
        $jobs = new IngestionJob($this->pdo);
        $id = $jobs->create([
            'utilisateur_id' => 1, 'titre' => 'Scan', 'categorie_id' => 1, 'nom_original' => 'scan.pdf',
            'type_mime' => 'application/pdf', 'taille_octets' => 10, 'chemin_tmp' => '/tmp/x.pdf', 'langue' => 'en',
        ]);

        self::assertTrue($jobs->claim($id));
        self::assertFalse($jobs->claim($id), 'a second worker must not process the same job');

        $jobs->complete($id, 42, 7);
        $row = $this->pdo->query('SELECT statut, document_id, chunks FROM ingestion_jobs')->fetch();
        self::assertSame(['statut' => 'termine', 'document_id' => 42, 'chunks' => 7], $row);
    }
}
