<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

function b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder > 0) {
        $data .= str_repeat('=', 4 - $remainder);
    }

    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? '' : $decoded;
}

function jwt_sign(array $payload, string $secret, int $ttlSeconds = 28800): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();

    $payload['iat'] = $now;
    $payload['exp'] = $now + max(1, $ttlSeconds);

    $headerJson = json_encode($header, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!is_string($headerJson) || !is_string($payloadJson)) {
        throw new RuntimeException('Gagal membuat JWT');
    }

    $h = b64url_encode($headerJson);
    $p = b64url_encode($payloadJson);
    $s = b64url_encode(hash_hmac('sha256', "{$h}.{$p}", $secret, true));

    return "{$h}.{$p}.{$s}";
}

function jwt_verify(string $token, string $secret): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$h, $p, $s] = $parts;

    if ($h === '' || $p === '' || $s === '') {
        return null;
    }

    $expected = b64url_encode(hash_hmac('sha256', "{$h}.{$p}", $secret, true));
    if (!hash_equals($expected, $s)) {
        return null;
    }

    $headerRaw = b64url_decode($h);
    $payloadRaw = b64url_decode($p);

    if ($headerRaw === '' || $payloadRaw === '') {
        return null;
    }

    $header = json_decode($headerRaw, true);
    $payload = json_decode($payloadRaw, true);

    if (!is_array($header) || !is_array($payload)) {
        return null;
    }

    if (($header['alg'] ?? '') !== 'HS256') {
        return null;
    }

    $now = time();

    if (isset($payload['nbf']) && $now < (int)$payload['nbf']) {
        return null;
    }

    if (isset($payload['iat']) && (int)$payload['iat'] > $now + 60) {
        return null;
    }

    if (isset($payload['exp']) && $now >= (int)$payload['exp']) {
        return null;
    }

    return $payload;
}
