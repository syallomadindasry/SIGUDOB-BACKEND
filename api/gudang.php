<?php
// FILE: backend/api/gudang.php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/db.php';

try {
    $method = request_method();

    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $row = db_one(
                "
                SELECT
                    id_gudang,
                    kode_gudang,
                    nama_gudang,
                    jenis_gudang,
                    status_gudang,
                    alamat,
                    kota,
                    telepon,
                    nama_kepala
                FROM gudang
                WHERE id_gudang = ?
                LIMIT 1
                ",
                [$id]
            );

            if (!$row) {
                respond(404, ['error' => 'Gudang tidak ditemukan']);
            }

            respond(200, $row);
        }

        $rows = db_all(
            "
            SELECT
                id_gudang,
                kode_gudang,
                nama_gudang,
                jenis_gudang,
                status_gudang,
                alamat,
                kota,
                telepon,
                nama_kepala
            FROM gudang
            ORDER BY id_gudang ASC
            "
        );

        respond(200, $rows);
    }

    if ($method === 'POST') {
        $input = request_input();

        $namaGudang = trim((string) ($input['nama_gudang'] ?? ''));
        $jenisGudang = trim((string) ($input['jenis_gudang'] ?? 'Puskesmas'));
        $statusGudang = trim((string) ($input['status_gudang'] ?? 'Aktif'));
        $alamat = trim((string) ($input['alamat'] ?? ''));
        $kota = trim((string) ($input['kota'] ?? ''));
        $telepon = trim((string) ($input['telepon'] ?? ''));
        $namaKepala = trim((string) ($input['nama_kepala'] ?? ''));

        if ($namaGudang === '') {
            respond(400, ['error' => 'nama_gudang wajib']);
        }

        $duplicate = db_one(
            "
            SELECT id_gudang
            FROM gudang
            WHERE LOWER(nama_gudang) = LOWER(?)
            LIMIT 1
            ",
            [$namaGudang]
        );

        if ($duplicate) {
            respond(409, ['error' => 'Nama gudang sudah digunakan']);
        }

        $kodeRow = db_one(
            "
            SELECT COALESCE(
                MAX(CAST(SUBSTRING_INDEX(kode_gudang, '-', -1) AS UNSIGNED)),
                0
            ) + 1 AS next_no
            FROM gudang
            WHERE kode_gudang IS NOT NULL
              AND kode_gudang <> ''
            "
        );

        $nextNo = (int) ($kodeRow['next_no'] ?? 1);
        $kodeGudang = 'GD-' . $nextNo;

        db_exec(
            "
            INSERT INTO gudang (
                kode_gudang,
                nama_gudang,
                jenis_gudang,
                status_gudang,
                alamat,
                kota,
                telepon,
                nama_kepala
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ",
            [
                $kodeGudang,
                $namaGudang,
                $jenisGudang !== '' ? $jenisGudang : null,
                $statusGudang !== '' ? $statusGudang : null,
                $alamat !== '' ? $alamat : null,
                $kota !== '' ? $kota : null,
                $telepon !== '' ? $telepon : null,
                $namaKepala !== '' ? $namaKepala : null,
            ]
        );

        $idRow = db_one("SELECT LAST_INSERT_ID() AS id_gudang");

        respond(201, [
            'message' => 'Gudang berhasil ditambahkan',
            'id_gudang' => (int) ($idRow['id_gudang'] ?? 0),
            'kode_gudang' => $kodeGudang,
        ]);
    }

    if ($method === 'PUT') {
        $input = request_input();
        $idGudang = (int) ($input['id_gudang'] ?? 0);

        if ($idGudang <= 0) {
            respond(400, ['error' => 'id_gudang wajib']);
        }

        $exists = db_one(
            "
            SELECT id_gudang, kode_gudang
            FROM gudang
            WHERE id_gudang = ?
            LIMIT 1
            ",
            [$idGudang]
        );

        if (!$exists) {
            respond(404, ['error' => 'Gudang tidak ditemukan']);
        }

        $namaGudang = trim((string) ($input['nama_gudang'] ?? ''));
        $jenisGudang = trim((string) ($input['jenis_gudang'] ?? ''));
        $statusGudang = trim((string) ($input['status_gudang'] ?? ''));
        $alamat = trim((string) ($input['alamat'] ?? ''));
        $kota = trim((string) ($input['kota'] ?? ''));
        $telepon = trim((string) ($input['telepon'] ?? ''));
        $namaKepala = trim((string) ($input['nama_kepala'] ?? ''));

        if ($namaGudang === '') {
            respond(400, ['error' => 'nama_gudang wajib']);
        }

        $duplicate = db_one(
            "
            SELECT id_gudang
            FROM gudang
            WHERE LOWER(nama_gudang) = LOWER(?)
              AND id_gudang <> ?
            LIMIT 1
            ",
            [$namaGudang, $idGudang]
        );

        if ($duplicate) {
            respond(409, ['error' => 'Nama gudang sudah digunakan']);
        }

        db_exec(
            "
            UPDATE gudang
            SET
                nama_gudang = ?,
                jenis_gudang = ?,
                status_gudang = ?,
                alamat = ?,
                kota = ?,
                telepon = ?,
                nama_kepala = ?
            WHERE id_gudang = ?
            ",
            [
                $namaGudang,
                $jenisGudang !== '' ? $jenisGudang : null,
                $statusGudang !== '' ? $statusGudang : null,
                $alamat !== '' ? $alamat : null,
                $kota !== '' ? $kota : null,
                $telepon !== '' ? $telepon : null,
                $namaKepala !== '' ? $namaKepala : null,
                $idGudang,
            ]
        );

        respond(200, [
            'message' => 'Gudang berhasil diupdate',
            'id_gudang' => $idGudang,
            'kode_gudang' => (string) ($exists['kode_gudang'] ?? ''),
        ]);
    }

    respond(405, ['error' => 'Method not allowed']);
} catch (Throwable $e) {
    respond(500, [
        'error' => $e->getMessage(),
    ]);
}