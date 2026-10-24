<?php

declare(strict_types=1);

namespace App\Tests;

use App\Core\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    private string $file = '';

    protected function tearDown(): void
    {
        if ($this->file !== '' && is_file($this->file)) {
            @unlink($this->file);
        }
    }

    private function write(string $content): string
    {
        $this->file = tempnam(sys_get_temp_dir(), 'envtest');
        file_put_contents($this->file, $content);

        return $this->file;
    }

    public function testLoadReadsKeyValuePairsAndStripsQuotes(): void
    {
        Env::load($this->write("FOO=bar\nBAZ=\"quoted\"\nNUM=42\n"));

        $this->assertSame('bar', getenv('FOO'));
        $this->assertSame('quoted', getenv('BAZ'));
        $this->assertSame('42', getenv('NUM'));

        putenv('FOO');
        putenv('BAZ');
        putenv('NUM');
    }

    public function testLoadIgnoresCommentsAndLinesWithoutEquals(): void
    {
        Env::load($this->write("# a comment\nNOT_A_VAR\nKEY=value\n"));

        $this->assertFalse(getenv('NOT_A_VAR'));
        $this->assertSame('value', getenv('KEY'));

        putenv('KEY');
    }

    public function testLoadDoesNotOverrideExistingEnvironment(): void
    {
        putenv('EXISTING=original');

        Env::load($this->write("EXISTING=override\n"));

        $this->assertSame('original', getenv('EXISTING'));

        putenv('EXISTING');
    }
}
