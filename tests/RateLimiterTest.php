<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\RateLimiter;
use PDO;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('CREATE TABLE rate_limit (
            cle TEXT PRIMARY KEY,
            compteur INTEGER NOT NULL DEFAULT 0,
            fenetre_debut INTEGER NOT NULL
        )');

        $this->limiter = new RateLimiter($pdo);
    }

    public function testAllowsUpToTheLimitThenBlocks(): void
    {
        $key = 'ask:1';

        $this->assertTrue($this->limiter->allow($key, 3, 60));
        $this->assertTrue($this->limiter->allow($key, 3, 60));
        $this->assertTrue($this->limiter->allow($key, 3, 60));
        $this->assertFalse($this->limiter->allow($key, 3, 60));
    }

    public function testDifferentKeysHaveSeparateWindows(): void
    {
        $this->assertTrue($this->limiter->allow('ask:1', 1, 60));
        $this->assertFalse($this->limiter->allow('ask:1', 1, 60));
        $this->assertTrue($this->limiter->allow('ask:2', 1, 60));
    }

    public function testWindowResetsAfterExpiry(): void
    {
        $key = 'translate:1';

        $this->assertTrue($this->limiter->allow($key, 1, 0));
        $this->assertTrue($this->limiter->allow($key, 1, 0));
    }
}
