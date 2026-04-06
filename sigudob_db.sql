-- ============================================================
-- SIGUDOB - SQL FINAL AMAN UNTUK XAMPP / phpMyAdmin / MariaDB
-- Database: sigudob_db
-- Tujuan:
-- - Urutan tabel aman untuk import lokal
-- - `user` dibungkus backtick
-- - FK dinyalakan setelah semua tabel parent siap
-- - Dummy data realistis untuk demo
--
-- Login default:
-- 1) Username: Admin Dinkes | Password: Admin@12345
-- 2) Username: Admin PKM 1  | Password: Pkm@1123
-- 3) Username: Admin PKM 2  | Password: Pkm@1223
-- 4) Username: Admin PKM 3  | Password: Pkm@123
-- ============================================================

CREATE DATABASE IF NOT EXISTS sigudob_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sigudob_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS stok_threshold;
DROP TABLE IF EXISTS opname_detail;
DROP TABLE IF EXISTS opname;
DROP TABLE IF EXISTS penghapusan_detail;
DROP TABLE IF EXISTS penghapusan;
DROP TABLE IF EXISTS retur_detail;
DROP TABLE IF EXISTS retur;
DROP TABLE IF EXISTS pemakaian_detail;
DROP TABLE IF EXISTS pemakaian;
DROP TABLE IF EXISTS mutasi_detail;
DROP TABLE IF EXISTS mutasi;
DROP TABLE IF EXISTS permintaan_detail;
DROP TABLE IF EXISTS permintaan;
DROP TABLE IF EXISTS pembelian_detail;
DROP TABLE IF EXISTS pembelian;
DROP TABLE IF EXISTS stok_batch;
DROP TABLE IF EXISTS data_batch;
DROP TABLE IF EXISTS data_obat;
DROP TABLE IF EXISTS satuan;
DROP TABLE IF EXISTS supplier;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS gudang;

