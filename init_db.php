<?php
function initSqliteDatabase($conn) {
    $conn->exec("PRAGMA foreign_keys = ON");

    $conn->exec("CREATE TABLE IF NOT EXISTS puskesmas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_puskesmas TEXT NOT NULL,
        alamat TEXT,
        telepon TEXT,
        kode_puskesmas TEXT UNIQUE,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        nama_lengkap TEXT NOT NULL,
        email TEXT,
        role TEXT NOT NULL DEFAULT 'admin' CHECK(role IN ('super_admin','admin')),
        id_puskesmas INTEGER,
        status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','inactive')),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_puskesmas) REFERENCES puskesmas(id) ON DELETE SET NULL
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS villages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_desa TEXT NOT NULL,
        kode_desa TEXT,
        id_puskesmas INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_puskesmas) REFERENCES puskesmas(id) ON DELETE SET NULL
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS lansia (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nik TEXT NOT NULL UNIQUE,
        nama_lengkap TEXT NOT NULL,
        tempat_lahir TEXT,
        tanggal_lahir TEXT NOT NULL,
        jenis_kelamin TEXT NOT NULL CHECK(jenis_kelamin IN ('L','P')),
        alamat TEXT,
        id_desa INTEGER,
        no_telepon TEXT,
        bpjs TEXT,
        status_kesehatan TEXT DEFAULT 'sehat' CHECK(status_kesehatan IN ('sehat','sakit_ringan','sakit_berat')),
        kategori_lansia TEXT DEFAULT 'lansia',
        status_risiko TEXT DEFAULT 'risiko_rendah' CHECK(status_risiko IN ('risiko_rendah','risiko_sedang','risiko_tinggi')),
        nama_keluarga TEXT,
        hubungan_keluarga TEXT,
        no_telepon_keluarga TEXT,
        tanggal_registrasi TEXT DEFAULT CURRENT_DATE,
        status_aktif TEXT DEFAULT 'aktif' CHECK(status_aktif IN ('aktif','nonaktif')),
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_desa) REFERENCES villages(id) ON DELETE SET NULL
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_lansia INTEGER NOT NULL,
        id_petugas INTEGER NOT NULL,
        tanggal_kunjungan TEXT NOT NULL,
        jam_kunjungan TEXT NOT NULL,
        jenis_kunjungan TEXT DEFAULT 'baru' CHECK(jenis_kunjungan IN ('baru','lama')),
        status_kesehatan TEXT DEFAULT 'sehat' CHECK(status_kesehatan IN ('sehat','sakit_ringan','sakit_berat')),
        tekanan_darah_sistol INTEGER,
        tekanan_darah_diastol INTEGER,
        berat_badan REAL,
        tinggi_badan REAL,
        imt REAL,
        nadi INTEGER,
        respiratory_rate INTEGER,
        status_disabilitas TEXT DEFAULT 'tidak_ada' CHECK(status_disabilitas IN ('tidak_ada','ringan','sedang','berat')),
        kelainan TEXT,
        keluhan TEXT,
        diagnosa TEXT,
        tindakan TEXT,
        rujukan TEXT,
        tujuan_rujukan TEXT,
        rekomendasi TEXT DEFAULT 'pemeriksaan_biasa',
        obat TEXT,
        gula_darah INTEGER,
        kolesterol INTEGER,
        hemoglobin REAL,
        spo2 INTEGER,
        suhu_tubuh REAL,
        gangguan_penglihatan TEXT DEFAULT 'tidak_ada',
        gangguan_pendengaran TEXT DEFAULT 'tidak_ada',
        risiko_jatuh TEXT DEFAULT 'rendah',
        status_kemandirian TEXT DEFAULT 'mandiri',
        gangguan_daya_ingat TEXT DEFAULT 'tidak_ada',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_lansia) REFERENCES lansia(id) ON DELETE CASCADE,
        FOREIGN KEY (id_petugas) REFERENCES users(id) ON DELETE CASCADE
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS activities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_user INTEGER NOT NULL,
        aktivitas TEXT NOT NULL,
        deskripsi TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        \"key\" TEXT NOT NULL UNIQUE,
        \"value\" TEXT,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        token TEXT NOT NULL UNIQUE,
        expires_at TEXT NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $count = $conn->query("SELECT COUNT(*) FROM puskesmas")->fetchColumn();
    if ($count == 0) {
        $conn->exec("INSERT INTO puskesmas (nama_puskesmas, alamat, telepon, kode_puskesmas) VALUES ('Puskesmas Utama', 'Jl. Kesehatan No. 1', '021-1234567', 'PKM001')");

        $hash = '$2y$12$3rSwJDShGswYPP23FkmE8.i./I0Nl6tc4yWh9nN/JrfRsUCc/CMU.';
        $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role, id_puskesmas) VALUES (?, ?, ?, ?, ?, ?)")->execute(['kepala_puskesmas', $hash, 'Kepala Puskesmas', 'kepala@puskesmas.com', 'super_admin', 1]);
        $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role, id_puskesmas) VALUES (?, ?, ?, ?, ?, ?)")->execute(['admin', $hash, 'Administrator', 'admin@puskesmas.com', 'admin', 1]);
        $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role, id_puskesmas) VALUES (?, ?, ?, ?, ?, ?)")->execute(['petugas1', $hash, 'Petugas Puskesmas', 'petugas@puskesmas.com', 'admin', 1]);
        $conn->prepare("INSERT INTO users (username, password, nama_lengkap, email, role, id_puskesmas) VALUES (?, ?, ?, ?, ?, ?)")->execute(['dokter1', $hash, 'Dr. Budi Santoso', 'dokter@puskesmas.com', 'admin', 1]);

        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Bligorejo', 'DS001', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Doro', 'DS002', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Dororejo', 'DS003', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Harjosari', 'DS004', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Kalimojosari', 'DS005', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Kutosari', 'DS006', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Larikan', 'DS007', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Lemah Abang', 'DS008', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Pungangan', 'DS009', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Randusari', 'DS010', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Rogoselo', 'DS011', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Sawangan', 'DS012', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Sidoharjo', 'DS013', 1)");
        $conn->exec("INSERT INTO villages (nama_desa, kode_desa, id_puskesmas) VALUES ('Wungkal', 'DS014', 1)");

        $conn->exec("INSERT INTO settings (\"key\", \"value\") VALUES ('app_name', 'Sistem Pelaporan Lansia')");
        $conn->exec("INSERT INTO settings (\"key\", \"value\") VALUES ('app_version', '1.0.0')");
        $conn->exec("INSERT INTO settings (\"key\", \"value\") VALUES ('total_lansia_target', '500')");

        $conn->exec("INSERT INTO lansia (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, bpjs, status_kesehatan, kategori_lansia, status_risiko, nama_keluarga, no_telepon_keluarga) VALUES
            ('3172012345670001', 'H. Ahmad Sukarno', 'Jakarta', '1945-06-15', 'L', 'Jl. Merdeka No. 10', 1, '081234567890', '1234567890', 'sehat', 'lansia', 'risiko_tinggi', 'Budi Sukarno', '081234567891')");
        $conn->exec("INSERT INTO lansia (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, bpjs, status_kesehatan, kategori_lansia, status_risiko, nama_keluarga, no_telepon_keluarga) VALUES
            ('3172012345670002', 'Hj. Siti Aminah', 'Bandung', '1948-03-22', 'P', 'Jl. Jaya No. 5', 1, '081234567892', '1234567891', 'sakit_ringan', 'lansia_utama', 'risiko_sedang', 'Aminah', '081234567893')");
        $conn->exec("INSERT INTO lansia (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, bpjs, status_kesehatan, kategori_lansia, status_risiko, nama_keluarga, no_telepon_keluarga) VALUES
            ('3172012345670003', 'H. Muhammad Idris', 'Surabaya', '1950-12-01', 'L', 'Jl. Raya Km. 3', 2, '081234567894', '1234567892', 'sehat', 'lansia', 'risiko_rendah', 'Idris Jr.', '081234567895')");
        $conn->exec("INSERT INTO lansia (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, bpjs, status_kesehatan, kategori_lansia, status_risiko, nama_keluarga, no_telepon_keluarga) VALUES
            ('3172012345670004', 'Hj. Rohmah', 'Yogyakarta', '1952-08-10', 'P', 'Jl. Pendakian No. 2', 2, '081234567896', '1234567893', 'sehat', 'lansia_utama', 'risiko_sedang', 'Rohmah Jr.', '081234567897')");
        $conn->exec("INSERT INTO lansia (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, id_desa, no_telepon, bpjs, status_kesehatan, kategori_lansia, status_risiko, nama_keluarga, no_telepon_keluarga) VALUES
            ('3172012345670005', 'H. Jenal Abidin', 'Medan', '1955-01-25', 'L', 'Jl. Pasar No. 8', 3, '081234567898', '1234567894', 'sakit_berat', 'pra_lansia', 'risiko_tinggi', 'Abidin', '081234567899')");

        $visitsSql = "INSERT INTO visits (id_lansia, id_petugas, tanggal_kunjungan, jam_kunjungan, jenis_kunjungan, status_kesehatan, tekanan_darah_sistol, tekanan_darah_diastol, berat_badan, tinggi_badan, imt, nadi, respiratory_rate, status_disabilitas, kelainan, keluhan, diagnosa, tindakan, rujukan, tujuan_rujukan, rekomendasi, obat, gangguan_penglihatan, gangguan_pendengaran, risiko_jatuh, status_kemandirian, gangguan_daya_ingat) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $conn->prepare($visitsSql)->execute([1, 2, '2026-04-13', '08:30:00', 'baru', 'sakit_ringan', 160, 95, 65.0, 165.0, 23.9, 80, 20, 'tidak_ada', 'Tidak ada', 'Pusing dan badan lemah', 'Hipertensi Grade 1', 'Pemeriksaan tekanan darah, istirahat', 'Rujuk ke Poli Umum', 'Poli Umum', 'rawat_jalan', 'Captopril 12.5mg', 'ringan', 'tidak_ada', 'rendah', 'mandiri', 'tidak_ada']);
        $conn->prepare($visitsSql)->execute([2, 2, '2026-04-13', '09:15:00', 'lama', 'sakit_ringan', 140, 85, 55.0, 150.0, 24.4, 76, 18, 'ringan', 'Nyeri sendi', 'Nyeri sendi', 'Osteoartritis', 'Pemeriksaan fisik, gave obat', '', '', 'pemeriksaan_biasa', 'Kalium diklofenak 50mg', 'tidak_ada', 'ringan', 'rendah', 'mandiri', 'tidak_ada']);
        $conn->prepare($visitsSql)->execute([3, 2, '2026-04-13', '10:00:00', 'baru', 'sehat', 120, 80, 70.0, 170.0, 24.2, 72, 16, 'tidak_ada', 'Tidak ada', 'Batuk pilek', 'ISPA', 'Istirahat, banyak minum', '', '', 'pemeriksaan_biasa', 'Paracetamol 500mg', 'tidak_ada', 'tidak_ada', 'rendah', 'mandiri', 'tidak_ada']);
        $conn->prepare($visitsSql)->execute([4, 3, '2026-04-13', '11:00:00', 'lama', 'sakit_ringan', 150, 90, 60.0, 155.0, 25.0, 100, 28, 'berat', 'Sesak nafas', 'Sesak nafas', 'PPOK eksaserbasi akut', 'Nebulizer, observasi', 'Rujuk IGD', 'IGD', 'rawat_inap', 'Salbutamol inhaler', 'berat', 'berat', 'tinggi', 'tergantung', 'ada']);
        $conn->prepare($visitsSql)->execute([5, 2, '2026-04-13', '11:30:00', 'lama', 'sakit_ringan', 130, 80, 75.0, 168.0, 26.6, 78, 18, 'tidak_ada', 'Tidak ada', 'Diabetes tidak terkontrol', 'DM tipo 2', 'Pemeriksaan GDS, edukasi', 'Rujuk ke Poli Umum', 'Poli Umum,Laboratorium', 'rawat_jalan', 'Metformin 500mg', 'ringan', 'tidak_ada', 'sedang', 'bantuan_sebagian', 'ada']);
    }
}
