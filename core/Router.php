<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal HTTP router (a subset of the foundation's Router).
 *
 * Registers (method, path) pairs and dispatches to a callable. Exact-match
 * only — enough for the demo endpoints. Replace it with the full Router when
 * the foundation is integrated.
 */
final class Router
{
    /** @var array<int, array{method: string, path: string, handler: callable}> */
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => '/' . trim($path, '/'),
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && $route['path'] === $path) {
                ($route['handler'])();

                return;
            }
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Not found.'], JSON_UNESCAPED_UNICODE);
    }
}
