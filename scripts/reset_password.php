<?php
// File: backend/scripts/reset_password.php
//
// Usage:
//   php backend/scripts/reset_password.php [username] [new_password]
//
// Default:
//   username: dinkes_admin
//   new_password: Admin@12345

require_once __DIR__ . '/../api/db.php';

$username = trim((string)($argv[1] ?? 'dinkes_admin'));
$newPassword = trim((string)($argv[2] ?? 'Admin@12345'));

if ($username === '' || $newPassword === '') {
  fwrite(STDERR, "ERR: username dan new_password wajib.\n");
  exit(1);
}

if (mb_strlen($newPassword) < 8) {
  fwrite(STDERR, "ERR: new_password minimal 8 karakter.\n");
  exit(1);
}

$row = db_one("SELECT id_admin, nama FROM user WHERE nama = ? LIMIT 1", [$username]);
if (!$row) {
  fwrite(STDERR, "ERR: user tidak ditemukan: {$username}\n");
  exit(1);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
if (!$hash) {
  fwrite(STDERR, "ERR: gagal membuat hash password.\n");
  exit(1);
}

db_exec("UPDATE user SET password = ? WHERE id_admin = ?", [$hash, (int)$row['id_admin']]);

fwrite(STDOUT, "OK: password direset untuk user '{$username}'.\n");
exit(0);
