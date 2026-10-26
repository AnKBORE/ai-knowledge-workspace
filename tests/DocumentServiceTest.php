<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\Document;
use App\Models\VersionDocument;
use App\Services\DocumentService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Document intake: storage, SHA-256, immutable versions (SQLite in memory).
 */
final class DocumentServiceTest extends TestCase
{
    private PDO $pdo;
    private DocumentService $service;
    private string $storage;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('CREATE TABLE documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            filename TEXT NULL,
            mime_type TEXT NULL,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            espace_id INTEGER NULL,
            resume TEXT NULL,
            type TEXT NOT NULL DEFAULT "autre",
            langue TEXT NOT NULL DEFAULT "fr",
            confidentialite TEXT NOT NULL DEFAULT "restreint",
            statut TEXT NOT NULL DEFAULT "publie",
            proprietaire_id INTEGER NULL,
            supprime_le TEXT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $this->pdo->exec('CREATE TABLE version_document (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            numero INTEGER NOT NULL DEFAULT 1,
            fichier TEXT NOT NULL,
            nom_original TEXT NOT NULL,
            type_mime TEXT NOT NULL,
            taille_octets INTEGER NOT NULL DEFAULT 0,
            empreinte TEXT NOT NULL,
            auteur_id INTEGER NULL,
            commentaire TEXT NULL,
            cree_le TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->storage = sys_get_temp_dir() . '/dsh_docs_' . bin2hex(random_bytes(4));
        $this->service = new DocumentService(
            new Document($this->pdo),
            new VersionDocument($this->pdo),
            $this->storage,
        );
    }

    protected function tearDown(): void
    {
        if (is_dir($this->storage)) {
            foreach (glob($this->storage . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->storage);
        }
    }

    /** @return array{tmp_name: string, name: string, type: string, size: int, error: int} */
    private function upload(string $content, string $name = 'report.txt'): array
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'upl_');
        file_put_contents($tmp, $content);

        return [
            'tmp_name' => $tmp,
            'name' => $name,
            'type' => 'text/plain',
            'size' => strlen($content),
            'error' => UPLOAD_ERR_OK,
        ];
    }

    public function testCreateDocumentStoresFileAndFirstVersion(): void
    {
        $result = $this->service->createDocument('Rapport', null, null, $this->upload('hello world'));

        $this->assertGreaterThan(0, $result['document_id']);
        $this->assertSame(hash('sha256', 'hello world'), $result['sha256']);
        $this->assertFileExists($this->storage . '/' . $result['sha256']);

        $document = (new Document($this->pdo))->find($result['document_id']);
        $this->assertSame('Rapport', $document['title']);

        $latest = (new VersionDocument($this->pdo))->latest($result['document_id']);
        $this->assertSame(1, (int) $latest['numero']);
    }

    public function testAddVersionIncrementsNumero(): void
    {
        $result = $this->service->createDocument('Rapport', null, null, $this->upload('v1'));
        $this->service->addVersion($result['document_id'], null, $this->upload('v2'));

        $latest = (new VersionDocument($this->pdo))->latest($result['document_id']);
        $this->assertSame(2, (int) $latest['numero']);
        $this->assertNotSame(hash('sha256', 'v1'), $latest['empreinte']);
    }

    public function testRejectsDisallowedExtension(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->createDocument('Exe', null, null, $this->upload('binary', 'virus.exe'));
    }

    public function testSameContentIsDeduplicatedOnDisk(): void
    {
        $first = $this->service->createDocument('A', null, null, $this->upload('same content'));
        $second = $this->service->createDocument('B', null, null, $this->upload('same content'));

        $this->assertSame($first['sha256'], $second['sha256']);
        $this->assertCount(1, glob($this->storage . '/*') ?: []);
    }
}
