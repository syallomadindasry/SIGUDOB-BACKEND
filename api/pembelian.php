<?php
// FILE: backend/api/pembelian.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$auditFile = __DIR__ . '/../lib/audit.php';
if (is_file($auditFile)) {
    require_once $auditFile;
}

$payload = require_auth();
require_role($payload, ['dinkes']);
$me = auth_ctx($payload);

$method = request_method();

function pembelian_input(): array
{
    return request_input();
}

function pembelian_audit_safe(int $userId, string $action, string $entity, int $entityId, array $meta = []): void
{
    if (function_exists('audit_log')) {
        audit_log($userId, $action, $entity, $entityId, $meta);
    }
}

function pembelian_gen_no(): string
{
    return 'PB-' . date('YmdHis');
}

try {
    if ($method === 'GET') {
        $id = (int)($_GET['id'] ?? 0);
        $idGudang = (int)($_GET['id_gudang'] ?? 0);

        if ($idGudang > 0 && $idGudang !== (int)($me['id_gudang'] ?? 0)) {
            respond(403, ['error' => 'Forbidden']);
        }

        if ($id > 0) {
            $hdr = db_one(
                "SELECT id, id_gudang
                 FROM pembelian
                 WHERE id = ?
                 LIMIT 1",
                [$id]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Pembelian tidak ditemukan']);
            }

            if ((int)$hdr['id_gudang'] !== (int)($me['id_gudang'] ?? 0)) {
                respond(403, ['error' => 'Forbidden']);
            }

            $rows = db_all(
                "SELECT
                    pd.*,
                    b.batch,
                    b.exp_date,
                    o.nama AS nama_obat,
                    o.satuan
                 FROM pembelian_detail pd
                 JOIN data_batch b ON b.id_batch = pd.id_batch
                 JOIN data_obat o ON o.id_obat = b.id_obat
                 WHERE pd.id_pembelian = ?
                 ORDER BY pd.id ASC",
                [$id]
            );

            respond(200, $rows);
        }

        $rows = db_all(
            "SELECT
                p.*,
                u.nama AS nama_admin,
                (
                    SELECT COUNT(*)
                    FROM pembelian_detail pd
                    WHERE pd.id_pembelian = p.id
                ) AS total_item,
                (
                    SELECT COALESCE(SUM(pd.jumlah * pd.harga), 0)
                    FROM pembelian_detail pd
                    WHERE pd.id_pembelian = p.id
                ) AS total_harga
             FROM pembelian p
             JOIN user u ON u.id_admin = p.id_admin
             WHERE p.id_gudang = ?
             ORDER BY p.created_at DESC, p.id DESC",
            [(int)($me['id_gudang'] ?? 0)]
        );

        respond(200, $rows);
    }

    if ($method === 'POST') {
        $b = pembelian_input();
        $type = (string)($b['type'] ?? 'master');

        if ($type === 'master') {
            $noNota = trim((string)($b['no_nota'] ?? ''));
            if ($noNota === '') {
                $noNota = pembelian_gen_no();
            }

            $tanggal = trim((string)($b['tanggal'] ?? date('Y-m-d')));
            $supplier = trim((string)($b['supplier'] ?? ''));
            $alamat = trim((string)($b['alamat'] ?? ''));
            $kota = trim((string)($b['kota'] ?? ''));
            $telepon = trim((string)($b['telepon'] ?? ''));
            $metodeBayar = trim((string)($b['metode_bayar'] ?? ''));
            $diskon = (float)($b['diskon'] ?? 0);
            $catatan = trim((string)($b['catatan'] ?? ''));

            db_exec(
                "INSERT INTO pembelian (
                    no_nota,
                    tanggal,
                    supplier,
                    alamat,
                    kota,
                    telepon,
                    metode_bayar,
                    diskon,
                    catatan,
                    id_admin,
                    id_gudang
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $noNota,
                    $tanggal !== '' ? $tanggal : date('Y-m-d'),
                    $supplier !== '' ? $supplier : null,
                    $alamat !== '' ? $alamat : null,
                    $kota !== '' ? $kota : null,
                    $telepon !== '' ? $telepon : null,
                    $metodeBayar !== '' ? $metodeBayar : null,
                    $diskon,
                    $catatan !== '' ? $catatan : null,
                    (int)($me['user_id'] ?? 0),
                    (int)($me['id_gudang'] ?? 0),
                ]
            );

            $idRow = db_one("SELECT LAST_INSERT_ID() AS id");
            $id = (int)($idRow['id'] ?? 0);

            pembelian_audit_safe((int)($me['user_id'] ?? 0), 'CREATE', 'pembelian', $id, [
                'id_gudang' => (int)($me['id_gudang'] ?? 0),
            ]);

            respond(201, [
                'id' => $id,
                'message' => 'Nota berhasil dibuat',
            ]);
        }

        if ($type === 'detail') {
            $idPembelian = (int)($b['id_pembelian'] ?? 0);
            $idBatch = (int)($b['id_batch'] ?? 0);
            $jumlah = (float)($b['jumlah'] ?? 0);
            $harga = (float)($b['harga'] ?? 0);

            if ($idPembelian <= 0 || $idBatch <= 0 || $jumlah <= 0) {
                respond(400, ['error' => 'Field wajib: id_pembelian, id_batch, jumlah']);
            }

            $hdr = db_one(
                "SELECT id, id_gudang
                 FROM pembelian
                 WHERE id = ?
                 LIMIT 1",
                [$idPembelian]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Pembelian tidak ditemukan']);
            }

            if ((int)$hdr['id_gudang'] !== (int)($me['id_gudang'] ?? 0)) {
                respond(403, ['error' => 'Forbidden']);
            }

            $batch = db_one(
                "SELECT id_batch
                 FROM data_batch
                 WHERE id_batch = ?
                 LIMIT 1",
                [$idBatch]
            );

            if (!$batch) {
                respond(404, ['error' => 'Batch tidak ditemukan']);
            }

            $pdo = db();
            $pdo->beginTransaction();

            try {
                db_exec(
                    "INSERT INTO pembelian_detail (id_pembelian, id_batch, jumlah, harga)
                     VALUES (?, ?, ?, ?)",
                    [$idPembelian, $idBatch, $jumlah, $harga]
                );

                $existing = db_one(
                    "SELECT stok
                     FROM stok_batch
                     WHERE id_gudang = ?
                       AND id_batch = ?
                     LIMIT 1",
                    [(int)($me['id_gudang'] ?? 0), $idBatch]
                );

                if ($existing) {
                    db_exec(
                        "UPDATE stok_batch
                         SET stok = stok + ?
                         WHERE id_gudang = ?
                           AND id_batch = ?",
                        [$jumlah, (int)($me['id_gudang'] ?? 0), $idBatch]
                    );
                } else {
                    db_exec(
                        "INSERT INTO stok_batch (id_gudang, id_batch, stok)
                         VALUES (?, ?, ?)",
                        [(int)($me['id_gudang'] ?? 0), $idBatch, $jumlah]
                    );
                }

                pembelian_audit_safe((int)($me['user_id'] ?? 0), 'ADD_ITEM', 'pembelian', $idPembelian, [
                    'id_batch' => $idBatch,
                    'qty' => $jumlah,
                    'harga' => $harga,
                ]);

                $pdo->commit();

                respond(201, [
                    'message' => 'Detail berhasil ditambahkan, stok diperbarui',
                ]);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                respond(500, ['error' => $e->getMessage()]);
            }
        }

        respond(400, ['error' => 'type tidak valid']);
    }

    respond(405, ['error' => 'Method not allowed']);
} catch (Throwable $e) {
    respond(500, ['error' => $e->getMessage()]);
}
