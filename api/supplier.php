<?php
// File: backend/api/supplier.php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function table_exists(string $table): bool {
  $stmt = db()->prepare(
    "SELECT 1
     FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
     LIMIT 1"
  );
  $stmt->execute([$table]);
  return (bool)$stmt->fetchColumn();
}

function normalize_npwp($v): string {
  return preg_replace('/\D+/', '', (string)($v ?? ''));
}

function validate_npwp_16_or_400($v): string {
  $npwp = normalize_npwp($v);
  if ($npwp === '' || strlen($npwp) !== 16) {
    respond(400, ['message' => 'NPWP harus 16 digit (angka saja).']);
  }
  return $npwp;
}

function normalize_phone($v): string {
  return preg_replace('/[^\d+]+/', '', (string)($v ?? ''));
}

function validate_phone_or_400($v): string {
  $p = normalize_phone($v);
  $digits = preg_replace('/\D+/', '', $p);
  if ($p === '' || strlen($digits) < 9) {
    respond(400, ['message' => 'Telepon minimal 9 digit.']);
  }
  return $p;
}

function validate_email_or_400($v): string {
  $e = trim((string)($v ?? ''));
  if ($e === '') respond(400, ['message' => 'email wajib']);
  if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
    respond(400, ['message' => 'Format email tidak valid.']);
  }
  return $e;
}

function validate_required_string_or_400(array $b, string $key): string {
  if (!isset($b[$key]) || trim((string)$b[$key]) === '') {
    respond(400, ['message' => "$key wajib"]);
  }
  return trim((string)$b[$key]);
}

function validate_status_or_400($v): string {
  $s = strtoupper(trim((string)($v ?? 'AKTIF')));
  if (!in_array($s, ['AKTIF', 'TIDAK_AKTIF'], true)) {
    respond(400, ['message' => 'status harus AKTIF atau TIDAK_AKTIF']);
  }
  return $s;
}

/**
 * Cek unik di level aplikasi (tetap wajib UNIQUE index di DB).
 * Jika duplicate, respond(409) dengan pesan jelas.
 */
function assert_unique_or_409(string $field, string $value, int $excludeId = 0): void {
  $value = trim($value);
  if ($value === '') return;

  $sql = "SELECT id_supplier
          FROM supplier
          WHERE $field = ?
          " . ($excludeId > 0 ? "AND id_supplier <> ?" : "") . "
          LIMIT 1";

  $params = [$value];
  if ($excludeId > 0) $params[] = $excludeId;

  $row = db_one($sql, $params);
  if ($row) {
    $label = $field === 'kode_supplier' ? 'Kode supplier' : ($field === 'npwp' ? 'NPWP' : $field);
    respond(409, ['message' => "$label sudah digunakan."]);
  }
}

/**
 * Tangani error duplicate key langsung dari DB (UNIQUE index).
 * Ini penting untuk kondisi race (dua request bersamaan).
 */
function respond_db_duplicate_or_throw(Throwable $e): void {
  $msg = $e->getMessage();

  // MySQL duplicate entry: SQLSTATE 23000 / errno 1062 biasanya muncul di message.
  if (str_contains($msg, 'Duplicate entry') || str_contains($msg, '1062') || str_contains($msg, '23000')) {
    if (str_contains($msg, 'kode_supplier')) respond(409, ['message' => 'Kode supplier sudah digunakan.']);
    if (str_contains($msg, 'npwp')) respond(409, ['message' => 'NPWP sudah digunakan.']);
    respond(409, ['message' => 'Data duplikat (kode_supplier/npwp) sudah ada.']);
  }

  throw $e;
}

if (!table_exists('supplier')) {
  respond(500, ['message' => 'Tabel supplier belum ada. Jalankan migrasi DB (lihat backend/sigudob_db.sql).']);
}

if ($method === 'GET') {
  $q = trim((string)($_GET['q'] ?? ''));
  $status = trim((string)($_GET['status'] ?? '')); // AKTIF / TIDAK_AKTIF
  $params = [];
  $where = [];

  if ($q !== '') {
    $where[] = "(kode_supplier LIKE ? OR nama_supplier LIKE ? OR kota LIKE ? OR pic LIKE ?)";
    $params = array_merge($params, array_fill(0, 4, '%' . $q . '%'));
  }
  if ($status !== '') {
    $where[] = "status = ?";
    $params[] = strtoupper($status);
  }

  $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

  $rows = db_all(
    "SELECT s.*,
            (SELECT COUNT(*) FROM pembelian p WHERE p.supplier = s.nama_supplier) AS pembelian
     FROM supplier s
     $whereSql
     ORDER BY s.nama_supplier",
    $params
  );

  $out = [];
  foreach ($rows as $r) {
    $out[] = [
      'id' => (string)$r['id_supplier'],
      'kode_supplier' => (string)$r['kode_supplier'],
      'nama_supplier' => (string)$r['nama_supplier'],
      'kota' => (string)($r['kota'] ?? ''),
      'pic' => (string)($r['pic'] ?? ''),
      'telepon' => (string)($r['telepon'] ?? ''),
      'email' => (string)($r['email'] ?? ''),
      'npwp' => (string)($r['npwp'] ?? ''),
      'pembelian' => (int)($r['pembelian'] ?? 0),
      'status' => (string)($r['status'] ?? 'AKTIF'),
    ];
  }

  respond(200, $out);
}

