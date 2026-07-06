# Sistem Informasi Pelaporan Lansia — PELANSIA

Aplikasi web untuk manajemen data lansia, pencatatan kunjungan, skrining geriatri, dan pelaporan puskesmas.

## ✅ Status: SELESAI (100% Fungsional)

Seluruh fitur telah selesai diimplementasikan dan sistem siap digunakan.

### 🔧 Fitur yang Ditambahkan
- ✅ Migrasi database SQLite (tanpa perlu XAMPP/MySQL)
- ✅ Dual interface: PHP server-rendered + React SPA
- ✅ 2-level access control (super_admin, admin)
- ✅ Auto-kategorisasi usia lansia (Pra Lansia/Lansia/Lansia Utama)
- ✅ Skrining geriatri dasar (5 domain)
- ✅ Engine klasifikasi kesehatan real-time age-adjusted
- ✅ Export PDF, CSV, Excel
- ✅ GitHub Actions CI (PHP lint + smoke test DB)

## 🚀 Fitur Utama

### 👥 Dual Role System

| Fitur | super_admin | admin |
|-------|:-----------:|:-----:|
| Dashboard & statistik | ✅ | ✅ |
| Lihat daftar lansia | ✅ (read-only) | ✅ (CRUD) |
| Tambah/Edit/Hapus lansia | ❌ | ✅ |
| Input kunjungan | ❌ | ✅ |
| Detail riwayat lansia | ✅ | ✅ |
| Laporan & export PDF/CSV/Excel | ✅ | ❌ |
| Kelola user (tambah/edit/nonaktifkan) | ✅ | ❌ |
| Kelola puskesmas & desa | ✅ | ❌ |
| Activity log | ✅ | ❌ |
| Pengaturan sistem | ✅ | ❌ |
| Setup database | ✅ | ❌ |
| Ganti password sendiri | ✅ | ✅ |

### 📊 Dashboard Interaktif
- 3 KPI cards (Total Lansia, Kunjungan Hari Ini, Lansia Sakit)
- Grafik kunjungan harian/mingguan/bulanan
- Pie chart kategori usia, status risiko, rekomendasi
- Drill-down modal per kategori usia

### 👴 Modul Lansia
- CRUD lengkap dengan soft-delete
- Auto-kategorisasi: Pra Lansia (45-59), Lansia (60-69), Lansia Utama (70+)
- Stratifikasi risiko (Rendah/Sedang/Tinggi)
- Data wali/keluarga

### 🏥 Modul Kunjungan
- Pemeriksaan fisik: TD, BB, TB, IMT (auto), nadi, RR
- Skrining Geriatri Dasar: penglihatan, pendengaran, risiko jatuh, kemandirian, daya ingat
- Hasil lab: gula darah, kolesterol, Hb, SpO2, suhu
- Diagnosa, tindakan, obat
- Rujukan internal poli
- Klasifikasi kesehatan real-time (Sehat/Waspada/Bahaya)

### 📋 Modul Laporan
- Filter tanggal, rekomendasi, status risiko
- Export PDF (print layout dengan grafik + signature)
- Export CSV & Excel

### 👤 Modul User Management
- Tambah/Edit/Nonaktifkan user (super_admin)
- Ubah password sendiri (semua role)

### 🏛️ Modul Puskesmas & Desa
- Kelola data puskesmas
- Kelola desa per puskesmas

### 📝 Activity Log
- Catatan aktivitas user (super_admin)

### ⚙️ Pengaturan Sistem
- Nama aplikasi, target lansia, backup toggle

## 🎨 Desain UI/UX
- **Modern & Responsive**: Mobile-first dengan Tailwind CSS 4
- **Dark Mode**: Toggle dengan localStorage
- **Dual Interface**: PHP server-rendered + React SPA
- **Recharts & Chart.js**: Grafik interaktif
- **SweetAlert2**: Modal & konfirmasi
- **Lucide Icons**: Ikon modern

## 🗄️ Database Schema (SQLite)

