<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
        public readonly string $rawBody = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $rawBody = rawBody();

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        $body = [];
        if ($rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            $body = is_array($decoded) ? $decoded : [];
        }

        return new self(
            $method,
            $path,
            $_GET,
            $body,
            $headers,
            $rawBody,
        );
    }

    public function bearerToken(): ?string
    {
        $auth = $this->headers['authorization'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? $default;
    }
}