if ($method === 'POST') {
  $b = json_input();

  $kode_supplier = validate_required_string_or_400($b, 'kode_supplier');
  $nama_supplier = validate_required_string_or_400($b, 'nama_supplier');

  $pic = validate_required_string_or_400($b, 'pic');
  $telepon = validate_phone_or_400($b['telepon'] ?? '');
  $email = validate_email_or_400($b['email'] ?? '');
  $npwp = validate_npwp_16_or_400($b['npwp'] ?? '');
  $status = validate_status_or_400($b['status'] ?? 'AKTIF');
  $kota = trim((string)($b['kota'] ?? ''));

  // app-level unique check
  assert_unique_or_409('kode_supplier', $kode_supplier, 0);
  assert_unique_or_409('npwp', $npwp, 0);

  $stmt = db()->prepare(
    "INSERT INTO supplier (kode_supplier,nama_supplier,kota,pic,telepon,email,npwp,status)
     VALUES (?,?,?,?,?,?,?,?)"
  );

  try {
    $stmt->execute([
      $kode_supplier,
      $nama_supplier,
      $kota,
      $pic,
      $telepon,
      $email,
      $npwp,
      $status,
    ]);
  } catch (Throwable $e) {
    respond_db_duplicate_or_throw($e);
  }

  respond(201, ['id' => (int)db()->lastInsertId(), 'message' => 'Supplier ditambahkan']);
}

if ($method === 'PUT') {
  $b = json_input();
  $id = (int)($b['id_supplier'] ?? $b['id'] ?? 0);
  if ($id <= 0) respond(400, ['message' => 'id_supplier wajib']);

  $existing = db_one("SELECT * FROM supplier WHERE id_supplier = ? LIMIT 1", [$id]);
  if (!$existing) respond(404, ['message' => 'Supplier tidak ditemukan']);

  // partial update aman: default pakai nilai existing
  $next = [
    'kode_supplier' => (string)$existing['kode_supplier'],
    'nama_supplier' => (string)$existing['nama_supplier'],
    'kota' => (string)($existing['kota'] ?? ''),
    'pic' => (string)($existing['pic'] ?? ''),
    'telepon' => (string)($existing['telepon'] ?? ''),
    'email' => (string)($existing['email'] ?? ''),
    'npwp' => (string)($existing['npwp'] ?? ''),
    'status' => (string)($existing['status'] ?? 'AKTIF'),
  ];

  if (array_key_exists('kode_supplier', $b)) {
    $v = trim((string)($b['kode_supplier'] ?? ''));
    if ($v === '') respond(400, ['message' => 'kode_supplier wajib']);
    $next['kode_supplier'] = $v;
  }

  if (array_key_exists('nama_supplier', $b)) {
    $v = trim((string)($b['nama_supplier'] ?? ''));
    if ($v === '') respond(400, ['message' => 'nama_supplier wajib']);
    $next['nama_supplier'] = $v;
  }

  if (array_key_exists('kota', $b)) $next['kota'] = trim((string)($b['kota'] ?? ''));

  if (array_key_exists('pic', $b)) {
    $v = trim((string)($b['pic'] ?? ''));
    if ($v === '') respond(400, ['message' => 'pic wajib']);
    $next['pic'] = $v;
  }

  if (array_key_exists('telepon', $b)) {
    $next['telepon'] = validate_phone_or_400($b['telepon'] ?? '');
  }

  if (array_key_exists('email', $b)) {
    $next['email'] = validate_email_or_400($b['email'] ?? '');
  }

  if (array_key_exists('npwp', $b)) {
    $next['npwp'] = validate_npwp_16_or_400($b['npwp'] ?? '');
  }

  if (array_key_exists('status', $b)) {
    $next['status'] = validate_status_or_400($b['status'] ?? 'AKTIF');
  }

  // unique check untuk field yang berubah
  if ($next['kode_supplier'] !== (string)$existing['kode_supplier']) {
    assert_unique_or_409('kode_supplier', $next['kode_supplier'], $id);
  }
  if ($next['npwp'] !== (string)($existing['npwp'] ?? '')) {
    assert_unique_or_409('npwp', $next['npwp'], $id);
  }

  $stmt = db()->prepare(
    "UPDATE supplier
     SET kode_supplier=?, nama_supplier=?, kota=?, pic=?, telepon=?, email=?, npwp=?, status=?
     WHERE id_supplier=?"
  );

  try {
    $stmt->execute([
      $next['kode_supplier'],
      $next['nama_supplier'],
      $next['kota'],
      $next['pic'],
      $next['telepon'],
      $next['email'],
      $next['npwp'],
      $next['status'],
      $id,
    ]);
  } catch (Throwable $e) {
    respond_db_duplicate_or_throw($e);
  }

  respond(200, ['message' => 'Supplier diupdate']);
}

if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) respond(400, ['message' => 'id wajib']);

  $stmt = db()->prepare("DELETE FROM supplier WHERE id_supplier=?");
  $stmt->execute([$id]);

  respond(200, ['message' => 'Supplier dihapus']);
}

respond(405, ['message' => 'Method not allowed']);