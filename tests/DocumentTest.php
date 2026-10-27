<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\Document;
use PDO;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    private Document $documents;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('CREATE TABLE documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            filename TEXT NULL,
            mime_type TEXT NULL,
            espace_id INTEGER NULL,
            proprietaire_id INTEGER NULL,
            type TEXT NOT NULL DEFAULT "autre",
            statut TEXT NOT NULL DEFAULT "publie",
            confidentialite TEXT NOT NULL DEFAULT "restreint",
            supprime_le TEXT NULL
        )');
        $pdo->exec('CREATE TABLE version_document (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL
        )');

        $this->documents = new Document($pdo);
    }

    public function testCountIgnoresDeletedDocuments(): void
    {
        $this->documents->create('A', null, null, 'a.txt', 'text/plain');
        $id = $this->documents->create('B', null, null, 'b.txt', 'text/plain');
        $this->documents->softDelete($id);

        $this->assertSame(1, $this->documents->count());
    }

    public function testListRecentPaginates(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->documents->create('Document ' . $i, null, null, 'd' . $i . '.txt', 'text/plain');
        }

        $first = $this->documents->listRecent(2, 0);
        $second = $this->documents->listRecent(2, 2);

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertNotSame((int) $first[0]['id'], (int) $second[0]['id']);
    }
}
