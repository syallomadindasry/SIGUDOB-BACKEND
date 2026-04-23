<?php
// FILE: backend/config.php

declare(strict_types=1);

if (!function_exists('app_env')) {
    function app_env(): string
    {
        $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'prod';
        return strtolower(trim((string) $env));
    }
}

$allowedOrigins = [
    'http://sistemgudangobat.infinityfreeapp.com',
    'https://sistemgudangobat.infinityfreeapp.com',
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
    header('Access-Control-Allow-Origin: http://sistemgudangobat.infinityfreeapp.com');
    header('Vary: Origin');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dbConfig = [
    'host' => 'sql200.byetcluster.com',
    'port' => 3306,
    'user' => 'if0_41686673',
    'pass' => 'upPvzvpKAe',
    'name' => 'if0_41686673_syallom',
    'charset' => 'utf8mb4',
];

$jwtSecret = 'ncisddcndiwgcuqdnmvoljvoasiqihdnvbuwvbplsoidcnmvbyhdueqqujimikaiuanchgbu';
$jwtTtl = 60 * 60 * 8;

return [
    'db' => $dbConfig,

    // format lama
    'jwt_secret' => $jwtSecret,
    'jwt_ttl_seconds' => $jwtTtl,

    // format baru, supaya code lama/baru sama-sama jalan
    'jwt' => [
        'secret' => $jwtSecret,
        'ttl' => $jwtTtl,
    ],
];