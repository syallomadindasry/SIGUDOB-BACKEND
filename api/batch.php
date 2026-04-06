<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $idObat = (int)($_GET['id_obat'] ?? 0);

  $sql = "SELECT b.*, o.nama AS nama_obat, o.satuan, o.jenis
          FROM data_batch b
          JOIN data_obat o ON o.id_obat = b.id_obat";
  $params = [];

  if ($idObat > 0) {
    $sql .= " WHERE b.id_obat = ?";
    $params[] = $idObat;
  }

  $sql .= " ORDER BY b.exp_date ASC, b.id_batch ASC";

  respond(200, db_all($sql, $params));
}

if ($method === 'POST') {
  $b = json_input();

  if (!isset($b['batch'], $b['id_obat'], $b['exp_date'])) {
    respond(400, ['message' => 'Field wajib: batch, id_obat, exp_date']);
  }

  $stmt = db()->prepare("INSERT INTO data_batch (batch, id_obat, exp_date) VALUES (?,?,?)");
  $stmt->execute([
    trim((string)$b['batch']),
    (int)$b['id_obat'],
    (string)$b['exp_date'],
  ]);

  respond(201, ['id_batch' => (int)db()->lastInsertId(), 'message' => 'Berhasil ditambahkan']);
}

if ($method === 'PUT') {
  $b = json_input();
  $id = (int)($b['id_batch'] ?? 0);

  if ($id <= 0) {
    respond(400, ['message' => 'id_batch wajib']);
  }

  $exists = db_one("SELECT id_batch FROM data_batch WHERE id_batch = ? LIMIT 1", [$id]);
  if (!$exists) {
    respond(404, ['message' => 'Batch tidak ditemukan']);
  }

  $stmt = db()->prepare("UPDATE data_batch SET batch=?, id_obat=?, exp_date=? WHERE id_batch=?");
  $stmt->execute([
    trim((string)($b['batch'] ?? '')),
    (int)($b['id_obat'] ?? 0),
    (string)($b['exp_date'] ?? ''),
    $id,
  ]);

  respond(200, ['message' => 'Berhasil diupdate']);
}

if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);

  if ($id <= 0) {
    respond(400, ['message' => 'id wajib']);
  }

  $stmt = db()->prepare("DELETE FROM data_batch WHERE id_batch = ?");
  $stmt->execute([$id]);

  respond(200, ['message' => 'Berhasil dihapus']);
}

respond(405, ['message' => 'Method not allowed']);