- **puskesmas** — Data puskesmas (`id`, `nama_puskesmas`, `alamat`, `telepon`, `kode_puskesmas`)
- **users** — Akun pengguna (`id`, `username`, `password`, `role` super_admin/admin, `id_puskesmas`, `status`)
- **villages** — Data desa (`id`, `nama_desa`, `kode_desa`, `id_puskesmas`)
- **lansia** — Data lansia (`id`, `nik`, `nama_lengkap`, `tanggal_lahir`, `jenis_kelamin`, `id_desa`, `status_kesehatan`, `kategori_lansia`, `status_risiko`, `nama_keluarga`)
- **visits** — Kunjungan (`id`, `id_lansia`, `id_petugas`, `tanggal_kunjungan`, hasil pemeriksaan, skrining geriatri, diagnosa, tindakan, rujukan)
- **activities** — Log aktivitas (`id`, `id_user`, `aktivitas`, `deskripsi`)
- **settings** — Pengaturan (`key`, `value`)
- **tokens** — Token autentikasi (`id`, `user_id`, `token`, `expires_at`)

## 🛠️ Teknologi

- **Backend**: PHP Native 8+ dengan PDO
- **Database**: SQLite
- **Frontend (SPA)**: React 18, Vite 6, Tailwind CSS 4, Recharts
- **Frontend (PHP)**: HTML5, Tailwind CSS, JavaScript ES6, Chart.js
- **Icons**: Lucide React
- **Modals**: SweetAlert2
- **CI/CD**: GitHub Actions (PHP lint + smoke test DB)

## 📦 Instalasi

### Persyaratan
- PHP 8.0+ (dengan ekstensi `pdo_sqlite` dan `sqlite3`)
- Web server (Apache / Nginx / PHP built-in server)

### Langkah Instalasi
1. Clone/download ke direktori web server
2. **Tidak perlu konfigurasi database** — SQLite otomatis terinisialisasi saat pertama diakses
3. Buka `login.php` di browser

### PHP Built-in Server
```bash
cd pelaporanlansia
php -S localhost:8000
```
Buka `http://localhost:8000/login.php`

## 🔐 Akun Demo

| Username | Password | Role |
|----------|----------|------|
| `kepala_puskesmas` | `password` | super_admin |
| `admin` | `password` | admin |
| `petugas1` | `password` | admin |
| `dokter1` | `password` | admin |

## 🔧 API Endpoints

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/api/login` | Public | Autentikasi |
| `GET` | `/api/lansia` | Public | Cari lansia |
| `POST` | `/api/lansia` | admin | Tambah lansia |
| `PUT` | `/api/lansia/{id}` | admin | Update lansia |
| `DELETE` | `/api/lansia/{id}` | admin | Hapus lansia |
| `GET` | `/api/visits` | Public | Data kunjungan |
| `POST` | `/api/visits` | admin | Tambah kunjungan |
| `GET` | `/api/dashboard` | Public | Statistik dashboard |
| `GET` | `/api/villages` | Public | Data desa |
| `GET` | `/api/riwayat/{id}` | Public | Riwayat lansia |
| `GET` | `/api/laporan` | super_admin | Data laporan |
| `GET` | `/api/users` | Public | Data user |
| `POST` | `/api/users` | super_admin | Tambah user |
| `PUT` | `/api/users/{id}` | super_admin | Update user |
| `DELETE` | `/api/users/{id}` | super_admin | Nonaktifkan user |
| `GET` | `/api/puskesmas` | Public | Data puskesmas |
| `POST` | `/api/puskesmas` | super_admin | Tambah puskesmas |
| `PUT` | `/api/puskesmas/{id}` | super_admin | Update puskesmas |
| `DELETE` | `/api/puskesmas/{id}` | super_admin | Hapus puskesmas |
| `GET` | `/api/profile` | Token | Profil sendiri |
| `PUT` | `/api/profile` | Token | Update profil |
| `GET` | `/api/activities` | super_admin | Log aktivitas |
| `GET` | `/api/settings` | Public | Pengaturan |
| `PUT` | `/api/settings` | super_admin | Update pengaturan |
| `GET` | `/api/health-classify` | Public | Klasifikasi kesehatan |

## 📝 Catatan Development
- **Prepared Statements**: Aman dari SQL Injection
- **CSRF Protection**: Semua form dilengkapi token
- **Bearer Token Auth**: API menggunakan token 24 jam
- **Error Handling**: Try-catch untuk semua operasi database
- **Role-Based Access**: Setiap halaman dicek role-nya
- **Responsive**: Breakpoints Tailwind (sm/md/lg/xl)
- **GitHub Actions**: CI otomatis (lint PHP + test database)

---

**Dibuat oleh Kelompok 1:**
- Nur Istiqlaliyah (101230045)
- Eka Febriana Ishak (101230061)
- Talita Zada Aqila (101230003)
- Fina Inayatul Maula (101230036)
