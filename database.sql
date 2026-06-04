-- Database untuk Sistem Pelaporan Kunjungan Harian Lansia Puskesmas
-- Compatible dengan MySQL/XAMPP

-- =====================================================
-- CARA IMPORT:
-- 1. Buka phpMyAdmin, buat database 'sistemlansia' (collation: utf8mb4_unicode_ci)
-- 2. Klik database -> SQL -> Paste semua ini -> Go
-- =====================================================

USE sistemlansia;

-- =====================================================
-- DROP TABLES (Jika tabel sudah ada)
-- =====================================================
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS lansia;
DROP TABLE IF EXISTS villages;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS puskesmas;

-- =====================================================
-- CREATE TABLES
-- =====================================================
-- DATABASE CREATION
-- =====================================================
-- NOTE: Buat database 'sistemlansia' terlebih dahulu di phpMyAdmin
-- Collation: utf8mb4_unicode_ci
-- Hapus semua tabel dulu jika ada, lalu import file ini
USE sistemlansia;

-- Drop tables if exists (untuk clean import)
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS activities;
DROP TABLE IF EXISTS lansia;
DROP TABLE IF EXISTS villages;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS puskesmas;

-- =====================================================
-- TABEL: users (Login & Autentikasi)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    id_puskesmas INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABEL: puskesmas
-- =====================================================
CREATE TABLE IF NOT EXISTS puskesmas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_puskesmas VARCHAR(150) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    kode_puskesmas VARCHAR(20) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABEL: villages (Desa/Kelurahan)
-- =====================================================
CREATE TABLE IF NOT EXISTS villages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_desa VARCHAR(100) NOT NULL,
    kode_desa VARCHAR(20),
    id_puskesmas INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_puskesmas) REFERENCES puskesmas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABEL: lansia (Data Lansia)
-- =====================================================
CREATE TABLE IF NOT EXISTS lansia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    alamat TEXT,
    id_desa INT,
    no_telepon VARCHAR(20),
    bpjs VARCHAR(20),
    status_kesehatan ENUM('sehat', 'sakit_ringan', 'sakit_berat') DEFAULT 'sehat',
    kategori_lansia ENUM('pra_lansia', 'lansia', 'lansia_utama') DEFAULT 'lansia',
    status_risiko ENUM('risiko_rendah', 'risiko_sedang', 'risiko_tinggi') DEFAULT 'risiko_rendah',
    nama_keluarga VARCHAR(100),
    no_telepon_keluarga VARCHAR(20),
    tanggal_registrasi DATE DEFAULT (CURRENT_DATE),
    status_aktif ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES villages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABEL: visits (Kunjungan Harian)
-- =====================================================
CREATE TABLE IF NOT EXISTS visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_lansia INT NOT NULL,
    id_petugas INT NOT NULL,
    tanggal_kunjungan DATE NOT NULL,
    jam_kunjungan TIME NOT NULL,
    jenis_kunjungan ENUM('baru', 'lama') DEFAULT 'baru',
    status_kesehatan ENUM('sehat', 'sakit_ringan', 'sakit_berat') DEFAULT 'sehat',
    tekanan_darah_sistol INT,
    tekanan_darah_diastol INT,
    berat_badan DECIMAL(5,2),
    tinggi_badan DECIMAL(5,2),
    imt DECIMAL(4,1),
    nadi INT,
    respiratory_rate INT,
    status_disabilitas ENUM('tidak_ada', 'ringan', 'sedang', 'berat') DEFAULT 'tidak_ada',
    kelainan TEXT,
    keluhan TEXT,
    diagnosa TEXT,
    tindakan TEXT,
    rujukan TEXT,
    tujuan_rujukan VARCHAR(200),
    rekomendasi ENUM('pemeriksaan_biasa', 'rawat_inap', 'rujuk_rs', 'rawat_jalan') DEFAULT 'pemeriksaan_biasa',
    obat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lansia) REFERENCES lansia(id) ON DELETE CASCADE,
    FOREIGN KEY (id_petugas) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABEL: activities (Log Aktivitas)
-- =====================================================
CREATE TABLE IF NOT EXISTS activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    aktivitas VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABEL: settings (Konfigurasi Sistem)
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- DATA AWAL (Default Admin)
-- =====================================================
INSERT IGNORE INTO puskesmas (nama_puskesmas, alamat, telepon, kode_puskesmas) VALUES
('Puskesmas Utama', 'Jl. Kesehatan No. 1', '021-1234567', 'PKM001');

INSERT IGNORE INTO users (username, password, nama_lengkap, email, role, id_puskesmas) VALUES
('kepala_puskesmas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Puskesmas', 'kepala@puskesmas.com', 'super_admin', 1),
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@puskesmas.com', 'admin', 1),
('petugas1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas Puskesmas', 'petugas@puskesmas.com', 'admin', 1),
('dokter1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Budi Santoso', 'dokter@puskesmas.com', 'admin', 1);

