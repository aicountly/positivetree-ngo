<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class GotenbergPdfService
{
    private const A4_WIDTH_INCHES = '8.27';
    private const A4_HEIGHT_INCHES = '11.69';

    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->resolveBaseUrl() !== null;
    }

    public function renderPdf(string $html, array $printSettings = []): string
    {
        $baseUrl = $this->resolveBaseUrl();
        if ($baseUrl === null) {
            throw new RuntimeException('Gotenberg base URL is not configured.');
        }

        $endpoint = rtrim($baseUrl, '/') . '/forms/chromium/convert/html';

        $tempDir = sys_get_temp_dir() . '/gotenberg-' . bin2hex(random_bytes(6));
        if (!mkdir($tempDir, 0700, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Failed to create temp directory for Gotenberg payload.');
        }

        $htmlPath = $tempDir . '/index.html';
        if (file_put_contents($htmlPath, $html) === false) {
            $this->cleanupDir($tempDir);
            throw new RuntimeException('Failed to write index.html for Gotenberg payload.');
        }

        $orientation = strtolower((string) ($printSettings['orientation'] ?? 'portrait'));
        $landscape = $orientation === 'landscape';

        $fields = [
            'paperWidth' => self::A4_WIDTH_INCHES,
            'paperHeight' => self::A4_HEIGHT_INCHES,
            'marginTop' => '0',
            'marginRight' => '0',
            'marginBottom' => '0',
            'marginLeft' => '0',
            'printBackground' => 'true',
            'preferCssPageSize' => 'true',
            'scale' => '1.0',
            'landscape' => $landscape ? 'true' : 'false',
        ];

        $postData = [];
        foreach ($fields as $name => $value) {
            $postData[$name] = $value;
        }
        $postData['files'] = new \CURLFile($htmlPath, 'text/html', 'index.html');

        $ch = curl_init($endpoint);
        if ($ch === false) {
            $this->cleanupDir($tempDir);
            throw new RuntimeException('Failed to initialise cURL for Gotenberg request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FAILONERROR => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/pdf',
            ],
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $this->cleanupDir($tempDir);

        if ($errno !== 0 || $response === false) {
            throw new RuntimeException('Gotenberg request failed: ' . ($error !== '' ? $error : 'unknown error'));
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $snippet = is_string($response) ? substr($response, 0, 500) : '';
            throw new RuntimeException(sprintf(
                'Gotenberg returned HTTP %d: %s',
                $statusCode,
                $snippet
            ));
        }

        if ($contentType !== '' && stripos($contentType, 'application/pdf') === false) {
            throw new RuntimeException('Gotenberg response was not a PDF (content-type: ' . $contentType . ').');
        }

        return (string) $response;
    }

    private function resolveBaseUrl(): ?string
    {
        if ($this->baseUrl !== null && trim($this->baseUrl) !== '') {
            return trim($this->baseUrl);
        }

        $url = function_exists('gotenbergUrl') ? gotenbergUrl() : null;
        if ($url === null || trim($url) === '') {
            return null;
        }

        return trim($url);
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach ((array) @scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === false) {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
