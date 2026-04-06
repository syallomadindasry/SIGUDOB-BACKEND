<?php
// File: backend/scripts/migrate_passwords.php
//
// CLI-only: hashes plaintext passwords in table `user` in-place,
// so existing users can keep logging in with the same password.
//
// Usage (Docker):
//   docker compose exec php php /var/www/html/backend/scripts/migrate_passwords.php

require_once __DIR__ . '/../api/db.php';

function looks_hashed(string $v): bool {
  return preg_match('/^\$(2y|2a|2b)\$/', $v) === 1 || str_starts_with($v, '$argon2');
}

$pdo = db();
$rows = $pdo->query("SELECT id_admin, nama, password FROM user")->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$skipped = 0;

$pdo->beginTransaction();
$stmt = $pdo->prepare("UPDATE user SET password=? WHERE id_admin=?");
foreach ($rows as $r) {
  $pw = (string)$r['password'];
  if ($pw === '' || looks_hashed($pw)) { $skipped++; continue; }
  $hash = password_hash($pw, PASSWORD_DEFAULT);
  $stmt->execute([$hash, (int)$r['id_admin']]);
  $updated += $stmt->rowCount();
}
$pdo->commit();

echo "Done. updated={$updated} skipped={$skipped}\n";