CREATE TABLE gudang (
  id_gudang   INT AUTO_INCREMENT PRIMARY KEY,
  nama_gudang VARCHAR(150) NOT NULL,
  alamat      VARCHAR(255) DEFAULT NULL,
  kota        VARCHAR(100) DEFAULT NULL,
  telepon     VARCHAR(50) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_gudang_nama (nama_gudang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user` (
  id_admin   INT AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(100) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('dinkes','puskesmas') NOT NULL,
  id_gudang  INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_user_nama (nama),
  KEY idx_user_role (role),
  KEY idx_user_gudang (id_gudang),
  CONSTRAINT fk_user_gudang
    FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier (
  id_supplier   INT AUTO_INCREMENT PRIMARY KEY,
  kode_supplier VARCHAR(50) NOT NULL,
  nama_supplier VARCHAR(150) NOT NULL,
  kota          VARCHAR(100) DEFAULT NULL,
  pic           VARCHAR(100) DEFAULT NULL,
  telepon       VARCHAR(50) DEFAULT NULL,
  email         VARCHAR(150) DEFAULT NULL,
  npwp          VARCHAR(50) DEFAULT NULL,
  status        ENUM('AKTIF','TIDAK_AKTIF') NOT NULL DEFAULT 'AKTIF',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_supplier_kode (kode_supplier),
  UNIQUE KEY uk_supplier_npwp (npwp),
  KEY idx_supplier_nama (nama_supplier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE satuan (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  kode       VARCHAR(50) NOT NULL,
  nama       VARCHAR(100) NOT NULL,
  singkat    VARCHAR(50) DEFAULT NULL,
  jenis      VARCHAR(50) DEFAULT NULL,
  keterangan VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_satuan_kode (kode),
  UNIQUE KEY uk_satuan_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE data_obat (
  id_obat    INT AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(150) NOT NULL,
  satuan     VARCHAR(50) NOT NULL,
  jenis      VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_data_obat_nama (nama),
  KEY idx_data_obat_jenis (jenis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE data_batch (
  id_batch   INT AUTO_INCREMENT PRIMARY KEY,
  batch      VARCHAR(100) NOT NULL,
  id_obat    INT NOT NULL,
  exp_date   DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_batch_obat (id_obat, batch),
  KEY idx_batch_obat (id_obat),
  KEY idx_batch_exp (exp_date),
  CONSTRAINT fk_batch_obat
    FOREIGN KEY (id_obat) REFERENCES data_obat(id_obat)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stok_batch (
  id_gudang  INT NOT NULL,
  id_batch   INT NOT NULL,
  stok       INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_gudang, id_batch),
  KEY idx_stok_batch_id_batch (id_batch),
  CONSTRAINT fk_stok_batch_gudang
    FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_stok_batch_batch
    FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pembelian (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  no_nota      VARCHAR(50) NOT NULL,
  tanggal      DATE NOT NULL,
  supplier     VARCHAR(150) NOT NULL,
  alamat       VARCHAR(200) DEFAULT NULL,
  kota         VARCHAR(100) DEFAULT NULL,
  telepon      VARCHAR(50) DEFAULT NULL,
  metode_bayar VARCHAR(50) NOT NULL DEFAULT 'Transfer Bank',
  diskon       DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  catatan      TEXT DEFAULT NULL,
  id_admin     INT NOT NULL,
  id_gudang    INT NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pembelian_tanggal (tanggal),
  KEY idx_pembelian_admin (id_admin),
  KEY idx_pembelian_gudang (id_gudang),
  CONSTRAINT fk_pembelian_admin
    FOREIGN KEY (id_admin) REFERENCES `user`(id_admin)
    ON UPDATE CASCADE,
  CONSTRAINT fk_pembelian_gudang
    FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pembelian_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  id_pembelian INT NOT NULL,
  id_batch     INT NOT NULL,
  jumlah       INT NOT NULL,
  harga        DECIMAL(15,2) NOT NULL,
  KEY idx_pembelian_detail_hdr (id_pembelian),
  KEY idx_pembelian_detail_batch (id_batch),
  CONSTRAINT fk_pembelian_detail_hdr
    FOREIGN KEY (id_pembelian) REFERENCES pembelian(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_pembelian_detail_batch
    FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permintaan (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  from_gudang_id INT NOT NULL,
  to_gudang_id   INT NOT NULL,
  priority       ENUM('LOW','MEDIUM','HIGH','URGENT') NOT NULL DEFAULT 'MEDIUM',
  note           TEXT DEFAULT NULL,
  status         ENUM('DRAFT','SUBMITTED','APPROVED','PARTIAL','ON_DELIVERY','REJECTED','FULFILLED')
                 NOT NULL DEFAULT 'DRAFT',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_permintaan_from (from_gudang_id),
  KEY idx_permintaan_to (to_gudang_id),
  KEY idx_permintaan_status (status),
  CONSTRAINT fk_permintaan_from
    FOREIGN KEY (from_gudang_id) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE,
  CONSTRAINT fk_permintaan_to
    FOREIGN KEY (to_gudang_id) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permintaan_detail (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  permintaan_id INT NOT NULL,
  obat_id       INT NOT NULL,
  qty_requested INT NOT NULL,
  qty_approved  INT DEFAULT NULL,
  KEY idx_permintaan_detail_hdr (permintaan_id),
  KEY idx_permintaan_detail_obat (obat_id),
  CONSTRAINT fk_permintaan_detail_hdr
    FOREIGN KEY (permintaan_id) REFERENCES permintaan(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_permintaan_detail_obat
    FOREIGN KEY (obat_id) REFERENCES data_obat(id_obat)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mutasi (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  no_mutasi     VARCHAR(50) NOT NULL,
  tanggal       DATE NOT NULL,
  sumber        INT NOT NULL,
  tujuan        INT NOT NULL,
  penyerah      VARCHAR(100) DEFAULT NULL,
  penerima      VARCHAR(100) DEFAULT NULL,
  catatan       TEXT DEFAULT NULL,
  id_admin      INT NOT NULL,
  permintaan_id INT DEFAULT NULL,
  mode          ENUM('INSTANT','WORKFLOW') NOT NULL DEFAULT 'INSTANT',
  status        ENUM('DRAFT','SENT','RECEIVED','PARTIAL','CANCELLED') NOT NULL DEFAULT 'RECEIVED',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mutasi_sumber (sumber),
  KEY idx_mutasi_tujuan (tujuan),
  KEY idx_mutasi_admin (id_admin),
  KEY idx_mutasi_permintaan (permintaan_id),
  KEY idx_mutasi_status (status),
  CONSTRAINT fk_mutasi_sumber
    FOREIGN KEY (sumber) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE,
  CONSTRAINT fk_mutasi_tujuan
    FOREIGN KEY (tujuan) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE,
  CONSTRAINT fk_mutasi_admin
    FOREIGN KEY (id_admin) REFERENCES `user`(id_admin)
    ON UPDATE CASCADE,
  CONSTRAINT fk_mutasi_permintaan
    FOREIGN KEY (permintaan_id) REFERENCES permintaan(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mutasi_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  id_mutasi    INT NOT NULL,
  id_batch     INT NOT NULL,
  jumlah       INT NOT NULL,
  qty_received INT DEFAULT NULL,
  KEY idx_mutasi_detail_hdr (id_mutasi),
  KEY idx_mutasi_detail_batch (id_batch),
  CONSTRAINT fk_mutasi_detail_hdr
    FOREIGN KEY (id_mutasi) REFERENCES mutasi(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_mutasi_detail_batch
    FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pemakaian (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  no_pemakaian VARCHAR(50) NOT NULL,
  tanggal      DATE NOT NULL,
  keterangan   TEXT DEFAULT NULL,
  id_admin     INT NOT NULL,
  id_gudang    INT NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pemakaian_admin (id_admin),
  KEY idx_pemakaian_gudang (id_gudang),
  KEY idx_pemakaian_tanggal (tanggal),
  CONSTRAINT fk_pemakaian_admin
    FOREIGN KEY (id_admin) REFERENCES `user`(id_admin)
    ON UPDATE CASCADE,
  CONSTRAINT fk_pemakaian_gudang
    FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pemakaian_detail (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  id_pemakaian INT NOT NULL,
  id_batch     INT NOT NULL,
  jumlah       INT NOT NULL,
  KEY idx_pemakaian_detail_hdr (id_pemakaian),
  KEY idx_pemakaian_detail_batch (id_batch),
  CONSTRAINT fk_pemakaian_detail_hdr
    FOREIGN KEY (id_pemakaian) REFERENCES pemakaian(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_pemakaian_detail_batch
    FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE retur (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  no_retur   VARCHAR(50) NOT NULL,
  tanggal    DATE NOT NULL,
  alasan     TEXT DEFAULT NULL,
  id_admin   INT NOT NULL,
  id_gudang  INT NOT NULL,
  tujuan     INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_retur_admin (id_admin),
  KEY idx_retur_gudang (id_gudang),
  KEY idx_retur_tujuan (tujuan),
  KEY idx_retur_tanggal (tanggal),
  CONSTRAINT fk_retur_admin
    FOREIGN KEY (id_admin) REFERENCES `user`(id_admin)
    ON UPDATE CASCADE,
  CONSTRAINT fk_retur_gudang
    FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE,
  CONSTRAINT fk_retur_tujuan
    FOREIGN KEY (tujuan) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE retur_detail (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  id_retur INT NOT NULL,
  id_batch INT NOT NULL,
  jumlah   INT NOT NULL,
  KEY idx_retur_detail_hdr (id_retur),
  KEY idx_retur_detail_batch (id_batch),
  CONSTRAINT fk_retur_detail_hdr
    FOREIGN KEY (id_retur) REFERENCES retur(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_retur_detail_batch
    FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE penghapusan (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  no_hapus   VARCHAR(50) NOT NULL,
  tanggal    DATE NOT NULL,
  alasan     TEXT NOT NULL,
  id_admin   INT NOT NULL,
  id_gudang  INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_penghapusan_admin (id_admin),
  KEY idx_penghapusan_gudang (id_gudang),
  KEY idx_penghapusan_tanggal (tanggal),
  CONSTRAINT fk_penghapusan_admin
    FOREIGN KEY (id_admin) REFERENCES `user`(id_admin)
    ON UPDATE CASCADE,
  CONSTRAINT fk_penghapusan_gudang
    FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE penghapusan_detail (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  id_hapus INT NOT NULL,
  id_batch INT NOT NULL,
  jumlah   INT NOT NULL,
  KEY idx_penghapusan_detail_hdr (id_hapus),
  KEY idx_penghapusan_detail_batch (id_batch),
  CONSTRAINT fk_penghapusan_detail_hdr
    FOREIGN KEY (id_hapus) REFERENCES penghapusan(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_penghapusan_detail_batch
    FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE opname (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  no_opname  VARCHAR(50) NOT NULL,
  tanggal    DATE NOT NULL,
  id_gudang  INT NOT NULL,
  status     ENUM('DRAFT','SUBMITTED','APPROVED','REJECTED') NOT NULL DEFAULT 'DRAFT',
  catatan    TEXT DEFAULT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_opname_gudang (id_gudang),
  KEY idx_opname_status (status),
  KEY idx_opname_created_by (created_by),
  CONSTRAINT fk_opname_gudang
    FOREIGN KEY (id_gudang) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE,
  CONSTRAINT fk_opname_created_by
    FOREIGN KEY (created_by) REFERENCES `user`(id_admin)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE opname_detail (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  opname_id   INT NOT NULL,
  id_batch    INT NOT NULL,
  stok_sistem INT NOT NULL DEFAULT 0,
  stok_fisik  INT NOT NULL DEFAULT 0,
  selisih     INT NOT NULL DEFAULT 0,
  note        VARCHAR(255) DEFAULT NULL,
  KEY idx_opname_detail_hdr (opname_id),
  KEY idx_opname_detail_batch (id_batch),
  CONSTRAINT fk_opname_detail_hdr
    FOREIGN KEY (opname_id) REFERENCES opname(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_opname_detail_batch
    FOREIGN KEY (id_batch) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stok_threshold (
  gudang_id  INT NOT NULL,
  obat_id    INT NOT NULL,
  min_qty    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (gudang_id, obat_id),
  KEY idx_stok_threshold_obat (obat_id),
  CONSTRAINT fk_stok_threshold_gudang
    FOREIGN KEY (gudang_id) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_stok_threshold_obat
    FOREIGN KEY (obat_id) REFERENCES data_obat(id_obat)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id         BIGINT AUTO_INCREMENT PRIMARY KEY,
  actor_id   INT DEFAULT NULL,
  action     VARCHAR(50) NOT NULL,
  entity     VARCHAR(50) NOT NULL,
  entity_id  INT DEFAULT NULL,
  payload    LONGTEXT DEFAULT NULL,
  ip         VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_actor (actor_id),
  KEY idx_audit_entity (entity, entity_id),
  CONSTRAINT fk_audit_actor
    FOREIGN KEY (actor_id) REFERENCES `user`(id_admin)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  gudang_id   INT NOT NULL,
  obat_id     INT NOT NULL,
  batch_id    INT NOT NULL,
  source_type VARCHAR(50) NOT NULL,
  source_id   INT DEFAULT NULL,
  direction   ENUM('IN','OUT','ADJUSTMENT') NOT NULL,
  qty         INT NOT NULL,
  note        VARCHAR(255) DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_stock_movements_lookup (gudang_id, obat_id, batch_id, created_at),
  KEY idx_stock_movements_obat (obat_id),
  KEY idx_stock_movements_batch (batch_id),
  CONSTRAINT fk_stock_movements_gudang
    FOREIGN KEY (gudang_id) REFERENCES gudang(id_gudang)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_stock_movements_obat
    FOREIGN KEY (obat_id) REFERENCES data_obat(id_obat)
    ON UPDATE CASCADE,
  CONSTRAINT fk_stock_movements_batch
    FOREIGN KEY (batch_id) REFERENCES data_batch(id_batch)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gudang (id_gudang, nama_gudang, alamat, kota, telepon) VALUES
  (1, 'Dinas Kesehatan Kabupaten Bantul', 'Jl. KH Wahid Hasyim No. 56', 'Bantul', '0274-367509'),
  (2, 'Puskesmas Bantul I', 'Jl. Jenderal Sudirman No. 12', 'Bantul', '0274-368120'),
  (3, 'Puskesmas Sewon I', 'Jl. Parangtritis Km 8.5', 'Sewon', '0274-389214'),
  (4, 'Puskesmas Kasihan I', 'Jl. Bibis Raya No. 45', 'Kasihan', '0274-377441');

INSERT INTO `user` (id_admin, nama, password, role, id_gudang) VALUES
  (1, 'Admin Dinkes', '$2y$12$7aRIG0PriljNQwRrMynPeeKmEYHue6ihWlja/iSV9wX24cbA4FqBG', 'dinkes', 1),
  (2, 'Admin PKM 1', '$2y$12$i8fQoAoSbsNkSaje1xPt0.dR9dttA.xpvqSMxcGVjgz8MEcN6bchy', 'puskesmas', 2),
  (3, 'Admin PKM 2', '$2y$12$QDryG6rYHJM1gKfJPY7CB.oTj5LXHfgvUR94LKFAgC6koBqzy92Ya', 'puskesmas', 3),
  (4, 'Admin PKM 3', '$2y$12$hX6WYlWDwax6M6g3yAyUruBqNhXXfx.lKmy6eNjb0mplGyh8dm9Bm', 'puskesmas', 4);

INSERT INTO satuan (id, kode, nama, singkat, jenis, keterangan) VALUES
  (1, 'TAB', 'Tablet', 'Tab', 'PADAT', 'Satuan tablet oral'),
  (2, 'KPS', 'Kapsul', 'Kps', 'PADAT', 'Satuan kapsul oral'),
  (3, 'SCH', 'Sachet', 'Sch', 'PADAT', 'Satuan sachet'),
  (4, 'BTL', 'Botol', 'Btl', 'CAIR', 'Satuan botol cair'),
  (5, 'AMP', 'Ampul', 'Amp', 'INJEKSI', 'Satuan ampul injeksi');

INSERT INTO supplier (id_supplier, kode_supplier, nama_supplier, kota, pic, telepon, email, npwp, status) VALUES
  (1, 'SUP-001', 'PT Kimia Farma Trading & Distribution', 'Yogyakarta', 'Rina Puspitasari', '081226781001', 'rina.puspitasari@kftd.test', '3174012300010001', 'AKTIF'),
  (2, 'SUP-002', 'PT Anugerah Pharmindo Lestari', 'Sleman', 'Arif Nugroho', '081226781002', 'arif.nugroho@apl.test', '3174012300010002', 'AKTIF'),
  (3, 'SUP-003', 'CV Medika Nusantara Sejahtera', 'Bantul', 'Dewi Lestari', '081226781003', 'dewi.lestari@mednusa.test', '3174012300010003', 'AKTIF');

INSERT INTO data_obat (id_obat, nama, satuan, jenis) VALUES
  (1, 'Paracetamol 500 mg', 'Tablet', 'Analgesik'),
  (2, 'Amoxicillin 500 mg', 'Kapsul', 'Antibiotik'),
  (3, 'Antasida DOEN', 'Tablet', 'Antasida'),
  (4, 'Vitamin C 50 mg', 'Tablet', 'Vitamin'),
  (5, 'Oralit', 'Sachet', 'Elektrolit'),
  (6, 'Metformin 500 mg', 'Tablet', 'Antidiabetik'),
  (7, 'Captopril 25 mg', 'Tablet', 'Antihipertensi'),
  (8, 'Salbutamol 2 mg', 'Tablet', 'Bronkodilator'),
  (9, 'CTM 4 mg', 'Tablet', 'Antihistamin'),
  (10, 'Ambroxol Sirup 15 mg/5 ml', 'Botol', 'Mukolitik'),
  (11, 'Gentamicin Injeksi 80 mg/2 ml', 'Ampul', 'Antibiotik'),
  (12, 'Omeprazole 20 mg', 'Kapsul', 'Gastrointestinal');

INSERT INTO data_batch (id_batch, batch, id_obat, exp_date) VALUES
  (1, 'PCM2501A', 1, '2027-01-31'),
  (2, 'AMX2502B', 2, '2026-11-30'),
  (3, 'ANT2503C', 3, '2027-03-31'),
  (4, 'VIT2504D', 4, '2027-07-31'),
  (5, 'ORL2505E', 5, '2026-09-30'),
  (6, 'MET2506F', 6, '2027-06-30'),
  (7, 'CAP2507G', 7, '2027-08-31'),
  (8, 'SAL2508H', 8, '2026-12-31'),
  (9, 'CTM2509J', 9, '2027-04-30'),
  (10, 'AMB2510K', 10, '2026-10-31'),
  (11, 'GEN2511L', 11, '2027-02-28'),
  (12, 'OME2512M', 12, '2027-05-31');

INSERT INTO stok_batch (id_gudang, id_batch, stok) VALUES
  (1, 1, 6500), (1, 2, 3200), (1, 3, 1800), (1, 4, 4100),
  (1, 5, 1400), (1, 6, 1200), (1, 7, 2100), (1, 8, 950),
  (1, 9, 2500), (1, 10, 420), (1, 11, 160), (1, 12, 780),
  (2, 1, 650), (2, 3, 220), (2, 5, 110), (2, 9, 300), (2, 10, 60),
  (3, 2, 340), (3, 4, 190), (3, 6, 150), (3, 8, 75), (3, 12, 55),
  (4, 1, 280), (4, 7, 130), (4, 10, 40), (4, 11, 24);

INSERT INTO stok_threshold (gudang_id, obat_id, min_qty) VALUES
  (1, 1, 1200.00), (1, 2, 1000.00), (1, 5, 600.00), (1, 10, 150.00),
  (2, 1, 150.00), (2, 3, 80.00), (2, 9, 120.00), (2, 10, 30.00),
  (3, 2, 100.00), (3, 4, 80.00), (3, 6, 70.00), (3, 12, 25.00),
  (4, 1, 100.00), (4, 7, 60.00), (4, 10, 20.00), (4, 11, 10.00);

INSERT INTO pembelian
  (id, no_nota, tanggal, supplier, alamat, kota, telepon, metode_bayar, diskon, catatan, id_admin, id_gudang)
VALUES
  (1, 'PO-2026-0001', '2026-03-01', 'PT Kimia Farma Trading & Distribution', 'Jl. Magelang Km 7', 'Yogyakarta', '081226781001', 'Transfer Bank', 2.50, 'Pengadaan triwulan I', 1, 1),
  (2, 'PO-2026-0002', '2026-03-12', 'PT Anugerah Pharmindo Lestari', 'Jl. Ring Road Utara No. 88', 'Sleman', '081226781002', 'Transfer Bank', 1.00, 'Stok antibiotik dan vitamin', 1, 1);

INSERT INTO pembelian_detail (id, id_pembelian, id_batch, jumlah, harga) VALUES
  (1, 1, 1, 4000, 180.00),
  (2, 1, 3, 1200, 210.00),
  (3, 1, 5, 900, 950.00),
  (4, 1, 10, 300, 8500.00),
  (5, 2, 2, 2500, 520.00),
  (6, 2, 4, 3000, 145.00),
  (7, 2, 11, 100, 6700.00),
  (8, 2, 12, 600, 890.00);

INSERT INTO permintaan
  (id, from_gudang_id, to_gudang_id, priority, note, status, created_at, updated_at)
VALUES
  (1, 2, 1, 'HIGH', 'Permintaan obat ISPA dan demam untuk peningkatan kasus musiman', 'APPROVED', '2026-03-15 08:30:00', '2026-03-16 09:10:00'),
  (2, 3, 1, 'MEDIUM', 'Permintaan obat hipertensi dan diabetes untuk layanan prolanis', 'FULFILLED', '2026-03-18 10:15:00', '2026-03-20 13:45:00'),
  (3, 4, 1, 'URGENT', 'Permintaan tambahan oralit dan paracetamol untuk kejadian diare', 'ON_DELIVERY', '2026-03-21 07:45:00', '2026-03-21 11:20:00');

INSERT INTO permintaan_detail (id, permintaan_id, obat_id, qty_requested, qty_approved) VALUES
  (1, 1, 1, 300, 300),
  (2, 1, 9, 150, 150),
  (3, 1, 10, 40, 40),
  (4, 2, 6, 120, 120),
  (5, 2, 7, 80, 80),
  (6, 2, 12, 40, 40),
  (7, 3, 1, 200, 200),
  (8, 3, 5, 120, 120);

INSERT INTO mutasi
  (id, no_mutasi, tanggal, sumber, tujuan, penyerah, penerima, catatan, id_admin, permintaan_id, mode, status, created_at)
VALUES
  (1, 'MTS-2026-0001', '2026-03-16', 1, 2, 'Rina Gudang Dinkes', 'Siti Farmasi Bantul I', 'Distribusi sesuai approval permintaan #1', 1, 1, 'WORKFLOW', 'RECEIVED', '2026-03-16 09:30:00'),
  (2, 'MTS-2026-0002', '2026-03-20', 1, 3, 'Rina Gudang Dinkes', 'Ahmad Farmasi Sewon I', 'Distribusi program prolanis', 1, 2, 'WORKFLOW', 'RECEIVED', '2026-03-20 09:50:00'),
  (3, 'MTS-2026-0003', '2026-03-21', 1, 4, 'Rina Gudang Dinkes', 'Nina Farmasi Kasihan I', 'Pengiriman cepat kasus diare', 1, 3, 'WORKFLOW', 'SENT', '2026-03-21 11:35:00');

INSERT INTO mutasi_detail (id, id_mutasi, id_batch, jumlah, qty_received) VALUES
  (1, 1, 1, 300, 300),
  (2, 1, 9, 150, 150),
  (3, 1, 10, 40, 40),
  (4, 2, 6, 120, 120),
  (5, 2, 7, 80, 80),
  (6, 2, 12, 40, 40),
  (7, 3, 1, 200, NULL),
  (8, 3, 5, 120, NULL);

INSERT INTO pemakaian
  (id, no_pemakaian, tanggal, keterangan, id_admin, id_gudang)
VALUES
  (1, 'PMK-2026-0001', '2026-03-22', 'Pemakaian poli umum dan rawat jalan', 2, 2),
  (2, 'PMK-2026-0002', '2026-03-22', 'Pemakaian layanan penyakit kronis', 3, 3),
  (3, 'PMK-2026-0003', '2026-03-23', 'Pemakaian IGD dan layanan infeksi akut', 4, 4);

INSERT INTO pemakaian_detail (id, id_pemakaian, id_batch, jumlah) VALUES
  (1, 1, 1, 45),
  (2, 1, 9, 20),
  (3, 1, 10, 6),
  (4, 2, 6, 18),
  (5, 2, 7, 12),
  (6, 2, 12, 10),
  (7, 3, 1, 25),
  (8, 3, 5, 16),
  (9, 3, 11, 4);

INSERT INTO retur
  (id, no_retur, tanggal, alasan, id_admin, id_gudang, tujuan)
VALUES
  (1, 'RTR-2026-0001', '2026-03-24', 'Kemasan rusak saat penerimaan', 2, 2, 1);

INSERT INTO retur_detail (id, id_retur, id_batch, jumlah) VALUES
  (1, 1, 10, 2);

INSERT INTO penghapusan
  (id, no_hapus, tanggal, alasan, id_admin, id_gudang)
VALUES
  (1, 'HPS-2026-0001', '2026-03-25', 'Obat kedaluwarsa hasil stock review bulanan', 1, 1);

INSERT INTO penghapusan_detail (id, id_hapus, id_batch, jumlah) VALUES
  (1, 1, 5, 15);

INSERT INTO opname
  (id, no_opname, tanggal, id_gudang, status, catatan, created_by, created_at, updated_at)
VALUES
  (1, 'OPN-2026-0001', '2026-03-28', 2, 'APPROVED', 'Stock opname akhir bulan gudang farmasi Puskesmas Bantul I', 2, '2026-03-28 08:00:00', '2026-03-28 11:30:00'),
  (2, 'OPN-2026-0002', '2026-03-29', 3, 'SUBMITTED', 'Stock opname akhir bulan gudang farmasi Puskesmas Sewon I', 3, '2026-03-29 08:15:00', '2026-03-29 10:45:00');

INSERT INTO opname_detail (id, opname_id, id_batch, stok_sistem, stok_fisik, selisih, note) VALUES
  (1, 1, 1, 650, 648, -2, 'Selisih kecil akibat dispensing harian'),
  (2, 1, 9, 300, 300, 0, 'Sesuai sistem'),
  (3, 1, 10, 58, 57, -1, 'Satu botol rusak'),
  (4, 2, 2, 340, 340, 0, 'Sesuai sistem'),
  (5, 2, 6, 150, 148, -2, 'Belum tercatat pemakaian akhir shift'),
  (6, 2, 12, 55, 55, 0, 'Sesuai sistem');

INSERT INTO audit_logs (id, actor_id, action, entity, entity_id, payload, ip, created_at) VALUES
  (1, 1, 'CREATE', 'pembelian', 1, '{"no_nota":"PO-2026-0001","supplier":"PT Kimia Farma Trading & Distribution"}', '127.0.0.1', '2026-03-01 09:15:00'),
  (2, 1, 'CREATE', 'mutasi', 1, '{"no_mutasi":"MTS-2026-0001","tujuan":2}', '127.0.0.1', '2026-03-16 09:32:00'),
  (3, 2, 'SUBMIT', 'opname', 1, '{"no_opname":"OPN-2026-0001","status":"APPROVED"}', '127.0.0.1', '2026-03-28 11:30:00');

INSERT INTO stock_movements (id, gudang_id, obat_id, batch_id, source_type, source_id, direction, qty, note, created_at) VALUES
  (1, 1, 1, 1, 'pembelian', 1, 'IN', 4000, 'Pengadaan triwulan I', '2026-03-01 09:20:00'),
  (2, 1, 2, 2, 'pembelian', 2, 'IN', 2500, 'Pengadaan antibiotik', '2026-03-12 10:05:00'),
  (3, 1, 1, 1, 'mutasi', 1, 'OUT', 300, 'Distribusi ke Puskesmas Bantul I', '2026-03-16 09:35:00'),
  (4, 2, 1, 1, 'mutasi', 1, 'IN', 300, 'Penerimaan distribusi dari Dinkes', '2026-03-16 14:10:00'),
  (5, 1, 6, 6, 'mutasi', 2, 'OUT', 120, 'Distribusi ke Puskesmas Sewon I', '2026-03-20 10:00:00'),
  (6, 3, 6, 6, 'mutasi', 2, 'IN', 120, 'Penerimaan distribusi dari Dinkes', '2026-03-20 14:30:00'),
  (7, 2, 1, 1, 'pemakaian', 1, 'OUT', 45, 'Pemakaian poli umum', '2026-03-22 15:30:00'),
  (8, 4, 5, 5, 'mutasi', 3, 'IN', 120, 'Menunggu konfirmasi penerimaan penuh', '2026-03-21 16:00:00');

SET FOREIGN_KEY_CHECKS = 1;
