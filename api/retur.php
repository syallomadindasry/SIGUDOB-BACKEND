<?php
// FILE: backend/api/retur.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
// require_once __DIR__ . '/../lib/ledger.php'; // removed (ledger deleted)
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['puskesmas', 'dinkes']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $id = (int)($_GET['id'] ?? 0);

  if ($id > 0) {
    $hdr = db_one("SELECT id, id_gudang, tujuan FROM retur WHERE id = ? LIMIT 1", [$id]);
    if (!$hdr) respond(404, ['error' => 'Retur tidak ditemukan']);

    $src = (int)$hdr['id_gudang'];
    $dst = (int)$hdr['tujuan'];

    if ($me['role'] === 'puskesmas' && $src !== $me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ($me['role'] === 'dinkes' && $dst !== $me['id_gudang']) respond(403, ['error' => 'Forbidden']);

    $rows = db_all(
      "SELECT rd.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
       FROM retur_detail rd
       JOIN data_batch b ON b.id_batch = rd.id_batch
       JOIN data_obat  o ON o.id_obat  = b.id_obat
       WHERE rd.id_retur = ?",
      [$id]
    );
    respond(200, $rows);
  }

  if ($me['role'] === 'dinkes') {
    $rows = db_all(
      "SELECT r.*, u.nama AS nama_admin, g.nama_gudang AS nama_tujuan,
              (SELECT COUNT(*) FROM retur_detail rd WHERE rd.id_retur = r.id) AS total_item
       FROM retur r
       JOIN user   u ON u.id_admin  = r.id_admin
       JOIN gudang g ON g.id_gudang = r.tujuan
       WHERE r.tujuan = ?
       ORDER BY r.created_at DESC",
      [$me['id_gudang']]
    );
    respond(200, $rows);
  }

  // puskesmas
  $rows = db_all(
    "SELECT r.*, u.nama AS nama_admin, g.nama_gudang AS nama_tujuan,
            (SELECT COUNT(*) FROM retur_detail rd WHERE rd.id_retur = r.id) AS total_item
     FROM retur r
     JOIN user   u ON u.id_admin  = r.id_admin
     JOIN gudang g ON g.id_gudang = r.tujuan
     WHERE r.id_gudang = ?
     ORDER BY r.created_at DESC",
    [$me['id_gudang']]
  );
  respond(200, $rows);
}

if ($method === 'POST') {
  require_role($payload, ['puskesmas']);
  $b = json_input();
  $type = (string)($b['type'] ?? 'master');

  if ($type === 'master') {
    $tujuan = (int)($b['tujuan'] ?? 0);
    if ($tujuan <= 0) respond(400, ['error' => 'Field wajib: tujuan']);

    $stmt = db()->prepare(
      "INSERT INTO retur (no_retur,tanggal,alasan,id_admin,id_gudang,tujuan)
       VALUES (?,?,?,?,?,?)"
    );
    $stmt->execute([
      (string)($b['no_retur'] ?? ''),
      (string)($b['tanggal'] ?? ''),
      (string)($b['alasan'] ?? ''),
      $me['user_id'],
      $me['id_gudang'],
      $tujuan,
    ]);

    $id = (int)db()->lastInsertId();
    audit_log($me['user_id'], 'CREATE', 'retur', $id, [
      'id_gudang' => $me['id_gudang'],
      'tujuan' => $tujuan,
    ]);

    respond(201, ['id' => $id, 'message' => 'Retur berhasil dibuat']);
  }

  if ($type === 'detail') {
    $id_retur = (int)($b['id_retur'] ?? 0);
    $id_batch = (int)($b['id_batch'] ?? 0);
    $jumlah = (float)($b['jumlah'] ?? 0);

    if ($id_retur <= 0 || $id_batch <= 0 || $jumlah <= 0) {
      respond(400, ['error' => 'Field wajib: id_retur,id_batch,jumlah']);
    }

    $hdr = db_one("SELECT id, id_gudang, tujuan FROM retur WHERE id = ? LIMIT 1", [$id_retur]);
    if (!$hdr) respond(404, ['error' => 'Retur tidak ditemukan']);

    $src = (int)$hdr['id_gudang'];
    $dst = (int)$hdr['tujuan'];

    if ($src !== $me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ($dst <= 0) respond(400, ['error' => 'Tujuan retur tidak valid']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("SELECT stok FROM stok_batch WHERE id_gudang=? AND id_batch=? FOR UPDATE");
      $stmt->execute([$src, $id_batch]);
      $stok = (float)($stmt->fetchColumn() ?: 0);

      if ($stok < $jumlah) {
        $pdo->rollBack();
        respond(400, ['error' => 'Stok tidak mencukupi. Stok tersedia: ' . $stok]);
      }

      $stmt = $pdo->prepare("INSERT INTO retur_detail (id_retur,id_batch,jumlah) VALUES (?,?,?)");
      $stmt->execute([$id_retur, $id_batch, $jumlah]);

      $stmt = $pdo->prepare("UPDATE stok_batch SET stok = stok - ? WHERE id_gudang=? AND id_batch=?");
      $stmt->execute([$jumlah, $src, $id_batch]);

      $stmt = $pdo->prepare(
        "INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok)"
      );
      $stmt->execute([$dst, $id_batch, $jumlah]);

      audit_log($me['user_id'], 'ADD_ITEM', 'retur', $id_retur, [
        'id_batch' => $id_batch,
        'qty' => $jumlah,
        'sumber' => $src,
        'tujuan' => $dst,
      ]);

      $pdo->commit();
      respond(201, ['message' => 'Detail ditambahkan, stok diperbarui']);
    } catch (Throwable $e) {
      $pdo->rollBack();
      respond(500, ['error' => 'Gagal simpan detail', 'detail' => $e->getMessage()]);
    }
  }

  respond(400, ['error' => 'type tidak valid']);
}

respond(405, ['error' => 'Method not allowed']);