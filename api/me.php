<?php
// FILE: backend/api/me.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (request_method() !== 'GET') {
    respond(405, ['error' => 'Method not allowed']);
}

$payload = require_auth();
$me = auth_ctx($payload);

try {
    $row = db_one(
        "SELECT
            u.id_admin,
            u.nama,
            u.role,
            u.id_gudang,
            u.created_at,
            g.id_gudang AS gudang_id,
            g.kode_gudang,
            g.nama_gudang,
            g.jenis_gudang,
            g.status_gudang
         FROM user u
         LEFT JOIN gudang g ON g.id_gudang = u.id_gudang
         WHERE u.id_admin = ?
         LIMIT 1",
        [$me['id_admin']]
    );

    if (!$row) {
        respond(404, ['error' => 'User tidak ditemukan']);
    }

    $role = strtolower(trim((string)($row['role'] ?? '')));

    respond(200, [
        'message' => 'OK',
        'user' => [
            'id' => (int)$row['id_admin'],
            'id_admin' => (int)$row['id_admin'],
            'user_id' => (int)$row['id_admin'],
            'nama' => (string)$row['nama'],
            'username' => (string)$row['nama'],
            'role' => $role,
            'type' => (string)($row['jenis_gudang'] ?? ''),
            'id_gudang' => (int)($row['id_gudang'] ?? 0),
            'nama_gudang' => (string)($row['nama_gudang'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
            'warehouse' => [
                'code' => (int)($row['gudang_id'] ?? $row['id_gudang'] ?? 0),
                'id_gudang' => (int)($row['gudang_id'] ?? $row['id_gudang'] ?? 0),
                'kode_gudang' => (string)($row['kode_gudang'] ?? ''),
                'name' => (string)($row['nama_gudang'] ?? ''),
                'nama_gudang' => (string)($row['nama_gudang'] ?? ''),
                'type' => (string)($row['jenis_gudang'] ?? ''),
                'jenis_gudang' => (string)($row['jenis_gudang'] ?? ''),
                'status_gudang' => (string)($row['status_gudang'] ?? ''),
            ],
        ],
    ]);
} catch (Throwable $e) {
    respond(500, [
        'error' => 'Gagal memuat profil user',
        'detail' => $e->getMessage(),
    ]);
}