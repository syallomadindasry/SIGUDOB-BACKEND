<?php
// FILE: backend/api/distribusi.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
$me = auth_ctx($payload);
$role = strtolower(trim((string)($me['role'] ?? '')));
$method = request_method();

function distribusi_input(): array
{
    $data = request_input();
    if (!empty($data)) {
        return $data;
    }

    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        parse_str($raw, $parsed);
        if (is_array($parsed)) {
            return $parsed;
        }
    }

    return [];
}

function distribusi_generate_no(string $tanggal, int $idHint = 0): string
{
    $date = preg_replace('/[^0-9]/', '', $tanggal);
    if ($date === '' || strlen($date) < 8) {
        $date = date('Ymd');
    } else {
        $date = substr($date, 0, 8);
    }

    $suffix = str_pad((string)max(1, $idHint), 4, '0', STR_PAD_LEFT);
    return 'DST-' . $date . '-' . $suffix;
}

function distribusi_header(int $id): ?array
{
    $row = db_one(
        "SELECT
            d.id,
            d.no_distribusi,
            d.permintaan_id,
            d.from_gudang_id,
            d.to_gudang_id,
            d.id_admin,
            d.tanggal,
            d.status,
            d.note,
            d.created_at,
            d.updated_at,
            gf.nama_gudang AS from_gudang_nama,
            gt.nama_gudang AS to_gudang_nama,
            u.nama AS nama_admin
         FROM distribusi d
         LEFT JOIN gudang gf ON gf.id_gudang = d.from_gudang_id
         LEFT JOIN gudang gt ON gt.id_gudang = d.to_gudang_id
         LEFT JOIN user u ON u.id_admin = d.id_admin
         WHERE d.id = ?
         LIMIT 1",
        [$id]
    );

    return $row ?: null;
}

function distribusi_detail_rows(int $id): array
{
    return db_all(
        "SELECT
            dd.id,
            dd.distribusi_id,
            dd.obat_id,
            dd.id_batch,
            dd.qty_distribusi,
            dd.qty_terima,
            dd.note,
            dd.created_at,
            dd.updated_at,
            o.nama AS nama_obat,
            o.satuan,
            b.batch,
            b.exp_date
         FROM distribusi_detail dd
         LEFT JOIN data_obat o ON o.id_obat = dd.obat_id
         LEFT JOIN data_batch b ON b.id_batch = dd.id_batch
         WHERE dd.distribusi_id = ?
         ORDER BY dd.id ASC",
        [$id]
    );
}

function distribusi_can_view(array $header, string $role, int $myGudangId): bool
{
    if ($role === 'dinkes' && (int)$header['from_gudang_id'] === $myGudangId) {
        return true;
    }

    if ($role === 'puskesmas' && (int)$header['to_gudang_id'] === $myGudangId) {
        return true;
    }

    return false;
}

function distribusi_ensure_gudang_exists(int $idGudang): void
{
    $row = db_one(
        "SELECT id_gudang, nama_gudang
         FROM gudang
         WHERE id_gudang = ?
         LIMIT 1",
        [$idGudang]
    );

    if (!$row) {
        respond(422, ['error' => 'Gudang tidak ditemukan']);
    }
}

function distribusi_ensure_source_stock(int $idGudang, int $idBatch, float $qty): void
{
    if ($idBatch <= 0 || $qty <= 0) {
        return;
    }

    $stok = db_one(
        "SELECT stok
         FROM stok_batch
         WHERE id_gudang = ? AND id_batch = ?
         LIMIT 1",
        [$idGudang, $idBatch]
    );

    $available = (float)($stok['stok'] ?? 0);
    if ($available < $qty) {
        respond(422, ['error' => 'Stok batch tidak mencukupi. Tersedia: ' . $available]);
    }
}

