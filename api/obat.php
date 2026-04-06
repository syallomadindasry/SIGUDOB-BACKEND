<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
  case 'GET':
    $q = isset($_GET['q']) ? '%' . trim((string)$_GET['q']) . '%' : '%';
    $rows = db_all(
      "SELECT * FROM data_obat WHERE nama LIKE ? OR jenis LIKE ? ORDER BY nama",
      [$q, $q]
    );
    respond(200, $rows);

  case 'POST':
    $b = json_input();
    if (!isset($b['nama'], $b['satuan'])) respond(400, ['message' => 'Field wajib: nama, satuan']);
    $jenis = (string)($b['jenis'] ?? null);

    $stmt = db()->prepare("INSERT INTO data_obat (nama, satuan, jenis) VALUES (?,?,?)");
    $stmt->execute([(string)$b['nama'], (string)$b['satuan'], $jenis]);
    respond(201, ['id_obat' => (int)db()->lastInsertId(), 'message' => 'Berhasil ditambahkan']);

  case 'PUT':
    $b = json_input();
    $id = (int)($b['id_obat'] ?? 0);
    if ($id <= 0) respond(400, ['message' => 'id_obat wajib']);
    $stmt = db()->prepare("UPDATE data_obat SET nama=?, satuan=?, jenis=? WHERE id_obat=?");
    $stmt->execute([(string)$b['nama'], (string)$b['satuan'], (string)($b['jenis'] ?? null), $id]);
    respond(200, ['message' => 'Berhasil diupdate']);

  case 'DELETE':
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) respond(400, ['message' => 'id wajib']);
    $stmt = db()->prepare("DELETE FROM data_obat WHERE id_obat=?");
    $stmt->execute([$id]);
    respond(200, ['message' => 'Berhasil dihapus']);

  default:
    respond(405, ['message' => 'Method not allowed']);
}
