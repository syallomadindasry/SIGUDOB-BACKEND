<?php
// FILE: backend/api/pembelian.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['dinkes']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $id = (int)($_GET['id'] ?? 0);

  if ($id > 0) {
    $hdr = db_one("SELECT id, id_gudang FROM pembelian WHERE id = ? LIMIT 1", [$id]);
    if (!$hdr) respond(404, ['error' => 'Pembelian tidak ditemukan']);
    if ((int)$hdr['id_gudang'] !== $me['id_gudang']) respond(403, ['error' => 'Forbidden']);

    $rows = db_all(
      "SELECT pd.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
       FROM pembelian_detail pd
       JOIN data_batch b ON b.id_batch = pd.id_batch
       JOIN data_obat  o ON o.id_obat  = b.id_obat
       WHERE pd.id_pembelian = ?",
      [$id]
    );
    respond(200, $rows);
  }

  $rows = db_all(
    "SELECT p.*, u.nama AS nama_admin,
            (SELECT COUNT(*) FROM pembelian_detail pd WHERE pd.id_pembelian = p.id) AS total_item,
            (SELECT COALESCE(SUM(pd.jumlah*pd.harga),0) FROM pembelian_detail pd WHERE pd.id_pembelian = p.id) AS total_harga
     FROM pembelian p
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
      "INSERT INTO pembelian (no_nota,tanggal,supplier,alamat,kota,telepon,metode_bayar,diskon,catatan,id_admin,id_gudang)
       VALUES (?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
      (string)($b['no_nota'] ?? ''),
      (string)($b['tanggal'] ?? ''),
      (string)($b['supplier'] ?? ''),
      (string)($b['alamat'] ?? ''),
      (string)($b['kota'] ?? ''),
      (string)($b['telepon'] ?? ''),
      (string)($b['metode_bayar'] ?? ''),
      (float)($b['diskon'] ?? 0),
      (string)($b['catatan'] ?? ''),
      $me['user_id'],
      $me['id_gudang'],
    ]);

    $id = (int)db()->lastInsertId();
    audit_log($me['user_id'], 'CREATE', 'pembelian', $id, ['id_gudang' => $me['id_gudang']]);

    respond(201, ['id' => $id, 'message' => 'Nota berhasil dibuat']);
  }

  if ($type === 'detail') {
    $id_pembelian = (int)($b['id_pembelian'] ?? 0);
    $id_batch = (int)($b['id_batch'] ?? 0);
    $jumlah = (float)($b['jumlah'] ?? 0);
    $harga = (float)($b['harga'] ?? 0);

    if ($id_pembelian<=0 || $id_batch<=0 || $jumlah<=0) {
      respond(400, ['error' => 'Field wajib: id_pembelian,id_batch,jumlah']);
    }

    $hdr = db_one("SELECT id, id_gudang FROM pembelian WHERE id = ? LIMIT 1", [$id_pembelian]);
    if (!$hdr) respond(404, ['error' => 'Pembelian tidak ditemukan']);
    if ((int)$hdr['id_gudang'] !== $me['id_gudang']) respond(403, ['error' => 'Forbidden']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare("INSERT INTO pembelian_detail (id_pembelian,id_batch,jumlah,harga) VALUES (?,?,?,?)");
      $stmt->execute([$id_pembelian, $id_batch, $jumlah, $harga]);

      $stmt = $pdo->prepare(
        "INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok)"
      );
      $stmt->execute([$me['id_gudang'], $id_batch, $jumlah]);

      audit_log($me['user_id'], 'ADD_ITEM', 'pembelian', $id_pembelian, [
        'id_batch' => $id_batch,
        'qty' => $jumlah,
        'harga' => $harga,
      ]);

      $pdo->commit();
      respond(201, ['message' => 'Detail berhasil ditambahkan, stok & ledger diperbarui']);
    } catch (Throwable $e) {
      $pdo->rollBack();
      respond(500, ['error' => 'Gagal simpan detail', 'detail' => $e->getMessage()]);
    }
  }

  respond(400, ['error' => 'type tidak valid']);
}

respond(405, ['error' => 'Method not allowed']);