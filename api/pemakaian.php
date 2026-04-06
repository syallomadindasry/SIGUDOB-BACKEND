<?php
// FILE: backend/api/pemakaian.php

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
    $hdr = db_one("SELECT id, id_gudang FROM pemakaian WHERE id = ? LIMIT 1", [$id]);
    if (!$hdr) respond(404, ['error' => 'Pemakaian tidak ditemukan']);
    if ((int)$hdr['id_gudang'] !== $me['id_gudang']) respond(403, ['error' => 'Forbidden']);

    $rows = db_all(
      "SELECT pd.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
       FROM pemakaian_detail pd
       JOIN data_batch b ON b.id_batch = pd.id_batch
       JOIN data_obat  o ON o.id_obat  = b.id_obat
       WHERE pd.id_pemakaian = ?",
      [$id]
    );
    respond(200, $rows);
  }

  $rows = db_all(
    "SELECT p.*, u.nama AS nama_admin,
            (SELECT COUNT(*) FROM pemakaian_detail pd WHERE pd.id_pemakaian = p.id) AS total_item
     FROM pemakaian p
     JOIN user u ON u.id_admin = p.id_admin
     WHERE p.id_gudang = ?
     ORDER BY p.created_at DESC",
    [$me['id_gudang']]
  );
  respond(200, $rows);
}

if ($method === 'POST') {
  $b = json_input();
  $type = (string)($b['type'] ?? 'master');

  if ($type === 'master') {
    $stmt = db()->prepare(
      "INSERT INTO pemakaian (no_pemakaian,tanggal,keterangan,id_admin,id_gudang) VALUES (?,?,?,?,?)"
    );
    $stmt->execute([
      (string)($b['no_pemakaian'] ?? ''),
      (string)($b['tanggal'] ?? ''),
      (string)($b['keterangan'] ?? ''),
      $me['user_id'],
      $me['id_gudang'],
    ]);

    $id = (int)db()->lastInsertId();
    audit_log($me['user_id'], 'CREATE', 'pemakaian', $id, ['id_gudang' => $me['id_gudang']]);

    respond(201, ['id' => $id, 'message' => 'Berhasil dibuat']);
  }

  if ($type === 'detail') {
    $id_pemakaian = (int)($b['id_pemakaian'] ?? 0);
    $id_batch = (int)($b['id_batch'] ?? 0);
    $jumlah = (float)($b['jumlah'] ?? 0);

    if ($id_pemakaian <= 0 || $id_batch <= 0 || $jumlah <= 0) {
      respond(400, ['error' => 'Field wajib: id_pemakaian,id_batch,jumlah']);
    }

    $hdr = db_one("SELECT id, id_gudang FROM pemakaian WHERE id = ? LIMIT 1", [$id_pemakaian]);
    if (!$hdr) respond(404, ['error' => 'Pemakaian tidak ditemukan']);
    if ((int)$hdr['id_gudang'] !== $me['id_gudang']) respond(403, ['error' => 'Forbidden']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("SELECT stok FROM stok_batch WHERE id_gudang=? AND id_batch=? FOR UPDATE");
      $stmt->execute([$me['id_gudang'], $id_batch]);
      $stok = (float)($stmt->fetchColumn() ?: 0);

      if ($stok < $jumlah) {
        $pdo->rollBack();
        respond(400, ['error' => 'Stok tidak mencukupi. Stok tersedia: ' . $stok]);
      }

      $stmt = $pdo->prepare("INSERT INTO pemakaian_detail (id_pemakaian,id_batch,jumlah) VALUES (?,?,?)");
      $stmt->execute([$id_pemakaian, $id_batch, $jumlah]);

      $stmt = $pdo->prepare("UPDATE stok_batch SET stok = stok - ? WHERE id_gudang=? AND id_batch=?");
      $stmt->execute([$jumlah, $me['id_gudang'], $id_batch]);

      audit_log($me['user_id'], 'ADD_ITEM', 'pemakaian', $id_pemakaian, [
        'id_batch' => $id_batch,
        'qty' => $jumlah,
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