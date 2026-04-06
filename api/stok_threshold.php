<?php
// FILE: backend/api/stok_threshold.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['dinkes','puskesmas']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $gudangId = (int)($_GET['id_gudang'] ?? $me['id_gudang']);
  enforce_gudang_scope($me, $gudangId);

  $rows = db_all(
    "SELECT t.gudang_id, t.obat_id, t.min_qty, o.nama AS nama_obat, o.satuan
     FROM stok_threshold t
     JOIN data_obat o ON o.id_obat = t.obat_id
     WHERE t.gudang_id = ?
     ORDER BY o.nama ASC",
    [$gudangId]
  );
  respond(200, $rows);
}

if ($method === 'POST') {
  $b = json_input();
  $gudangId = (int)($b['id_gudang'] ?? $me['id_gudang']);
  $obatId = (int)($b['obat_id'] ?? 0);
  $minQty = (float)($b['min_qty'] ?? 0);

  enforce_gudang_scope($me, $gudangId);
  if ($obatId <= 0) respond(400, ['error' => 'obat_id wajib']);

  db_exec(
    "INSERT INTO stok_threshold (gudang_id, obat_id, min_qty)
     VALUES (?,?,?)
     ON DUPLICATE KEY UPDATE min_qty = VALUES(min_qty)",
    [$gudangId, $obatId, $minQty]
  );

  audit_log($me['user_id'], 'UPSERT', 'stok_threshold', null, ['id_gudang' => $gudangId, 'obat_id' => $obatId, 'min_qty' => $minQty]);
  respond(200, ['ok' => true]);
}

if ($method === 'DELETE') {
  $gudangId = (int)($_GET['id_gudang'] ?? $me['id_gudang']);
  $obatId = (int)($_GET['obat_id'] ?? 0);

  enforce_gudang_scope($me, $gudangId);
  if ($obatId <= 0) respond(400, ['error' => 'obat_id wajib']);

  db_exec("DELETE FROM stok_threshold WHERE gudang_id=? AND obat_id=?", [$gudangId, $obatId]);
  audit_log($me['user_id'], 'DELETE', 'stok_threshold', null, ['id_gudang' => $gudangId, 'obat_id' => $obatId]);

  respond(200, ['ok' => true]);
}

respond(405, ['error' => 'Method not allowed']);