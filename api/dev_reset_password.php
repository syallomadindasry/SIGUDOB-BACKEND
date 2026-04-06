<?php
// File: backend/api/dev_reset_password.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

if (strtolower(getenv('APP_ENV') ?: 'prod') !== 'dev') {
  respond(404, ['error' => 'Not found']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(405, ['error' => 'Method not allowed']);
}

$body = json_input();

$username = trim((string)($body['username'] ?? ''));
$newPassword = (string)($body['new_password'] ?? '');

if ($username === '' || $newPassword === '') {
  respond(400, ['error' => 'username and new_password are required']);
}

if (mb_strlen($newPassword) < 8) {
  respond(400, ['error' => 'new_password minimal 8 karakter']);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
if (!$hash) {
  respond(500, ['error' => 'Failed to hash password']);
}

$stmt = db()->prepare('UPDATE user SET password = :password WHERE nama = :nama');
$stmt->execute([
  ':password' => $hash,
  ':nama' => $username,
]);

if ($stmt->rowCount() < 1) {
  respond(404, ['error' => 'User not found']);
}

respond(200, ['ok' => true, 'username' => $username]);
