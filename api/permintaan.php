<?php
// FILE: backend/api/permintaan.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$payload = require_auth();
$me = auth_ctx($payload);

$role = strtolower(trim((string)($me['role'] ?? '')));
$method = request_method();

function permintaan_input(): array
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

function permintaan_header(int $id): ?array
{
    $row = db_one(
        "SELECT
            p.id,
            p.from_gudang_id,
            p.to_gudang_id,
            p.priority,
            p.note,
            p.status,
            p.created_at,
            p.updated_at,
            gf.nama_gudang AS from_gudang_nama,
            gt.nama_gudang AS to_gudang_nama
         FROM permintaan p
         LEFT JOIN gudang gf ON gf.id_gudang = p.from_gudang_id
         LEFT JOIN gudang gt ON gt.id_gudang = p.to_gudang_id
         WHERE p.id = ?
         LIMIT 1",
        [$id]
    );

    return $row ?: null;
}

function permintaan_detail_rows(int $id): array
{
    return db_all(
        "SELECT
            pd.id,
            pd.permintaan_id,
            pd.obat_id,
            pd.qty_requested,
            pd.qty_approved,
            o.nama AS nama_obat,
            o.satuan
         FROM permintaan_detail pd
         LEFT JOIN data_obat o ON o.id_obat = pd.obat_id
         WHERE pd.permintaan_id = ?
         ORDER BY pd.id ASC",
        [$id]
    );
}

function permintaan_can_view(array $header, string $role, int $myGudangId): bool
{
    if ($role === 'dinkes' && (int)$header['to_gudang_id'] === $myGudangId) {
        return true;
    }

    if ($role === 'puskesmas' && (int)$header['from_gudang_id'] === $myGudangId) {
        return true;
    }

    return false;
}

function permintaan_ensure_gudang_exists(int $idGudang): void
{
    $row = db_one(
        "SELECT id_gudang
         FROM gudang
         WHERE id_gudang = ?
         LIMIT 1",
        [$idGudang]
    );

    if (!$row) {
        respond(422, ['error' => 'Gudang tidak ditemukan']);
    }
}

function permintaan_find_dinkes_gudang_id(): int
{
    $row = db_one(
        "SELECT id_gudang
         FROM gudang
         WHERE LOWER(TRIM(jenis_gudang)) = 'dinkes'
         ORDER BY id_gudang ASC
         LIMIT 1"
    );

    if ($row && (int)($row['id_gudang'] ?? 0) > 0) {
        return (int)$row['id_gudang'];
    }

    $row = db_one(
        "SELECT id_gudang
         FROM gudang
         WHERE LOWER(TRIM(nama_gudang)) LIKE '%dinkes%'
         ORDER BY id_gudang ASC
         LIMIT 1"
    );

    if ($row && (int)($row['id_gudang'] ?? 0) > 0) {
        return (int)$row['id_gudang'];
    }

    respond(500, ['error' => 'Gudang dinkes tidak ditemukan']);

    throw new RuntimeException('Gudang dinkes tidak ditemukan');
}

