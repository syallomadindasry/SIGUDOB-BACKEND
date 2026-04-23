<?php
// FILE: backend/api/opname.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
$me = auth_ctx($payload);

if (request_method() !== 'GET') {
    respond(405, ['error' => 'Method not allowed']);
}

try {
    $id = (int) ($_GET['id'] ?? 0);

    if ($id > 0) {
        $detailSqlCandidates = [
            "SELECT * FROM opname_detail WHERE id_opname = ? ORDER BY id_opname_detail DESC",
            "SELECT * FROM opname_detail WHERE id_opname = ? ORDER BY id DESC",
            "SELECT * FROM opname_detail WHERE id_opname = ?",
        ];

        foreach ($detailSqlCandidates as $sql) {
            try {
                respond(200, db_all($sql, [$id]));
            } catch (Throwable $e) {
            }
        }

        respond(200, []);
    }

    if ($me['role'] === 'dinkes') {
        $listSqlCandidates = [
            "SELECT * FROM opname ORDER BY id DESC",
            "SELECT * FROM opname ORDER BY created_at DESC",
            "SELECT * FROM opname",
        ];

        foreach ($listSqlCandidates as $sql) {
            try {
                respond(200, db_all($sql));
            } catch (Throwable $e) {
            }
        }

        respond(200, []);
    }

    $listSqlCandidates = [
        "SELECT * FROM opname WHERE id_gudang = ? ORDER BY id DESC",
        "SELECT * FROM opname WHERE id_gudang = ? ORDER BY created_at DESC",
        "SELECT * FROM opname WHERE id_gudang = ?",
    ];

    foreach ($listSqlCandidates as $sql) {
        try {
            respond(200, db_all($sql, [$me['id_gudang']]));
        } catch (Throwable $e) {
        }
    }

    respond(200, []);
} catch (Throwable $e) {
    respond(200, []);
}