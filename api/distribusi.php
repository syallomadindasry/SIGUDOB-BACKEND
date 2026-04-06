<?php
// FILE: backend/api/distribusi.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['puskesmas', 'dinkes']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function maybe_fulfill_permintaan(int $permintaanId): void {
  if ($permintaanId <= 0) return;

  $needRows = db_all(
    "SELECT pd.obat_id,
            COALESCE(pd.qty_approved, pd.qty_requested, 0) AS qty_need
     FROM permintaan_detail pd
     WHERE pd.permintaan_id = ?",
    [$permintaanId]
  );
  if (!$needRows) return;

  // total diterima per obat dari semua mutasi yg terkait permintaan_id
  $recvRows = db_all(
    "SELECT b.id_obat AS obat_id,
            SUM(COALESCE(md.qty_received, 0)) AS qty_recv
     FROM mutasi m
     JOIN mutasi_detail md ON md.id_mutasi = m.id
     JOIN data_batch b ON b.id_batch = md.id_batch
     WHERE m.permintaan_id = ?
     GROUP BY b.id_obat",
    [$permintaanId]
  );

  $recvMap = [];
  foreach ($recvRows as $r) {
    $recvMap[(int)$r['obat_id']] = (int)($r['qty_recv'] ?? 0);
  }

  foreach ($needRows as $n) {
    $obatId = (int)$n['obat_id'];
    $need = (int)($n['qty_need'] ?? 0);
    if ($need <= 0) continue;
    $got = (int)($recvMap[$obatId] ?? 0);
    if ($got < $need) return;
  }

  db_exec("UPDATE permintaan SET status='FULFILLED' WHERE id=?", [$permintaanId]);
}

