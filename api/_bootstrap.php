<?php
// File: backend/api/_bootstrap.php

header("Content-Type: application/json; charset=utf-8");

function json_input(): array {
  $raw = file_get_contents("php://input");
  if (!$raw) return [];
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Extracts "Bearer <token>" from Authorization header.
 */
function bearer_token(): ?string {
  $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
  if ($header === '' && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (is_array($headers)) {
      $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
  }
  if (!is_string($header) || $header === '') return null;

  if (preg_match('/^\s*Bearer\s+(.*?)\s*$/i', $header, $m)) {
    return $m[1] ?: null;
  }
  return null;
}
