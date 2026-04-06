<?php
// FILE: backend/api/auth.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/jwt.php';

if (!function_exists('require_auth')) {
  function require_auth(): array {
    $config = require __DIR__ . '/../config.php';

    $token = bearer_token();
    if (!$token) respond(401, ['error' => 'Unauthorized']);

    $payload = jwt_verify($token, (string)($config['jwt_secret'] ?? ''));
    if (!is_array($payload)) respond(401, ['error' => 'Invalid token']);

    if (empty($payload['sub']) || empty($payload['role']) || empty($payload['id_gudang'])) {
      respond(401, ['error' => 'Token tidak lengkap']);
    }

    return $payload;
  }
}

if (!function_exists('require_role')) {
  function require_role(array $payload, array $allowedRoles): void {
    $role = (string)($payload['role'] ?? '');
    if (!in_array($role, $allowedRoles, true)) respond(403, ['error' => 'Forbidden']);
  }
}

if (!function_exists('auth_ctx')) {
  function auth_ctx(array $payload): array {
    return [
      'user_id' => (int)($payload['sub'] ?? 0),
      'username' => (string)($payload['username'] ?? ''),
      'role' => (string)($payload['role'] ?? ''),
      'type' => (string)($payload['type'] ?? ''),
      'id_gudang' => (int)($payload['id_gudang'] ?? 0),
      'nama_gudang' => (string)($payload['nama_gudang'] ?? ''),
      'payload' => $payload,
    ];
  }
}

if (!function_exists('enforce_gudang_scope')) {
  function enforce_gudang_scope(array $me, int $gudangId): void {
    if ($me['role'] === 'puskesmas' && $gudangId !== $me['id_gudang']) {
      respond(403, ['error' => 'Tidak boleh mengakses gudang lain']);
    }
  }
}