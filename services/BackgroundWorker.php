<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Launches a PHP CLI script as a detached process, so long tasks (OCR, ASR)
 * neither block the HTTP response nor the single-threaded `php -S` server.
 */
final class BackgroundWorker
{
    /**
     * @param list<string> $args
     */
    public static function spawn(string $script, array $args = []): void
    {
        // Under php-fpm/Apache PHP_BINARY may point to the SAPI binary, not the CLI.
        $php = getenv('PHP_CLI_BINARY') ?: PHP_BINARY;
        $command = escapeshellarg($php) . ' -d memory_limit=512M ' . escapeshellarg($script);
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            // `start /B` detaches; the empty "" is the window title argument.
            pclose(popen('start "" /B ' . $command . ' > NUL 2>&1', 'r'));
        } else {
            exec($command . ' > /dev/null 2>&1 &');
        }
    }
}