function distribusi_decrease_source_stock(PDO $pdo, int $idGudang, int $idBatch, float $qty): void
{
    if ($idBatch <= 0 || $qty <= 0) {
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT stok
         FROM stok_batch
         WHERE id_gudang = ? AND id_batch = ?
         FOR UPDATE"
    );
    $stmt->execute([$idGudang, $idBatch]);
    $stok = (float)($stmt->fetchColumn() ?: 0);

    if ($stok < $qty) {
        respond(422, ['error' => 'Stok batch tidak mencukupi. Tersedia: ' . $stok]);
    }

    $stmt = $pdo->prepare(
        "UPDATE stok_batch
         SET stok = stok - ?
         WHERE id_gudang = ? AND id_batch = ?"
    );
    $stmt->execute([$qty, $idGudang, $idBatch]);
}

function distribusi_increase_target_stock(PDO $pdo, int $idGudang, int $idBatch, float $qty): void
{
    if ($idBatch <= 0 || $qty <= 0) {
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT stok
         FROM stok_batch
         WHERE id_gudang = ? AND id_batch = ?
         LIMIT 1"
    );
    $stmt->execute([$idGudang, $idBatch]);
    $exists = $stmt->fetchColumn();

    if ($exists === false) {
        $stmt = $pdo->prepare(
            "INSERT INTO stok_batch (id_gudang, id_batch, stok)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([$idGudang, $idBatch, $qty]);
        return;
    }

    $stmt = $pdo->prepare(
        "UPDATE stok_batch
         SET stok = stok + ?
         WHERE id_gudang = ? AND id_batch = ?"
    );
    $stmt->execute([$qty, $idGudang, $idBatch]);
}

function distribusi_insert_detail(
    PDO $pdo,
    int $distribusiId,
    int $fromGudangId,
    array $detail,
    bool $decreaseSourceStock = false
): void {
    $obatId = (int)($detail['obat_id'] ?? 0);
    $idBatch = (int)($detail['id_batch'] ?? 0);
    $qtyDistribusi = (float)($detail['qty_distribusi'] ?? 0);
    $qtyTerima = (float)($detail['qty_terima'] ?? 0);
    $note = trim((string)($detail['note'] ?? ''));

    if ($obatId <= 0) {
        respond(422, ['error' => 'obat_id pada detail wajib diisi']);
    }

    if ($qtyDistribusi <= 0) {
        respond(422, ['error' => 'qty_distribusi pada detail wajib > 0']);
    }

    if ($decreaseSourceStock && $idBatch > 0) {
        distribusi_decrease_source_stock($pdo, $fromGudangId, $idBatch, $qtyDistribusi);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO distribusi_detail
            (distribusi_id, obat_id, id_batch, qty_distribusi, qty_terima, note)
         VALUES
            (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $distribusiId,
        $obatId,
        $idBatch > 0 ? $idBatch : null,
        $qtyDistribusi,
        $qtyTerima,
        $note !== '' ? $note : null,
    ]);
}

if ($method === 'GET') {
    try {
        $id = (int)($_GET['id'] ?? 0);
        $requestedGudang = (int)($_GET['id_gudang'] ?? 0);
        $myGudangId = (int)($me['id_gudang'] ?? 0);

        if ($id > 0) {
            $header = distribusi_header($id);

            if (!$header) {
                respond(404, ['error' => 'Distribusi tidak ditemukan']);
            }

            if (!distribusi_can_view($header, $role, $myGudangId)) {
                respond(403, ['error' => 'Forbidden']);
            }

            respond(200, [
                'header' => $header,
                'detail' => distribusi_detail_rows($id),
            ]);
        }

        if ($role === 'dinkes') {
            if ($requestedGudang > 0) {
                $rows = db_all(
                    "SELECT
                        d.id,
                        d.no_distribusi,
                        d.permintaan_id,
                        d.from_gudang_id,
                        d.to_gudang_id,
                        d.id_admin,
                        d.tanggal,
                        d.status,
                        d.note,
                        d.created_at,
                        d.updated_at,
                        gf.nama_gudang AS from_gudang_nama,
                        gt.nama_gudang AS to_gudang_nama,
                        COALESCE(COUNT(dd.id), 0) AS total_item
                     FROM distribusi d
                     LEFT JOIN gudang gf ON gf.id_gudang = d.from_gudang_id
                     LEFT JOIN gudang gt ON gt.id_gudang = d.to_gudang_id
                     LEFT JOIN distribusi_detail dd ON dd.distribusi_id = d.id
                     WHERE d.from_gudang_id = ? AND d.to_gudang_id = ?
                     GROUP BY
                        d.id, d.no_distribusi, d.permintaan_id, d.from_gudang_id, d.to_gudang_id,
                        d.id_admin, d.tanggal, d.status, d.note, d.created_at, d.updated_at,
                        gf.nama_gudang, gt.nama_gudang
                     ORDER BY d.tanggal DESC, d.id DESC",
                    [$myGudangId, $requestedGudang]
                );

                respond(200, $rows);
            }

            $rows = db_all(
                "SELECT
                    d.id,
                    d.no_distribusi,
                    d.permintaan_id,
                    d.from_gudang_id,
                    d.to_gudang_id,
                    d.id_admin,
                    d.tanggal,
                    d.status,
                    d.note,
                    d.created_at,
                    d.updated_at,
                    gf.nama_gudang AS from_gudang_nama,
                    gt.nama_gudang AS to_gudang_nama,
                    COALESCE(COUNT(dd.id), 0) AS total_item
                 FROM distribusi d
                 LEFT JOIN gudang gf ON gf.id_gudang = d.from_gudang_id
                 LEFT JOIN gudang gt ON gt.id_gudang = d.to_gudang_id
                 LEFT JOIN distribusi_detail dd ON dd.distribusi_id = d.id
                 WHERE d.from_gudang_id = ?
                 GROUP BY
                    d.id, d.no_distribusi, d.permintaan_id, d.from_gudang_id, d.to_gudang_id,
                    d.id_admin, d.tanggal, d.status, d.note, d.created_at, d.updated_at,
                    gf.nama_gudang, gt.nama_gudang
                 ORDER BY d.tanggal DESC, d.id DESC",
                [$myGudangId]
            );

            respond(200, $rows);
        }

        if ($role === 'puskesmas') {
            $rows = db_all(
                "SELECT
                    d.id,
                    d.no_distribusi,
                    d.permintaan_id,
                    d.from_gudang_id,
                    d.to_gudang_id,
                    d.id_admin,
                    d.tanggal,
                    d.status,
                    d.note,
                    d.created_at,
                    d.updated_at,
                    gf.nama_gudang AS from_gudang_nama,
                    gt.nama_gudang AS to_gudang_nama,
                    COALESCE(COUNT(dd.id), 0) AS total_item
                 FROM distribusi d
                 LEFT JOIN gudang gf ON gf.id_gudang = d.from_gudang_id
                 LEFT JOIN gudang gt ON gt.id_gudang = d.to_gudang_id
                 LEFT JOIN distribusi_detail dd ON dd.distribusi_id = d.id
                 WHERE d.to_gudang_id = ?
                 GROUP BY
                    d.id, d.no_distribusi, d.permintaan_id, d.from_gudang_id, d.to_gudang_id,
                    d.id_admin, d.tanggal, d.status, d.note, d.created_at, d.updated_at,
                    gf.nama_gudang, gt.nama_gudang
                 ORDER BY d.tanggal DESC, d.id DESC",
                [$myGudangId]
            );

            respond(200, $rows);
        }

        respond(403, ['error' => 'Forbidden']);
    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Gagal memuat data distribusi',
            'detail' => $e->getMessage(),
        ]);
    }
}

