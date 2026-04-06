<?php
// FILE: backend/api/permintaan.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['puskesmas', 'dinkes']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function gen_no_mutasi(): string {
  return 'MUT-' . date('YmdHis') . '-' . substr((string)mt_rand(1000, 9999), 0, 4);
}

/**
 * FIFO allocate qty for a given obat from stok_batch (gudang sumber),
 * ordering by exp_date ASC then id_batch ASC.
 *
 * Returns array of [id_batch => qty_alloc]
 * Throws Exception if stock insufficient.
 */
function fifo_allocate_batches(int $idGudangSumber, int $idObat, int $qtyNeed, PDO $pdo): array {
  if ($qtyNeed <= 0) return [];

  // Lock rows to avoid race (best effort)
  $rows = db_all(
    "SELECT sb.id_batch, sb.stok, b.exp_date
     FROM stok_batch sb
     JOIN data_batch b ON b.id_batch = sb.id_batch
     WHERE sb.id_gudang = ?
       AND b.id_obat = ?
       AND sb.stok > 0
     ORDER BY (b.exp_date IS NULL) ASC, b.exp_date ASC, sb.id_batch ASC
     FOR UPDATE",
    [$idGudangSumber, $idObat]
  );

  $alloc = [];
  $remain = $qtyNeed;

  foreach ($rows as $r) {
    if ($remain <= 0) break;
    $idBatch = (int)$r['id_batch'];
    $stok = (int)$r['stok'];

    if ($stok <= 0) continue;

    $take = min($stok, $remain);
    if ($take > 0) {
      $alloc[$idBatch] = ($alloc[$idBatch] ?? 0) + $take;
      $remain -= $take;
    }
  }

  if ($remain > 0) {
    throw new Exception("Stok tidak cukup untuk obat_id=$idObat. Kurang: $remain");
  }

  return $alloc;
}

/**
 * Ensure there is a workflow mutasi linked to permintaan_id.
 * Returns mutasi id.
 */
function ensure_workflow_mutasi(int $permintaanId, array $hdr, array $me): int {
  // Try existing
  $existing = db_one(
    "SELECT id FROM mutasi WHERE permintaan_id=? AND mode='WORKFLOW' ORDER BY id DESC LIMIT 1",
    [$permintaanId]
  );
  if ($existing && (int)$existing['id'] > 0) return (int)$existing['id'];

  // Create new mutasi header
  // Distribusi: sumber = gudang dinkes (me.id_gudang), tujuan = gudang puskesmas (hdr.from_gudang_id)
  $sumber = (int)$me['id_gudang'];
  $tujuan = (int)($hdr['from_gudang_id'] ?? 0);
  if ($tujuan <= 0) throw new Exception("from_gudang_id tidak valid pada permintaan");

  $stmt = db()->prepare(
    "INSERT INTO mutasi (no_mutasi, tanggal, sumber, tujuan, penyerah, penerima, catatan, id_admin, permintaan_id, mode, status)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
  );
  $stmt->execute([
    gen_no_mutasi(),
    date('Y-m-d'),
    $sumber,
    $tujuan,
    (string)($me['username'] ?? 'Admin Dinkes'),
    null,
    'Distribusi (auto dari permintaan #' . $permintaanId . ')',
    (int)$me['user_id'],
    $permintaanId,
    'WORKFLOW',
    'DRAFT',
  ]);

  return (int)db()->lastInsertId();
}

/**
 * Auto fill mutasi_detail based on permintaan_detail qty_approved (or qty_requested if approved null).
 * - Clears previous mutasi_detail for that mutasi (safe if rerun).
 * - Inserts mutasi_detail (jumlah, qty_received NULL) using FIFO.
 */
function autofill_mutasi_detail_from_permintaan(int $mutasiId, int $permintaanId, array $me, PDO $pdo): void {
  $idGudangSumber = (int)$me['id_gudang'];

  // Get approved items
  $items = db_all(
    "SELECT id, obat_id,
            COALESCE(qty_approved, qty_requested, 0) AS qty_need
     FROM permintaan_detail
     WHERE permintaan_id=?
     ORDER BY id ASC",
    [$permintaanId]
  );

  // Clear existing details to avoid duplicates
  db_exec("DELETE FROM mutasi_detail WHERE id_mutasi=?", [$mutasiId]);

  foreach ($items as $it) {
    $obatId = (int)$it['obat_id'];
    $qtyNeed = (int)$it['qty_need'];

    if ($qtyNeed <= 0) continue;

    $alloc = fifo_allocate_batches($idGudangSumber, $obatId, $qtyNeed, $pdo);

    foreach ($alloc as $idBatch => $qtyAlloc) {
      db_exec(
        "INSERT INTO mutasi_detail (id_mutasi, id_batch, jumlah, qty_received)
         VALUES (?,?,?,NULL)",
        [$mutasiId, (int)$idBatch, (int)$qtyAlloc]
      );
    }
  }
}

function json_body(): array {
  $raw = file_get_contents('php://input');
  $b = json_decode($raw ?: '[]', true);
  return is_array($b) ? $b : [];
}

