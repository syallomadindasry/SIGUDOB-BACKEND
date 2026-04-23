<?php
// FILE: backend/api/retur.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$auditFile = __DIR__ . '/../lib/audit.php';
if (is_file($auditFile)) {
    require_once $auditFile;
}

$payload = require_auth();
require_role($payload, ['dinkes', 'puskesmas']);
$me = auth_ctx($payload);

$method = request_method();

function retur_input(): array
{
    return request_input();
}

function retur_audit_safe(int $userId, string $action, string $entity, int $entityId, array $meta = []): void
{
    if (function_exists('audit_log')) {
        audit_log($userId, $action, $entity, $entityId, $meta);
    }
}

function retur_gen_no(): string
{
    return 'RTR-' . date('YmdHis');
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
                 FROM retur
                 WHERE id = ?
                 LIMIT 1",
                [$id]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Retur tidak ditemukan']);
            }

            if ((int)$hdr['id_gudang'] !== (int)($me['id_gudang'] ?? 0)) {
                respond(403, ['error' => 'Forbidden']);
            }

            $rows = db_all(
                "SELECT
                    rd.*,
                    b.batch,
                    b.exp_date,
                    o.nama AS nama_obat,
                    o.satuan
                 FROM retur_detail rd
                 JOIN data_batch b ON b.id_batch = rd.id_batch
                 JOIN data_obat o ON o.id_obat = b.id_obat
                 WHERE rd.id_retur = ?
                 ORDER BY rd.id ASC",
                [$id]
            );

            respond(200, $rows);
        }

        $rows = db_all(
            "SELECT
                r.*,
                u.nama AS nama_admin,
                (
                    SELECT COUNT(*)
                    FROM retur_detail rd
                    WHERE rd.id_retur = r.id
                ) AS total_item
             FROM retur r
             JOIN user u ON u.id_admin = r.id_admin
             WHERE r.id_gudang = ?
             ORDER BY r.created_at DESC, r.id DESC",
            [(int)($me['id_gudang'] ?? 0)]
        );

        respond(200, $rows);
    }

    if ($method === 'POST') {
        $b = retur_input();
        $type = (string)($b['type'] ?? 'master');

        if ($type === 'master') {
            $noRetur = trim((string)($b['no_retur'] ?? ''));
            if ($noRetur === '') {
                $noRetur = retur_gen_no();
            }

            $tanggal = trim((string)($b['tanggal'] ?? date('Y-m-d')));
            $supplier = trim((string)($b['supplier'] ?? ''));
            $alasan = trim((string)($b['alasan'] ?? ''));
            $catatan = trim((string)($b['catatan'] ?? ''));

            db_exec(
                "INSERT INTO retur (
                    no_retur,
                    tanggal,
                    supplier,
                    alasan,
                    catatan,
                    id_admin,
                    id_gudang
                ) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $noRetur,
                    $tanggal !== '' ? $tanggal : date('Y-m-d'),
                    $supplier !== '' ? $supplier : null,
                    $alasan !== '' ? $alasan : null,
                    $catatan !== '' ? $catatan : null,
                    (int)($me['user_id'] ?? 0),
                    (int)($me['id_gudang'] ?? 0),
                ]
            );

            $idRow = db_one("SELECT LAST_INSERT_ID() AS id");
            $id = (int)($idRow['id'] ?? 0);

            retur_audit_safe((int)($me['user_id'] ?? 0), 'CREATE', 'retur', $id, [
                'id_gudang' => (int)($me['id_gudang'] ?? 0),
                'role' => (string)($me['role'] ?? ''),
            ]);

            respond(201, [
                'id' => $id,
                'message' => 'Retur berhasil dibuat',
            ]);
        }

        if ($type === 'detail') {
            $idRetur = (int)($b['id_retur'] ?? 0);
            $idBatch = (int)($b['id_batch'] ?? 0);
            $jumlah = (float)($b['jumlah'] ?? 0);
            $note = trim((string)($b['note'] ?? ''));

            if ($idRetur <= 0 || $idBatch <= 0 || $jumlah <= 0) {
                respond(400, ['error' => 'Field wajib: id_retur, id_batch, jumlah']);
            }

            $hdr = db_one(
                "SELECT id, id_gudang
                 FROM retur
                 WHERE id = ?
                 LIMIT 1",
                [$idRetur]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Retur tidak ditemukan']);
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
                    "INSERT INTO retur_detail (id_retur, id_batch, jumlah, note)
                     VALUES (?, ?, ?, ?)",
                    [
                        $idRetur,
                        $idBatch,
                        $jumlah,
                        $note !== '' ? $note : null,
                    ]
                );

                db_exec(
                    "UPDATE stok_batch
                     SET stok = stok - ?
                     WHERE id_gudang = ?
                       AND id_batch = ?",
                    [$jumlah, (int)($me['id_gudang'] ?? 0), $idBatch]
                );

                retur_audit_safe((int)($me['user_id'] ?? 0), 'ADD_ITEM', 'retur', $idRetur, [
                    'id_batch' => $idBatch,
                    'qty' => $jumlah,
                    'role' => (string)($me['role'] ?? ''),
                ]);

                $pdo->commit();

                respond(201, [
                    'message' => 'Detail retur ditambahkan, stok diperbarui',
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