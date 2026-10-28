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
            version_document_id INTEGER NULL,
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

        $this->assertStringStartsWith('Grounded answer from context.', $answer);
    }

    public function testQueryWithoutDocumentsFallsBackGracefully(): void
    {
        $answer = $this->rag->query('What are the main risks?');

        $this->assertStringContainsString('provided documents', $answer);
    }

    public function testAnswerReturnsCitedSources(): void
    {
        $documentId = $this->insertDocument('Quarterly report');
        $this->rag->indexDocument($documentId, 'The quarterly report describes the main risks.');

        $result = $this->rag->answer('What are the main risks?');

        $this->assertStringStartsWith('Grounded answer from context.', $result['answer']);
        $this->assertNotEmpty($result['sources']);
        $this->assertSame('Quarterly report', $result['sources'][0]['title']);
        $this->assertArrayHasKey('excerpt', $result['sources'][0]);
    }

    public function testTaskReturnsADraftWithSources(): void
    {
        $documentId = $this->insertDocument('Report');
        $this->rag->indexDocument($documentId, 'The quarterly report describes the main risks.');

        $result = $this->rag->task('Prepare a note about the risks');

        $this->assertStringStartsWith('Grounded answer from context.', $result['draft']);
        $this->assertNotEmpty($result['sources']);
        $this->assertSame('Report', $result['sources'][0]['title']);
    }

    public function testSummarizeReturnsASummaryWithSources(): void
    {
        $documentId = $this->insertDocument('Report');
        $this->rag->indexDocument($documentId, 'The quarterly report describes the main risks.');

        $result = $this->rag->summarize($documentId);

        $this->assertStringStartsWith('Grounded answer from context.', $result['summary']);
        $this->assertSame($documentId, $result['document_id']);
        $this->assertNotEmpty($result['sources']);
        $this->assertSame('Report', $result['sources'][0]['title']);
    }

    public function testSummarizeWithoutChunksFallsBack(): void
    {
        $result = $this->rag->summarize(999);

        $this->assertStringContainsString('No indexed content', $result['summary']);
        $this->assertSame([], $result['sources']);
    }

    public function testHybridSearchRanksLexicalMatchesFirst(): void
    {
        $informatique = $this->insertDocument('Informatique');
        $this->rag->indexDocument($informatique, 'Le cours informatique de 6e presente le bon comportement en salle informatique.');

        $nutrition = $this->insertDocument('Nutrition');
        $this->rag->indexDocument($nutrition, 'Le rapport nutrition traite des maladies chroniques et de l alimentation.');

        $results = $this->rag->search('informatique', 1);

        $this->assertNotEmpty($results);
        $this->assertSame('Informatique', $results[0]['title']);
    }
}
