<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/api/index.php';
    return true;
}

$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false;
}

if (is_file(__DIR__ . '/index.html')) {
    include __DIR__ . '/index.html';
    return true;
}

http_response_code(404);
echo 'Not found';
return true;
