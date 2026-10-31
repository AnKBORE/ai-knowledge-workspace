<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\LexiqueMoore;
use App\Services\Ai\Proverbes;
use PHPUnit\Framework\TestCase;

final class MooreResourcesTest extends TestCase
{
    public function testLexiconIsLoadedAndSearchable(): void
    {
        $this->assertGreaterThan(1000, LexiqueMoore::count());

        $prompt = LexiqueMoore::promptFor('Le soldat mange du riz blanc.', 'fr', 'mo');

        $this->assertStringContainsString('riz', $prompt);
    }

    public function testLexiconIgnoresSameSourceAndTarget(): void
    {
        $this->assertSame('', LexiqueMoore::promptFor('Le soldat mange.', 'fr', 'fr'));
    }

    public function testProverbsAreLoaded(): void
    {
        $this->assertGreaterThanOrEqual(40, Proverbes::count());

        $prompt = Proverbes::prompt(3);

        $this->assertStringContainsString('Mooré proverbs', $prompt);
        $this->assertStringContainsString(' — ', $prompt);
    }
}
