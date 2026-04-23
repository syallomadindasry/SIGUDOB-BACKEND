-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 02:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sigudob_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `actor_id`, `action`, `entity`, `entity_id`, `payload`, `ip`, `created_at`) VALUES
(1, 1, 'CREATE', 'pembelian', 1, '{\"no_nota\":\"PO-2026-0001\",\"supplier\":\"PT Kimia Farma Trading & Distribution\"}', '127.0.0.1', '2026-03-01 02:15:00'),
(2, 1, 'CREATE', 'mutasi', 1, '{\"no_mutasi\":\"MTS-2026-0001\",\"tujuan\":2}', '127.0.0.1', '2026-03-16 02:32:00'),
(3, 2, 'SUBMIT', 'opname', 1, '{\"no_opname\":\"OPN-2026-0001\",\"status\":\"APPROVED\"}', '127.0.0.1', '2026-03-28 04:30:00'),
(4, 2, 'CREATE', 'permintaan', 4, '{\"to_gudang_id\":1}', '::1', '2026-04-06 09:09:44'),
(5, 2, 'ADD_ITEM', 'permintaan', 4, '{\"obat_id\":6,\"qty\":12}', '::1', '2026-04-06 09:09:45'),
(6, 2, 'ADD_ITEM', 'permintaan', 4, '{\"obat_id\":10,\"qty\":10}', '::1', '2026-04-06 09:09:45'),
(7, 2, 'SUBMIT', 'permintaan', 4, NULL, '::1', '2026-04-06 09:11:31'),
(8, 1, 'APPROVE', 'permintaan', 4, '{\"status\":\"APPROVED\",\"mutasi_id\":4,\"autofill\":true}', '::1', '2026-04-06 09:11:48'),
(9, 1, 'SEND', 'mutasi', 4, '{\"permintaan_id\":4}', '::1', '2026-04-06 10:25:09'),
(10, 2, 'RECEIVE', 'mutasi', 4, '{\"permintaan_id\":4}', '::1', '2026-04-06 10:25:29'),
(11, 1, 'CREATE', 'mutasi', 5, '{\"mode\":\"WORKFLOW\",\"status\":\"DRAFT\",\"tujuan\":2}', '::1', '2026-04-08 11:16:33'),
(12, 1, 'CREATE', 'gudang', 5, '{\"nama_gudang\":\"Gudang Puskesmas 4\"}', '::1', '2026-04-08 11:48:08'),
(13, 1, 'CREATE', 'pembelian', 3, '{\"id_gudang\":1}', '::1', '2026-04-13 03:23:17'),
(14, 1, 'CREATE', 'pembelian', 4, '{\"id_gudang\":1}', '::1', '2026-04-13 03:23:35'),
(15, 2, 'CREATE', 'permintaan', 21, '{\"to_gudang_id\":1}', '::1', '2026-04-16 02:04:55'),
(16, 2, 'ADD_ITEM', 'permintaan', 21, '{\"obat_id\":10,\"qty\":10}', '::1', '2026-04-16 02:04:55'),
(17, 2, 'ADD_ITEM', 'permintaan', 21, '{\"obat_id\":3,\"qty\":1}', '::1', '2026-04-16 02:04:55'),
(18, 2, 'SUBMIT', 'permintaan', 21, NULL, '::1', '2026-04-16 02:28:44'),
(19, 1, 'APPROVE', 'permintaan', 21, '{\"status\":\"APPROVED\",\"mutasi_id\":6,\"autofill\":true}', '::1', '2026-04-16 02:28:52'),
(20, 1, 'SEND', 'mutasi', 6, '{\"permintaan_id\":21}', '::1', '2026-04-16 02:29:47'),
(21, 2, 'RECEIVE', 'mutasi', 6, '{\"permintaan_id\":21}', '::1', '2026-04-16 02:30:08'),
(22, 1, 'UPDATE', 'gudang', 1, '{\"kode_gudang\":\"GD-1\",\"nama_gudang\":\"Gudang Dinkes\",\"synced_user\":\"Gudang Dinkes\"}', '::1', '2026-04-16 06:09:53'),
(23, 1, 'UPDATE', 'gudang', 1, '{\"kode_gudang\":\"GD-1\",\"nama_gudang\":\"Gudang Dinkes\",\"synced_user\":\"Gudang Dinkes\"}', '::1', '2026-04-16 06:10:13'),
(24, 1, 'UPDATE', 'gudang', 1, '{\"kode_gudang\":\"GD-1\",\"nama_gudang\":\"Gudang Dinkes\",\"synced_user\":\"Gudang Dinkes\"}', '::1', '2026-04-16 06:13:29'),
(25, 1, 'CREATE', 'gudang', 7, '{\"kode_gudang\":\"GD-7\",\"nama_gudang\":\"Gudang Puskemas 7\",\"auto_user\":\"Gudang Puskemas 7\"}', '::1', '2026-04-16 06:30:38'),
(26, 1, 'UPDATE', 'gudang', 1, '{\"kode_gudang\":\"GD-1\",\"nama_gudang\":\"Gudang Dinkes\",\"synced_user\":\"Gudang Dinkes\"}', '::1', '2026-04-16 06:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `data_batch`
--

CREATE TABLE `data_batch` (
  `id_batch` int(11) NOT NULL,
  `batch` varchar(100) NOT NULL,
  `id_obat` int(11) NOT NULL,
  `exp_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `data_batch`
--

INSERT INTO `data_batch` (`id_batch`, `batch`, `id_obat`, `exp_date`, `created_at`) VALUES
(1, 'PCM2501A', 1, '2027-01-31', '2026-04-04 09:50:09'),
(2, 'AMX2502B', 2, '2026-11-30', '2026-04-04 09:50:09'),
(3, 'ANT2503C', 3, '2027-03-31', '2026-04-04 09:50:09'),
(4, 'VIT2504D', 4, '2027-07-31', '2026-04-04 09:50:09'),
(5, 'ORL2505E', 5, '2026-09-30', '2026-04-04 09:50:09'),
(6, 'MET2506F', 6, '2027-06-30', '2026-04-04 09:50:09'),
(7, 'CAP2507G', 7, '2027-08-31', '2026-04-04 09:50:09'),
(8, 'SAL2508H', 8, '2026-12-31', '2026-04-04 09:50:09'),
(9, 'CTM2509J', 9, '2027-04-30', '2026-04-04 09:50:09'),
(10, 'AMB2510K', 10, '2026-10-31', '2026-04-04 09:50:09'),
(11, 'GEN2511L', 11, '2027-02-28', '2026-04-04 09:50:09'),
(12, 'OME2512M', 12, '2027-05-31', '2026-04-04 09:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `data_obat`
--

CREATE TABLE `data_obat` (
  `id_obat` int(11) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `satuan` varchar(50) NOT NULL,
  `jenis` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `data_obat`
--

INSERT INTO `data_obat` (`id_obat`, `nama`, `satuan`, `jenis`, `created_at`) VALUES
(1, 'Paracetamol 500 mg', 'Tablet', 'Analgesik', '2026-04-04 09:50:09'),
(2, 'Amoxicillin 500 mg', 'Kapsul', 'Antibiotik', '2026-04-04 09:50:09'),
(3, 'Antasida DOEN', 'Tablet', 'Antasida', '2026-04-04 09:50:09'),
(4, 'Vitamin C 50 mg', 'Tablet', 'Vitamin', '2026-04-04 09:50:09'),
(5, 'Oralit', 'Sachet', 'Elektrolit', '2026-04-04 09:50:09'),
(6, 'Metformin 500 mg', 'Tablet', 'Antidiabetik', '2026-04-04 09:50:09'),
(7, 'Captopril 25 mg', 'Tablet', 'Antihipertensi', '2026-04-04 09:50:09'),
(8, 'Salbutamol 2 mg', 'Tablet', 'Bronkodilator', '2026-04-04 09:50:09'),
(9, 'CTM 4 mg', 'Tablet', 'Antihistamin', '2026-04-04 09:50:09'),
(10, 'Ambroxol Sirup 15 mg/5 ml', 'Botol', 'Mukolitik', '2026-04-04 09:50:09'),
(11, 'Gentamicin Injeksi 80 mg/2 ml', 'Ampul', 'Antibiotik', '2026-04-04 09:50:09'),
(12, 'Omeprazole 20 mg', 'Kapsul', 'Gastrointestinal', '2026-04-04 09:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `gudang`
--

CREATE TABLE `gudang` (
  `id_gudang` int(11) NOT NULL,
  `kode_gudang` varchar(20) NOT NULL,
  `nama_gudang` varchar(150) NOT NULL,
  `jenis_gudang` enum('UTAMA','PUSKESMAS','CABANG') DEFAULT 'PUSKESMAS',
  `status_gudang` enum('AKTIF','NONAKTIF') DEFAULT 'AKTIF',
  `alamat` varchar(255) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `email_gudang` varchar(100) DEFAULT NULL,
  `nama_kepala` varchar(100) DEFAULT NULL,
  `nip` varchar(50) DEFAULT NULL,
  `hp_kepala` varchar(20) DEFAULT NULL,
  `email_kepala` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `dibuat_pada` datetime DEFAULT NULL,
  `dibuat_oleh` int(11) DEFAULT NULL,
  `diupdate_oleh` varchar(50) DEFAULT NULL,
  `diupdate_pada` datetime DEFAULT NULL,
  `id_user_penanggungjawab` int(11) DEFAULT NULL,
  `tipe_penyimpanan` enum('UMUM','DINGIN','VAKSIN') DEFAULT NULL,
  `suhu_min` decimal(5,2) DEFAULT NULL,
  `suhu_max` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gudang`
--

INSERT INTO `gudang` (`id_gudang`, `kode_gudang`, `nama_gudang`, `jenis_gudang`, `status_gudang`, `alamat`, `kota`, `kecamatan`, `provinsi`, `kode_pos`, `telepon`, `email_gudang`, `nama_kepala`, `nip`, `hp_kepala`, `email_kepala`, `catatan`, `dibuat_pada`, `dibuat_oleh`, `diupdate_oleh`, `diupdate_pada`, `id_user_penanggungjawab`, `tipe_penyimpanan`, `suhu_min`, `suhu_max`) VALUES
(1, 'GD-1', 'Gudang Dinkes', '', 'AKTIF', 'Jl. KH Wahid Hasyim No. 56', 'Bantul', NULL, NULL, NULL, '0274-367509', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 16:50:09', NULL, NULL, '2026-04-09 09:05:13', NULL, NULL, NULL, NULL),
(2, 'GD-2', 'Gudang Puskesmas 1', 'PUSKESMAS', 'AKTIF', 'Jl. Jenderal Sudirman No. 12', 'Bantul', NULL, NULL, NULL, '0274-368120', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 16:50:09', NULL, NULL, '2026-04-09 09:05:13', NULL, NULL, NULL, NULL),
(3, 'GD-3', 'Gudang Puskesmas 2', 'PUSKESMAS', 'AKTIF', 'Jl. Parangtritis Km 8.5', 'Sewon', NULL, NULL, NULL, '0274-389214', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 16:50:09', NULL, NULL, '2026-04-09 09:05:13', NULL, NULL, NULL, NULL),
(4, 'GD-4', 'Gudang Puskesmas 3', 'PUSKESMAS', 'AKTIF', 'Jl. Bibis Raya No. 45', 'Kasihan', NULL, NULL, NULL, '0274-377441', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-04 16:50:09', NULL, NULL, '2026-04-09 09:05:13', NULL, NULL, NULL, NULL),
(5, 'GD-5', 'Gudang Puskesmas 4', 'PUSKESMAS', 'AKTIF', 'Jl Nanas no 11', 'Salatiga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-08 18:48:08', NULL, NULL, '2026-04-09 09:05:13', NULL, NULL, NULL, NULL),
(6, 'GD-6', 'Gudang Puskesmas 5', 'PUSKESMAS', 'AKTIF', 'Jl Mawar no 21', 'Salatiga', NULL, NULL, NULL, '09876543216', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-09 09:30:32', NULL, NULL, '2026-04-09 09:30:32', NULL, NULL, NULL, NULL),
(7, 'GD-7', 'Gudang Puskemas 7', 'PUSKESMAS', 'AKTIF', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mutasi`
--

CREATE TABLE `mutasi` (
  `id` int(11) NOT NULL,
  `no_mutasi` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `sumber` int(11) NOT NULL,
  `tujuan` int(11) NOT NULL,
  `penyerah` varchar(100) DEFAULT NULL,
  `penerima` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `id_admin` int(11) NOT NULL,
  `permintaan_id` int(11) DEFAULT NULL,
  `mode` enum('INSTANT','WORKFLOW') NOT NULL DEFAULT 'INSTANT',
  `status` enum('DRAFT','SENT','RECEIVED','PARTIAL','CANCELLED') NOT NULL DEFAULT 'RECEIVED',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mutasi`
--

INSERT INTO `mutasi` (`id`, `no_mutasi`, `tanggal`, `sumber`, `tujuan`, `penyerah`, `penerima`, `catatan`, `id_admin`, `permintaan_id`, `mode`, `status`, `created_at`, `sent_at`, `received_at`) VALUES
(1, 'MTS-2026-0001', '2026-03-16', 1, 2, 'Rina Gudang Dinkes', 'Siti Farmasi Bantul I', 'Distribusi sesuai approval permintaan #1', 1, 1, 'WORKFLOW', 'RECEIVED', '2026-03-16 02:30:00', NULL, NULL),
(2, 'MTS-2026-0002', '2026-03-20', 1, 3, 'Rina Gudang Dinkes', 'Ahmad Farmasi Sewon I', 'Distribusi program prolanis', 1, 2, 'WORKFLOW', 'RECEIVED', '2026-03-20 02:50:00', NULL, NULL),
(3, 'MTS-2026-0003', '2026-03-21', 1, 4, 'Rina Gudang Dinkes', 'Nina Farmasi Kasihan I', 'Pengiriman cepat kasus diare', 1, 3, 'WORKFLOW', 'SENT', '2026-03-21 04:35:00', NULL, NULL),
(4, 'MUT-20260406111148-6190', '2026-04-06', 1, 2, 'Gudang Dinkes', NULL, 'Distribusi (auto dari permintaan #4)', 1, 4, 'WORKFLOW', 'RECEIVED', '2026-04-06 09:11:48', '2026-04-06 10:25:09', '2026-04-06 10:25:29'),
(5, 'DS-1775646883956', '2026-04-08', 1, 2, 'Gudang Dinkes', 'Suci', 'obat 1 minggu', 1, NULL, 'WORKFLOW', 'DRAFT', '2026-04-08 11:16:33', NULL, NULL),
(6, 'MUT-20260416042852-6809', '2026-04-16', 1, 2, 'Gudang Dinkes', NULL, 'Distribusi (auto dari permintaan #21)', 1, 21, 'WORKFLOW', 'RECEIVED', '2026-04-16 02:28:52', '2026-04-16 02:29:47', '2026-04-16 02:30:08');

-- --------------------------------------------------------

--
-- Table structure for table `mutasi_detail`
--

CREATE TABLE `mutasi_detail` (
  `id` int(11) NOT NULL,
  `id_mutasi` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `qty_received` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mutasi_detail`
--

INSERT INTO `mutasi_detail` (`id`, `id_mutasi`, `id_batch`, `jumlah`, `qty_received`) VALUES
(1, 1, 1, 300, 300),
(2, 1, 9, 150, 150),
(3, 1, 10, 40, 40),
(4, 2, 6, 120, 120),
(5, 2, 7, 80, 80),
(6, 2, 12, 40, 40),
(7, 3, 1, 200, NULL),
(8, 3, 5, 120, NULL),
(9, 4, 6, 12, 12),
(10, 4, 10, 10, 10),
(11, 6, 10, 10, 10),
(12, 6, 3, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_dibaca`
--

CREATE TABLE `notifikasi_dibaca` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_user` int(11) NOT NULL,
  `notif_key` varchar(120) NOT NULL COMMENT 'Key unik notifikasi, contoh: kritis:42 atau nearexp:B-2024-05',
  `dibaca_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Status baca notifikasi per user';

-- --------------------------------------------------------

--
-- Table structure for table `opname`
--

CREATE TABLE `opname` (
  `id` int(11) NOT NULL,
  `no_opname` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `id_gudang` int(11) NOT NULL,
  `status` enum('DRAFT','SUBMITTED','APPROVED','REJECTED') NOT NULL DEFAULT 'DRAFT',
  `catatan` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `opname`
--

INSERT INTO `opname` (`id`, `no_opname`, `tanggal`, `id_gudang`, `status`, `catatan`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'OPN-2026-0001', '2026-03-28', 2, 'APPROVED', 'Stock opname akhir bulan gudang farmasi Puskesmas Bantul I', 2, '2026-03-28 01:00:00', '2026-03-28 04:30:00'),
(2, 'OPN-2026-0002', '2026-03-29', 3, 'SUBMITTED', 'Stock opname akhir bulan gudang farmasi Puskesmas Sewon I', 3, '2026-03-29 01:15:00', '2026-03-29 03:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `opname_detail`
--

CREATE TABLE `opname_detail` (
  `id` int(11) NOT NULL,
  `opname_id` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `stok_sistem` int(11) NOT NULL DEFAULT 0,
  `stok_fisik` int(11) NOT NULL DEFAULT 0,
  `selisih` int(11) NOT NULL DEFAULT 0,
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `opname_detail`
--

INSERT INTO `opname_detail` (`id`, `opname_id`, `id_batch`, `stok_sistem`, `stok_fisik`, `selisih`, `note`) VALUES
(1, 1, 1, 650, 648, -2, 'Selisih kecil akibat dispensing harian'),
(2, 1, 9, 300, 300, 0, 'Sesuai sistem'),
(3, 1, 10, 58, 57, -1, 'Satu botol rusak'),
(4, 2, 2, 340, 340, 0, 'Sesuai sistem'),
(5, 2, 6, 150, 148, -2, 'Belum tercatat pemakaian akhir shift'),
(6, 2, 12, 55, 55, 0, 'Sesuai sistem');

-- --------------------------------------------------------

--
-- Table structure for table `pemakaian`
--

CREATE TABLE `pemakaian` (
  `id` int(11) NOT NULL,
  `no_pemakaian` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `id_admin` int(11) NOT NULL,
  `id_gudang` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemakaian`
--

INSERT INTO `pemakaian` (`id`, `no_pemakaian`, `tanggal`, `keterangan`, `id_admin`, `id_gudang`, `created_at`) VALUES
(1, 'PMK-2026-0001', '2026-03-22', 'Pemakaian poli umum dan rawat jalan', 2, 2, '2026-04-04 09:50:10'),
(2, 'PMK-2026-0002', '2026-03-22', 'Pemakaian layanan penyakit kronis', 3, 3, '2026-04-04 09:50:10'),
(3, 'PMK-2026-0003', '2026-03-23', 'Pemakaian IGD dan layanan infeksi akut', 4, 4, '2026-04-04 09:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `pemakaian_detail`
--

CREATE TABLE `pemakaian_detail` (
  `id` int(11) NOT NULL,
  `id_pemakaian` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemakaian_detail`
--

INSERT INTO `pemakaian_detail` (`id`, `id_pemakaian`, `id_batch`, `jumlah`) VALUES
(1, 1, 1, 45),
(2, 1, 9, 20),
(3, 1, 10, 6),
(4, 2, 6, 18),
(5, 2, 7, 12),
(6, 2, 12, 10),
(7, 3, 1, 25),
(8, 3, 5, 16),
(9, 3, 11, 4);

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id` int(11) NOT NULL,
  `no_nota` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `supplier` varchar(150) NOT NULL,
  `alamat` varchar(200) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `telepon` varchar(50) DEFAULT NULL,
  `metode_bayar` varchar(50) NOT NULL DEFAULT 'Transfer Bank',
  `diskon` decimal(5,2) NOT NULL DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `id_admin` int(11) NOT NULL,
  `id_gudang` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pembelian`
--

INSERT INTO `pembelian` (`id`, `no_nota`, `tanggal`, `supplier`, `alamat`, `kota`, `telepon`, `metode_bayar`, `diskon`, `catatan`, `id_admin`, `id_gudang`, `created_at`) VALUES
(1, 'PO-2026-0001', '2026-03-01', 'PT Kimia Farma Trading & Distribution', 'Jl. Magelang Km 7', 'Yogyakarta', '081226781001', 'Transfer Bank', 2.50, 'Pengadaan triwulan I', 1, 1, '2026-04-04 09:50:09'),
(2, 'PO-2026-0002', '2026-03-12', 'PT Anugerah Pharmindo Lestari', 'Jl. Ring Road Utara No. 88', 'Sleman', '081226781002', 'Transfer Bank', 1.00, 'Stok antibiotik dan vitamin', 1, 1, '2026-04-04 09:50:09'),
(3, '', '2026-04-13', '', '', '', '', '', 0.00, '', 1, 1, '2026-04-13 03:23:17'),
(4, '', '2026-04-13', '', '', '', '', '', 0.00, '', 1, 1, '2026-04-13 03:23:35'),
(5, 'NP-1776236171941', '2026-04-15', 'PT Kimia Farma Trading & Distribution', '', 'Yogyakarta', '081226781001', 'Transfer Bank', 0.00, '', 1, 1, '2026-04-15 06:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `pembelian_detail`
--

CREATE TABLE `pembelian_detail` (
  `id` int(11) NOT NULL,
  `id_pembelian` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pembelian_detail`
--

INSERT INTO `pembelian_detail` (`id`, `id_pembelian`, `id_batch`, `jumlah`, `harga`) VALUES
(1, 1, 1, 4000, 180.00),
(2, 1, 3, 1200, 210.00),
(3, 1, 5, 900, 950.00),
(4, 1, 10, 300, 8500.00),
(5, 2, 2, 2500, 520.00),
(6, 2, 4, 3000, 145.00),
(7, 2, 11, 100, 6700.00),
(8, 2, 12, 600, 890.00),
(9, 5, 7, 5, 10000.00),
(10, 5, 9, 12, 13500.00);

-- --------------------------------------------------------

--
-- Table structure for table `penghapusan`
--

CREATE TABLE `penghapusan` (
  `id` int(11) NOT NULL,
  `no_hapus` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `alasan` text NOT NULL,
  `id_admin` int(11) NOT NULL,
  `id_gudang` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penghapusan`
--

INSERT INTO `penghapusan` (`id`, `no_hapus`, `tanggal`, `alasan`, `id_admin`, `id_gudang`, `created_at`) VALUES
(1, 'HPS-2026-0001', '2026-03-25', 'Obat kedaluwarsa hasil stock review bulanan', 1, 1, '2026-04-04 09:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `penghapusan_detail`
--

CREATE TABLE `penghapusan_detail` (
  `id` int(11) NOT NULL,
  `id_hapus` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penghapusan_detail`
--

INSERT INTO `penghapusan_detail` (`id`, `id_hapus`, `id_batch`, `jumlah`) VALUES
(1, 1, 5, 15);

-- --------------------------------------------------------

--
-- Table structure for table `permintaan`
--

CREATE TABLE `permintaan` (
  `id` int(11) NOT NULL,
  `from_gudang_id` int(11) NOT NULL,
  `to_gudang_id` int(11) NOT NULL,
  `priority` enum('LOW','MEDIUM','HIGH','URGENT') NOT NULL DEFAULT 'MEDIUM',
  `note` text DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','APPROVED','PARTIAL','ON_DELIVERY','REJECTED','FULFILLED') NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permintaan`
--

INSERT INTO `permintaan` (`id`, `from_gudang_id`, `to_gudang_id`, `priority`, `note`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'HIGH', 'Permintaan obat ISPA dan demam untuk peningkatan kasus musiman', 'APPROVED', '2026-03-15 01:30:00', '2026-03-16 02:10:00'),
(2, 3, 1, 'MEDIUM', 'Permintaan obat hipertensi dan diabetes untuk layanan prolanis', 'FULFILLED', '2026-03-18 03:15:00', '2026-03-20 06:45:00'),
(3, 4, 1, 'URGENT', 'Permintaan tambahan oralit dan paracetamol untuk kejadian diare', 'ON_DELIVERY', '2026-03-21 00:45:00', '2026-03-21 04:20:00'),
(4, 2, 1, 'HIGH', 'stok untuk 1 minggu kedepan', 'FULFILLED', '2026-04-06 09:09:44', '2026-04-06 10:25:29'),
(5, 3, 1, 'HIGH', 'obat yang diminta sudah habis', 'DRAFT', '2026-04-14 06:43:29', '2026-04-14 06:43:29'),
(6, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 06:44:45', '2026-04-14 06:44:45'),
(7, 3, 1, 'HIGH', 'obat yang diminta sudah habis', 'DRAFT', '2026-04-14 06:47:02', '2026-04-14 06:47:02'),
(8, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 06:48:05', '2026-04-14 06:48:05'),
(9, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 06:49:17', '2026-04-14 06:49:17'),
(10, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 06:50:29', '2026-04-14 06:50:29'),
(11, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 06:51:27', '2026-04-14 06:51:27'),
(12, 3, 1, 'HIGH', 'obat sangat diperlukan', 'DRAFT', '2026-04-14 06:58:04', '2026-04-14 06:58:04'),
(13, 3, 1, 'HIGH', 'obat sangat diperlukan', 'DRAFT', '2026-04-14 06:59:20', '2026-04-14 06:59:20'),
(14, 3, 1, 'HIGH', 'obat sangat diperlukan', 'DRAFT', '2026-04-14 07:14:13', '2026-04-14 07:14:13'),
(15, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 07:17:26', '2026-04-14 07:17:26'),
(16, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 07:19:02', '2026-04-14 07:19:02'),
(17, 3, 1, 'MEDIUM', NULL, 'DRAFT', '2026-04-14 07:19:57', '2026-04-14 07:19:57'),
(18, 2, 1, 'HIGH', 'stok sudah habis', 'DRAFT', '2026-04-15 17:12:37', '2026-04-15 17:12:37'),
(19, 2, 1, 'HIGH', 'stok sudah habis', 'DRAFT', '2026-04-15 17:13:26', '2026-04-15 17:13:26'),
(20, 2, 1, 'HIGH', 'stok sudah habis', 'DRAFT', '2026-04-15 17:27:29', '2026-04-15 17:27:29'),
(21, 2, 1, 'HIGH', 'stok habis', 'FULFILLED', '2026-04-16 02:04:55', '2026-04-16 02:30:08');

-- --------------------------------------------------------

--
-- Table structure for table `permintaan_detail`
--

CREATE TABLE `permintaan_detail` (
  `id` int(11) NOT NULL,
  `permintaan_id` int(11) NOT NULL,
  `obat_id` int(11) NOT NULL,
  `qty_requested` int(11) NOT NULL,
  `qty_approved` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permintaan_detail`
--

INSERT INTO `permintaan_detail` (`id`, `permintaan_id`, `obat_id`, `qty_requested`, `qty_approved`) VALUES
(1, 1, 1, 300, 300),
(2, 1, 9, 150, 150),
(3, 1, 10, 40, 40),
(4, 2, 6, 120, 120),
(5, 2, 7, 80, 80),
(6, 2, 12, 40, 40),
(7, 3, 1, 200, 200),
(8, 3, 5, 120, 120),
(9, 4, 6, 12, 12),
(10, 4, 10, 10, 10),
(11, 21, 10, 10, 10),
(12, 21, 3, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `retur`
--

CREATE TABLE `retur` (
  `id` int(11) NOT NULL,
  `no_retur` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `alasan` text DEFAULT NULL,
  `id_admin` int(11) NOT NULL,
  `id_gudang` int(11) NOT NULL,
  `tujuan` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `retur`
--

INSERT INTO `retur` (`id`, `no_retur`, `tanggal`, `alasan`, `id_admin`, `id_gudang`, `tujuan`, `created_at`) VALUES
(1, 'RTR-2026-0001', '2026-03-24', 'Kemasan rusak saat penerimaan', 2, 2, 1, '2026-04-04 09:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `retur_detail`
--

CREATE TABLE `retur_detail` (
  `id` int(11) NOT NULL,
  `id_retur` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `retur_detail`
--

INSERT INTO `retur_detail` (`id`, `id_retur`, `id_batch`, `jumlah`) VALUES
(1, 1, 10, 2);

-- --------------------------------------------------------

--
-- Table structure for table `satuan`
--

CREATE TABLE `satuan` (
  `id` int(11) NOT NULL,
  `kode` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `singkat` varchar(50) DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `satuan`
--

INSERT INTO `satuan` (`id`, `kode`, `nama`, `singkat`, `jenis`, `keterangan`, `created_at`) VALUES
(1, 'TAB', 'Tablet', 'Tab', 'PADAT', 'Satuan tablet oral', '2026-04-04 09:50:09'),
(2, 'KPS', 'Kapsul', 'Kps', 'PADAT', 'Satuan kapsul oral', '2026-04-04 09:50:09'),
(3, 'SCH', 'Sachet', 'Sch', 'PADAT', 'Satuan sachet', '2026-04-04 09:50:09'),
(4, 'BTL', 'Botol', 'Btl', 'CAIR', 'Satuan botol cair', '2026-04-04 09:50:09'),
(5, 'AMP', 'Ampul', 'Amp', 'INJEKSI', 'Satuan ampul injeksi', '2026-04-04 09:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) NOT NULL,
  `gudang_id` int(11) NOT NULL,
  `obat_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `source_id` int(11) DEFAULT NULL,
  `direction` enum('IN','OUT','ADJUSTMENT') NOT NULL,
  `qty` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `gudang_id`, `obat_id`, `batch_id`, `source_type`, `source_id`, `direction`, `qty`, `note`, `created_at`) VALUES
(1, 1, 1, 1, 'pembelian', 1, 'IN', 4000, 'Pengadaan triwulan I', '2026-03-01 02:20:00'),
(2, 1, 2, 2, 'pembelian', 2, 'IN', 2500, 'Pengadaan antibiotik', '2026-03-12 03:05:00'),
(3, 1, 1, 1, 'mutasi', 1, 'OUT', 300, 'Distribusi ke Puskesmas Bantul I', '2026-03-16 02:35:00'),
(4, 2, 1, 1, 'mutasi', 1, 'IN', 300, 'Penerimaan distribusi dari Dinkes', '2026-03-16 07:10:00'),
(5, 1, 6, 6, 'mutasi', 2, 'OUT', 120, 'Distribusi ke Puskesmas Sewon I', '2026-03-20 03:00:00'),
(6, 3, 6, 6, 'mutasi', 2, 'IN', 120, 'Penerimaan distribusi dari Dinkes', '2026-03-20 07:30:00'),
(7, 2, 1, 1, 'pemakaian', 1, 'OUT', 45, 'Pemakaian poli umum', '2026-03-22 08:30:00'),
(8, 4, 5, 5, 'mutasi', 3, 'IN', 120, 'Menunggu konfirmasi penerimaan penuh', '2026-03-21 09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `stok_batch`
--

CREATE TABLE `stok_batch` (
  `id_gudang` int(11) NOT NULL,
  `id_batch` int(11) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stok_batch`
--

INSERT INTO `stok_batch` (`id_gudang`, `id_batch`, `stok`, `updated_at`) VALUES
(1, 1, 6500, '2026-04-04 09:50:09'),
(1, 2, 3200, '2026-04-04 09:50:09'),
(1, 3, 1799, '2026-04-16 02:29:47'),
(1, 4, 4100, '2026-04-04 09:50:09'),
(1, 5, 1400, '2026-04-04 09:50:09'),
(1, 6, 1188, '2026-04-06 10:25:09'),
(1, 7, 2105, '2026-04-15 07:55:09'),
(1, 8, 950, '2026-04-04 09:50:09'),
(1, 9, 2512, '2026-04-15 07:55:26'),
(1, 10, 400, '2026-04-16 02:29:47'),
(1, 11, 160, '2026-04-04 09:50:09'),
(1, 12, 780, '2026-04-04 09:50:09'),
(2, 1, 650, '2026-04-04 09:50:09'),
(2, 3, 221, '2026-04-16 02:30:08'),
(2, 5, 110, '2026-04-04 09:50:09'),
(2, 6, 12, '2026-04-06 10:25:29'),
(2, 9, 300, '2026-04-04 09:50:09'),
(2, 10, 80, '2026-04-16 02:30:08'),
(3, 2, 340, '2026-04-04 09:50:09'),
(3, 4, 190, '2026-04-04 09:50:09'),
(3, 6, 150, '2026-04-04 09:50:09'),
(3, 8, 75, '2026-04-04 09:50:09'),
(3, 12, 55, '2026-04-04 09:50:09'),
(4, 1, 280, '2026-04-04 09:50:09'),
(4, 7, 130, '2026-04-04 09:50:09'),
(4, 10, 40, '2026-04-04 09:50:09'),
(4, 11, 24, '2026-04-04 09:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `stok_threshold`
--

CREATE TABLE `stok_threshold` (
  `gudang_id` int(11) NOT NULL,
  `obat_id` int(11) NOT NULL,
  `min_qty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stok_threshold`
--

INSERT INTO `stok_threshold` (`gudang_id`, `obat_id`, `min_qty`, `updated_at`) VALUES
(1, 1, 1200.00, '2026-04-04 09:50:09'),
(1, 2, 1000.00, '2026-04-04 09:50:09'),
(1, 5, 600.00, '2026-04-04 09:50:09'),
(1, 10, 150.00, '2026-04-04 09:50:09'),
(2, 1, 150.00, '2026-04-04 09:50:09'),
(2, 3, 80.00, '2026-04-04 09:50:09'),
(2, 9, 120.00, '2026-04-04 09:50:09'),
(2, 10, 30.00, '2026-04-04 09:50:09'),
(3, 2, 100.00, '2026-04-04 09:50:09'),
(3, 4, 80.00, '2026-04-04 09:50:09'),
(3, 6, 70.00, '2026-04-04 09:50:09'),
(3, 12, 25.00, '2026-04-04 09:50:09'),
(4, 1, 100.00, '2026-04-04 09:50:09'),
(4, 7, 60.00, '2026-04-04 09:50:09'),
(4, 10, 20.00, '2026-04-04 09:50:09'),
(4, 11, 10.00, '2026-04-04 09:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` int(11) NOT NULL,
  `kode_supplier` varchar(50) NOT NULL,
  `nama_supplier` varchar(150) NOT NULL,
  `nama_legal_perusahaan` varchar(150) DEFAULT NULL,
  `nama_brand` varchar(150) DEFAULT NULL,
  `jenis_perusahaan` varchar(50) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL,
  `negara` varchar(100) DEFAULT 'Indonesia',
  `pic` varchar(100) NOT NULL,
  `telepon` varchar(30) NOT NULL,
  `no_hp_whatsapp` varchar(30) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `npwp` varchar(16) NOT NULL,
  `nama_bank` varchar(100) DEFAULT NULL,
  `no_rekening` varchar(50) DEFAULT NULL,
  `atas_nama_rekening` varchar(150) DEFAULT NULL,
  `last_transaction_date` date DEFAULT NULL,
  `status` enum('AKTIF','TIDAK_AKTIF') NOT NULL DEFAULT 'AKTIF',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`id_supplier`, `kode_supplier`, `nama_supplier`, `nama_legal_perusahaan`, `nama_brand`, `jenis_perusahaan`, `kota`, `alamat`, `provinsi`, `kode_pos`, `negara`, `pic`, `telepon`, `no_hp_whatsapp`, `email`, `npwp`, `nama_bank`, `no_rekening`, `atas_nama_rekening`, `last_transaction_date`, `status`, `created_at`, `created_by`, `updated_at`, `notes`) VALUES
(1, 'SUP-001', 'PT Kimia Farma Trading & Distribution', 'PT Kimia Farma Trading & Distribution', NULL, NULL, 'Yogyakarta', NULL, NULL, NULL, 'Indonesia', 'Rina Puspitasari', '081226781001', '081226781001', 'rina.puspitasari@kftd.test', '3174012300010001', NULL, NULL, NULL, NULL, 'AKTIF', '2026-04-04 09:50:09', NULL, '2026-04-16 11:47:25', NULL),
(2, 'SUP-002', 'PT Anugerah Pharmindo Lestari', 'PT Anugerah Pharmindo Lestari', NULL, NULL, 'Sleman', NULL, NULL, NULL, 'Indonesia', 'Arif Nugroho', '081226781002', '081226781002', 'arif.nugroho@apl.test', '3174012300010002', NULL, NULL, NULL, NULL, 'AKTIF', '2026-04-04 09:50:09', NULL, '2026-04-16 11:47:25', NULL),
(3, 'SUP-003', 'CV Medika Nusantara Sejahtera', 'CV Medika Nusantara Sejahtera', NULL, NULL, 'Bantul', NULL, NULL, NULL, 'Indonesia', 'Dewi Lestari', '081226781003', '081226781003', 'dewi.lestari@mednusa.test', '3174012300010003', NULL, NULL, NULL, NULL, 'AKTIF', '2026-04-04 09:50:09', NULL, '2026-04-16 11:47:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_admin` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('dinkes','puskesmas') NOT NULL,
  `id_gudang` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_admin`, `nama`, `password`, `role`, `id_gudang`, `created_at`) VALUES
(1, 'Gudang Dinkes', '$2y$10$TqhmAjj6q9e8xVFFuV4u7.u4Q95MGxIXC.tGwWKnl9YJKRR.X1ViS', 'dinkes', 1, '2026-04-04 09:50:09'),
(2, 'Gudang Puskesmas 1', '$2y$10$uEVk35YLPRSP8sJvHqsC2O191igR77csgVCnNcSfVvif2p5DUQLqu', 'puskesmas', 2, '2026-04-04 09:50:09'),
(3, 'Gudang Puskesmas 2', '$2y$12$mnUfAMdg9Zjkw494egoAG.RtgRwO/APPXh3t3wGJ.QBt0XZYKF4jG', 'puskesmas', 3, '2026-04-04 09:50:09'),
(4, 'Gudang Puskesmas 3', '$2y$12$y3pU7lZ31.mgAqdvwuB3He7K3bVzx0W9y0BWHXg.UVeK4xrVVVexm', 'puskesmas', 4, '2026-04-04 09:50:09'),
(5, 'Gudang Puskesmas 4', '$2y$10$3SiFhsnJ14Qsfsc22vQ/Web/YRM3PFuhVctOL9uGHdue5ixVqd7Om', 'puskesmas', 5, '2026-04-08 20:20:05'),
(6, 'Gudang Puskemas 7', '$2y$10$V5Oz/kGAy48cgrobGjEYN.YcnVTWj3LVbc1H330jutSqndbB8/OTu', 'puskesmas', 7, '2026-04-16 06:30:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_actor` (`actor_id`),
  ADD KEY `idx_audit_entity` (`entity`,`entity_id`);

--
-- Indexes for table `data_batch`
--
ALTER TABLE `data_batch`
  ADD PRIMARY KEY (`id_batch`),
  ADD UNIQUE KEY `uk_batch_obat` (`id_obat`,`batch`),
  ADD KEY `idx_batch_obat` (`id_obat`),
  ADD KEY `idx_batch_exp` (`exp_date`);

--
-- Indexes for table `data_obat`
--
ALTER TABLE `data_obat`
  ADD PRIMARY KEY (`id_obat`),
  ADD KEY `idx_data_obat_nama` (`nama`),
  ADD KEY `idx_data_obat_jenis` (`jenis`);

--
-- Indexes for table `gudang`
--
ALTER TABLE `gudang`
  ADD PRIMARY KEY (`id_gudang`),
  ADD UNIQUE KEY `uk_gudang_nama` (`nama_gudang`),
  ADD UNIQUE KEY `kode_gudang` (`kode_gudang`),
  ADD UNIQUE KEY `kode_gudang_2` (`kode_gudang`),
  ADD KEY `idx_kode_gudang` (`kode_gudang`);

--
-- Indexes for table `mutasi`
--
ALTER TABLE `mutasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mutasi_sumber` (`sumber`),
  ADD KEY `idx_mutasi_tujuan` (`tujuan`),
  ADD KEY `idx_mutasi_admin` (`id_admin`),
  ADD KEY `idx_mutasi_permintaan` (`permintaan_id`),
  ADD KEY `idx_mutasi_status` (`status`);

--
-- Indexes for table `mutasi_detail`
--
ALTER TABLE `mutasi_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mutasi_detail_hdr` (`id_mutasi`),
  ADD KEY `idx_mutasi_detail_batch` (`id_batch`);

--
-- Indexes for table `notifikasi_dibaca`
--
ALTER TABLE `notifikasi_dibaca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_notif` (`id_user`,`notif_key`),
  ADD KEY `idx_user` (`id_user`);

--
-- Indexes for table `opname`
--
ALTER TABLE `opname`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_opname_gudang` (`id_gudang`),
  ADD KEY `idx_opname_status` (`status`),
  ADD KEY `idx_opname_created_by` (`created_by`);

--
-- Indexes for table `opname_detail`
--
ALTER TABLE `opname_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_opname_detail_hdr` (`opname_id`),
  ADD KEY `idx_opname_detail_batch` (`id_batch`);

--
-- Indexes for table `pemakaian`
--
ALTER TABLE `pemakaian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pemakaian_admin` (`id_admin`),
  ADD KEY `idx_pemakaian_gudang` (`id_gudang`),
  ADD KEY `idx_pemakaian_tanggal` (`tanggal`);

--
-- Indexes for table `pemakaian_detail`
--
ALTER TABLE `pemakaian_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pemakaian_detail_hdr` (`id_pemakaian`),
  ADD KEY `idx_pemakaian_detail_batch` (`id_batch`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pembelian_tanggal` (`tanggal`),
  ADD KEY `idx_pembelian_admin` (`id_admin`),
  ADD KEY `idx_pembelian_gudang` (`id_gudang`);

--
-- Indexes for table `pembelian_detail`
--
ALTER TABLE `pembelian_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pembelian_detail_hdr` (`id_pembelian`),
  ADD KEY `idx_pembelian_detail_batch` (`id_batch`);

--
-- Indexes for table `penghapusan`
--
ALTER TABLE `penghapusan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_penghapusan_admin` (`id_admin`),
  ADD KEY `idx_penghapusan_gudang` (`id_gudang`),
  ADD KEY `idx_penghapusan_tanggal` (`tanggal`);

--
-- Indexes for table `penghapusan_detail`
--
ALTER TABLE `penghapusan_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_penghapusan_detail_hdr` (`id_hapus`),
  ADD KEY `idx_penghapusan_detail_batch` (`id_batch`);

--
-- Indexes for table `permintaan`
--
ALTER TABLE `permintaan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_permintaan_from` (`from_gudang_id`),
  ADD KEY `idx_permintaan_to` (`to_gudang_id`),
  ADD KEY `idx_permintaan_status` (`status`);

--
-- Indexes for table `permintaan_detail`
--
ALTER TABLE `permintaan_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_permintaan_detail_hdr` (`permintaan_id`),
  ADD KEY `idx_permintaan_detail_obat` (`obat_id`);

--
-- Indexes for table `retur`
--
ALTER TABLE `retur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_retur_admin` (`id_admin`),
  ADD KEY `idx_retur_gudang` (`id_gudang`),
  ADD KEY `idx_retur_tujuan` (`tujuan`),
  ADD KEY `idx_retur_tanggal` (`tanggal`);

--
-- Indexes for table `retur_detail`
--
ALTER TABLE `retur_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_retur_detail_hdr` (`id_retur`),
  ADD KEY `idx_retur_detail_batch` (`id_batch`);

--
-- Indexes for table `satuan`
--
ALTER TABLE `satuan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_satuan_kode` (`kode`),
  ADD UNIQUE KEY `uk_satuan_nama` (`nama`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stock_movements_lookup` (`gudang_id`,`obat_id`,`batch_id`,`created_at`),
  ADD KEY `idx_stock_movements_obat` (`obat_id`),
  ADD KEY `idx_stock_movements_batch` (`batch_id`);

--
-- Indexes for table `stok_batch`
--
ALTER TABLE `stok_batch`
  ADD PRIMARY KEY (`id_gudang`,`id_batch`),
  ADD KEY `idx_stok_batch_id_batch` (`id_batch`);

--
-- Indexes for table `stok_threshold`
--
ALTER TABLE `stok_threshold`
  ADD PRIMARY KEY (`gudang_id`,`obat_id`),
  ADD KEY `idx_stok_threshold_obat` (`obat_id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`),
  ADD UNIQUE KEY `uk_supplier_kode` (`kode_supplier`),
  ADD UNIQUE KEY `uk_supplier_npwp` (`npwp`),
  ADD KEY `idx_supplier_nama` (`nama_supplier`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `uk_user_nama` (`nama`),
  ADD KEY `idx_user_role` (`role`),
  ADD KEY `idx_user_gudang` (`id_gudang`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `data_batch`
--
ALTER TABLE `data_batch`
  MODIFY `id_batch` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `data_obat`
--
ALTER TABLE `data_obat`
  MODIFY `id_obat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `gudang`
--
ALTER TABLE `gudang`
  MODIFY `id_gudang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `mutasi`
--
ALTER TABLE `mutasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mutasi_detail`
--
ALTER TABLE `mutasi_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifikasi_dibaca`
--
ALTER TABLE `notifikasi_dibaca`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `opname`
--
ALTER TABLE `opname`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `opname_detail`
--
ALTER TABLE `opname_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pemakaian`
--
ALTER TABLE `pemakaian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pemakaian_detail`
--
ALTER TABLE `pemakaian_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pembelian_detail`
--
ALTER TABLE `pembelian_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `penghapusan`
--
ALTER TABLE `penghapusan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `penghapusan_detail`
--
ALTER TABLE `penghapusan_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permintaan`
--
ALTER TABLE `permintaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `permintaan_detail`
--
ALTER TABLE `permintaan_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `retur`
--
ALTER TABLE `retur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `retur_detail`
--
ALTER TABLE `retur_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `satuan`
--
ALTER TABLE `satuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `id_supplier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `user` (`id_admin`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `data_batch`
--
ALTER TABLE `data_batch`
  ADD CONSTRAINT `fk_batch_obat` FOREIGN KEY (`id_obat`) REFERENCES `data_obat` (`id_obat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mutasi`
--
ALTER TABLE `mutasi`
  ADD CONSTRAINT `fk_mutasi_admin` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mutasi_permintaan` FOREIGN KEY (`permintaan_id`) REFERENCES `permintaan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mutasi_sumber` FOREIGN KEY (`sumber`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mutasi_tujuan` FOREIGN KEY (`tujuan`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;

--
-- Constraints for table `mutasi_detail`
--
ALTER TABLE `mutasi_detail`
  ADD CONSTRAINT `fk_mutasi_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `data_batch` (`id_batch`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mutasi_detail_hdr` FOREIGN KEY (`id_mutasi`) REFERENCES `mutasi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `opname`
--
ALTER TABLE `opname`
  ADD CONSTRAINT `fk_opname_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_opname_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;

--
-- Constraints for table `opname_detail`
--
ALTER TABLE `opname_detail`
  ADD CONSTRAINT `fk_opname_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `data_batch` (`id_batch`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_opname_detail_hdr` FOREIGN KEY (`opname_id`) REFERENCES `opname` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pemakaian`
--
ALTER TABLE `pemakaian`
  ADD CONSTRAINT `fk_pemakaian_admin` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pemakaian_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;

--
-- Constraints for table `pemakaian_detail`
--
ALTER TABLE `pemakaian_detail`
  ADD CONSTRAINT `fk_pemakaian_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `data_batch` (`id_batch`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pemakaian_detail_hdr` FOREIGN KEY (`id_pemakaian`) REFERENCES `pemakaian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `fk_pembelian_admin` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembelian_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;

--
-- Constraints for table `pembelian_detail`
--
ALTER TABLE `pembelian_detail`
  ADD CONSTRAINT `fk_pembelian_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `data_batch` (`id_batch`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembelian_detail_hdr` FOREIGN KEY (`id_pembelian`) REFERENCES `pembelian` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penghapusan`
--
ALTER TABLE `penghapusan`
  ADD CONSTRAINT `fk_penghapusan_admin` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penghapusan_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;

--
-- Constraints for table `penghapusan_detail`
--
ALTER TABLE `penghapusan_detail`
  ADD CONSTRAINT `fk_penghapusan_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `data_batch` (`id_batch`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penghapusan_detail_hdr` FOREIGN KEY (`id_hapus`) REFERENCES `penghapusan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `permintaan`
--
ALTER TABLE `permintaan`
  ADD CONSTRAINT `fk_permintaan_from` FOREIGN KEY (`from_gudang_id`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_permintaan_to` FOREIGN KEY (`to_gudang_id`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;

--
-- Constraints for table `permintaan_detail`
--
ALTER TABLE `permintaan_detail`
  ADD CONSTRAINT `fk_permintaan_detail_hdr` FOREIGN KEY (`permintaan_id`) REFERENCES `permintaan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_permintaan_detail_obat` FOREIGN KEY (`obat_id`) REFERENCES `data_obat` (`id_obat`) ON UPDATE CASCADE;

--
-- Constraints for table `retur`
--
ALTER TABLE `retur`
  ADD CONSTRAINT `fk_retur_admin` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_admin`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_retur_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_retur_tujuan` FOREIGN KEY (`tujuan`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;

--
-- Constraints for table `retur_detail`
--
ALTER TABLE `retur_detail`
  ADD CONSTRAINT `fk_retur_detail_batch` FOREIGN KEY (`id_batch`) REFERENCES `data_batch` (`id_batch`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_retur_detail_hdr` FOREIGN KEY (`id_retur`) REFERENCES `retur` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_stock_movements_batch` FOREIGN KEY (`batch_id`) REFERENCES `data_batch` (`id_batch`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_movements_gudang` FOREIGN KEY (`gudang_id`) REFERENCES `gudang` (`id_gudang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_movements_obat` FOREIGN KEY (`obat_id`) REFERENCES `data_obat` (`id_obat`) ON UPDATE CASCADE;

--
-- Constraints for table `stok_batch`
--
ALTER TABLE `stok_batch`
  ADD CONSTRAINT `fk_stok_batch_batch` FOREIGN KEY (`id_batch`) REFERENCES `data_batch` (`id_batch`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stok_batch_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `gudang` (`id_gudang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stok_threshold`
--
ALTER TABLE `stok_threshold`
  ADD CONSTRAINT `fk_stok_threshold_gudang` FOREIGN KEY (`gudang_id`) REFERENCES `gudang` (`id_gudang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stok_threshold_obat` FOREIGN KEY (`obat_id`) REFERENCES `data_obat` (`id_obat`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_gudang` FOREIGN KEY (`id_gudang`) REFERENCES `gudang` (`id_gudang`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