/**
 * Expected table columns (based on your UI & workflow):
 * permintaan:
 * - id
 * - from_gudang_id
 * - to_gudang_id
 * - priority
 * - note
 * - status (DRAFT|SUBMITTED|APPROVED|PARTIAL|ON_DELIVERY|REJECTED|FULFILLED)
 * - created_at
 *
 * permintaan_detail:
 * - id
 * - permintaan_id
 * - obat_id
 * - qty_requested
 * - qty_approved (nullable)
 */

if ($method === 'GET') {
  $id = (int)($_GET['id'] ?? 0);

  if ($id > 0) {
    $hdr = db_one(
      "SELECT p.*,
              g1.nama_gudang AS nama_from,
              g2.nama_gudang AS nama_to
       FROM permintaan p
       JOIN gudang g1 ON g1.id_gudang = p.from_gudang_id
       JOIN gudang g2 ON g2.id_gudang = p.to_gudang_id
       WHERE p.id=? LIMIT 1",
      [$id]
    );
    if (!$hdr) respond(404, ['error' => 'Permintaan tidak ditemukan']);

    // authorization: puskesmas only own, dinkes only own target
    if ($me['role'] === 'puskesmas' && (int)$hdr['from_gudang_id'] !== (int)$me['id_gudang']) {
      respond(403, ['error' => 'Forbidden']);
    }
    if ($me['role'] === 'dinkes' && (int)$hdr['to_gudang_id'] !== (int)$me['id_gudang']) {
      respond(403, ['error' => 'Forbidden']);
    }

    $items = db_all(
      "SELECT pd.*,
              o.nama AS nama_obat,
              o.satuan
       FROM permintaan_detail pd
       JOIN data_obat o ON o.id_obat = pd.obat_id
       WHERE pd.permintaan_id=?
       ORDER BY pd.id ASC",
      [$id]
    );

    respond(200, ['permintaan' => $hdr, 'items' => $items]);
  }

  // list
  if ($me['role'] === 'puskesmas') {
    $rows = db_all(
      "SELECT p.id, p.status, p.priority, p.note, p.created_at,
              g2.nama_gudang AS nama_to
       FROM permintaan p
       JOIN gudang g2 ON g2.id_gudang = p.to_gudang_id
       WHERE p.from_gudang_id=?
       ORDER BY p.id DESC",
      [$me['id_gudang']]
    );
    respond(200, $rows);
  }

  // dinkes inbox
  $rows = db_all(
    "SELECT p.id, p.status, p.priority, p.note, p.created_at,
            g1.nama_gudang AS nama_from
     FROM permintaan p
     JOIN gudang g1 ON g1.id_gudang = p.from_gudang_id
     WHERE p.to_gudang_id=?
     ORDER BY p.id DESC",
    [$me['id_gudang']]
  );
  respond(200, $rows);
}

