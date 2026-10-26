<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\TranscriptionService;
use App\Services\Ai\VisionService;
use PHPUnit\Framework\TestCase;

final class MultiModalServicesTest extends TestCase
{
    private string $tmp = '';

    protected function tearDown(): void
    {
        if ($this->tmp !== '' && is_file($this->tmp)) {
            @unlink($this->tmp);
        }
    }

    private function tempFile(string $content = 'x'): string
    {
        $this->tmp = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($this->tmp, $content);

        return $this->tmp;
    }

    public function testTranscriptionReturnsText(): void
    {
        $service = new TranscriptionService(new FakeMetaClient());

        $this->assertSame('transcribed meeting text', $service->transcribe($this->tempFile('audio')));
    }

    public function testTranscriptionThrowsOnMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);

        (new TranscriptionService(new FakeMetaClient()))->transcribe('/no/such/file.mp3');
    }

    public function testVisionReturnsText(): void
    {
        $service = new VisionService(new FakeMetaClient());

        $this->assertSame('scanned document text', $service->extractText($this->tempFile('img')));
    }

    public function testVisionThrowsOnMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);

        (new VisionService(new FakeMetaClient()))->extractText('/no/such/scan.png');
    }
}
