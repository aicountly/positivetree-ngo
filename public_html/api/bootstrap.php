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

function bootstrapAuthorizationHeader(): void
{
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return;
    }

    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        return;
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (!empty($headers['Authorization'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $headers['Authorization'];
        } elseif (!empty($headers['authorization'])) {
            $_SERVER['HTTP_AUTHORIZATION'] = $headers['authorization'];
        }
    }
}

bootstrapAuthorizationHeader();

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

function generatePublicReceiptToken(): string
{
    return bin2hex(random_bytes(16));
}

function uploadsDir(): string
{
    $dir = __DIR__ . '/data/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
}

function documentLogoPath(): string
{
    return __DIR__ . '/assets/documents/logo.png';
}

function documentLogoDataUri(): ?string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $path = documentLogoPath();
    if (!is_readable($path)) {
        return null;
    }

    $fallback = static function () use ($path): string {
        return 'data:image/png;base64,' . base64_encode((string) file_get_contents($path));
    };

    if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) {
        $cached = $fallback();
        return $cached;
    }

    $source = @imagecreatefrompng($path);
    if ($source === false) {
        $cached = $fallback();
        return $cached;
    }

    $width = imagesx($source);
    $height = imagesy($source);
    $dest = imagecreatetruecolor($width, $height);
    if ($dest === false) {
        imagedestroy($source);
        $cached = $fallback();
        return $cached;
    }

    imagealphablending($dest, false);
    imagesavealpha($dest, true);
    $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
    imagefilledrectangle($dest, 0, 0, $width, $height, $transparent);

    imagealphablending($source, false);
    imagesavealpha($source, true);

    $matteThreshold = 25;
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $color = imagecolorat($source, $x, $y);
            $alpha = ($color >> 24) & 0x7F;
            $red = ($color >> 16) & 0xFF;
            $green = ($color >> 8) & 0xFF;
            $blue = $color & 0xFF;

            if ($red <= $matteThreshold && $green <= $matteThreshold && $blue <= $matteThreshold) {
                imagesetpixel($dest, $x, $y, $transparent);
                continue;
            }

            $pixel = imagecolorallocatealpha($dest, $red, $green, $blue, $alpha);
            imagesetpixel($dest, $x, $y, $pixel);
        }
    }

    ob_start();
    imagepng($dest);
    $pngData = (string) ob_get_clean();
    imagedestroy($source);
    imagedestroy($dest);

    $cached = 'data:image/png;base64,' . base64_encode($pngData);
    return $cached;
}

function documentAssetPath(string $filename): string
{
    return __DIR__ . '/assets/documents/' . basename($filename);
}

function documentAssetDataUri(string $filename): ?string
{
    $path = documentAssetPath($filename);
    if (!is_readable($path)) {
        return null;
    }

    $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'application/octet-stream',
    };

    return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
}

function documentSignatureDataUri(?string $uploadFilename): ?string
{
    if ($uploadFilename !== null && $uploadFilename !== '') {
        $path = uploadsDir() . '/' . basename($uploadFilename);
        if (is_readable($path)) {
            $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };

            return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
        }
    }

    return documentAssetDataUri('signature-default.svg');
}

function normalizePan(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $pan = strtoupper(trim($value));
    if ($pan === '') {
        return null;
    }

    if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
        return null;
    }

    return $pan;
}

function validatePan(?string $value): ?string
{
    if ($value === null || trim($value) === '') {
        return 'Donor PAN is required before issuing certificate';
    }

    if (normalizePan($value) === null) {
        return 'Invalid PAN format. Expected format: ABCDE1234F';
    }

    return null;
}

/** @return array{0: ?string, 1: ?string} [pan, error] */
function parseOptionalPanInput(mixed $value): array
{
    if ($value === null || trim((string) $value) === '') {
        return [null, null];
    }

    $pan = normalizePan((string) $value);
    if ($pan === null) {
        return [null, 'Invalid PAN format. Expected format: ABCDE1234F'];
    }

    return [$pan, null];
}
