<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') respond(405, ['message' => 'Method not allowed']);

$id_gudang = (int)($_GET['id_gudang'] ?? 0);

$params = [];
$where = '';
if ($id_gudang > 0) {
  $where = 'WHERE s.id_gudang = ?';
  $params[] = $id_gudang;
}

/**
 * Schema baseline (from sigudob_db.sql) does not include:
 * - data_obat.kode_obat, harga, min_stok, low_stok
 * We provide compatible fields for the frontend with sensible defaults.
 */
$sql = "SELECT
          s.id_gudang, s.id_batch, s.stok,
          b.batch, b.exp_date,
          o.id_obat,
          o.nama AS nama_obat,
          o.satuan,
          o.jenis,
          CONCAT('OBT-', LPAD(o.id_obat, 3, '0')) AS kode_obat,
          0 AS harga,
          100 AS min_stok,
          200 AS low_stok,
          g.nama_gudang
        FROM stok_batch s
        JOIN data_batch b ON b.id_batch  = s.id_batch
        JOIN data_obat  o ON o.id_obat   = b.id_obat
        JOIN gudang     g ON g.id_gudang = s.id_gudang
        $where
        ORDER BY o.nama, b.exp_date";

$rows = db_all($sql, $params);
respond(200, $rows);
