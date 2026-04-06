<?php
// FILE: backend/api/stock_movements.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
require_role($payload, ['dinkes', 'puskesmas']);
$me = auth_ctx($payload);

$gudangId = (int)($_GET['id_gudang'] ?? $me['id_gudang']);
enforce_gudang_scope($me, $gudangId);

$idObat  = (int)($_GET['id_obat'] ?? 0);
$idBatch = (int)($_GET['id_batch'] ?? 0);
$from    = trim((string)($_GET['from'] ?? ''));
$to      = trim((string)($_GET['to'] ?? ''));

$limit  = (int)($_GET['limit'] ?? 50);
$offset = (int)($_GET['offset'] ?? 0);
if ($limit < 1) $limit = 50;
if ($limit > 200) $limit = 200;
if ($offset < 0) $offset = 0;

// WHERE builder
$where = ["sm.gudang_id = ?"];
$params = [$gudangId];

if ($idObat > 0) { $where[] = "sm.obat_id = ?"; $params[] = $idObat; }
if ($idBatch > 0) { $where[] = "sm.batch_id = ?"; $params[] = $idBatch; }

if ($from !== '') { $where[] = "sm.created_at >= ?"; $params[] = $from . " 00:00:00"; }
if ($to !== '')   { $where[] = "sm.created_at <= ?"; $params[] = $to . " 23:59:59"; }

$whereSql = implode(" AND ", $where);

// total
$totalRow = db_one("SELECT COUNT(*) AS c FROM stock_movements sm WHERE $whereSql", $params);
$total = (int)($totalRow['c'] ?? 0);

// IMPORTANT: gunakan LIMIT offset,limit literal integer biar aman di MariaDB
$sql = "
SELECT sm.*,
       o.nama AS nama_obat, o.satuan,
       b.batch, b.exp_date,
       g.nama_gudang
FROM stock_movements sm
JOIN data_obat o ON o.id_obat = sm.obat_id
JOIN data_batch b ON b.id_batch = sm.batch_id
JOIN gudang g ON g.id_gudang = sm.gudang_id
WHERE $whereSql
ORDER BY sm.created_at DESC, sm.id DESC
LIMIT $offset, $limit
";

$items = db_all($sql, $params);

respond(200, ['total' => $total, 'items' => $items]);