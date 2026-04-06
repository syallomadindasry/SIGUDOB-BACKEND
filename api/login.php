<?php
// File: backend/api/login.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
$config = require __DIR__ . '/../config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(405, ['error' => 'Gunakan POST']);
}

$in = json_input();
$username = trim((string)($in['username'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($username === '' || $password === '') {
  respond(400, ['error' => 'Username dan password wajib diisi']);
}

$sql = "
SELECT
  u.id_admin AS id,
  u.nama AS username,
  u.password AS pass_stored,
  u.role,
  g.id_gudang,
  g.nama_gudang
FROM `user` u
JOIN gudang g ON g.id_gudang = u.id_gudang
WHERE u.nama = ?
LIMIT 1
";

$row = db_one($sql, [$username]);

if (!$row) {
  respond(401, ['error' => 'Username atau password salah']);
}

$stored = (string)$row['pass_stored'];
if (!password_verify($password, $stored)) {
  // NOTE: jika DB masih berisi plaintext, migrasikan ke password_hash().
  respond(401, ['error' => 'Username atau password salah']);
}

$type = stripos((string)$row['nama_gudang'], 'dinkes') !== false ? 'DINKES' : 'PUSKESMAS';

$payload = [
  'sub' => (int)$row['id'],
  'username' => $row['username'],
  'role' => $row['role'],
  'type' => $type,
  'id_gudang' => (int)$row['id_gudang'],
  'nama_gudang' => $row['nama_gudang'],
];

$token = jwt_sign($payload, $config['jwt_secret'], (int)$config['jwt_ttl_seconds']);

respond(200, [
  'token' => $token,
  'user' => [
    'id' => (int)$row['id'],
    'username' => $row['username'],
    'role' => $row['role'],
    'type' => $type,
    'id_gudang' => (int)$row['id_gudang'],
    'nama_gudang' => $row['nama_gudang'],
  ],
]);
