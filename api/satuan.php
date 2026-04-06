<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function table_exists(string $table): bool {
  $stmt = db()->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1");
  $stmt->execute([$table]);
  return (bool)$stmt->fetchColumn();
}

$hasTable = table_exists('satuan');

if ($method === 'GET') {
  $q = trim((string)($_GET['q'] ?? ''));

  if ($hasTable) {
    $params = [];
    $where = '';
    if ($q !== '') {
      $where = "WHERE kode LIKE ? OR nama LIKE ? OR singkat LIKE ? OR jenis LIKE ?";
      $params = array_fill(0, 4, '%' . $q . '%');
    }
    $rows = db_all("SELECT * FROM satuan $where ORDER BY nama", $params);
    respond(200, $rows);
  }

  // fallback
  $rows = db_all("SELECT DISTINCT satuan AS nama FROM data_obat WHERE satuan IS NOT NULL AND satuan <> '' ORDER BY satuan");
  $out = [];
  foreach ($rows as $r) {
    $nama = (string)$r['nama'];
    $out[] = [
      'id' => null,
      'kode' => strtoupper(preg_replace('/\W+/', '_', $nama)),
      'nama' => $nama,
      'singkat' => $nama,
      'jenis' => 'AUTO',
      'keterangan' => 'auto-generated from data_obat',
    ];
  }
  respond(200, $out);
}

if (!$hasTable) {
  respond(400, ['message' => 'Tabel satuan belum ada. Jalankan migrasi DB (lihat backend/sigudob_db.sql).']);
}

if ($method === 'POST') {
  $b = json_input();
  foreach (['kode','nama'] as $k) if (!isset($b[$k]) || trim((string)$b[$k])==='') respond(400, ['message' => "$k wajib"]);

  $stmt = db()->prepare("INSERT INTO satuan (kode,nama,singkat,jenis,keterangan) VALUES (?,?,?,?,?)");
  $stmt->execute([
    (string)$b['kode'],
    (string)$b['nama'],
    (string)($b['singkat'] ?? ''),
    (string)($b['jenis'] ?? ''),
    (string)($b['keterangan'] ?? ''),
  ]);
  respond(201, ['id' => (int)db()->lastInsertId(), 'message' => 'Satuan ditambahkan']);
}

if ($method === 'PUT') {
  $b = json_input();
  $id = (int)($b['id'] ?? 0);
  if ($id <= 0) respond(400, ['message' => 'id wajib']);

  $stmt = db()->prepare("UPDATE satuan SET kode=?, nama=?, singkat=?, jenis=?, keterangan=? WHERE id=?");
  $stmt->execute([
    (string)($b['kode'] ?? ''),
    (string)($b['nama'] ?? ''),
    (string)($b['singkat'] ?? ''),
    (string)($b['jenis'] ?? ''),
    (string)($b['keterangan'] ?? ''),
    $id,
  ]);
  respond(200, ['message' => 'Satuan diupdate']);
}

if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) respond(400, ['message' => 'id wajib']);
  $stmt = db()->prepare("DELETE FROM satuan WHERE id=?");
  $stmt->execute([$id]);
  respond(200, ['message' => 'Satuan dihapus']);
}

respond(405, ['message' => 'Method not allowed']);
