<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

require_once __DIR__ . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'POST') !== 'POST') {
  respond(405, ['message' => 'Gunakan POST']);
}

$me = require_auth();
require_role($me, ['dinkes']);

$pdo = db();

function looks_hashed(string $v): bool {
  return preg_match('/^\$(2y|2a|2b)\$/', $v) === 1 || str_starts_with($v, '$argon2');
}

$rows = db_all("SELECT id_admin, nama, password FROM user");
$updated = 0;
$skipped = 0;

$pdo->beginTransaction();
try {
  $stmt = $pdo->prepare("UPDATE user SET password=? WHERE id_admin=?");
  foreach ($rows as $r) {
    $pw = (string)$r['password'];
    if ($pw === '' || looks_hashed($pw)) { $skipped++; continue; }
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt->execute([$hash, (int)$r['id_admin']]);
    $updated += $stmt->rowCount();
  }
  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  respond(500, ['message' => 'Migrasi gagal', 'detail' => $e->getMessage()]);
}

respond(200, ['message' => 'Migrasi selesai', 'updated' => $updated, 'skipped' => $skipped]);
