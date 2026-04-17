<?php

declare(strict_types=1);

if (!function_exists('app_env')) {
    function app_env(): string
    {
        return strtolower((string)(getenv('APP_ENV') ?: 'prod'));
    }
}

$allowedOrigins = array_filter(
    array_map('trim', explode(',', (string)(getenv('CORS_ALLOWED_ORIGINS') ?: '')))
);

if (!$allowedOrigins) {
    $allowedOrigins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'http://localhost',
        'http://127.0.0.1',
    ];
}

$origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');

if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
} else {
    header("Access-Control-Allow-Origin: {$allowedOrigins[0]}");
    header('Vary: Origin');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

return [
    'db' => [
        'host' => (string)(getenv('DB_HOST') ?: '127.0.0.1'),
        'port' => (int)(getenv('DB_PORT') ?: 3306),
        'user' => (string)(getenv('DB_USER') ?: 'root'),
        'pass' => (string)(getenv('DB_PASS') ?: ''),
        'name' => (string)(getenv('DB_NAME') ?: 'sigudob_db'),
        'charset' => (string)(getenv('DB_CHARSET') ?: 'utf8mb4'),
    ],
    'jwt_secret' => (string)(getenv('JWT_SECRET') ?: 'sigudob-dev-secret-change-this'),
    'jwt_ttl_seconds' => (int)(getenv('JWT_TTL_SECONDS') ?: 60 * 60 * 8),
];
