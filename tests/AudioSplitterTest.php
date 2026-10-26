<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\AudioSplitter;
use PHPUnit\Framework\TestCase;

final class AudioSplitterTest extends TestCase
{
    public function testWavDurationAndSplit(): void
    {
        $rate = 8000;
        $seconds = 12;
        $pcm = str_repeat("\x00\x00", $rate * $seconds);
        $fmt = pack('v', 1) . pack('v', 1) . pack('V', $rate) . pack('V', $rate * 2)
            . pack('v', 2) . pack('v', 16);
        $dataSize = strlen($pcm);
        $header = 'RIFF' . pack('V', 4 + 8 + strlen($fmt) + 8 + $dataSize) . 'WAVE'
            . 'fmt ' . pack('V', strlen($fmt)) . $fmt
            . 'data' . pack('V', $dataSize);

        $path = sys_get_temp_dir() . '/split_' . bin2hex(random_bytes(4)) . '.wav';
        file_put_contents($path, $header . $pcm);

        $splitter = new AudioSplitter();

        $this->assertEqualsWithDelta(12.0, (float) $splitter->duration($path), 0.5);

        $parts = $splitter->split($path, 5);
        $this->assertCount(3, $parts);

        foreach ($parts as $part) {
            $this->assertLessThanOrEqual(5.5, (float) $splitter->duration($part));
            @unlink($part);
        }

        @unlink($path);
    }

    public function testShortWavIsNotSplit(): void
    {
        $rate = 8000;
        $pcm = str_repeat("\x00\x00", $rate * 2);
        $fmt = pack('v', 1) . pack('v', 1) . pack('V', $rate) . pack('V', $rate * 2)
            . pack('v', 2) . pack('v', 16);
        $dataSize = strlen($pcm);
        $header = 'RIFF' . pack('V', 4 + 8 + strlen($fmt) + 8 + $dataSize) . 'WAVE'
            . 'fmt ' . pack('V', strlen($fmt)) . $fmt
            . 'data' . pack('V', $dataSize);

        $path = sys_get_temp_dir() . '/short_' . bin2hex(random_bytes(4)) . '.wav';
        file_put_contents($path, $header . $pcm);

        $parts = (new AudioSplitter())->split($path, 5);

        $this->assertSame([$path], $parts);

        @unlink($path);
    }
}
