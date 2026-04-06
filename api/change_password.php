<?php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/password_policy.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['dinkes', 'puskesmas']);
$me = auth_ctx($payload);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(405, ['error' => 'Gunakan POST']);
}

$in = json_input();
$old = (string)($in['old_password'] ?? '');
$new = (string)($in['new_password'] ?? '');

if ($old === '' || $new === '') {
  respond(400, ['error' => 'old_password dan new_password wajib diisi']);
}

$policyErr = validate_password_policy($new);
if ($policyErr) {
  respond(400, ['error' => $policyErr]);
}

$row = db_one("SELECT id_admin, password FROM user WHERE id_admin = ? LIMIT 1", [$me['user_id']]);
if (!$row) respond(404, ['error' => 'User tidak ditemukan']);

$stored = (string)($row['password'] ?? '');
if (!password_verify($old, $stored)) {
  respond(401, ['error' => 'Password lama salah']);
}

$hashed = hash_password($new);
db_exec("UPDATE user SET password = ? WHERE id_admin = ?", [$hashed, $me['user_id']]);

audit_log($me['user_id'], 'CHANGE_PASSWORD', 'user', (int)$me['user_id'], []);
respond(200, ['ok' => true, 'message' => 'Password berhasil diubah']);