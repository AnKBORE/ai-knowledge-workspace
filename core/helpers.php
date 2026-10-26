<?php

declare(strict_types=1);

/**
 * Global view helpers (subset of the foundation's helpers.php).
 * Loaded from bootstrap.php before any view is rendered.
 */

if (!function_exists('e')) {
    /**
     * Escape any value for HTML output. Every view variable goes through this.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        $config = require dirname(__DIR__) . '/config/app.php';

        return $config[$key] ?? $default;
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        // Absolute base when APP_URL is set; otherwise derive the base path from
        // the front controller (works with `php -S -t public` and sub-directories).
        $base = rtrim((string) getenv('APP_URL'), '/');
        if ($base === '') {
            $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
            $dir = str_replace('\\', '/', dirname($script));
            $dir = rtrim($dir, '/');
            $base = ($dir === '' || $dir === '.') ? '' : $dir;
        }

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('brand_mark')) {
    /**
     * Neutral product mark: a network of three nodes (same as the foundation).
     */
    function brand_mark(): string
    {
        return '<span class="brand-mark" aria-hidden="true">'
            . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"'
            . ' stroke-linecap="round" stroke-linejoin="round">'
            . '<circle cx="12" cy="5.2" r="2.3"/><circle cx="5" cy="18" r="2.3"/>'
            . '<circle cx="19" cy="18" r="2.3"/>'
            . '<path d="M10.3 7.2 6.5 15.7M13.7 7.2l3.8 8.5M7.3 18h9.4"/>'
            . '</svg></span>';
    }
}
