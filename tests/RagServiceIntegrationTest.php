<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\EmbeddingService;
use App\Services\Ai\RagService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test of the full RAG pipeline (index -> retrieve -> generate),
 * using SQLite in memory and a fake Meta client — no database server or API.
 */
final class RagServiceIntegrationTest extends TestCase
{
    private PDO $pdo;
    private RagService $rag;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            filename TEXT,
            mime_type TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        $this->pdo->exec('CREATE TABLE document_chunks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            chunk_index INTEGER NOT NULL,
            content TEXT NOT NULL,
            embedding TEXT NOT NULL
        )');

        $client = new FakeMetaClient();
        $this->rag = new RagService($client, new EmbeddingService($client), $this->pdo);
    }

    private function insertDocument(string $title): int
    {
        $this->pdo->exec('INSERT INTO documents (title) VALUES (' . $this->pdo->quote($title) . ')');

        return (int) $this->pdo->lastInsertId();
    }

    public function testIndexDocumentStoresChunks(): void
    {
        $documentId = $this->insertDocument('Report');

        $this->rag->indexDocument($documentId, str_repeat('word ', 500));

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM document_chunks')->fetchColumn();
        $this->assertGreaterThan(0, $count);
    }

    public function testQueryReturnsGroundedAnswer(): void
    {
        $documentId = $this->insertDocument('Report');
        $this->rag->indexDocument($documentId, 'The quarterly report describes the main risks.');

        $answer = $this->rag->query('What are the main risks?');

        $this->assertSame('Grounded answer from context.', $answer);
    }

    public function testQueryWithoutDocumentsFallsBackGracefully(): void
    {
        $answer = $this->rag->query('What are the main risks?');

        $this->assertStringContainsString('No relevant document', $answer);
    }

    public function testAnswerReturnsCitedSources(): void
    {
        $documentId = $this->insertDocument('Quarterly report');
        $this->rag->indexDocument($documentId, 'The quarterly report describes the main risks.');

        $result = $this->rag->answer('What are the main risks?');

        $this->assertSame('Grounded answer from context.', $result['answer']);
        $this->assertNotEmpty($result['sources']);
        $this->assertSame('Quarterly report', $result['sources'][0]['title']);
        $this->assertArrayHasKey('excerpt', $result['sources'][0]);
    }

    public function testTaskReturnsADraftWithSources(): void
    {
        $documentId = $this->insertDocument('Report');
        $this->rag->indexDocument($documentId, 'The quarterly report describes the main risks.');

        $result = $this->rag->task('Prepare a note about the risks');

        $this->assertSame('Grounded answer from context.', $result['draft']);
        $this->assertNotEmpty($result['sources']);
        $this->assertSame('Report', $result['sources'][0]['title']);
    }
}