if ($method === 'POST') {
  $b = json_body();
  $type = (string)($b['type'] ?? '');

  // create master (puskesmas)
  if ($type === 'master') {
    require_role($payload, ['puskesmas']);

    $toGudang = (int)($b['to_gudang_id'] ?? 0);
    if ($toGudang <= 0) respond(400, ['error' => 'to_gudang_id wajib']);

    $priority = (string)($b['priority'] ?? 'MEDIUM');
    if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'], true)) $priority = 'MEDIUM';

    $note = trim((string)($b['note'] ?? ''));

    $stmt = db()->prepare(
      "INSERT INTO permintaan (from_gudang_id, to_gudang_id, priority, note, status, created_at)
       VALUES (?,?,?,?, 'DRAFT', NOW())"
    );
    $stmt->execute([(int)$me['id_gudang'], $toGudang, $priority, $note ?: null]);

    $id = (int)db()->lastInsertId();
    audit_log((int)$me['user_id'], 'CREATE', 'permintaan', $id, ['to_gudang_id' => $toGudang]);

    respond(201, ['id' => $id, 'message' => 'Permintaan dibuat (Draft)']);
  }

  // add detail item (puskesmas)
  if ($type === 'detail') {
    require_role($payload, ['puskesmas']);

    $permintaanId = (int)($b['permintaan_id'] ?? 0);
    $obatId = (int)($b['obat_id'] ?? 0);
    $qtyReq = (int)($b['qty_requested'] ?? 0);

    if ($permintaanId <= 0 || $obatId <= 0 || $qtyReq <= 0) respond(400, ['error' => 'permintaan_id, obat_id, qty_requested wajib']);

    $hdr = db_one("SELECT * FROM permintaan WHERE id=? LIMIT 1", [$permintaanId]);
    if (!$hdr) respond(404, ['error' => 'Permintaan tidak ditemukan']);
    if ((int)$hdr['from_gudang_id'] !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ((string)$hdr['status'] !== 'DRAFT') respond(400, ['error' => 'Hanya bisa tambah item saat DRAFT']);

    db_exec(
      "INSERT INTO permintaan_detail (permintaan_id, obat_id, qty_requested, qty_approved)
       VALUES (?,?,?,NULL)",
      [$permintaanId, $obatId, $qtyReq]
    );

    audit_log((int)$me['user_id'], 'ADD_ITEM', 'permintaan', $permintaanId, ['obat_id' => $obatId, 'qty' => $qtyReq]);

    respond(201, ['message' => 'Item ditambahkan']);
  }

  respond(400, ['error' => 'type tidak valid']);
}

if ($method === 'PUT') {
  $b = json_body();
  $action = (string)($b['action'] ?? '');
  $id = (int)($b['id'] ?? 0);
  if ($id <= 0) respond(400, ['error' => 'id wajib']);

  $hdr = db_one("SELECT * FROM permintaan WHERE id=? LIMIT 1", [$id]);
  if (!$hdr) respond(404, ['error' => 'Permintaan tidak ditemukan']);

  // submit (puskesmas)
  if ($action === 'submit') {
    require_role($payload, ['puskesmas']);
    if ((int)$hdr['from_gudang_id'] !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ((string)$hdr['status'] !== 'DRAFT') respond(400, ['error' => 'Harus DRAFT']);

    $cnt = db_one("SELECT COUNT(*) AS c FROM permintaan_detail WHERE permintaan_id=?", [$id]);
    if ((int)($cnt['c'] ?? 0) <= 0) respond(400, ['error' => 'Item belum ada']);

    db_exec("UPDATE permintaan SET status='SUBMITTED' WHERE id=?", [$id]);
    audit_log((int)$me['user_id'], 'SUBMIT', 'permintaan', $id, []);

    respond(200, ['message' => 'Permintaan diajukan']);
  }

  // reject (dinkes)
  if ($action === 'reject') {
    require_role($payload, ['dinkes']);
    if ((int)$hdr['to_gudang_id'] !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ((string)$hdr['status'] !== 'SUBMITTED') respond(400, ['error' => 'Harus SUBMITTED']);

    $note = trim((string)($b['note'] ?? 'Ditolak'));
    db_exec("UPDATE permintaan SET status='REJECTED', note=? WHERE id=?", [$note ?: null, $id]);

    audit_log((int)$me['user_id'], 'REJECT', 'permintaan', $id, ['note' => $note]);
    respond(200, ['message' => 'Permintaan ditolak']);
  }

  // approve (dinkes) + AUTO-FILL DISTRIBUSI FIFO ✅
  if ($action === 'approve') {
    require_role($payload, ['dinkes']);
    if ((int)$hdr['to_gudang_id'] !== (int)$me['id_gudang']) respond(403, ['error' => 'Forbidden']);
    if ((string)$hdr['status'] !== 'SUBMITTED') respond(400, ['error' => 'Harus SUBMITTED']);

    $items = $b['items'] ?? null;
    if (!is_array($items) || count($items) === 0) respond(400, ['error' => 'items wajib']);

    $pdo = db();
    $pdo->beginTransaction();

    try {
      $anyPartial = false;

      foreach ($items as $it) {
        $detailId = (int)($it['detail_id'] ?? 0);
        if ($detailId <= 0) continue;

        $row = db_one(
          "SELECT qty_requested FROM permintaan_detail WHERE id=? AND permintaan_id=? LIMIT 1",
          [$detailId, $id]
        );
        if (!$row) continue;

        $qtyReq = (int)($row['qty_requested'] ?? 0);
        $qtyA = (int)($it['qty_approved'] ?? 0);

        if ($qtyA < 0) $qtyA = 0;
        if ($qtyA > $qtyReq) $qtyA = $qtyReq;

        if ($qtyA < $qtyReq) $anyPartial = true;

        db_exec(
          "UPDATE permintaan_detail SET qty_approved=? WHERE id=? AND permintaan_id=?",
          [$qtyA, $detailId, $id]
        );
      }

      $newStatus = $anyPartial ? 'PARTIAL' : 'APPROVED';
      db_exec("UPDATE permintaan SET status=? WHERE id=?", [$newStatus, $id]);

      // ✅ ensure mutasi header exists (WORKFLOW)
      $mutasiId = ensure_workflow_mutasi($id, $hdr, $me);

      // ✅ auto-fill mutasi_detail using FIFO batches
      autofill_mutasi_detail_from_permintaan($mutasiId, $id, $me, $pdo);

      audit_log((int)$me['user_id'], 'APPROVE', 'permintaan', $id, [
        'status' => $newStatus,
        'mutasi_id' => $mutasiId,
        'autofill' => true,
      ]);

      $pdo->commit();

      respond(200, [
        'message' => 'Permintaan disetujui & distribusi otomatis dibuat (FIFO)',
        'status' => $newStatus,
        'mutasi_id' => $mutasiId,
      ]);
    } catch (Throwable $e) {
      $pdo->rollBack();
      respond(400, ['error' => $e->getMessage()]);
    }
  }

  respond(400, ['error' => 'action tidak valid']);
}

respond(405, ['error' => 'Method not allowed']);