<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') respond(405, ['message' => 'Method not allowed']);

$id_obat = (int)($_GET['id_obat'] ?? 0);
$id_gudang = (int)($_GET['id_gudang'] ?? 0);
if ($id_obat <= 0) respond(400, ['message' => 'id_obat required']);

$paramsGudang = [];
$whereStok = '';
if ($id_gudang > 0) {
  $whereStok = 'AND s.id_gudang = ?';
  $paramsGudang[] = $id_gudang;
}

$kondisi = db_one(
  "SELECT
     o.id_obat,
     CONCAT('OBT-', LPAD(o.id_obat, 3, '0')) AS kode_obat,
     o.nama AS nama_obat,
     o.jenis AS kategori,
     o.satuan,
     0 AS harga,
     100 AS min_stok,
     200 AS low_stok,
     COALESCE(SUM(s.stok), 0) AS total_stok
   FROM data_obat o
   LEFT JOIN data_batch b ON b.id_obat = o.id_obat
   LEFT JOIN stok_batch s ON s.id_batch = b.id_batch " . ($id_gudang > 0 ? "AND s.id_gudang = ?" : "") . "
   WHERE o.id_obat = ?
   GROUP BY o.id_obat
   LIMIT 1",
  array_merge($paramsGudang, [$id_obat])
);

if (!$kondisi) respond(404, ['message' => 'Obat tidak ditemukan']);

$batches = db_all(
  "SELECT
     s.id_gudang,
     g.nama_gudang,
     b.id_batch,
     b.batch,
     b.exp_date,
     s.stok
   FROM data_batch b
   JOIN stok_batch s ON s.id_batch = b.id_batch
   JOIN gudang g ON g.id_gudang = s.id_gudang
   WHERE b.id_obat = ? " . ($id_gudang > 0 ? "AND s.id_gudang = ?" : "") . "
   ORDER BY b.exp_date ASC",
  $id_gudang > 0 ? [$id_obat, $id_gudang] : [$id_obat]
);

$purchases = db_all(
  "SELECT
     p.id AS id_pembelian,
     p.no_nota,
     p.tanggal,
     p.supplier,
     p.id_gudang,
     g.nama_gudang,
     pd.id_batch,
     b.batch,
     b.exp_date,
     pd.jumlah,
     pd.harga
   FROM pembelian_detail pd
   JOIN pembelian p ON p.id = pd.id_pembelian
   JOIN gudang g ON g.id_gudang = p.id_gudang
   JOIN data_batch b ON b.id_batch = pd.id_batch
   WHERE b.id_obat = ? " . ($id_gudang > 0 ? "AND p.id_gudang = ?" : "") . "
   ORDER BY p.tanggal DESC, p.id DESC
   LIMIT 200",
  $id_gudang > 0 ? [$id_obat, $id_gudang] : [$id_obat]
);

$distributions = db_all(
  "SELECT
     m.id AS id_mutasi,
     m.no_mutasi,
     m.tanggal,
     m.sumber,
     g1.nama_gudang AS nama_sumber,
     m.tujuan,
     g2.nama_gudang AS nama_tujuan,
     md.id_batch,
     b.batch,
     b.exp_date,
     md.jumlah
   FROM mutasi_detail md
   JOIN mutasi m ON m.id = md.id_mutasi
   JOIN gudang g1 ON g1.id_gudang = m.sumber
   JOIN gudang g2 ON g2.id_gudang = m.tujuan
   JOIN data_batch b ON b.id_batch = md.id_batch
   WHERE b.id_obat = ? " . ($id_gudang > 0 ? "AND m.sumber = ?" : "") . "
   ORDER BY m.tanggal DESC, m.id DESC
   LIMIT 200",
  $id_gudang > 0 ? [$id_obat, $id_gudang] : [$id_obat]
);

respond(200, [
  'kondisi' => $kondisi,
  'batches' => $batches,
  'purchases' => $purchases,
  'distributions' => $distributions,
]);
