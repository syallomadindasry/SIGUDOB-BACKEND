<?php
// FILE: backend/api/report_stok.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
require_role($payload, ['dinkes','puskesmas']);
$me = auth_ctx($payload);

$gudangId = (int)($_GET['id_gudang'] ?? $me['id_gudang']);
enforce_gudang_scope($me, $gudangId);

$rows = db_all(
  "SELECT o.id_obat, o.nama AS nama_obat, o.satuan,
          SUM(sb.stok) AS total_stok
   FROM stok_batch sb
   JOIN data_batch b ON b.id_batch = sb.id_batch
   JOIN data_obat o ON o.id_obat = b.id_obat
   WHERE sb.id_gudang = ?
   GROUP BY o.id_obat, o.nama, o.satuan
   ORDER BY o.nama ASC",
  [$gudangId]
);

respond(200, $rows);