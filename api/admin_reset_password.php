<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

require_once __DIR__ . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
  respond(405, ['message' => 'Gunakan POST']);
}

$me = require_auth();
require_role($me, ['dinkes']);

$in = json_input();
$id_admin = (int)($in['id_admin'] ?? 0);
$username = trim((string)($in['username'] ?? ''));
$new_password = (string)($in['new_password'] ?? '');

if ($new_password === '') respond(400, ['message' => 'new_password wajib']);

if ($id_admin <= 0 && $username === '') {
  respond(400, ['message' => 'Sediakan id_admin atau username']);
}

$hash = password_hash($new_password, PASSWORD_DEFAULT);

if ($id_admin > 0) {
  $stmt = db()->prepare("UPDATE user SET password=? WHERE id_admin=?");
  $stmt->execute([$hash, $id_admin]);
  respond(200, ['message' => 'Password direset', 'updated' => $stmt->rowCount()]);
}

$stmt = db()->prepare("UPDATE user SET password=? WHERE nama=?");
$stmt->execute([$hash, $username]);
respond(200, ['message' => 'Password direset', 'updated' => $stmt->rowCount()]);
