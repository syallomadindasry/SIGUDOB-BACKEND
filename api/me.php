<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$payload = require_auth();

respond(200, [
    'user' => [
        'id' => (int)($payload['sub'] ?? 0),
        'username' => (string)($payload['username'] ?? ''),
        'role' => (string)($payload['role'] ?? ''),
        'type' => (string)($payload['type'] ?? ''),
        'id_gudang' => (int)($payload['id_gudang'] ?? 0),
        'nama_gudang' => (string)($payload['nama_gudang'] ?? ''),
        'warehouse' => [
            'code' => (int)($payload['id_gudang'] ?? 0),
            'id_gudang' => (int)($payload['id_gudang'] ?? 0),
            'name' => (string)($payload['nama_gudang'] ?? ''),
            'nama_gudang' => (string)($payload['nama_gudang'] ?? ''),
            'type' => (string)($payload['type'] ?? ''),
        ],
    ],
]);