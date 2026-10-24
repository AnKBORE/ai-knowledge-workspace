<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\EmbeddingService;
use App\Services\Ai\MetaClient;
use App\Services\Ai\RagService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Fake Meta client: fixed embeddings and a fixed answer, no network.
 */
final class FakeMetaClient extends MetaClient
{
    public function __construct()
    {
        parent::__construct([]);
    }

    public function embed(string $text): array
    {
        return [1.0, 0.0, 0.0];
    }

    public function chat(string $prompt, array $context = [], ?string $model = null): string
    {
        return 'Grounded answer from context.';
    }
}

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
}