INSERT IGNORE INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES
('Desa Sehat Sejahtera', 'DS001', 1),
('Kelurahan Maju Jaya', 'KL001', 1),
('Desa Bina Sejahtera', 'DS002', 1);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('app_name', 'Sistem Pelaporan Lansia'),
('app_version', '1.0.0'),
('total_lansia_target', '500'),
('backup_last_date', NULL);

-- =====================================================
-- SAMPLE DATA LANSIA
-- =====================================================
INSERT IGNORE INTO lansia (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, bpjs, status_kesehatan, kategori_lansia, status_risiko, nama_keluarga, no_telepon_keluarga) VALUES
('3172012345670001', 'H. Ahmad Sukarno', 'Jakarta', '1945-06-15', 'L', 'Jl. Merdeka No. 10', 1, '081234567890', '1234567890', 'sehat', 'lansia', 'risiko_tinggi', 'Budi Sukarno', '081234567891'),
('3172012345670002', 'Hj. Siti Aminah', 'Bandung', '1948-03-22', 'P', 'Jl. Jaya No. 5', 1, '081234567892', '1234567891', 'sakit_ringan', 'lansia_utama', 'risiko_sedang', 'Aminah', '081234567893'),
('3172012345670003', 'H. Muhammad Idris', 'Surabaya', '1950-12-01', 'L', 'Jl. Raya Km. 3', 2, '081234567894', '1234567892', 'sehat', 'lansia', 'risiko_rendah', 'Idris Jr.', '081234567895'),
('3172012345670004', 'Hj. Rohmah', 'Yogyakarta', '1952-08-10', 'P', 'Jl. Pendakian No. 2', 2, '081234567896', '1234567893', 'sehat', 'lansia_utama', 'risiko_sedang', 'Rohmah Jr.', '081234567897'),
('3172012345670005', 'H. Jenal Abidin', 'Medan', '1955-01-25', 'L', 'Jl. Pasar No. 8', 3, '081234567898', '1234567894', 'sakit_berat', 'pra_lansia', 'risiko_tinggi', 'Abidin', '081234567899');

-- =====================================================
-- SAMPLE DATA KUNJUNGAN
-- =====================================================
INSERT IGNORE INTO visits (id_lansia, id_petugas, tanggal_kunjungan, jam_kunjungan, jenis_kunjungan, status_kesehatan, tekanan_darah_sistol, tekanan_darah_diastol, berat_badan, tinggi_badan, imt, nadi, respiratory_rate, status_disabilitas, kelainan, keluhan, diagnosa, tindakan, rujukan, tujuan_rujukan, rekomendasi, obat) VALUES
(1, 2, '2026-04-13', '08:30:00', 'baru', 'sakit_ringan', 160, 95, 65.0, 165.0, 23.9, 80, 20, 'tidak_ada', 'Tidak ada', 'Pusing dan badan lemah', 'Hipertensi Grade 1', 'Pemeriksaan tekanan darah, istirahat', 'Rujuk ke Poli Umum', 'Poli Umum', 'rawat_jalan', 'Captopril 12.5mg'),
(2, 2, '2026-04-13', '09:15:00', 'lama', 'sakit_ringan', 140, 85, 55.0, 150.0, 24.4, 76, 18, 'ringan', 'Nyeri sendi', 'Nyeri sendi', 'Osteoartritis', 'Pemeriksaan fisik, gave obat', '', '', 'pemeriksaan_biasa', 'Kalium diklofenak 50mg'),
(3, 2, '2026-04-13', '10:00:00', 'baru', 'sehat', 120, 80, 70.0, 170.0, 24.2, 72, 16, 'tidak_ada', 'Tidak ada', 'Batuk pilek', 'ISPA', 'Istirahat, banyak minum', '', '', 'pemeriksaan_biasa', 'Paracetamol 500mg'),
(4, 3, '2026-04-13', '11:00:00', 'lama', 'sakit_ringan', 150, 90, 60.0, 155.0, 25.0, 100, 28, 'berat', 'Sesak nafas', 'Sesak nafas', 'PPOK eksaserbasi akut', 'Nebulizer, observasi', 'Rujuk IGD', 'IGD', 'rawat_inap', 'Salbutamol inhaler'),
(5, 2, '2026-04-13', '11:30:00', 'lama', 'sakit_ringan', 130, 80, 75.0, 168.0, 26.6, 78, 18, 'tidak_ada', 'Tidak ada', 'Diabetes tidak terkontrol', 'DM tipo 2', 'Pemeriksaan GDS, edukasi', 'Rujuk ke Poli Umum', 'Poli Umum,Laboratorium', 'rawat_jalan', 'Metformin 500mg');
