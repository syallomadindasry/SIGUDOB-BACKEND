<?php
// FILE: backend/api/login.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$config = require __DIR__ . '/../config.php';

if (request_method() !== 'POST') {
    respond(405, ['error' => 'Gunakan POST']);
}

$rawBody = file_get_contents('php://input');
$input = request_input();

$username = trim((string)($input['nama'] ?? $input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($username === '' || $password === '') {
    respond(422, [
        'error' => 'Username dan password wajib diisi',
        'debug' => [
            'content_type' => (string)($_SERVER['CONTENT_TYPE'] ?? ''),
            'post_keys' => array_keys($_POST ?? []),
            'input_keys' => array_keys($input),
            'raw_body' => $rawBody,
            'parsed_input' => $input,
        ],
    ]);
}

$sql = <<<SQL
SELECT
    u.id_admin,
    u.nama,
    u.password,
    u.role,
    u.id_gudang,
    g.id_gudang AS gudang_id,
    g.kode_gudang,
    g.nama_gudang,
    g.jenis_gudang,
    g.status_gudang
FROM user u
LEFT JOIN gudang g ON g.id_gudang = u.id_gudang
WHERE LOWER(TRIM(u.nama)) = LOWER(TRIM(:nama))
LIMIT 1
SQL;

try {
    $user = db_one($sql, ['nama' => $username]);
} catch (Throwable $e) {
    error_log('Login query error: ' . $e->getMessage());
    respond(500, ['error' => 'Gagal memproses login']);
}

if (!$user) {
    respond(401, ['error' => 'Username atau password salah']);
}

$hash = (string)($user['password'] ?? '');
if ($hash === '' || !password_verify($password, $hash)) {
    respond(401, ['error' => 'Username atau password salah']);
}

$secret = (string)($config['jwt_secret'] ?? ($config['jwt']['secret'] ?? ''));
$ttl = (int)($config['jwt_ttl_seconds'] ?? ($config['jwt']['ttl'] ?? 28800));

if ($secret === '') {
    respond(500, ['error' => 'Konfigurasi token belum lengkap']);
}

try {
    $token = jwt_sign([
        'sub' => (int)$user['id_admin'],
        'id_admin' => (int)$user['id_admin'],
        'nama' => (string)$user['nama'],
        'username' => (string)$user['nama'],
        'role' => strtolower(trim((string)$user['role'])),
        'id_gudang' => (int)$user['id_gudang'],
        'nama_gudang' => (string)($user['nama_gudang'] ?? ''),
        'type' => (string)($user['jenis_gudang'] ?? ''),
    ], $secret, $ttl);
} catch (Throwable $e) {
    error_log('JWT sign error: ' . $e->getMessage());
    respond(500, ['error' => 'Gagal membuat token login']);
}

respond(200, [
    'message' => 'Login berhasil',
    'token' => $token,
    'user' => [
        'id' => (int)$user['id_admin'],
        'id_admin' => (int)$user['id_admin'],
        'nama' => (string)$user['nama'],
        'username' => (string)$user['nama'],
        'role' => strtolower(trim((string)$user['role'])),
        'type' => (string)($user['jenis_gudang'] ?? ''),
        'id_gudang' => (int)$user['id_gudang'],
        'nama_gudang' => (string)($user['nama_gudang'] ?? ''),
        'warehouse' => [
            'code' => (int)($user['gudang_id'] ?? $user['id_gudang']),
            'id_gudang' => (int)($user['gudang_id'] ?? $user['id_gudang']),
            'kode_gudang' => (string)($user['kode_gudang'] ?? ''),
            'name' => (string)($user['nama_gudang'] ?? ''),
            'nama_gudang' => (string)($user['nama_gudang'] ?? ''),
            'type' => (string)($user['jenis_gudang'] ?? ''),
            'jenis_gudang' => (string)($user['jenis_gudang'] ?? ''),
            'status_gudang' => (string)($user['status_gudang'] ?? ''),
        ],
    ],
]);