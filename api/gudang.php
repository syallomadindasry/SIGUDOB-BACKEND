<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['dinkes', 'puskesmas']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function json_body(): array
{
    $raw = file_get_contents('php://input');
    $b = json_decode($raw ?: '[]', true);
    return is_array($b) ? $b : [];
}

function is_dinkes(array $me): bool
{
    return ($me['role'] ?? '') === 'dinkes';
}

function gudang_role_from_name(string $namaGudang): string
{
    $lower = function_exists('mb_strtolower')
        ? mb_strtolower(trim($namaGudang), 'UTF-8')
        : strtolower(trim($namaGudang));

    if (strpos($lower, 'dinkes') !== false || strpos($lower, 'dinas') !== false) {
        return 'dinkes';
    }

    return 'puskesmas';
}

function unique_username(string $base): string
{
    $base = trim($base);
    if ($base === '') {
        $base = 'User Gudang';
    }

    $exists = db_one("SELECT id_admin FROM `user` WHERE nama = ? LIMIT 1", [$base]);
    if (!$exists) {
        return $base;
    }

    $i = 2;
    while (true) {
        $candidate = $base . ' ' . $i;
        $exists = db_one("SELECT id_admin FROM `user` WHERE nama = ? LIMIT 1", [$candidate]);
        if (!$exists) {
            return $candidate;
        }
        $i++;
    }
}

function get_primary_user_by_gudang(int $idGudang): ?array
{
    return db_one(
        "
        SELECT id_admin, nama, role, id_gudang, created_at
        FROM `user`
        WHERE id_gudang = ?
        ORDER BY id_admin ASC
        LIMIT 1
        ",
        [$idGudang]
    );
}

function create_default_user_for_gudang(int $idGudang, string $namaGudang): array
{
    $username = unique_username($namaGudang);
    $passwordPlain = '123456';
    $passwordHash = password_hash($passwordPlain, PASSWORD_BCRYPT);
    $role = gudang_role_from_name($namaGudang);

    db_exec(
        "
        INSERT INTO `user` (nama, password, role, id_gudang, created_at)
        VALUES (?, ?, ?, ?, NOW())
        ",
        [$username, $passwordHash, $role, $idGudang]
    );

    $created = db_one("SELECT LAST_INSERT_ID() AS id_admin");

    return [
        'id_admin' => (int)($created['id_admin'] ?? 0),
        'nama' => $username,
        'role' => $role,
        'id_gudang' => $idGudang,
        'default_password' => $passwordPlain,
    ];
}

function sync_primary_user_name_with_gudang(int $idGudang, string $oldNamaGudang, string $newNamaGudang): ?array
{
    $user = get_primary_user_by_gudang($idGudang);
    if (!$user) {
        return null;
    }

    $currentName = trim((string)($user['nama'] ?? ''));
    $oldNamaGudang = trim($oldNamaGudang);
    $newNamaGudang = trim($newNamaGudang);

    if ($currentName !== $oldNamaGudang) {
        return $user;
    }

    $newUsername = $newNamaGudang;
    $duplicate = db_one(
        "SELECT id_admin FROM `user` WHERE nama = ? AND id_admin <> ? LIMIT 1",
        [$newUsername, (int)$user['id_admin']]
    );

    if ($duplicate) {
        $newUsername = unique_username($newNamaGudang);
    }

    $newRole = gudang_role_from_name($newNamaGudang);

    db_exec(
        "
        UPDATE `user`
        SET nama = ?, role = ?
        WHERE id_admin = ?
        ",
        [$newUsername, $newRole, (int)$user['id_admin']]
    );

    return db_one(
        "
        SELECT id_admin, nama, role, id_gudang, created_at
        FROM `user`
        WHERE id_admin = ?
        LIMIT 1
        ",
        [(int)$user['id_admin']]
    );
}

function generate_kode_gudang(): string
{
    $row = db_one(
        "
        SELECT MAX(CAST(SUBSTRING_INDEX(kode_gudang, '-', -1) AS UNSIGNED)) AS max_kode
        FROM gudang
        WHERE kode_gudang IS NOT NULL
          AND kode_gudang <> ''
        "
    );

    $next = (int)($row['max_kode'] ?? 0) + 1;
    return 'GD-' . $next;
}

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {
        $row = db_one(
            "
            SELECT
                id_gudang,
                kode_gudang,
                nama_gudang,
                jenis_gudang,
                status_gudang,
                alamat,
                kota,
                telepon,
                nama_kepala
            FROM gudang
            WHERE id_gudang = ?
            LIMIT 1
            ",
            [$id]
        );

        if (!$row) {
            respond(404, ['error' => 'Gudang tidak ditemukan']);
        }

        respond(200, $row);
    }

    $rows = db_all(
        "
        SELECT
            id_gudang,
            kode_gudang,
            nama_gudang,
            jenis_gudang,
            status_gudang,
            alamat,
            kota,
            telepon,
            nama_kepala
        FROM gudang
        ORDER BY id_gudang ASC
        "
    );

    respond(200, $rows);
}

