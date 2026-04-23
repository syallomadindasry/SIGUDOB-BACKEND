<?php
// FILE: backend/api/penghapusan.php

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

function penghapusan_input(): array
{
    return request_input();
}

function penghapusan_audit_safe(int $userId, string $action, string $entity, int $entityId, array $meta = []): void
{
    if (function_exists('audit_log')) {
        audit_log($userId, $action, $entity, $entityId, $meta);
    }
}

function penghapusan_gen_no(): string
{
    return 'HPS-' . date('YmdHis');
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
                 FROM penghapusan
                 WHERE id = ?
                 LIMIT 1",
                [$id]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Penghapusan tidak ditemukan']);
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
                 FROM penghapusan_detail pd
                 JOIN data_batch b ON b.id_batch = pd.id_batch
                 JOIN data_obat o ON o.id_obat = b.id_obat
                 WHERE pd.id_hapus = ?
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
                    FROM penghapusan_detail pd
                    WHERE pd.id_hapus = p.id
                ) AS total_item
             FROM penghapusan p
             JOIN user u ON u.id_admin = p.id_admin
             WHERE p.id_gudang = ?
             ORDER BY p.created_at DESC, p.id DESC",
            [(int)($me['id_gudang'] ?? 0)]
        );

        respond(200, $rows);
    }

    if ($method === 'POST') {
        $b = penghapusan_input();
        $type = (string)($b['type'] ?? 'master');

        if ($type === 'master') {
            $noHapus = trim((string)($b['no_hapus'] ?? ''));
            if ($noHapus === '') {
                $noHapus = penghapusan_gen_no();
            }

            $tanggal = trim((string)($b['tanggal'] ?? date('Y-m-d')));
            $alasan = trim((string)($b['alasan'] ?? ''));

            db_exec(
                "INSERT INTO penghapusan (
                    no_hapus,
                    tanggal,
                    alasan,
                    id_admin,
                    id_gudang
                ) VALUES (?, ?, ?, ?, ?)",
                [
                    $noHapus,
                    $tanggal !== '' ? $tanggal : date('Y-m-d'),
                    $alasan !== '' ? $alasan : null,
                    (int)($me['user_id'] ?? 0),
                    (int)($me['id_gudang'] ?? 0),
                ]
            );

            $idRow = db_one("SELECT LAST_INSERT_ID() AS id");
            $id = (int)($idRow['id'] ?? 0);

            penghapusan_audit_safe((int)($me['user_id'] ?? 0), 'CREATE', 'penghapusan', $id, [
                'id_gudang' => (int)($me['id_gudang'] ?? 0),
            ]);

            respond(201, [
                'id' => $id,
                'message' => 'Berhasil dibuat',
            ]);
        }

        if ($type === 'detail') {
            $idHapus = (int)($b['id_hapus'] ?? 0);
            $idBatch = (int)($b['id_batch'] ?? 0);
            $jumlah = (float)($b['jumlah'] ?? 0);

            if ($idHapus <= 0 || $idBatch <= 0 || $jumlah <= 0) {
                respond(400, ['error' => 'Field wajib: id_hapus, id_batch, jumlah']);
            }

            $hdr = db_one(
                "SELECT id, id_gudang
                 FROM penghapusan
                 WHERE id = ?
                 LIMIT 1",
                [$idHapus]
            );

            if (!$hdr) {
                respond(404, ['error' => 'Penghapusan tidak ditemukan']);
            }

            if ((int)$hdr['id_gudang'] !== (int)($me['id_gudang'] ?? 0)) {
                respond(403, ['error' => 'Forbidden']);
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
                    "INSERT INTO penghapusan_detail (id_hapus, id_batch, jumlah)
                     VALUES (?, ?, ?)",
                    [$idHapus, $idBatch, $jumlah]
                );

                db_exec(
                    "UPDATE stok_batch
                     SET stok = stok - ?
                     WHERE id_gudang = ?
                       AND id_batch = ?",
                    [$jumlah, (int)($me['id_gudang'] ?? 0), $idBatch]
                );

                penghapusan_audit_safe((int)($me['user_id'] ?? 0), 'ADD_ITEM', 'penghapusan', $idHapus, [
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