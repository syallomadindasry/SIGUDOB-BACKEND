<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

function require_dinkes(): void
{
    $role = $_SERVER['HTTP_X_USER_ROLE'] ?? '';
    if (strtolower(trim((string) $role)) !== 'dinkes') {
        respond(403, ['error' => 'Akses ditolak. Hanya dinkes.']);
    }
}

function now_mysql(): string
{
    return date('Y-m-d H:i:s');
}

require_dinkes();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $rows = db_all(
            "
            SELECT
                u.id_admin,
                u.nama,
                u.role,
                u.id_gudang,
                u.created_at,
                g.nama_gudang
            FROM `user` u
            LEFT JOIN gudang g ON g.id_gudang = u.id_gudang
            ORDER BY u.id_admin ASC
            "
        );

        respond(200, $rows);
    }

    if ($method === 'POST') {
        $data = json_input();

        $nama = trim((string) ($data['nama'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = trim((string) ($data['role'] ?? 'puskesmas'));
        $idGudang = (int) ($data['id_gudang'] ?? 0);

        if ($nama === '') {
            respond(422, ['error' => 'Nama wajib diisi']);
        }

        if ($password === '') {
            respond(422, ['error' => 'Password wajib diisi']);
        }

        if (!in_array($role, ['dinkes', 'puskesmas'], true)) {
            respond(422, ['error' => 'Role tidak valid']);
        }

        if ($idGudang <= 0) {
            respond(422, ['error' => 'Gudang wajib dipilih']);
        }

        $exists = db_one("SELECT id_admin FROM `user` WHERE nama = ? LIMIT 1", [$nama]);
        if ($exists) {
            respond(409, ['error' => 'Nama user sudah digunakan']);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        db_exec(
            "
            INSERT INTO `user` (nama, password, role, id_gudang, created_at)
            VALUES (?, ?, ?, ?, ?)
            ",
            [$nama, $hash, $role, $idGudang, now_mysql()]
        );

        $created = db_one("SELECT LAST_INSERT_ID() AS id_admin");
        respond(201, [
            'ok' => true,
            'id_admin' => (int) ($created['id_admin'] ?? 0),
        ]);
    }

    if ($method === 'PUT') {
        $data = json_input();

        $idAdmin = (int) ($data['id_admin'] ?? 0);
        $nama = trim((string) ($data['nama'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = trim((string) ($data['role'] ?? 'puskesmas'));
        $idGudang = (int) ($data['id_gudang'] ?? 0);

        if ($idAdmin <= 0) {
            respond(422, ['error' => 'id_admin wajib']);
        }

        if ($nama === '') {
            respond(422, ['error' => 'Nama wajib diisi']);
        }

        if (!in_array($role, ['dinkes', 'puskesmas'], true)) {
            respond(422, ['error' => 'Role tidak valid']);
        }

        if ($idGudang <= 0) {
            respond(422, ['error' => 'Gudang wajib dipilih']);
        }

        $row = db_one("SELECT id_admin FROM `user` WHERE id_admin = ? LIMIT 1", [$idAdmin]);
        if (!$row) {
            respond(404, ['error' => 'User tidak ditemukan']);
        }

        $duplicate = db_one(
            "SELECT id_admin FROM `user` WHERE nama = ? AND id_admin <> ? LIMIT 1",
            [$nama, $idAdmin]
        );
        if ($duplicate) {
            respond(409, ['error' => 'Nama user sudah digunakan']);
        }

        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            db_exec(
                "
                UPDATE `user`
                SET nama = ?, password = ?, role = ?, id_gudang = ?
                WHERE id_admin = ?
                ",
                [$nama, $hash, $role, $idGudang, $idAdmin]
            );
        } else {
            db_exec(
                "
                UPDATE `user`
                SET nama = ?, role = ?, id_gudang = ?
                WHERE id_admin = ?
                ",
                [$nama, $role, $idGudang, $idAdmin]
            );
        }

        respond(200, ['ok' => true]);
    }

    if ($method === 'DELETE') {
        $idAdmin = (int) ($_GET['id_admin'] ?? 0);

        if ($idAdmin <= 0) {
            respond(422, ['error' => 'id_admin wajib']);
        }

        $row = db_one("SELECT id_admin FROM `user` WHERE id_admin = ? LIMIT 1", [$idAdmin]);
        if (!$row) {
            respond(404, ['error' => 'User tidak ditemukan']);
        }

        db_exec("DELETE FROM `user` WHERE id_admin = ?", [$idAdmin]);

        respond(200, ['ok' => true]);
    }

    respond(405, ['error' => 'Method tidak didukung']);
} catch (Throwable $e) {
    respond(500, ['error' => $e->getMessage()]);
}