if ($method === 'POST') {
    if (!is_dinkes($me)) {
        respond(403, ['error' => 'Forbidden']);
    }

    $b = json_body();

    $nama = trim((string)($b['nama_gudang'] ?? ''));
    if ($nama === '') {
        respond(400, ['error' => 'nama_gudang wajib']);
    }

    $alamat = trim((string)($b['alamat'] ?? ''));
    $kota = trim((string)($b['kota'] ?? ''));
    $telepon = trim((string)($b['telepon'] ?? ''));

    $duplicateGudang = db_one(
        "SELECT id_gudang FROM gudang WHERE LOWER(nama_gudang) = LOWER(?) LIMIT 1",
        [$nama]
    );
    if ($duplicateGudang) {
        respond(409, ['error' => 'Nama gudang sudah digunakan']);
    }

    $kode_gudang = generate_kode_gudang();

    db_exec(
        "
        INSERT INTO gudang (kode_gudang, nama_gudang, alamat, kota, telepon)
        VALUES (?, ?, ?, ?, ?)
        ",
        [
            $kode_gudang,
            $nama,
            $alamat !== '' ? $alamat : null,
            $kota !== '' ? $kota : null,
            $telepon !== '' ? $telepon : null,
        ]
    );

    $idGudangRow = db_one("SELECT LAST_INSERT_ID() AS id_gudang");
    $idGudang = (int)($idGudangRow['id_gudang'] ?? 0);

    $createdUser = create_default_user_for_gudang($idGudang, $nama);

    audit_log($me['user_id'], 'CREATE', 'gudang', $idGudang, [
        'kode_gudang' => $kode_gudang,
        'nama_gudang' => $nama,
        'auto_user' => $createdUser['nama'],
    ]);

    respond(201, [
        'id_gudang' => $idGudang,
        'kode_gudang' => $kode_gudang,
        'message' => 'Gudang berhasil ditambahkan dan akun login otomatis dibuat',
        'akun_login' => [
            'id_admin' => $createdUser['id_admin'],
            'username' => $createdUser['nama'],
            'role' => $createdUser['role'],
            'default_password' => $createdUser['default_password'],
        ],
    ]);
}

if ($method === 'PUT') {
    if (!is_dinkes($me)) {
        respond(403, ['error' => 'Forbidden']);
    }

    $b = json_body();
    $id = (int)($b['id_gudang'] ?? 0);

    if ($id <= 0) {
        respond(400, ['error' => 'id_gudang wajib']);
    }

    $exists = db_one(
        "SELECT id_gudang, nama_gudang, kode_gudang FROM gudang WHERE id_gudang = ? LIMIT 1",
        [$id]
    );
    if (!$exists) {
        respond(404, ['error' => 'Gudang tidak ditemukan']);
    }

    $nama = trim((string)($b['nama_gudang'] ?? ''));
    if ($nama === '') {
        respond(400, ['error' => 'nama_gudang wajib']);
    }

    $duplicateGudang = db_one(
        "SELECT id_gudang FROM gudang WHERE LOWER(nama_gudang) = LOWER(?) AND id_gudang <> ? LIMIT 1",
        [$nama, $id]
    );
    if ($duplicateGudang) {
        respond(409, ['error' => 'Nama gudang sudah digunakan']);
    }

    $alamat = trim((string)($b['alamat'] ?? ''));
    $kota = trim((string)($b['kota'] ?? ''));
    $telepon = trim((string)($b['telepon'] ?? ''));

    db_exec(
        "
        UPDATE gudang
        SET nama_gudang = ?, alamat = ?, kota = ?, telepon = ?
        WHERE id_gudang = ?
        ",
        [
            $nama,
            $alamat !== '' ? $alamat : null,
            $kota !== '' ? $kota : null,
            $telepon !== '' ? $telepon : null,
            $id,
        ]
    );

    $syncedUser = sync_primary_user_name_with_gudang(
        $id,
        (string)$exists['nama_gudang'],
        $nama
    );

    audit_log($me['user_id'], 'UPDATE', 'gudang', $id, [
        'kode_gudang' => $exists['kode_gudang'] ?? null,
        'nama_gudang' => $nama,
        'synced_user' => $syncedUser['nama'] ?? null,
    ]);

    respond(200, [
        'message' => 'Gudang berhasil diupdate',
        'kode_gudang' => $exists['kode_gudang'] ?? null,
        'akun_login' => $syncedUser
            ? [
                'id_admin' => (int)$syncedUser['id_admin'],
                'username' => $syncedUser['nama'],
                'role' => $syncedUser['role'],
            ]
            : null,
    ]);
}

respond(405, ['error' => 'Method not allowed']);