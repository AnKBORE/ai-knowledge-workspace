<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\EmbeddingService;
use App\Services\Ai\MetaClient;
use PHPUnit\Framework\TestCase;

final class EmbeddingServiceTest extends TestCase
{
    private EmbeddingService $embeddings;

    protected function setUp(): void
    {
        $this->embeddings = new EmbeddingService(new MetaClient([]));
    }

    public function testIdenticalVectorsHaveSimilarityOne(): void
    {
        $this->assertEqualsWithDelta(
            1.0,
            $this->embeddings->similarity([1.0, 2.0, 3.0], [1.0, 2.0, 3.0]),
            1e-9
        );
    }

    public function testOrthogonalVectorsHaveSimilarityZero(): void
    {
        $this->assertSame(0.0, $this->embeddings->similarity([1.0, 0.0], [0.0, 1.0]));
    }

    public function testZeroVectorReturnsZero(): void
    {
        $this->assertSame(0.0, $this->embeddings->similarity([0.0, 0.0], [1.0, 1.0]));
    }

    public function testDifferentLengthsUseTheShorterOne(): void
    {
        $this->assertEqualsWithDelta(
            1.0,
            $this->embeddings->similarity([1.0, 2.0, 3.0, 4.0], [1.0, 2.0, 3.0]),
            1e-9
        );
    }
}
