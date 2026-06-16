<?php

declare(strict_types=1);

namespace App;

class Jwt
{
    public static function encode(array $payload, string $secret, int $ttlSeconds = 86400): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload['exp'] = time() + $ttlSeconds;
        $payload['iat'] = time();

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $expected = self::base64UrlEncode(
            hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $secret, true)
        );

        if (!hash_equals($expected, $signatureB64)) {
            return null;
        }

        $headerJson = self::base64UrlDecode($headerB64);
        if ($headerJson === null) {
            return null;
        }

        $header = json_decode($headerJson, true);
        if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
            return null;
        }

        $payloadJson = self::base64UrlDecode($payloadB64);
        if ($payloadJson === null) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}
