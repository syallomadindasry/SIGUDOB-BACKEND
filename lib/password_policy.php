<?php
/**
 * Password policy:
 * - min 6 char
 * - at least 1 letter
 * - at least 1 digit
 * - at least 1 symbol (non-alphanumeric)
 *
 * Returns null if OK, otherwise error message.
 */
function validate_password_policy(string $password): ?string {
  $pwd = trim($password);

  // Use mb_strlen if available (more correct for UTF-8), fallback to strlen.
  $len = function_exists('mb_strlen') ? mb_strlen($pwd, 'UTF-8') : strlen($pwd);

  if ($len < 6) return "Password minimal 6 karakter.";
  if (!preg_match('/[A-Za-z]/', $pwd)) return "Password wajib mengandung minimal 1 huruf.";
  if (!preg_match('/\d/', $pwd)) return "Password wajib mengandung minimal 1 angka.";
  if (!preg_match('/[^A-Za-z0-9]/', $pwd)) return "Password wajib mengandung minimal 1 simbol.";
  return null;
}

function hash_password(string $password): string {
  return password_hash($password, PASSWORD_BCRYPT);
}
