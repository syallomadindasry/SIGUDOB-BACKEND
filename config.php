<?php

declare(strict_types=1);

if (!function_exists('app_env')) {
    function app_env(): string
    {
        $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'local';
        return strtolower(trim((string) $env));
    }
}

$allowedOrigins = [
    'https://sistemgudangobat.netlify.app',
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:5174',
    'http://127.0.0.1:5174',
    'http://localhost',
    'http://127.0.0.1',
];

$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');

if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: https://sistemgudangobat.netlify.app');
    header('Vary: Origin');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$isProd = app_env() === 'prod';

return [
    'db' => [
        'host' => $isProd ? 'teguhprasetyo.web.id' : '127.0.0.1',
        'port' => 3306,
        'user' => $isProd ? 'syallom' : 'root',
        'pass' => $isProd ? 'a6NJ18YQ9bbF' : '',
        'name' => $isProd ? 'syallom' : 'syallom',
        'charset' => 'utf8mb4',
    ],
    'jwt_secret' => $isProd
        ? 'ganti-ini-dengan-secret-random-panjang-production'
        : 'sigudob-local-secret',
    'jwt_ttl_seconds' => 60 * 60 * 8,
];