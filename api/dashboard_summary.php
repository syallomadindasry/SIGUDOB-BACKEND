<?php
// FILE: backend/api/dashboard_summary.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

if (request_method() !== 'GET') {
    respond(405, ['error' => 'Method not allowed']);
}

$id_gudang = (int)($_GET['id_gudang'] ?? 0);
$role = strtolower(trim((string)($_GET['role'] ?? '')));
$isDinkes = $role === 'dinkes';

try {
    if ($isDinkes) {
        $row = db_one("
            SELECT COUNT(*) AS total_item_obat
            FROM data_obat
        ");
    } else {
        $row = db_one("
            SELECT COUNT(DISTINCT b.id_obat) AS total_item_obat
            FROM stok_batch sb
            JOIN data_batch b ON b.id_batch = sb.id_batch
            WHERE sb.id_gudang = ?
              AND sb.stok > 0
        ", [$id_gudang]);
    }
    $totalItemObat = (int)($row['total_item_obat'] ?? 0);

    if ($isDinkes) {
        $row = db_one("
            SELECT COALESCE(SUM(stok), 0) AS total_stok
            FROM stok_batch
        ");
    } else {
        $row = db_one("
            SELECT COALESCE(SUM(stok), 0) AS total_stok
            FROM stok_batch
            WHERE id_gudang = ?
        ", [$id_gudang]);
    }
    $totalStok = (int)($row['total_stok'] ?? 0);

    if ($isDinkes) {
        $row = db_one("
            SELECT COUNT(*) AS jumlah_stok_kritis
            FROM (
                SELECT b.id_obat
                FROM stok_batch sb
                JOIN data_batch b ON b.id_batch = sb.id_batch
                GROUP BY b.id_obat
                HAVING SUM(sb.stok) < 100
            ) x
        ");
    } else {
        $row = db_one("
            SELECT COUNT(*) AS jumlah_stok_kritis
            FROM (
                SELECT b.id_obat
                FROM stok_batch sb
                JOIN data_batch b ON b.id_batch = sb.id_batch
                WHERE sb.id_gudang = ?
                GROUP BY b.id_obat
                HAVING SUM(sb.stok) < 100
            ) x
        ", [$id_gudang]);
    }
    $jumlahStokKritis = (int)($row['jumlah_stok_kritis'] ?? 0);

    if ($isDinkes) {
        $row = db_one("
            SELECT COUNT(*) AS jumlah_segera_expired
            FROM stok_batch sb
            JOIN data_batch b ON b.id_batch = sb.id_batch
            WHERE sb.stok > 0
              AND b.exp_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ");
    } else {
        $row = db_one("
            SELECT COUNT(*) AS jumlah_segera_expired
            FROM stok_batch sb
            JOIN data_batch b ON b.id_batch = sb.id_batch
            WHERE sb.id_gudang = ?
              AND sb.stok > 0
              AND b.exp_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ", [$id_gudang]);
    }
    $jumlahSegeraExpired = (int)($row['jumlah_segera_expired'] ?? 0);

    respond(200, [
        'success' => true,
        'data' => [
            'total_item_obat' => $totalItemObat,
            'total_stok' => $totalStok,
            'jumlah_stok_kritis' => $jumlahStokKritis,
            'jumlah_segera_expired' => $jumlahSegeraExpired,
        ],
    ]);
} catch (Throwable $e) {
    respond(500, [
        'error' => $e->getMessage(),
    ]);
}