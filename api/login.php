<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

$config = require __DIR__ . '/../config.php';

if (request_method() !== 'POST') {
    respond(405, ['error' => 'Gunakan POST']);
}

function normalize_login_key(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = str_replace(['.', '_', '-'], ' ', $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return trim($value);
}

function shrink_login_key(string $value): string
{
    $value = normalize_login_key($value);
    $value = preg_replace('/\b(admin|gudang)\b/u', ' ', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return trim($value);
}

function build_login_aliases(array $row): array
{
    $username = (string)($row['username'] ?? '');
    $namaGudang = (string)($row['nama_gudang'] ?? '');

    $aliases = [
        $username,
        $namaGudang,
        'admin ' . $username,
        'admin ' . $namaGudang,
        'gudang ' . $username,
        'gudang ' . $namaGudang,
    ];

    $shortUsername = shrink_login_key($username);
    $shortGudang = shrink_login_key($namaGudang);

    if ($shortUsername !== '') {
        $aliases[] = $shortUsername;
        $aliases[] = 'admin ' . $shortUsername;
    }

    if ($shortGudang !== '') {
        $aliases[] = $shortGudang;
        $aliases[] = 'admin ' . $shortGudang;
    }

    if (stripos($username, 'dinkes') !== false || stripos($namaGudang, 'dinkes') !== false) {
        $aliases[] = 'dinkes';
        $aliases[] = 'admin dinkes';
        $aliases[] = 'gudang dinkes';
    }

    if (preg_match('/puskesmas\s*(\d+)/i', $username, $m) || preg_match('/puskesmas\s*(\d+)/i', $namaGudang, $m)) {
        $n = $m[1];
        $aliases[] = "puskesmas {$n}";
        $aliases[] = "admin puskesmas {$n}";
        $aliases[] = "gudang puskesmas {$n}";
        $aliases[] = "pkm{$n}";
        $aliases[] = "admin pkm{$n}";
    }

    $result = [];
    foreach ($aliases as $alias) {
        $a = normalize_login_key((string)$alias);
        if ($a !== '') {
            $result[$a] = true;
        }

        $b = shrink_login_key((string)$alias);
        if ($b !== '') {
            $result[$b] = true;
        }
    }

    return array_keys($result);
}

function verify_password_value(string $plain, string $stored): bool
{
    if ($plain === '' || $stored === '') {
        return false;
    }

    $info = password_get_info($stored);
    $isHashed = !empty($info['algo']);

    if ($isHashed) {
        return password_verify($plain, $stored);
    }

    return hash_equals($stored, $plain);
}

function should_upgrade_hash(string $plain, string $stored): bool
{
    if ($plain === '' || $stored === '') {
        return false;
    }

    $info = password_get_info($stored);
    $isHashed = !empty($info['algo']);

    if (!$isHashed) {
        return true;
    }

    return password_needs_rehash($stored, PASSWORD_DEFAULT);
}

function upgrade_hash_if_needed(int $userId, string $plain, string $stored): void
{
    if (!should_upgrade_hash($plain, $stored)) {
        return;
    }

    $newHash = password_hash($plain, PASSWORD_DEFAULT);

    db_exec(
        'UPDATE `user` SET password = ? WHERE id_admin = ?',
        [$newHash, $userId]
    );
}

$input = request_input();
$usernameInput = trim((string)($input['username'] ?? $input['nama'] ?? ''));
$passwordInput = (string)($input['password'] ?? '');

if ($usernameInput === '' || $passwordInput === '') {
    respond(400, ['error' => 'Username dan password wajib diisi']);
}

$sql = "
    SELECT
        u.id_admin AS id,
        u.nama AS username,
        u.password AS pass_stored,
        u.role,
        u.id_gudang,
        g.nama_gudang
    FROM `user` u
    INNER JOIN gudang g ON g.id_gudang = u.id_gudang
";

$users = db_all($sql);

$inputKeys = array_values(array_unique(array_filter([
    normalize_login_key($usernameInput),
    shrink_login_key($usernameInput),
])));

$matched = null;

foreach ($users as $user) {
    $aliases = build_login_aliases($user);

    foreach ($inputKeys as $key) {
        if (in_array($key, $aliases, true)) {
            $matched = $user;
            break 2;
        }
    }
}

if (!$matched) {
    respond(401, ['error' => 'Username atau password salah']);
}

$userId = (int)($matched['id'] ?? 0);
$storedPassword = (string)($matched['pass_stored'] ?? '');

if (!verify_password_value($passwordInput, $storedPassword)) {
    respond(401, ['error' => 'Username atau password salah']);
}

upgrade_hash_if_needed($userId, $passwordInput, $storedPassword);

$namaGudang = trim((string)($matched['nama_gudang'] ?? ''));
$type = stripos($namaGudang, 'dinkes') !== false ? 'DINKES' : 'PUSKESMAS';

$payload = [
    'sub' => $userId,
    'username' => (string)($matched['username'] ?? ''),
    'role' => (string)($matched['role'] ?? ''),
    'type' => $type,
    'id_gudang' => (int)($matched['id_gudang'] ?? 0),
    'nama_gudang' => $namaGudang,
];

$token = jwt_sign(
    $payload,
    (string)$config['jwt_secret'],
    (int)$config['jwt_ttl_seconds']
);

respond(200, [
    'token' => $token,
    'user' => [
        'id' => $userId,
        'username' => (string)($matched['username'] ?? ''),
        'role' => (string)($matched['role'] ?? ''),
        'type' => $type,
        'id_gudang' => (int)($matched['id_gudang'] ?? 0),
        'nama_gudang' => $namaGudang,
        'warehouse' => [
            'code' => (int)($matched['id_gudang'] ?? 0),
            'id_gudang' => (int)($matched['id_gudang'] ?? 0),
            'name' => $namaGudang,
            'nama_gudang' => $namaGudang,
            'type' => $type,
        ],
    ],
]);
