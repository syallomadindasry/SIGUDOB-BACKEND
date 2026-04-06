<?php
// FILE: backend/api/gudang.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['dinkes', 'puskesmas']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function json_body(): array {
  $raw = file_get_contents('php://input');
  $b = json_decode($raw ?: '[]', true);
  return is_array($b) ? $b : [];
}

function is_puskesmas(array $me): bool {
  return ($me['role'] ?? '') === 'puskesmas';
}

function is_dinkes(array $me): bool {
  return ($me['role'] ?? '') === 'dinkes';
}

if ($method === 'GET') {
  $id = (int)($_GET['id'] ?? 0);

  // Puskesmas: hanya boleh lihat gudang tujuan (dinkes/dinas)
  if (is_puskesmas($me)) {
    if ($id > 0) {
      $row = db_one(
        "SELECT id_gudang, nama_gudang, alamat, kota, telepon
         FROM gudang
         WHERE id_gudang = ?
           AND (LOWER(nama_gudang) LIKE '%dinkes%' OR LOWER(nama_gudang) LIKE '%dinas%')
         LIMIT 1",
        [$id]
      );
      if (!$row) respond(404, ['error' => 'Gudang tidak ditemukan']);
      respond(200, $row);
    }

    $rows = db_all(
      "SELECT id_gudang, nama_gudang, alamat, kota, telepon
       FROM gudang
       WHERE (LOWER(nama_gudang) LIKE '%dinkes%' OR LOWER(nama_gudang) LIKE '%dinas%')
       ORDER BY id_gudang ASC"
    );
    respond(200, $rows);
  }

  // Dinkes: boleh lihat semua
  if ($id > 0) {
    $row = db_one(
      "SELECT id_gudang, nama_gudang, alamat, kota, telepon
       FROM gudang
       WHERE id_gudang = ?
       LIMIT 1",
      [$id]
    );
    if (!$row) respond(404, ['error' => 'Gudang tidak ditemukan']);
    respond(200, $row);
  }

  $rows = db_all(
    "SELECT id_gudang, nama_gudang, alamat, kota, telepon
     FROM gudang
     ORDER BY id_gudang ASC"
  );
  respond(200, $rows);
}

if ($method === 'POST') {
  // CRUD gudang hanya dinkes
  if (!is_dinkes($me)) respond(403, ['error' => 'Forbidden']);

  $b = json_body();
  $nama = trim((string)($b['nama_gudang'] ?? ''));
  if ($nama === '') respond(400, ['error' => 'nama_gudang wajib']);

  $alamat  = trim((string)($b['alamat'] ?? ''));
  $kota    = trim((string)($b['kota'] ?? ''));
  $telepon = trim((string)($b['telepon'] ?? ''));

  $stmt = db()->prepare("INSERT INTO gudang (nama_gudang, alamat, kota, telepon) VALUES (?,?,?,?)");
  $stmt->execute([
    $nama,
    $alamat ?: null,
    $kota ?: null,
    $telepon ?: null,
  ]);

  $id = (int)db()->lastInsertId();
  audit_log($me['user_id'], 'CREATE', 'gudang', $id, ['nama_gudang' => $nama]);

  respond(201, ['id_gudang' => $id, 'message' => 'Gudang berhasil ditambahkan']);
}

if ($method === 'PUT') {
  // CRUD gudang hanya dinkes
  if (!is_dinkes($me)) respond(403, ['error' => 'Forbidden']);

  $b = json_body();
  $id = (int)($b['id_gudang'] ?? 0);
  if ($id <= 0) respond(400, ['error' => 'id_gudang wajib']);

  $exists = db_one("SELECT id_gudang FROM gudang WHERE id_gudang = ? LIMIT 1", [$id]);
  if (!$exists) respond(404, ['error' => 'Gudang tidak ditemukan']);

  $nama = trim((string)($b['nama_gudang'] ?? ''));
  if ($nama === '') respond(400, ['error' => 'nama_gudang wajib']);

  $alamat  = trim((string)($b['alamat'] ?? ''));
  $kota    = trim((string)($b['kota'] ?? ''));
  $telepon = trim((string)($b['telepon'] ?? ''));

  $stmt = db()->prepare("UPDATE gudang SET nama_gudang=?, alamat=?, kota=?, telepon=? WHERE id_gudang=?");
  $stmt->execute([
    $nama,
    $alamat ?: null,
    $kota ?: null,
    $telepon ?: null,
    $id,
  ]);

  audit_log($me['user_id'], 'UPDATE', 'gudang', $id, ['nama_gudang' => $nama]);

  respond(200, ['message' => 'Gudang berhasil diupdate']);
}

respond(405, ['error' => 'Method not allowed']);