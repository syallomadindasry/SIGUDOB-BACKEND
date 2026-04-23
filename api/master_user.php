<?php
// FILE: backend/api/master_user.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
$me = auth_ctx($payload);

$role = strtolower(trim((string)($me['role'] ?? $payload['role'] ?? '')));
$method = request_method();

if ($role !== 'dinkes') {
    respond(403, ['error' => 'Akses ditolak. Hanya dinkes.']);
}

function input_data(): array
{
    $data = request_input();
    if (!empty($data)) {
        return $data;
    }

    if (in_array(request_method(), ['PUT', 'PATCH', 'DELETE'], true)) {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            parse_str($raw, $parsed);
            if (is_array($parsed)) {
                return $parsed;
            }
        }
    }

    return [];
}

function clean_role(string $value): string
{
    $role = strtolower(trim($value));
    return in_array($role, ['dinkes', 'puskesmas'], true) ? $role : '';
}

function clean_name(string $value): string
{
    return trim($value);
}

function clean_password(string $value): string
{
    return trim($value);
}

function clean_gudang_id($value): int
{
    return (int)$value;
}

function find_user_by_id(int $id): ?array
{
    $row = db_one(
        "SELECT
            u.id_admin,
            u.nama,
            u.role,
            u.id_gudang,
            u.created_at,
            g.nama_gudang,
            g.kode_gudang,
            g.jenis_gudang
         FROM user u
         LEFT JOIN gudang g ON g.id_gudang = u.id_gudang
         WHERE u.id_admin = ?
         LIMIT 1",
        [$id]
    );

    return $row ?: null;
}

function ensure_gudang_exists(int $idGudang): void
{
    $gudang = db_one(
        "SELECT id_gudang, nama_gudang
         FROM gudang
         WHERE id_gudang = ?
         LIMIT 1",
        [$idGudang]
    );

    if (!$gudang) {
        respond(422, ['error' => 'Gudang tidak ditemukan']);
    }
}

function ensure_name_unique(string $nama, ?int $excludeId = null): void
{
    if ($excludeId && $excludeId > 0) {
        $exists = db_one(
            "SELECT id_admin
             FROM user
             WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?))
               AND id_admin <> ?
             LIMIT 1",
            [$nama, $excludeId]
        );
    } else {
        $exists = db_one(
            "SELECT id_admin
             FROM user
             WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?))
             LIMIT 1",
            [$nama]
        );
    }

    if ($exists) {
        respond(422, ['error' => 'Nama user sudah digunakan']);
    }
}

try {
    if ($method === 'GET') {
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $row = find_user_by_id($id);

            if (!$row) {
                respond(404, ['error' => 'User tidak ditemukan']);
            }

            respond(200, $row);
        }

        $rows = db_all(
            "SELECT
                u.id_admin,
                u.nama,
                u.role,
                u.id_gudang,
                u.created_at,
                g.nama_gudang,
                g.kode_gudang,
                g.jenis_gudang
             FROM user u
             LEFT JOIN gudang g ON g.id_gudang = u.id_gudang
             ORDER BY u.id_admin ASC"
        );

        respond(200, $rows);
    }

    if ($method === 'POST') {
        $data = input_data();

        $nama = clean_name((string)($data['nama'] ?? ''));
        $password = clean_password((string)($data['password'] ?? ''));
        $newRole = clean_role((string)($data['role'] ?? ''));
        $idGudang = clean_gudang_id($data['id_gudang'] ?? 0);

        if ($nama === '' || $password === '' || $newRole === '' || $idGudang <= 0) {
            respond(422, ['error' => 'Field wajib: nama, password, role, id_gudang']);
        }

        ensure_name_unique($nama);
        ensure_gudang_exists($idGudang);

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = db()->prepare(
            "INSERT INTO user (nama, password, role, id_gudang)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nama, $hash, $newRole, $idGudang]);

        $id = (int)db()->lastInsertId();
        $row = find_user_by_id($id);

        respond(201, [
            'message' => 'User berhasil ditambahkan',
            'user' => $row,
        ]);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $data = input_data();

        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            respond(422, ['error' => 'ID user wajib diisi']);
        }

        $existing = db_one(
            "SELECT id_admin, nama, role, id_gudang
             FROM user
             WHERE id_admin = ?
             LIMIT 1",
            [$id]
        );

        if (!$existing) {
            respond(404, ['error' => 'User tidak ditemukan']);
        }

        $nama = array_key_exists('nama', $data)
            ? clean_name((string)$data['nama'])
            : (string)$existing['nama'];

        $newRole = array_key_exists('role', $data)
            ? clean_role((string)$data['role'])
            : strtolower(trim((string)$existing['role']));

        $idGudang = array_key_exists('id_gudang', $data)
            ? clean_gudang_id($data['id_gudang'])
            : (int)$existing['id_gudang'];

        $password = array_key_exists('password', $data)
            ? clean_password((string)$data['password'])
            : '';

        if ($nama === '' || $newRole === '' || $idGudang <= 0) {
            respond(422, ['error' => 'Field tidak valid']);
        }

        ensure_name_unique($nama, $id);
        ensure_gudang_exists($idGudang);

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = db()->prepare(
                "UPDATE user
                 SET nama = ?, password = ?, role = ?, id_gudang = ?
                 WHERE id_admin = ?"
            );
            $stmt->execute([$nama, $hash, $newRole, $idGudang, $id]);
        } else {
            $stmt = db()->prepare(
                "UPDATE user
                 SET nama = ?, role = ?, id_gudang = ?
                 WHERE id_admin = ?"
            );
            $stmt->execute([$nama, $newRole, $idGudang, $id]);
        }

        $row = find_user_by_id($id);

        respond(200, [
            'message' => 'User berhasil diperbarui',
            'user' => $row,
        ]);
    }

    if ($method === 'DELETE') {
        $data = input_data();
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            respond(422, ['error' => 'ID user wajib diisi']);
        }

        if ($id === (int)$me['id_admin']) {
            respond(422, ['error' => 'User login saat ini tidak boleh dihapus']);
        }

        $existing = db_one(
            "SELECT id_admin, nama
             FROM user
             WHERE id_admin = ?
             LIMIT 1",
            [$id]
        );

        if (!$existing) {
            respond(404, ['error' => 'User tidak ditemukan']);
        }

        $stmt = db()->prepare("DELETE FROM user WHERE id_admin = ?");
        $stmt->execute([$id]);

        respond(200, [
            'message' => 'User berhasil dihapus',
            'id' => $id,
        ]);
    }

    respond(405, ['error' => 'Method not allowed']);
} catch (Throwable $e) {
    respond(500, [
        'error' => 'Gagal memproses master user',
        'detail' => $e->getMessage(),
    ]);
}