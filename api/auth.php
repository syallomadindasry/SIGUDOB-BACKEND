<?php
// FILE: backend/api/auth.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

function auth_config(): array
{
    $config = require __DIR__ . '/../config.php';
    return is_array($config) ? $config : [];
}

function auth_secret(): string
{
    $config = auth_config();
    return (string)($config['jwt_secret'] ?? ($config['jwt']['secret'] ?? ''));
}

function normalize_role_value($value): string
{
    return strtolower(trim((string)$value));
}

function require_auth(): array
{
    $token = bearer_token();

    if (!$token) {
        respond(401, ['error' => 'Unauthorized']);
    }

    $secret = auth_secret();
    if ($secret === '') {
        respond(500, ['error' => 'JWT secret belum dikonfigurasi']);
    }

    try {
        $payload = jwt_verify($token, $secret);
    } catch (Throwable $e) {
        respond(401, ['error' => 'Token tidak valid']);
    }

    if (!is_array($payload)) {
        respond(401, ['error' => 'Token tidak valid']);
    }

    $payload['role'] = normalize_role_value($payload['role'] ?? $payload['type'] ?? '');
    $payload['id_admin'] = (int)($payload['id_admin'] ?? $payload['sub'] ?? $payload['user_id'] ?? 0);
    $payload['id_gudang'] = (int)($payload['id_gudang'] ?? 0);
    $payload['nama'] = (string)($payload['nama'] ?? $payload['username'] ?? '');

    if ($payload['id_admin'] <= 0) {
        respond(401, ['error' => 'Token tidak valid']);
    }

    return $payload;
}

function auth_ctx(array $payload): array
{
    $idAdmin = (int)($payload['id_admin'] ?? $payload['sub'] ?? $payload['user_id'] ?? 0);
    $idGudang = (int)($payload['id_gudang'] ?? 0);
    $role = normalize_role_value($payload['role'] ?? $payload['type'] ?? '');
    $nama = (string)($payload['nama'] ?? $payload['username'] ?? '');

    if ($idAdmin <= 0) {
        respond(401, ['error' => 'Unauthorized']);
    }

    try {
        $user = db_one(
            "SELECT id_admin, nama, role, id_gudang
             FROM user
             WHERE id_admin = ?
             LIMIT 1",
            [$idAdmin]
        );
    } catch (Throwable $e) {
        respond(500, ['error' => 'Gagal membaca data user', 'detail' => $e->getMessage()]);
    }

    if (!$user) {
        respond(401, ['error' => 'User tidak ditemukan']);
    }

    return [
        'user_id' => (int)$user['id_admin'],
        'id_admin' => (int)$user['id_admin'],
        'nama' => (string)$user['nama'],
        'role' => normalize_role_value($user['role'] ?? $role),
        'id_gudang' => (int)($user['id_gudang'] ?? $idGudang),
    ];
}

function require_role(array $payload, array $roles): void
{
    $currentRole = normalize_role_value($payload['role'] ?? '');
    $allowed = array_map('normalize_role_value', $roles);

    if (!in_array($currentRole, $allowed, true)) {
        respond(403, ['error' => 'Forbidden']);
    }
}