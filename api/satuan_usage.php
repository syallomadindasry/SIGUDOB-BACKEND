<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') respond(405, ['message' => 'Method not allowed']);

$pdo = db();

function table_exists(PDO $pdo, string $table): bool {
  $stmt = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
  $stmt->execute([$table]);
  return (bool)$stmt->fetchColumn();
}

if (table_exists($pdo, 'satuan')) {
  // Hitung penggunaan dari data_obat.satuan (string) dengan mapping ke kode/nama/singkat.
  $rows = db_all(
    "SELECT
       s.kode,
       s.nama,
       COUNT(o.id_obat) AS jumlah
     FROM satuan s
     LEFT JOIN data_obat o
       ON LOWER(o.satuan) = LOWER(s.nama)
       OR LOWER(o.satuan) = LOWER(s.singkat)
       OR LOWER(o.satuan) = LOWER(s.kode)
     GROUP BY s.kode, s.nama
     ORDER BY s.nama"
  );

  $out = array_map(fn($r) => [
    'kode' => (string)$r['kode'],
    'nama' => (string)$r['nama'],
    'jumlah' => (int)$r['jumlah'],
  ], $rows);

  respond(200, $out);
}

// fallback: tidak ada tabel satuan → group by data_obat.satuan
$rows = db_all(
  "SELECT satuan AS nama, COUNT(*) AS jumlah
   FROM data_obat
   WHERE satuan IS NOT NULL AND satuan <> ''
   GROUP BY satuan
   ORDER BY satuan"
);

$out = [];
foreach ($rows as $r) {
  $nama = (string)$r['nama'];
  $kode = strtoupper(preg_replace('/\W+/', '_', $nama));
  $out[] = ['kode' => $kode, 'nama' => $nama, 'jumlah' => (int)$r['jumlah']];
}
respond(200, $out);