<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Split an audio file into segments shorter than the model's per-request limit.
 *
 * WAV is split precisely on the PCM data chunk; MP3 is split on frame
 * boundaries (byte offset estimated from the first frame's bitrate, then
 * aligned to the next sync word). Files are streamed, not loaded in memory.
 */
final class AudioSplitter
{
    /**
     * @return list<string> segment paths (the original path when no split is needed)
     */
    public function split(string $path, int $maxSeconds): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'wav' => $this->splitWav($path, $maxSeconds),
            'mp3' => $this->splitMp3($path, $maxSeconds),
            default => [$path],
        };
    }

    /**
     * Estimated duration in seconds, or null when it cannot be computed.
     */
    public function duration(string $path): ?float
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'wav' => $this->wavDuration($path),
            'mp3' => $this->mp3Duration($path),
            default => null,
        };
    }

    /** @return list<string> */
    private function splitWav(string $path, int $maxSeconds): array
    {
        $info = $this->wavInfo($path);
        if ($info === null) {
            return [$path];
        }

        $bytesPerSecond = $info['bytesPerSecond'];
        $blockAlign = $info['blockAlign'];
        $maxBytes = $maxSeconds * $bytesPerSecond;

        if ($info['dataSize'] <= $maxBytes) {
            return [$path];
        }

        $segments = [];
        $position = 0;
        $index = 0;

        while ($position < $info['dataSize']) {
            $size = min($maxBytes, $info['dataSize'] - $position);
            $size -= $size % $blockAlign;
            if ($size <= 0) {
                break;
            }

            $segmentPath = $path . '.part' . $index . '.wav';
            $this->copyRange(
                $path,
                $segmentPath,
                $info['dataOffset'] + $position,
                $size,
                $this->wavHeader($info['fmt'], $size),
            );
            $segments[] = $segmentPath;
            $position += $size;
            $index++;
        }

        return $segments === [] ? [$path] : $segments;
    }

    /** @return list<string> */
    private function splitMp3(string $path, int $maxSeconds): array
    {
        $size = (int) filesize($path);
        $head = file_get_contents($path, false, null, 0, 65536);
        if ($head === false) {
            return [$path];
        }

        $bitrate = $this->firstFrameBitrate($head, $this->skipId3($head));
        if ($bitrate === null || $bitrate <= 0) {
            return [$path];
        }

        $totalSeconds = ($size * 8) / ($bitrate * 1000);
        if ($totalSeconds <= $maxSeconds) {
            return [$path];
        }

        $boundaries = [];
        for ($second = $maxSeconds; $second < $totalSeconds; $second += $maxSeconds) {
            $boundaries[] = $this->alignToFrame($path, (int) (($second / $totalSeconds) * $size));
        }

        $segments = [];
        $start = 0;
        $index = 0;

        foreach ($boundaries as $boundary) {
            if ($boundary <= $start || $boundary >= $size) {
                continue;
            }
            $segmentPath = $path . '.part' . $index . '.mp3';
            $this->copyRange($path, $segmentPath, $start, $boundary - $start);
            $segments[] = $segmentPath;
            $start = $boundary;
            $index++;
        }

        if ($start < $size) {
            $segmentPath = $path . '.part' . $index . '.mp3';
            $this->copyRange($path, $segmentPath, $start, $size - $start);
            $segments[] = $segmentPath;
        }

        return $segments === [] ? [$path] : $segments;
    }

    private function copyRange(string $source, string $target, int $start, int $length, string $header = ''): void
    {
        $in = fopen($source, 'rb');
        $out = fopen($target, 'wb');

        if ($in === false || $out === false) {
            throw new \RuntimeException('Could not open audio files for splitting.');
        }

        if ($header !== '') {
            fwrite($out, $header);
        }

        fseek($in, $start);
        $remaining = $length;

        while ($remaining > 0 && !feof($in)) {
            $chunk = fread($in, min(1048576, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            fwrite($out, $chunk);
            $remaining -= strlen($chunk);
        }

        fclose($in);
        fclose($out);
    }

    /**
     * @return array{fmt: string, dataOffset: int, dataSize: int, bytesPerSecond: int, blockAlign: int}|null
     */
    private function wavInfo(string $path): ?array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        $riff = fread($handle, 12);
        if (strlen($riff) < 12 || substr($riff, 0, 4) !== 'RIFF' || substr($riff, 8, 4) !== 'WAVE') {
            fclose($handle);

            return null;
        }

        $fmt = null;
        $dataOffset = null;
        $dataSize = null;

        while (!feof($handle)) {
            $chunk = fread($handle, 8);
            if (strlen($chunk) < 8) {
                break;
            }

            $id = substr($chunk, 0, 4);
            $size = (int) unpack('V', substr($chunk, 4, 4))[1];
            $dataPos = ftell($handle) ?: 0;

            if ($id === 'fmt ') {
                $fmt = fread($handle, $size);
            } elseif ($id === 'data') {
                $dataOffset = $dataPos;
                $dataSize = $size;
                break;
            } else {
                fseek($handle, $size + ($size % 2), SEEK_CUR);
            }
        }

        fclose($handle);

        if ($fmt === null || strlen($fmt) < 16 || $dataOffset === null || $dataSize === null) {
            return null;
        }

        $channels = (int) unpack('v', substr($fmt, 2, 2))[1];
        $sampleRate = (int) unpack('V', substr($fmt, 4, 4))[1];
        $bits = (int) unpack('v', substr($fmt, 14, 2))[1];
        $blockAlign = max(1, $channels * intdiv($bits, 8));
        $bytesPerSecond = $sampleRate * $blockAlign;

        if ($bytesPerSecond <= 0) {
            return null;
        }

        return [
            'fmt' => $fmt,
            'dataOffset' => $dataOffset,
            'dataSize' => $dataSize,
            'bytesPerSecond' => $bytesPerSecond,
            'blockAlign' => $blockAlign,
        ];
    }

    private function wavHeader(string $fmt, int $dataSize): string
    {
        $riffSize = 4 + 8 + strlen($fmt) + 8 + $dataSize;

        return 'RIFF' . pack('V', $riffSize) . 'WAVE'
            . 'fmt ' . pack('V', strlen($fmt)) . $fmt
            . 'data' . pack('V', $dataSize);
    }

    private function wavDuration(string $path): ?float
    {
        $info = $this->wavInfo($path);

        return $info === null ? null : $info['dataSize'] / $info['bytesPerSecond'];
    }

    private function skipId3(string $data): int
    {
        if (strlen($data) < 10 || substr($data, 0, 3) !== 'ID3') {
            return 0;
        }

        $size = ((ord($data[6]) & 0x7F) << 21)
            | ((ord($data[7]) & 0x7F) << 14)
            | ((ord($data[8]) & 0x7F) << 7)
            | (ord($data[9]) & 0x7F);

        return 10 + $size;
    }

    private function firstFrameBitrate(string $data, int $offset): ?int
    {
        $length = strlen($data);
        $table = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320];

        while ($offset + 4 <= $length) {
            if (ord($data[$offset]) === 0xFF && (ord($data[$offset + 1]) & 0xE0) === 0xE0) {
                $bitrateIndex = (ord($data[$offset + 2]) >> 4) & 0x0F;
                if (isset($table[$bitrateIndex]) && $table[$bitrateIndex] > 0) {
                    return $table[$bitrateIndex];
                }
            }
            $offset++;
        }

        return null;
    }

    private function alignToFrame(string $path, int $offset): int
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return $offset;
        }

        $size = (int) filesize($path);
        fseek($handle, max(0, $offset));
        $buffer = '';

        while (!feof($handle)) {
            $chunk = fread($handle, 65536);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $buffer .= $chunk;
            $length = strlen($buffer);

            for ($i = 0; $i + 4 <= $length; $i++) {
                if (ord($buffer[$i]) === 0xFF && (ord($buffer[$i + 1]) & 0xE0) === 0xE0) {
                    $position = $offset + $i;
                    fclose($handle);

                    return $position < $size ? $position : $size;
                }
            }

            if (strlen($chunk) > 3) {
                $buffer = substr($buffer, -3);
            }
        }

        fclose($handle);

        return $size;
    }

    private function mp3Duration(string $path): ?float
    {
        $head = file_get_contents($path, false, null, 0, 65536);
        if ($head === false) {
            return null;
        }

        $bitrate = $this->firstFrameBitrate($head, $this->skipId3($head));
        $size = (int) filesize($path);

        return $bitrate === null || $bitrate <= 0 ? null : ($size * 8) / ($bitrate * 1000);
    }
}
