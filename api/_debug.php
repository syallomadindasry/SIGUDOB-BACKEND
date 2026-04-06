<?php
require_once __DIR__ . '/_bootstrap.php';

$raw = file_get_contents('php://input');

respond(200, [
  'method' => $_SERVER['REQUEST_METHOD'] ?? null,
  'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
  'raw_len' => is_string($raw) ? strlen($raw) : null,
  'raw' => $raw,
  'post' => $_POST,
  'parsed' => json_input(),
]);