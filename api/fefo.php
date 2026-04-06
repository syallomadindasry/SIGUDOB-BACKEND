<?php
// FILE: backend/api/fefo.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
require_role($payload, ['dinkes','puskesmas']);
$me = auth_ctx($payload);

$gudangId = (int)($_GET['id_gudang'] ?? $me['id_gudang']);
enforce_gudang_scope($me, $gudangId);

$idObat = (int)($_GET['id_obat'] ?? 0);
$qtyNeed = max(0, (int)($_GET['qty'] ?? 0));

if ($idObat <= 0) respond(400, ['error' => 'id_obat wajib']);

$rows = db_all(
  "SELECT sb.id_batch, sb.stok, b.batch, b.exp_date
   FROM stok_batch sb
   JOIN data_batch b ON b.id_batch = sb.id_batch
   WHERE sb.id_gudang = ?
     AND b.id_obat = ?
     AND sb.stok > 0
   ORDER BY b.exp_date ASC",
  [$gudangId, $idObat]
);

$pick = [];
$remaining = $qtyNeed;

foreach ($rows as $r) {
  if ($qtyNeed <= 0) {
    $pick[] = ['id_batch' => (int)$r['id_batch'], 'batch' => $r['batch'], 'exp_date' => $r['exp_date'], 'stok' => (int)$r['stok'], 'suggest' => 0];
    continue;
  }
  if ($remaining <= 0) break;

  $take = min($remaining, (int)$r['stok']);
  $pick[] = [
    'id_batch' => (int)$r['id_batch'],
    'batch' => $r['batch'],
    'exp_date' => $r['exp_date'],
    'stok' => (int)$r['stok'],
    'suggest' => (int)$take,
  ];
  $remaining -= $take;
}

respond(200, ['id_obat' => $idObat, 'qty_need' => $qtyNeed, 'remaining' => $remaining, 'pick' => $pick]);