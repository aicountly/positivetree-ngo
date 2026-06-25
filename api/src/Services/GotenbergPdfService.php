<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class GotenbergPdfService
{
    private const A4_WIDTH_INCHES = '8.27';
    private const A4_HEIGHT_INCHES = '11.69';

    /** @var array<string, mixed> */
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? self::loadConfigFromEnv();
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadConfigFromEnv(): array
    {
        $baseUrl = self::envString('GOTENBERG_BASE_URL');
        if ($baseUrl === '') {
            $baseUrl = self::envString('GOTENBERG_URL');
        }

        return [
            'base_url' => rtrim($baseUrl, '/'),
            'html_path' => self::envString('GOTENBERG_HTML_PATH', '/forms/chromium/convert/html'),
            'health_path' => self::envString('GOTENBERG_HEALTH_PATH', '/health'),
            'timeout' => self::envInt('GOTENBERG_TIMEOUT_SECONDS', 60),
            'connect_timeout' => self::envInt('GOTENBERG_CONNECT_TIMEOUT_SECONDS', 10),
            'verify_ssl' => self::envBool('GOTENBERG_VERIFY_SSL', true),
            'auth_mode' => strtolower(self::envString('GOTENBERG_AUTH_MODE', 'none')),
            'username' => self::envString('GOTENBERG_USERNAME'),
            'password' => self::envString('GOTENBERG_PASSWORD'),
            'bearer_token' => self::envString('GOTENBERG_BEARER_TOKEN'),
            'header_name' => self::envString('GOTENBERG_AUTH_HEADER_NAME'),
            'header_value' => self::envString('GOTENBERG_AUTH_HEADER_VALUE'),
        ];
    }

    public function isConfigured(): bool
    {
        return ($this->config['base_url'] ?? '') !== '';
    }

    /**
     * @return array{ok: bool, status: int, base_url: string, safe_error: ?string}
     */
    public function health(): array
    {
        $baseUrl = (string) ($this->config['base_url'] ?? '');
        if ($baseUrl === '') {
            return [
                'ok' => false,
                'status' => 0,
                'base_url' => '',
                'safe_error' => 'GOTENBERG_BASE_URL is not configured.',
            ];
        }

        $url = $baseUrl . (string) ($this->config['health_path'] ?? '/health');

        try {
            $result = $this->curlRequest('HEAD', $url, ['Accept' => 'application/json'], null);
            $status = (int) $result['status'];
            $ok = $status >= 200 && $status < 300;

            return [
                'ok' => $ok,
                'status' => $status,
                'base_url' => $baseUrl,
                'safe_error' => $ok ? null : $this->safeErrorFor($status),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'base_url' => $baseUrl,
                'safe_error' => 'PDF service health probe failed: ' . $e->getMessage(),
            ];
        }
    }

    public function renderPdf(string $html, array $printSettings = []): string
    {
        $baseUrl = (string) ($this->config['base_url'] ?? '');
        if ($baseUrl === '') {
            throw new RuntimeException(
                'GOTENBERG_BASE_URL is not configured. Set it in .env to your Gotenberg service URL.'
            );
        }

        $endpoint = $baseUrl . (string) ($this->config['html_path'] ?? '/forms/chromium/convert/html');

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

        $postData = $fields;
        $postData['files'] = new \CURLFile($htmlPath, 'text/html; charset=UTF-8', 'index.html');

        try {
            $result = $this->curlRequest('POST', $endpoint, ['Accept' => 'application/pdf'], $postData);
        } finally {
            $this->cleanupDir($tempDir);
        }

        $statusCode = (int) $result['status'];
        $response = (string) $result['body'];
        $contentType = (string) ($result['content_type'] ?? '');

        if ($statusCode < 200 || $statusCode >= 300) {
            $snippet = substr($response, 0, 500);
            throw new RuntimeException(sprintf(
                '%s (HTTP %d%s)',
                $this->safeErrorFor($statusCode),
                $statusCode,
                $snippet !== '' ? ': ' . $snippet : ''
            ));
        }

        if ($contentType !== '' && stripos($contentType, 'application/pdf') === false) {
            throw new RuntimeException('Gotenberg response was not a PDF (content-type: ' . $contentType . ').');
        }

        return $response;
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>|null $postFields
     *
     * @return array{status: int, body: string, content_type: ?string}
     */
    private function curlRequest(string $method, string $url, array $headers, array|null $postFields): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is not available.');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization' && ($this->config['auth_mode'] ?? '') === 'basic') {
                continue;
            }
            $headerLines[] = $name . ': ' . $value;
        }

        $this->applyAuth($headerLines);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Failed to initialise cURL for Gotenberg request.');
        }

        $options = [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => max(2, (int) ($this->config['connect_timeout'] ?? 10)),
            CURLOPT_TIMEOUT => max(5, (int) ($this->config['timeout'] ?? 60)),
            CURLOPT_SSL_VERIFYPEER => (bool) ($this->config['verify_ssl'] ?? true),
            CURLOPT_SSL_VERIFYHOST => ($this->config['verify_ssl'] ?? true) ? 2 : 0,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_FAILONERROR => false,
        ];

        if ($postFields !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postFields;
        }

        if (($this->config['auth_mode'] ?? '') === 'basic'
            && (($this->config['username'] ?? '') !== '' || ($this->config['password'] ?? '') !== '')) {
            $options[CURLOPT_USERPWD] = (string) $this->config['username'] . ':' . (string) $this->config['password'];
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch) ?: 'unknown error';
            curl_close($ch);
            throw new RuntimeException('Gotenberg request failed: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr((string) $raw, 0, $headerSize);
        $body = substr((string) $raw, $headerSize);
        $contentType = null;
        if (preg_match('/^Content-Type:\s*([^\r\n]+)/im', $rawHeaders, $matches)) {
            $contentType = trim($matches[1]);
        }

        return [
            'status' => $status,
            'body' => $body,
            'content_type' => $contentType,
        ];
    }

    /**
     * @param list<string> $headerLines
     */
    private function applyAuth(array &$headerLines): void
    {
        switch ($this->config['auth_mode'] ?? 'none') {
            case 'basic':
                break;
            case 'bearer':
                if (($this->config['bearer_token'] ?? '') !== '') {
                    $headerLines[] = 'Authorization: Bearer ' . $this->config['bearer_token'];
                }
                break;
            case 'custom_header':
                if (($this->config['header_name'] ?? '') !== '') {
                    $headerLines[] = $this->config['header_name'] . ': ' . ($this->config['header_value'] ?? '');
                }
                break;
            case 'none':
            default:
                break;
        }
    }

    private function safeErrorFor(int $status): string
    {
        return match (true) {
            $status === 400 => 'PDF service rejected the request',
            $status === 401 => 'PDF service authentication failed — check GOTENBERG_AUTH_MODE and credentials',
            $status === 403 => 'PDF service access denied',
            $status === 409 => 'PDF service rendering failed',
            $status === 503 => 'PDF service unavailable',
            $status === 504 => 'PDF service timed out',
            default => 'PDF service error',
        };
    }

    private static function envString(string $key, string $default = ''): string
    {
        $raw = function_exists('config') ? config($key) : false;
        if ($raw === null || $raw === false || trim((string) $raw) === '') {
            $raw = getenv($key);
        }
        if ($raw === false || $raw === null || trim((string) $raw) === '') {
            return $default;
        }

        return trim((string) $raw);
    }

    private static function envInt(string $key, int $default): int
    {
        $raw = self::envString($key, '');
        if ($raw === '') {
            return $default;
        }

        return (int) $raw;
    }

    private static function envBool(string $key, bool $default): bool
    {
        $raw = self::envString($key, '');
        if ($raw === '') {
            return $default;
        }

        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
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
