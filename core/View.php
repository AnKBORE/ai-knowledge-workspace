<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple PHP template engine: a view is a PHP file rendered inside a layout.
 * Every value must be escaped with e() before output.
 */
final class View
{
    public function __construct(private readonly string $viewsPath)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], string $layout = 'main'): string
    {
        $file = $this->viewsPath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        $content = $this->capture($file, $data);

        if ($layout === '') {
            return $content;
        }

        $layoutFile = $this->viewsPath . '/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Layout not found: ' . $layout);
        }

        return $this->capture($layoutFile, $data + ['content' => $content]);
    }

    /** @param array<string, mixed> $data */
    private function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            require $file;
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return (string) ob_get_clean();
    }
}