if ($method === 'GET') {
  $id = (int)($_GET['id'] ?? 0);

  if ($id > 0) {
    $hdr = db_one("SELECT * FROM mutasi WHERE id=? LIMIT 1", [$id]);
    if (!$hdr) respond(404, ['error' => 'Distribusi tidak ditemukan']);

    $sumber = (int)$hdr['sumber'];
    $tujuan = (int)$hdr['tujuan'];

    if ($me['role'] === 'dinkes' && $sumber !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ($me['role'] === 'puskesmas' && $tujuan !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);

    $rows = db_all(
      "SELECT md.*, b.batch, b.exp_date, b.id_obat, o.nama AS nama_obat, o.satuan
       FROM mutasi_detail md
       JOIN data_batch b ON b.id_batch = md.id_batch
       JOIN data_obat o ON o.id_obat = b.id_obat
       WHERE md.id_mutasi = ?
       ORDER BY md.id ASC",
      [$id]
    );

    respond(200, ['mutasi' => $hdr, 'items' => $rows]);
  }

  if ($me['role'] === 'dinkes') {
    $rows = db_all(
      "SELECT m.*,
              g1.nama_gudang AS nama_sumber,
              g2.nama_gudang AS nama_tujuan,
              u.nama AS nama_admin,
              (SELECT COUNT(*) FROM mutasi_detail md WHERE md.id_mutasi=m.id) AS total_item
       FROM mutasi m
       JOIN gudang g1 ON g1.id_gudang=m.sumber
       JOIN gudang g2 ON g2.id_gudang=m.tujuan
       JOIN user u ON u.id_admin=m.id_admin
       WHERE m.sumber=?
       ORDER BY m.created_at DESC, m.id DESC",
      [$me['id_gudang']]
    );
    respond(200, $rows);
  }

  $rows = db_all(
    "SELECT m.*,
            g1.nama_gudang AS nama_sumber,
            g2.nama_gudang AS nama_tujuan,
            u.nama AS nama_admin,
            (SELECT COUNT(*) FROM mutasi_detail md WHERE md.id_mutasi=m.id) AS total_item
     FROM mutasi m
     JOIN gudang g1 ON g1.id_gudang=m.sumber
     JOIN gudang g2 ON g2.id_gudang=m.tujuan
     JOIN user u ON u.id_admin=m.id_admin
     WHERE m.tujuan=?
     ORDER BY m.created_at DESC, m.id DESC",
    [$me['id_gudang']]
  );
  respond(200, $rows);
}

if ($method === 'POST') {
  require_role($payload, ['dinkes']);

  $b = json_input();
  $type = (string)($b['type'] ?? 'master');

  if ($type === 'master') {
    $tujuan = (int)($b['tujuan'] ?? 0);
    if ($tujuan <= 0) respond(400, ['error' => 'tujuan wajib']);

    $mode = (string)($b['mode'] ?? 'INSTANT');
    if (!in_array($mode, ['INSTANT', 'WORKFLOW'], true)) $mode = 'INSTANT';

    $status = $mode === 'WORKFLOW' ? 'DRAFT' : 'RECEIVED';

    $stmt = db()->prepare(
      "INSERT INTO mutasi (no_mutasi,tanggal,sumber,tujuan,penyerah,penerima,catatan,id_admin,permintaan_id,mode,status)
       VALUES (?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
      (string)($b['no_mutasi'] ?? ''),
      (string)($b['tanggal'] ?? date('Y-m-d')),
      $me['id_gudang'],
      $tujuan,
      (string)($b['penyerah'] ?? $me['username']),
      (string)($b['penerima'] ?? ''),
      (string)($b['catatan'] ?? ''),
      $me['user_id'],
      isset($b['permintaan_id']) ? (int)$b['permintaan_id'] : null,
      $mode,
      $status,
    ]);

    $id = (int)db()->lastInsertId();
    audit_log($me['user_id'], 'CREATE', 'mutasi', $id, ['mode' => $mode, 'status' => $status, 'tujuan' => $tujuan]);

    respond(201, ['id' => $id, 'message' => 'Distribusi dibuat', 'mode' => $mode, 'status' => $status]);
  }

  if ($type === 'detail') {
    $idMutasi = (int)($b['id_mutasi'] ?? 0);
    $idBatch = (int)($b['id_batch'] ?? 0);
    $jumlah = (int)($b['jumlah'] ?? 0);

    if ($idMutasi <= 0 || $idBatch <= 0 || $jumlah <= 0) respond(400, ['error' => 'id_mutasi,id_batch,jumlah wajib']);

    $hdr = db_one("SELECT * FROM mutasi WHERE id=? LIMIT 1", [$idMutasi]);
    if (!$hdr) respond(404, ['error' => 'Mutasi tidak ditemukan']);
    if ((int)$hdr['sumber'] !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);

    $mode = (string)($hdr['mode'] ?? 'INSTANT');
    $status = (string)($hdr['status'] ?? 'RECEIVED');
    $sumber = (int)$hdr['sumber'];
    $tujuan = (int)$hdr['tujuan'];

    if ($mode === 'WORKFLOW' && $status !== 'DRAFT') respond(400, ['error' => 'WORKFLOW: hanya bisa tambah item saat DRAFT']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
      db_exec("INSERT INTO mutasi_detail (id_mutasi,id_batch,jumlah,qty_received) VALUES (?,?,?,NULL)", [$idMutasi, $idBatch, $jumlah]);

      if ($mode === 'INSTANT') {
        $row = db_one("SELECT stok FROM stok_batch WHERE id_gudang=? AND id_batch=? FOR UPDATE", [$sumber, $idBatch]);
        $stok = (int)($row['stok'] ?? 0);
        if ($stok < $jumlah) {
          $pdo->rollBack();
          respond(400, ['error' => "Stok tidak cukup. Stok: $stok"]);
        }

        db_exec("UPDATE stok_batch SET stok = stok - ? WHERE id_gudang=? AND id_batch=?", [$jumlah, $sumber, $idBatch]);
        db_exec(
          "INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES (?,?,?)
           ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok)",
          [$tujuan, $idBatch, $jumlah]
        );
      }

      audit_log($me['user_id'], 'ADD_ITEM', 'mutasi', $idMutasi, ['id_batch' => $idBatch, 'qty' => $jumlah, 'mode' => $mode]);
      $pdo->commit();

      respond(201, ['message' => 'Item ditambahkan', 'mode' => $mode]);
    } catch (Throwable $e) {
      $pdo->rollBack();
      respond(500, ['error' => 'Gagal simpan detail', 'detail' => $e->getMessage()]);
    }
  }

  respond(400, ['error' => 'type tidak valid']);
}

