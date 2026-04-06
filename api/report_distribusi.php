<?php
// FILE: backend/api/report_distribusi.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
require_role($payload, ['dinkes','puskesmas']);
$me = auth_ctx($payload);

$from = (string)($_GET['from'] ?? date('Y-m-01'));
$to = (string)($_GET['to'] ?? date('Y-m-t'));

if ($me['role'] === 'dinkes') {
  $rows = db_all(
    "SELECT m.tujuan AS id_gudang, g.nama_gudang,
            SUM(md.jumlah) AS total_item
     FROM mutasi m
     JOIN mutasi_detail md ON md.id_mutasi = m.id
     JOIN gudang g ON g.id_gudang = m.tujuan
     WHERE m.sumber = ?
       AND m.tanggal BETWEEN ? AND ?
     GROUP BY m.tujuan, g.nama_gudang
     ORDER BY total_item DESC",
    [$me['id_gudang'], $from, $to]
  );
  respond(200, ['from' => $from, 'to' => $to, 'items' => $rows]);
}

$rows = db_all(
  "SELECT m.sumber AS id_gudang, g.nama_gudang,
          SUM(md.jumlah) AS total_item
   FROM mutasi m
   JOIN mutasi_detail md ON md.id_mutasi = m.id
   JOIN gudang g ON g.id_gudang = m.sumber
   WHERE m.tujuan = ?
     AND m.tanggal BETWEEN ? AND ?
   GROUP BY m.sumber, g.nama_gudang
   ORDER BY total_item DESC",
  [$me['id_gudang'], $from, $to]
);

respond(200, ['from' => $from, 'to' => $to, 'items' => $rows]);