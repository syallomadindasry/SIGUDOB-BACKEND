<?php
// FILE: backend/api/opname.php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/audit.php';

$payload = require_auth();
require_role($payload, ['dinkes', 'puskesmas']);
$me = auth_ctx($payload);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function enforce_gudang_scope(array $me, int $gudangId): void
{
    $role = (string)($me['role'] ?? '');
    $myGudangId = (int)($me['id_gudang'] ?? 0);

    if ($role === 'puskesmas' && $gudangId !== $myGudangId) {
        respond(403, ['error' => 'Forbidden']);
    }
}

try {
    if ($method === 'GET') {
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $hdr = db_one("SELECT * FROM opname WHERE id=? LIMIT 1", [$id]);
            if (!$hdr) {
                respond(404, ['error' => 'Opname tidak ditemukan']);
            }

            $gudangId = (int)$hdr['id_gudang'];
            enforce_gudang_scope($me, $gudangId);

            $items = db_all(
                "SELECT d.*, b.batch, b.exp_date, o.nama AS nama_obat, o.satuan
                 FROM opname_detail d
                 JOIN data_batch b ON b.id_batch = d.id_batch
                 JOIN data_obat o ON o.id_obat = b.id_obat
                 WHERE d.opname_id = ?
                 ORDER BY d.id ASC",
                [$id]
            );

            respond(200, ['opname' => $hdr, 'items' => $items]);
        }

        if ($me['role'] === 'dinkes') {
            $rows = db_all(
                "SELECT o.*, g.nama_gudang
                 FROM opname o
                 JOIN gudang g ON g.id_gudang = o.id_gudang
                 ORDER BY o.id DESC"
            );
            respond(200, $rows);
        }

        $rows = db_all(
            "SELECT o.*, g.nama_gudang
             FROM opname o
             JOIN gudang g ON g.id_gudang = o.id_gudang
             WHERE o.id_gudang = ?
             ORDER BY o.id DESC",
            [$me['id_gudang']]
        );
        respond(200, $rows);
    }

    if ($method === 'POST') {
        $b = json_input();
        $type = (string)($b['type'] ?? 'master');

        if ($type === 'master') {
            require_role($payload, ['puskesmas']);
            $no = (string)($b['no_opname'] ?? ('OPN-' . date('YmdHis')));

            $stmt = db()->prepare(
                "INSERT INTO opname (no_opname,tanggal,id_gudang,status,catatan,created_by)
                 VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([
                $no,
                (string)($b['tanggal'] ?? date('Y-m-d')),
                $me['id_gudang'],
                'DRAFT',
                (string)($b['catatan'] ?? ''),
                $me['user_id'],
            ]);

            $id = (int)db()->lastInsertId();
            audit_log((int)$me['user_id'], 'CREATE', 'opname', $id, ['id_gudang' => $me['id_gudang']]);

            respond(201, ['id' => $id, 'message' => 'Opname dibuat']);
        }

        if ($type === 'detail') {
            require_role($payload, ['puskesmas']);

            $opnameId = (int)($b['opname_id'] ?? 0);
            $idBatch = (int)($b['id_batch'] ?? 0);
            $stokFisik = (int)($b['stok_fisik'] ?? 0);

            if ($opnameId <= 0 || $idBatch <= 0) {
                respond(400, ['error' => 'opname_id dan id_batch wajib']);
            }

            $hdr = db_one("SELECT * FROM opname WHERE id=? LIMIT 1", [$opnameId]);
            if (!$hdr) {
                respond(404, ['error' => 'Opname tidak ditemukan']);
            }
            if ((int)$hdr['id_gudang'] !== (int)$me['id_gudang']) {
                respond(403, ['error' => 'Forbidden']);
            }
            if ((string)$hdr['status'] !== 'DRAFT') {
                respond(400, ['error' => 'Hanya bisa tambah item saat DRAFT']);
            }

            $row = db_one(
                "SELECT stok FROM stok_batch WHERE id_gudang=? AND id_batch=? LIMIT 1",
                [$me['id_gudang'], $idBatch]
            );
            $stokSistem = (int)($row['stok'] ?? 0);
            $selisih = $stokFisik - $stokSistem;

            db_exec(
                "INSERT INTO opname_detail (opname_id,id_batch,stok_sistem,stok_fisik,selisih,note)
                 VALUES (?,?,?,?,?,?)",
                [$opnameId, $idBatch, $stokSistem, $stokFisik, $selisih, (string)($b['note'] ?? '')]
            );

            audit_log((int)$me['user_id'], 'ADD_ITEM', 'opname', $opnameId, [
                'id_batch' => $idBatch,
                'stok_sistem' => $stokSistem,
                'stok_fisik' => $stokFisik,
                'selisih' => $selisih,
            ]);

            respond(201, ['message' => 'Item opname ditambahkan']);
        }

        respond(400, ['error' => 'type tidak valid']);
    }

    if ($method === 'PUT') {
        $b = json_input();
        $action = (string)($b['action'] ?? '');
        $id = (int)($b['id'] ?? 0);

        if ($id <= 0) {
            respond(400, ['error' => 'id wajib']);
        }

        $hdr = db_one("SELECT * FROM opname WHERE id=? LIMIT 1", [$id]);
        if (!$hdr) {
            respond(404, ['error' => 'Opname tidak ditemukan']);
        }

        $gudangId = (int)$hdr['id_gudang'];

        if ($action === 'submit') {
            require_role($payload, ['puskesmas']);
            if ($gudangId !== (int)$me['id_gudang']) {
                respond(403, ['error' => 'Forbidden']);
            }
            if ((string)$hdr['status'] !== 'DRAFT') {
                respond(400, ['error' => 'Harus DRAFT']);
            }

            db_exec("UPDATE opname SET status='SUBMITTED' WHERE id=?", [$id]);
            audit_log((int)$me['user_id'], 'SUBMIT', 'opname', $id, []);

            respond(200, ['message' => 'Opname diajukan']);
        }

        if ($action === 'approve') {
            require_role($payload, ['dinkes']);
            if ((string)$hdr['status'] !== 'SUBMITTED') {
                respond(400, ['error' => 'Harus SUBMITTED']);
            }

            $items = db_all("SELECT id_batch, selisih FROM opname_detail WHERE opname_id=?", [$id]);
            if (!$items) {
                respond(400, ['error' => 'Tidak ada item']);
            }

            $pdo = db();
            $pdo->beginTransaction();

            try {
                foreach ($items as $it) {
                    $idBatch = (int)$it['id_batch'];
                    $selisih = (int)$it['selisih'];

                    if ($selisih === 0) {
                        continue;
                    }

                    db_exec(
                        "INSERT INTO stok_batch (id_gudang,id_batch,stok) VALUES (?,?,?)
                         ON DUPLICATE KEY UPDATE stok = stok + VALUES(stok)",
                        [$gudangId, $idBatch, $selisih]
                    );
                }

                db_exec("UPDATE opname SET status='APPROVED' WHERE id=?", [$id]);
                audit_log((int)$me['user_id'], 'APPROVE', 'opname', $id, []);

                $pdo->commit();
                respond(200, ['message' => 'Opname disetujui & stok disesuaikan']);
            } catch (Throwable $e) {
                $pdo->rollBack();
                respond(500, ['error' => 'Gagal approve opname', 'detail' => $e->getMessage()]);
            }
        }

        if ($action === 'reject') {
            require_role($payload, ['dinkes']);
            if ((string)$hdr['status'] !== 'SUBMITTED') {
                respond(400, ['error' => 'Harus SUBMITTED']);
            }

            db_exec("UPDATE opname SET status='REJECTED' WHERE id=?", [$id]);
            audit_log((int)$me['user_id'], 'REJECT', 'opname', $id, ['note' => (string)($b['note'] ?? '')]);

            respond(200, ['message' => 'Opname ditolak']);
        }

        respond(400, ['error' => 'action tidak valid']);
    }

    respond(405, ['error' => 'Method not allowed']);
} catch (Throwable $e) {
    error_log('opname.php failed: ' . $e->getMessage());
    respond(500, ['error' => 'Internal server error', 'detail' => $e->getMessage()]);
}