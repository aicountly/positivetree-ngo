<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_readable($autoload)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'API dependencies are not installed. Run composer install in the api directory.',
    ]);
    exit;
}

require_once $autoload;

function loadEnv(string $path): void
{
    if (!is_readable($path)) {
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

        [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
        $name = trim($name);
        $value = trim($value, " \t\"'");

        if ($name !== '' && getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

function config(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function nowIso(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
}

function rawBody(): string
{
    static $raw = null;
    if ($raw === null) {
        $raw = file_get_contents('php://input') ?: '';
    }

    return $raw;
}

function jsonBody(): array
{
    $raw = rawBody();
    if (trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function normalizeDonatedAt(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return nowIso();
    }

    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value . 'T00:00:00Z';
    }

    return $value;
}

function escapeLike(string $term): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);
}
