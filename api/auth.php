<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/jwt.php';

if (!function_exists('require_auth')) {
    function require_auth(): array
    {
        $token = bearer_token();
        if (!$token) {
            respond(401, ['error' => 'Unauthorized']);
        }

        $config = require __DIR__ . '/../config.php';

        try {
            $payload = jwt_verify($token, (string) $config['jwt_secret']);
        } catch (Throwable $e) {
            respond(401, ['error' => 'Token tidak valid']);
        }

        if (!is_array($payload)) {
            respond(401, ['error' => 'Token tidak valid']);
        }

        return $payload;
    }
}

if (!function_exists('require_role')) {
    function require_role(array $payload, array $roles): void
    {
        $role = (string) ($payload['role'] ?? '');
        if ($role === '' || !in_array($role, $roles, true)) {
            respond(403, ['error' => 'Forbidden']);
        }
    }
}

if (!function_exists('auth_ctx')) {
    function auth_ctx(array $payload): array
    {
        return [
            'user_id' => (int) ($payload['sub'] ?? 0),
            'username' => (string) ($payload['username'] ?? ''),
            'role' => (string) ($payload['role'] ?? ''),
            'id_gudang' => (int) ($payload['id_gudang'] ?? 0),
            'nama_gudang' => (string) ($payload['nama_gudang'] ?? ''),
            'type' => (string) ($payload['type'] ?? ''),
        ];
    }
}