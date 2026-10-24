<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\EmbeddingService;
use App\Services\Ai\MetaClient;
use App\Services\Ai\RagService;
use PDO;
use PHPUnit\Framework\TestCase;

final class RagServiceTest extends TestCase
{
    private function makeService(): RagService
    {
        $client = new MetaClient([]);
        $embeddings = new EmbeddingService($client);
        // In-memory SQLite is enough: chunk() does not touch the database.
        $pdo = new PDO('sqlite::memory:');

        return new RagService($client, $embeddings, $pdo);
    }

    public function testShortTextProducesASingleChunk(): void
    {
        $chunks = $this->makeService()->chunk('short text', 800, 100);

        $this->assertCount(1, $chunks);
        $this->assertSame('short text', $chunks[0]);
    }

    public function testLongTextIsSplitIntoMultipleChunks(): void
    {
        $chunks = $this->makeService()->chunk(str_repeat('a', 2500), 800, 100);

        $this->assertGreaterThan(1, count($chunks));
    }

    public function testEmptyTextProducesNoChunks(): void
    {
        $this->assertSame([], $this->makeService()->chunk('', 800, 100));
    }

    public function testChunksNeverExceedTheSize(): void
    {
        $chunks = $this->makeService()->chunk(str_repeat('x', 2000), 800, 100);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(800, mb_strlen($chunk));
        }
    }

    public function testChunksOverlapByTheOverlapAmount(): void
    {
        // With size 10 and overlap 2, the second chunk starts 8 chars after the first.
        $text = 'abcdefghijklmnopqrstuvwxyz';
        $chunks = $this->makeService()->chunk($text, 10, 2);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertSame('abcdefghij', $chunks[0]);
        $this->assertSame('ijklmnopqr', $chunks[1]);
    }
}
