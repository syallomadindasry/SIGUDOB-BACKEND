<?php
// FILE: backend/api/report_pemakaian.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
require_role($payload, ['dinkes','puskesmas']);
$me = auth_ctx($payload);

$gudangId = (int)($_GET['id_gudang'] ?? $me['id_gudang']);
enforce_gudang_scope($me, $gudangId);

$from = (string)($_GET['from'] ?? date('Y-m-01'));
$to = (string)($_GET['to'] ?? date('Y-m-t'));

$rows = db_all(
  "SELECT o.id_obat, o.nama AS nama_obat, o.satuan,
          SUM(pd.jumlah) AS total_qty
   FROM pemakaian p
   JOIN pemakaian_detail pd ON pd.id_pemakaian = p.id
   JOIN data_batch b ON b.id_batch = pd.id_batch
   JOIN data_obat o ON o.id_obat = b.id_obat
   WHERE p.id_gudang = ?
     AND p.tanggal BETWEEN ? AND ?
   GROUP BY o.id_obat, o.nama, o.satuan
   ORDER BY total_qty DESC",
  [$gudangId, $from, $to]
);

respond(200, ['from' => $from, 'to' => $to, 'items' => $rows]);