if ($method === 'POST') {
    $b = distribusi_input();
    $type = strtolower(trim((string)($b['type'] ?? $b['action'] ?? 'master')));
    $myGudangId = (int)($me['id_gudang'] ?? 0);

    try {
        if ($type === 'master') {
            if ($role !== 'dinkes') {
                respond(403, ['error' => 'Hanya dinkes yang dapat membuat distribusi']);
            }

            $permintaanId = (int)($b['permintaan_id'] ?? 0);
            $tanggal = trim((string)($b['tanggal'] ?? date('Y-m-d')));
            $status = strtoupper(trim((string)($b['status'] ?? 'SIAP_KIRIM')));
            $note = trim((string)($b['note'] ?? ''));
            $details = is_array($b['details'] ?? null) ? $b['details'] : [];

            $fromGudangId = $myGudangId;
            $toGudangId = (int)($b['to_gudang_id'] ?? 0);

            if ($permintaanId > 0) {
                $permintaan = db_one(
                    "SELECT id, from_gudang_id, to_gudang_id, priority, note, status
                     FROM permintaan
                     WHERE id = ?
                     LIMIT 1",
                    [$permintaanId]
                );

                if (!$permintaan) {
                    respond(404, ['error' => 'Permintaan tidak ditemukan']);
                }

                $fromGudangId = (int)$permintaan['to_gudang_id'];
                $toGudangId = (int)$permintaan['from_gudang_id'];

                if ($fromGudangId !== $myGudangId) {
                    respond(403, ['error' => 'Permintaan ini bukan untuk gudang login']);
                }

                if (empty($details)) {
                    $permintaanDetails = db_all(
                        "SELECT
                            id,
                            obat_id,
                            qty_requested,
                            qty_approved
                         FROM permintaan_detail
                         WHERE permintaan_id = ?
                         ORDER BY id ASC",
                        [$permintaanId]
                    );

                    foreach ($permintaanDetails as $pd) {
                        $qty = (float)($pd['qty_approved'] ?? 0);
                        if ($qty <= 0) {
                            $qty = (float)($pd['qty_requested'] ?? 0);
                        }

                        if ($qty <= 0) {
                            continue;
                        }

                        $details[] = [
                            'obat_id' => (int)$pd['obat_id'],
                            'id_batch' => 0,
                            'qty_distribusi' => $qty,
                            'qty_terima' => in_array($status, ['DITERIMA'], true) ? $qty : 0,
                            'note' => 'Auto generated from permintaan_detail #' . (int)$pd['id'],
                        ];
                    }
                }
            }

            if ($toGudangId <= 0) {
                respond(422, ['error' => 'to_gudang_id wajib diisi']);
            }

            distribusi_ensure_gudang_exists($fromGudangId);
            distribusi_ensure_gudang_exists($toGudangId);

            $pdo = db();
            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO distribusi
                        (no_distribusi, permintaan_id, from_gudang_id, to_gudang_id, id_admin, tanggal, status, note)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    '',
                    $permintaanId > 0 ? $permintaanId : null,
                    $fromGudangId,
                    $toGudangId,
                    (int)$me['id_admin'],
                    $tanggal,
                    $status,
                    $note !== '' ? $note : null,
                ]);

                $idDistribusi = (int)$pdo->lastInsertId();
                $noDistribusi = trim((string)($b['no_distribusi'] ?? ''));
                if ($noDistribusi === '') {
                    $noDistribusi = distribusi_generate_no($tanggal, $idDistribusi);
                }

                $stmt = $pdo->prepare(
                    "UPDATE distribusi
                     SET no_distribusi = ?
                     WHERE id = ?"
                );
                $stmt->execute([$noDistribusi, $idDistribusi]);

                foreach ($details as $detail) {
                    distribusi_insert_detail($pdo, $idDistribusi, $fromGudangId, $detail, true);
                }

                $pdo->commit();

                respond(201, [
                    'message' => 'Distribusi berhasil dibuat',
                    'id' => $idDistribusi,
                    'header' => distribusi_header($idDistribusi),
                    'detail' => distribusi_detail_rows($idDistribusi),
                ]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        if ($type === 'detail') {
            if ($role !== 'dinkes') {
                respond(403, ['error' => 'Hanya dinkes yang dapat menambah detail distribusi']);
            }

            $idDistribusi = (int)($b['distribusi_id'] ?? $b['id_distribusi'] ?? 0);
            if ($idDistribusi <= 0) {
                respond(422, ['error' => 'distribusi_id wajib diisi']);
            }

            $header = distribusi_header($idDistribusi);
            if (!$header) {
                respond(404, ['error' => 'Distribusi tidak ditemukan']);
            }

            if ((int)$header['from_gudang_id'] !== $myGudangId) {
                respond(403, ['error' => 'Forbidden']);
            }

            $detail = [
                'obat_id' => (int)($b['obat_id'] ?? 0),
                'id_batch' => (int)($b['id_batch'] ?? 0),
                'qty_distribusi' => (float)($b['qty_distribusi'] ?? 0),
                'qty_terima' => (float)($b['qty_terima'] ?? 0),
                'note' => (string)($b['note'] ?? ''),
            ];

            $pdo = db();
            $pdo->beginTransaction();

            try {
                distribusi_insert_detail($pdo, $idDistribusi, (int)$header['from_gudang_id'], $detail, true);
                $pdo->commit();

                respond(201, [
                    'message' => 'Detail distribusi berhasil ditambahkan',
                    'header' => distribusi_header($idDistribusi),
                    'detail' => distribusi_detail_rows($idDistribusi),
                ]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        if ($type === 'receive') {
            $idDistribusi = (int)($b['distribusi_id'] ?? $b['id_distribusi'] ?? 0);
            if ($idDistribusi <= 0) {
                respond(422, ['error' => 'distribusi_id wajib diisi']);
            }

            $header = distribusi_header($idDistribusi);
            if (!$header) {
                respond(404, ['error' => 'Distribusi tidak ditemukan']);
            }

            if ((int)$header['to_gudang_id'] !== $myGudangId) {
                respond(403, ['error' => 'Distribusi ini bukan untuk gudang login']);
            }

            $items = is_array($b['items'] ?? null) ? $b['items'] : [];
            $pdo = db();
            $pdo->beginTransaction();

            try {
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $detailId = (int)($item['id'] ?? 0);
                        $qtyTerima = (float)($item['qty_terima'] ?? 0);

                        if ($detailId <= 0) {
                            continue;
                        }

                        $detail = db_one(
                            "SELECT id, id_batch, qty_distribusi
                             FROM distribusi_detail
                             WHERE id = ? AND distribusi_id = ?
                             LIMIT 1",
                            [$detailId, $idDistribusi]
                        );

                        if (!$detail) {
                            continue;
                        }

                        if ($qtyTerima <= 0) {
                            $qtyTerima = (float)$detail['qty_distribusi'];
                        }

                        $stmt = $pdo->prepare(
                            "UPDATE distribusi_detail
                             SET qty_terima = ?
                             WHERE id = ?"
                        );
                        $stmt->execute([$qtyTerima, $detailId]);

                        distribusi_increase_target_stock(
                            $pdo,
                            (int)$header['to_gudang_id'],
                            (int)($detail['id_batch'] ?? 0),
                            $qtyTerima
                        );
                    }
                } else {
                    $details = db_all(
                        "SELECT id, id_batch, qty_distribusi
                         FROM distribusi_detail
                         WHERE distribusi_id = ?",
                        [$idDistribusi]
                    );

                    foreach ($details as $detail) {
                        $qtyTerima = (float)($detail['qty_distribusi'] ?? 0);

                        $stmt = $pdo->prepare(
                            "UPDATE distribusi_detail
                             SET qty_terima = ?
                             WHERE id = ?"
                        );
                        $stmt->execute([$qtyTerima, (int)$detail['id']]);

                        distribusi_increase_target_stock(
                            $pdo,
                            (int)$header['to_gudang_id'],
                            (int)($detail['id_batch'] ?? 0),
                            $qtyTerima
                        );
                    }
                }

                $stmt = $pdo->prepare(
                    "UPDATE distribusi
                     SET status = 'DITERIMA'
                     WHERE id = ?"
                );
                $stmt->execute([$idDistribusi]);

                $pdo->commit();

                respond(200, [
                    'message' => 'Distribusi berhasil diterima',
                    'header' => distribusi_header($idDistribusi),
                    'detail' => distribusi_detail_rows($idDistribusi),
                ]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        respond(422, ['error' => 'type/action tidak valid']);
    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Gagal memproses distribusi',
            'detail' => $e->getMessage(),
        ]);
    }
}

respond(405, ['error' => 'Method not allowed']);