if ($method === 'PUT') {
  $b = json_input();
  $action = (string)($b['action'] ?? '');
  $idMutasi = (int)($b['id'] ?? 0);
  if ($idMutasi <= 0) respond(400, ['error' => 'id wajib']);

  $hdr = db_one("SELECT * FROM mutasi WHERE id=? LIMIT 1", [$idMutasi]);
  if (!$hdr) respond(404, ['error' => 'Mutasi tidak ditemukan']);

  $mode = (string)($hdr['mode'] ?? 'INSTANT');
  if ($mode !== 'WORKFLOW') respond(400, ['error' => 'Action hanya untuk mode WORKFLOW']);

  $status = (string)($hdr['status'] ?? 'DRAFT');
  $sumber = (int)$hdr['sumber'];
  $tujuan = (int)$hdr['tujuan'];
  $permintaanId = (int)($hdr['permintaan_id'] ?? 0);

  if ($action === 'send') {
    require_role($payload, ['dinkes']);
    if ($sumber !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ($status !== 'DRAFT') respond(400, ['error' => 'Status harus DRAFT']);

    $items = db_all("SELECT id_batch, jumlah FROM mutasi_detail WHERE id_mutasi=?", [$idMutasi]);
    if (!$items) respond(400, ['error' => 'Tidak ada item']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
      foreach ($items as $it) {
        $idBatch = (int)$it['id_batch'];
        $qty = (int)$it['jumlah'];

        $row = db_one("SELECT stok FROM stok_batch WHERE id_gudang=? AND id_batch=? FOR UPDATE", [$sumber, $idBatch]);
        $stok = (int)($row['stok'] ?? 0);
        if ($stok < $qty) {
          $pdo->rollBack();
          respond(400, ['error' => "Stok tidak cukup untuk batch $idBatch. Stok: $stok"]);
        }
        db_exec("UPDATE stok_batch SET stok = stok - ? WHERE id_gudang=? AND id_batch=?", [$qty, $sumber, $idBatch]);
      }

      db_exec("UPDATE mutasi SET status='SENT', sent_at=NOW() WHERE id=?", [$idMutasi]);

      // ✅ Permintaan => Dalam pengiriman
      if ($permintaanId > 0) {
        db_exec(
          "UPDATE permintaan
           SET status='ON_DELIVERY'
           WHERE id=? AND status IN ('APPROVED','PARTIAL','SUBMITTED')",
          [$permintaanId]
        );
      }

      audit_log($me['user_id'], 'SEND', 'mutasi', $idMutasi, ['permintaan_id' => $permintaanId ?: null]);
      $pdo->commit();

      respond(200, ['message' => 'Distribusi dikirim', 'permintaan_id' => $permintaanId ?: null]);
    } catch (Throwable $e) {
      $pdo->rollBack();
      respond(500, ['error' => 'Gagal kirim', 'detail' => $e->getMessage()]);
    }
  }

  if ($action === 'receive') {
    require_role($payload, ['puskesmas']);
    if ($tujuan !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ($status !== 'SENT') respond(400, ['error' => 'Status harus SENT']);

    $items = db_all("SELECT id, id_batch, jumlah FROM mutasi_detail WHERE id_mutasi=?", [$idMutasi]);
    if (!$items) respond(400, ['error' => 'Tidak ada item']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
      foreach ($items as $it) {
        $detailId = (int)$it['id'];
        $idBatch = (int)$it['id_batch'];
        $qtySent = (int)$it['jumlah'];

        $qtyRecv = $qtySent;
        if (isset($b['received']) && is_array($b['received'])) {
          $qtyRecv = (int)($b['received'][(string)$detailId] ?? $qtySent);
          $qtyRecv = max(0, min($qtyRecv, $qtySent));
        }

        db_exec(
          "INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES (?,?,?)
           ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok)",
          [$tujuan, $idBatch, $qtyRecv]
        );

        db_exec("UPDATE mutasi_detail SET qty_received=? WHERE id=? AND id_mutasi=?", [$qtyRecv, $detailId, $idMutasi]);
      }

      db_exec("UPDATE mutasi SET status='RECEIVED', received_at=NOW() WHERE id=?", [$idMutasi]);

      // ✅ Auto fulfill jika lengkap
      if ($permintaanId > 0) {
        maybe_fulfill_permintaan($permintaanId);
      }

      audit_log($me['user_id'], 'RECEIVE', 'mutasi', $idMutasi, ['permintaan_id' => $permintaanId ?: null]);
      $pdo->commit();

      respond(200, ['message' => 'Distribusi diterima', 'permintaan_id' => $permintaanId ?: null]);
    } catch (Throwable $e) {
      $pdo->rollBack();
      respond(500, ['error' => 'Gagal terima', 'detail' => $e->getMessage()]);
    }
  }

  respond(400, ['error' => 'action tidak valid']);
}

respond(405, ['error' => 'Method not allowed']);