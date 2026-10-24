<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal .env loader (subset of the foundation's Env).
 *
 * Reads KEY=VALUE pairs from a .env file into the process environment so that
 * getenv() works throughout the application. Existing environment variables
 * are never overridden.
 */
final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
        }
    }
}
