<?php
// File: backend/config.php

/**
 * SIGUDOB backend configuration.
 *
 * - Reads config from environment variables (Docker/local friendly).
 * - Sets CORS consistently for all endpoints.
 */

if (!function_exists('app_env')) {
  function app_env(): string {
    return strtolower(getenv('APP_ENV') ?: 'prod');
  }
}

$allowed_origins = array_filter(array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: '')));

if (!$allowed_origins) {
  $allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost',
    'http://127.0.0.1',
  ];
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowed_origins, true)) {
  header("Access-Control-Allow-Origin: $origin");
  header("Vary: Origin");
} else {
  header("Access-Control-Allow-Origin: {$allowed_origins[0]}");
  header("Vary: Origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

return [
  'db' => [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'name' => getenv('DB_NAME') ?: 'sigudob_db',
    'charset' => 'utf8mb4',
  ],
  'jwt_secret' => getenv('JWT_SECRET') ?: 'CHANGE_ME_IN_ENV',
  'jwt_ttl_seconds' => (int)(getenv('JWT_TTL_SECONDS') ?: 60 * 60 * 8),
];