if ($method === 'GET') {
    try {
        $id = (int)($_GET['id'] ?? 0);
        $myGudangId = (int)($me['id_gudang'] ?? 0);

        if ($id > 0) {
            $header = permintaan_header($id);

            if (!$header) {
                respond(404, ['error' => 'Permintaan tidak ditemukan']);
            }

            if (!permintaan_can_view($header, $role, $myGudangId)) {
                respond(403, ['error' => 'Forbidden']);
            }

            respond(200, [
                'header' => $header,
                'detail' => permintaan_detail_rows($id),
            ]);
        }

        if ($role === 'dinkes') {
            $rows = db_all(
                "SELECT
                    p.id,
                    p.from_gudang_id,
                    p.to_gudang_id,
                    p.priority,
                    p.note,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    gf.nama_gudang AS from_gudang_nama,
                    gt.nama_gudang AS to_gudang_nama,
                    COUNT(pd.id) AS total_item
                 FROM permintaan p
                 LEFT JOIN gudang gf ON gf.id_gudang = p.from_gudang_id
                 LEFT JOIN gudang gt ON gt.id_gudang = p.to_gudang_id
                 LEFT JOIN permintaan_detail pd ON pd.permintaan_id = p.id
                 WHERE p.to_gudang_id = ?
                 GROUP BY
                    p.id, p.from_gudang_id, p.to_gudang_id, p.priority, p.note,
                    p.status, p.created_at, p.updated_at,
                    gf.nama_gudang, gt.nama_gudang
                 ORDER BY p.id DESC",
                [$myGudangId]
            );

            respond(200, $rows);
        }

        if ($role === 'puskesmas') {
            $rows = db_all(
                "SELECT
                    p.id,
                    p.from_gudang_id,
                    p.to_gudang_id,
                    p.priority,
                    p.note,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    gf.nama_gudang AS from_gudang_nama,
                    gt.nama_gudang AS to_gudang_nama,
                    COUNT(pd.id) AS total_item
                 FROM permintaan p
                 LEFT JOIN gudang gf ON gf.id_gudang = p.from_gudang_id
                 LEFT JOIN gudang gt ON gt.id_gudang = p.to_gudang_id
                 LEFT JOIN permintaan_detail pd ON pd.permintaan_id = p.id
                 WHERE p.from_gudang_id = ?
                 GROUP BY
                    p.id, p.from_gudang_id, p.to_gudang_id, p.priority, p.note,
                    p.status, p.created_at, p.updated_at,
                    gf.nama_gudang, gt.nama_gudang
                 ORDER BY p.id DESC",
                [$myGudangId]
            );

            respond(200, $rows);
        }

        respond(403, ['error' => 'Forbidden']);
    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Gagal memuat data permintaan',
            'detail' => $e->getMessage(),
        ]);
    }
}

