<?php
// FILE: backend/api/_bootstrap.php

declare(strict_types=1);

if (ob_get_level() === 0) {
    ob_start();
}

$config = require __DIR__ . '/../config.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function apply_cors(array $allowedOrigins = []): void
{
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');

    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');

    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

$allowedOrigins = [];
if (isset($config['app']['allowed_origins']) && is_array($config['app']['allowed_origins'])) {
    $allowedOrigins = $config['app']['allowed_origins'];
}

apply_cors($allowedOrigins);

function json_input(): array
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $raw = file_get_contents('php://input');

    if (!is_string($raw) || trim($raw) === '') {
        $cached = [];
        return $cached;
    }

    $decoded = json_decode($raw, true);
    $cached = is_array($decoded) ? $decoded : [];

    return $cached;
}

function request_input(): array
{
    $json = json_input();
    if (!empty($json)) {
        return $json;
    }

    return is_array($_POST ?? null) ? $_POST : [];
}

function request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function respond(int $code, array $payload): void
{
    http_response_code($code);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bearer_token(): ?string
{
    $header =
        $_SERVER['HTTP_AUTHORIZATION'] ??
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ??
        $_SERVER['Authorization'] ??
        '';

    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (is_array($headers)) {
            $header =
                $headers['Authorization'] ??
                $headers['authorization'] ??
                '';
        }
    }

    if (!is_string($header) || trim($header) === '') {
        return null;
    }

    if (preg_match('/^\s*Bearer\s+(.+?)\s*$/i', $header, $matches)) {
        $token = trim($matches[1]);
        return $token !== '' ? $token : null;
    }

    return null;
}