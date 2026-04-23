<?php
// FILE: backend/api/pemakaian.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$auditFile = __DIR__ . '/../lib/audit.php';
if (is_file($auditFile)) {
    require_once $auditFile;
}

$payload = require_auth();
require_role($payload, ['puskesmas', 'dinkes']);
$me = auth_ctx($payload);

$method = request_method();

function pemakaian_input(): array
{
    return request_input();
}

function pemakaian_audit_safe(int $userId, string $action, string $entity, int $entityId, array $meta = []): void
{
    if (function_exists('audit_log')) {
        audit_log($userId, $action, $entity, $entityId, $meta);
    }
}

function pemakaian_gen_no(): string
{
    return 'PMK-' . date('YmdHis');
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
                 FROM pemakaian
                 WHERE id = ?
                 LIMIT 1",
                [$id]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Pemakaian tidak ditemukan']);
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
                 FROM pemakaian_detail pd
                 JOIN data_batch b ON b.id_batch = pd.id_batch
                 JOIN data_obat o ON o.id_obat = b.id_obat
                 WHERE pd.id_pemakaian = ?
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
                    FROM pemakaian_detail pd
                    WHERE pd.id_pemakaian = p.id
                ) AS total_item
             FROM pemakaian p
             JOIN user u ON u.id_admin = p.id_admin
             WHERE p.id_gudang = ?
             ORDER BY p.created_at DESC, p.id DESC",
            [(int)($me['id_gudang'] ?? 0)]
        );

        respond(200, $rows);
    }

    if ($method === 'POST') {
        $b = pemakaian_input();
        $type = (string)($b['type'] ?? 'master');

        if ($type === 'master') {
            $noPemakaian = trim((string)($b['no_pemakaian'] ?? ''));
            if ($noPemakaian === '') {
                $noPemakaian = pemakaian_gen_no();
            }

            $tanggal = trim((string)($b['tanggal'] ?? date('Y-m-d')));
            $keterangan = trim((string)($b['keterangan'] ?? ''));

            db_exec(
                "INSERT INTO pemakaian (
                    no_pemakaian,
                    tanggal,
                    keterangan,
                    id_admin,
                    id_gudang
                ) VALUES (?, ?, ?, ?, ?)",
                [
                    $noPemakaian,
                    $tanggal !== '' ? $tanggal : date('Y-m-d'),
                    $keterangan !== '' ? $keterangan : null,
                    (int)($me['user_id'] ?? 0),
                    (int)($me['id_gudang'] ?? 0),
                ]
            );

            $idRow = db_one("SELECT LAST_INSERT_ID() AS id");
            $id = (int)($idRow['id'] ?? 0);

            pemakaian_audit_safe((int)($me['user_id'] ?? 0), 'CREATE', 'pemakaian', $id, [
                'id_gudang' => (int)($me['id_gudang'] ?? 0),
            ]);

            respond(201, [
                'id' => $id,
                'message' => 'Berhasil dibuat',
            ]);
        }

        if ($type === 'detail') {
            $idPemakaian = (int)($b['id_pemakaian'] ?? 0);
            $idBatch = (int)($b['id_batch'] ?? 0);
            $jumlah = (float)($b['jumlah'] ?? 0);

            if ($idPemakaian <= 0 || $idBatch <= 0 || $jumlah <= 0) {
                respond(400, ['error' => 'Field wajib: id_pemakaian, id_batch, jumlah']);
            }

            $hdr = db_one(
                "SELECT id, id_gudang
                 FROM pemakaian
                 WHERE id = ?
                 LIMIT 1",
                [$idPemakaian]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Pemakaian tidak ditemukan']);
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
                $stokRow = db_one(
                    "SELECT stok
                     FROM stok_batch
                     WHERE id_gudang = ?
                       AND id_batch = ?
                     LIMIT 1",
                    [(int)($me['id_gudang'] ?? 0), $idBatch]
                );

                $stok = (float)($stokRow['stok'] ?? 0);
                if ($stok < $jumlah) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    respond(400, ['error' => 'Stok tidak mencukupi. Stok tersedia: ' . $stok]);
                }

                db_exec(
                    "INSERT INTO pemakaian_detail (id_pemakaian, id_batch, jumlah)
                     VALUES (?, ?, ?)",
                    [$idPemakaian, $idBatch, $jumlah]
                );

                db_exec(
                    "UPDATE stok_batch
                     SET stok = stok - ?
                     WHERE id_gudang = ?
                       AND id_batch = ?",
                    [$jumlah, (int)($me['id_gudang'] ?? 0), $idBatch]
                );

                pemakaian_audit_safe((int)($me['user_id'] ?? 0), 'ADD_ITEM', 'pemakaian', $idPemakaian, [
                    'id_batch' => $idBatch,
                    'qty' => $jumlah,
                ]);

                $pdo->commit();

                respond(201, [
                    'message' => 'Detail ditambahkan, stok diperbarui',
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