if ($method === 'POST') {
    $b = permintaan_input();
    $type = strtolower(trim((string)($b['type'] ?? 'master')));
    $myGudangId = (int)($me['id_gudang'] ?? 0);

    try {
        if ($type === 'master') {
            if ($role !== 'puskesmas') {
                respond(403, ['error' => 'Hanya puskesmas yang dapat membuat permintaan']);
            }

            $priority = strtoupper(trim((string)($b['priority'] ?? 'MEDIUM')));
            $note = trim((string)($b['note'] ?? ''));
            $details = is_array($b['details'] ?? null) ? $b['details'] : [];

            if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'], true)) {
                $priority = 'MEDIUM';
            }

            if (empty($details)) {
                respond(422, ['error' => 'Detail permintaan wajib diisi']);
            }

            $toGudangId = (int)($b['to_gudang_id'] ?? 0);
            if ($toGudangId <= 0) {
                $toGudangId = permintaan_find_dinkes_gudang_id();
            }

            permintaan_ensure_gudang_exists($myGudangId);
            permintaan_ensure_gudang_exists($toGudangId);

            $pdo = db();
            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO permintaan
                        (from_gudang_id, to_gudang_id, priority, note, status)
                     VALUES
                        (?, ?, ?, ?, 'DRAFT')"
                );
                $stmt->execute([
                    $myGudangId,
                    $toGudangId,
                    $priority,
                    $note !== '' ? $note : null,
                ]);

                $idPermintaan = (int)$pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    "INSERT INTO permintaan_detail
                        (permintaan_id, obat_id, qty_requested, qty_approved)
                     VALUES
                        (?, ?, ?, 0)"
                );

                foreach ($details as $item) {
                    $obatId = (int)($item['obat_id'] ?? 0);
                    $qtyRequested = (float)($item['qty_requested'] ?? 0);

                    if ($obatId <= 0 || $qtyRequested <= 0) {
                        continue;
                    }

                    $stmt->execute([
                        $idPermintaan,
                        $obatId,
                        $qtyRequested,
                    ]);
                }

                $detailCount = (int)$pdo->query(
                    "SELECT COUNT(*) FROM permintaan_detail WHERE permintaan_id = " . $idPermintaan
                )->fetchColumn();

                if ($detailCount <= 0) {
                    $pdo->rollBack();
                    respond(422, ['error' => 'Minimal 1 item permintaan harus valid']);
                }

                $pdo->commit();

                respond(201, [
                    'message' => 'Permintaan berhasil dibuat',
                    'header' => permintaan_header($idPermintaan),
                    'detail' => permintaan_detail_rows($idPermintaan),
                ]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        if ($type === 'submit') {
            if ($role !== 'puskesmas') {
                respond(403, ['error' => 'Hanya puskesmas yang dapat mengajukan permintaan']);
            }

            $permintaanId = (int)($b['permintaan_id'] ?? 0);
            if ($permintaanId <= 0) {
                respond(422, ['error' => 'permintaan_id wajib diisi']);
            }

            $header = permintaan_header($permintaanId);
            if (!$header) {
                respond(404, ['error' => 'Permintaan tidak ditemukan']);
            }

            if ((int)$header['from_gudang_id'] !== $myGudangId) {
                respond(403, ['error' => 'Forbidden']);
            }

            if (strtoupper(trim((string)$header['status'])) !== 'DRAFT') {
                respond(422, ['error' => 'Hanya permintaan DRAFT yang dapat diajukan']);
            }

            db()->prepare(
                "UPDATE permintaan
                 SET status = 'SUBMITTED', updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?"
            )->execute([$permintaanId]);

            respond(200, [
                'message' => 'Permintaan berhasil diajukan',
                'header' => permintaan_header($permintaanId),
                'detail' => permintaan_detail_rows($permintaanId),
            ]);
        }

        if ($type === 'approve') {
            if ($role !== 'dinkes') {
                respond(403, ['error' => 'Hanya dinkes yang dapat menyetujui permintaan']);
            }

            $permintaanId = (int)($b['permintaan_id'] ?? 0);
            if ($permintaanId <= 0) {
                respond(422, ['error' => 'permintaan_id wajib diisi']);
            }

            $header = permintaan_header($permintaanId);
            if (!$header) {
                respond(404, ['error' => 'Permintaan tidak ditemukan']);
            }

            if ((int)$header['to_gudang_id'] !== $myGudangId) {
                respond(403, ['error' => 'Forbidden']);
            }

            if (strtoupper(trim((string)$header['status'])) !== 'SUBMITTED') {
                respond(422, ['error' => 'Hanya permintaan SUBMITTED yang dapat disetujui']);
            }

            $pdo = db();
            $pdo->beginTransaction();

            try {
                $items = is_array($b['items'] ?? null) ? $b['items'] : [];

                if (!empty($items)) {
                    $stmt = $pdo->prepare(
                        "UPDATE permintaan_detail
                         SET qty_approved = ?
                         WHERE id = ? AND permintaan_id = ?"
                    );

                    foreach ($items as $item) {
                        $detailId = (int)($item['id'] ?? 0);
                        $qtyApproved = (float)($item['qty_approved'] ?? 0);

                        if ($detailId <= 0) {
                            continue;
                        }

                        $stmt->execute([
                            max(0, $qtyApproved),
                            $detailId,
                            $permintaanId,
                        ]);
                    }
                }

                $pdo->prepare(
                    "UPDATE permintaan_detail
                     SET qty_approved = qty_requested
                     WHERE permintaan_id = ?
                       AND COALESCE(qty_approved, 0) <= 0"
                )->execute([$permintaanId]);

                $pdo->prepare(
                    "UPDATE permintaan
                     SET status = 'APPROVED', updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?"
                )->execute([$permintaanId]);

                $pdo->commit();

                respond(200, [
                    'message' => 'Permintaan berhasil disetujui',
                    'header' => permintaan_header($permintaanId),
                    'detail' => permintaan_detail_rows($permintaanId),
                ]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        if ($type === 'reject') {
            if ($role !== 'dinkes') {
                respond(403, ['error' => 'Hanya dinkes yang dapat menolak permintaan']);
            }

            $permintaanId = (int)($b['permintaan_id'] ?? 0);
            if ($permintaanId <= 0) {
                respond(422, ['error' => 'permintaan_id wajib diisi']);
            }

            $header = permintaan_header($permintaanId);
            if (!$header) {
                respond(404, ['error' => 'Permintaan tidak ditemukan']);
            }

            if ((int)$header['to_gudang_id'] !== $myGudangId) {
                respond(403, ['error' => 'Forbidden']);
            }

            if (strtoupper(trim((string)$header['status'])) !== 'SUBMITTED') {
                respond(422, ['error' => 'Hanya permintaan SUBMITTED yang dapat ditolak']);
            }

            db()->prepare(
                "UPDATE permintaan
                 SET status = 'REJECTED', updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?"
            )->execute([$permintaanId]);

            respond(200, [
                'message' => 'Permintaan berhasil ditolak',
                'header' => permintaan_header($permintaanId),
                'detail' => permintaan_detail_rows($permintaanId),
            ]);
        }

        respond(422, ['error' => 'type tidak valid']);
    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Gagal memproses permintaan',
            'detail' => $e->getMessage(),
        ]);
    }
}

respond(405, ['error' => 'Method not allowed']);