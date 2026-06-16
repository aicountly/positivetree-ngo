<?php

declare(strict_types=1);

namespace App;

use App\Http\Request;
use App\Http\Response;

class Router
{
    /** @var array<int, array{methods: string[], pattern: string, handler: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add(['GET'], $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add(['POST'], $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->add(['PUT'], $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add(['PATCH'], $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add(['DELETE'], $pattern, $handler);
    }

    /** @param string[] $methods */
    private function add(array $methods, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'methods' => $methods,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if (!in_array($request->method, $route['methods'], true)) {
                continue;
            }

            $regex = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $route['pattern']) . '$#';
            if (!preg_match($regex, $request->path, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                fn ($key) => !is_int($key),
                ARRAY_FILTER_USE_KEY
            );

            ($route['handler'])($request, $params);
            return;
        }

        Response::error('Not found', 404);
    }
}
