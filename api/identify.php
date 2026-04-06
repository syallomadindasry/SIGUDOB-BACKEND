<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

function pick_column(string $table, array $candidates): ?string {
  $stmt = db()->query("SHOW COLUMNS FROM `{$table}`");
  $cols = [];
  foreach ($stmt->fetchAll() as $r) $cols[] = $r['Field'];
  foreach ($candidates as $c) if (in_array($c, $cols, true)) return $c;
  return null;
}

$T_USER = 'user';
$T_GUDANG = 'gudang';

$C_USER_NAME = pick_column($T_USER, ['username', 'nama', 'name']);
$C_USER_ROLE = pick_column($T_USER, ['role']);
$C_USER_GUDANG_ID = pick_column($T_USER, ['id_gudang', 'gudang_id']);

$C_GUDANG_ID = pick_column($T_GUDANG, ['id_gudang', 'id']);
$C_GUDANG_NAME = pick_column($T_GUDANG, ['nama_gudang', 'nama', 'name']);

if (!$C_USER_NAME || !$C_USER_GUDANG_ID || !$C_GUDANG_ID || !$C_GUDANG_NAME) {
  respond(500, ['error' => 'Mapping kolom gagal (user/gudang).']);
}

$login = trim((string)($_GET['username'] ?? $_GET['nama'] ?? ''));
if ($login === '') respond(200, ['found' => false]);

$sql = "
  SELECT
    u.`{$C_USER_NAME}` AS login_name,
    " . ($C_USER_ROLE ? "u.`{$C_USER_ROLE}` AS role," : "'user' AS role,") . "
    u.`{$C_USER_GUDANG_ID}` AS gudang_id,
    g.`{$C_GUDANG_NAME}` AS gudang_name
  FROM `{$T_USER}` u
  JOIN `{$T_GUDANG}` g ON g.`{$C_GUDANG_ID}` = u.`{$C_USER_GUDANG_ID}`
  WHERE u.`{$C_USER_NAME}` = ?
  LIMIT 1
";

$row = db_one($sql, [$login]);
if (!$row) respond(200, ['found' => false]);

$gudangName = (string)$row['gudang_name'];
$type = (stripos($gudangName, 'dinkes') !== false || stripos((string)$row['role'], 'dinkes') !== false)
  ? 'DINKES'
  : 'PUSKESMAS';

respond(200, [
  'found' => true,
  'warehouse' => [
    'code' => (string)$row['gudang_id'],
    'name' => $gudangName,
    'type' => $type,
  ],
]);