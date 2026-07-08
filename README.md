# PELANSIA — Pelaporan Kunjungan Lansia Puskesmas

Aplikasi web untuk mencatat, memantau, dan melaporkan data kunjungan lansia di puskesmas.

## Status

✅ **100% Fungsional** — siap digunakan di lokal maupun hosting.

## Fitur Utama

| Fitur | Keterangan |
|---|---|
| **Dashboard** | 3 KPI cards (Total Lansia, Kunjungan Hari Ini, Lansia Sakit) — klik buka modal detail. Grafik kunjungan (harian/mingguan/bulanan) + pie chart status kesehatan |
| **Data Lansia** | CRUD + auto-kategorisasi usia (Pra Lansia 45-59, Lansia 60-69, Lansia Tua 70-79, Risiko Tinggi 80+) + stratifikasi risiko |
| **Kunjungan** | Skrining geriatri 5 domain (penglihatan, pendengaran, risiko jatuh, kemandirian, daya ingat), pemeriksaan fisik (TD, BB, TB, IMT, nadi, RR), hasil lab, diagnosa, tindakan, rujukan |
| **Laporan** | Filter tanggal, export PDF (print layout + preview modal), CSV, Excel |
| **Notifikasi** | 6 trigger: lansia_baru, lansia_risti, kunjungan_baru, kesehatan_memburuk, laporan_terkirim. Broadcast ke super_admin |
| **Bantuan** | Modal panduan cepat + arti warna status + info sistem — akses dari gear icon |
| **User Management** | Tambah/edit/nonaktifkan user (super_admin), ganti password sendiri |
| **Puskesmas & Desa** | Kelola data puskesmas dan desa per puskesmas (super_admin) |

## Role

| Fitur | super_admin | admin |
|---|---|---|
| Dashboard & statistik | ✅ | ✅ |
| Data lansia (tambah/edit/hapus) | ❌ | ✅ |
| Data lansia (lihat) | ✅ | ✅ |
| Input kunjungan | ❌ | ✅ |
| Laporan & export PDF/CSV/Excel | ✅ | ❌ |
| Kelola user | ✅ | ❌ |
| Kelola puskesmas & desa | ✅ | ❌ |
| Ganti password sendiri | ✅ | ✅ |

## Teknologi

- **Backend:** PHP 8+ (PDO SQLite)
- **Database:** SQLite (file `data/lansia.db` — auto-init)
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, Chart.js
- **Server:** Apache (`mod_rewrite` untuk routing SPA)

## Instalasi

### Lokal (XAMPP / Laragon)

1. Letakkan folder project di `htdocs/`
2. Buka `http://localhost/pelaporanlansia/login.php`
3. Database dan tabel terbuat otomatis saat pertama diakses

Atau via PHP built-in server:

```bash
cd pelaporanlansia
php -S localhost:8000
```

Buka `http://localhost:8000/login.php`

### Hosting (InfinityFree / serupa)

1. Upload semua file via File Manager / FTP
2. **cPanel → PHP Settings:** pilih PHP 8.2, enable ekstensi `pdo_sqlite` dan `sqlite3`
3. Set permission folder `data/` ke `755` (writable)
4. Akses `https://namasubdomain.infinityfreeapp.com/login.php`
5. Jika muncul error 500:
   - Cek `check.php` untuk diagnosa lingkungan
   - Rename `.htaccess` → `.htaccess.bak` sementara

## Akun Demo

| Username | Password | Role |
|---|---|---|
| `kepala_puskesmas` | `password` | super_admin |
| `admin` | `password` | admin |
| `petugas1` | `password` | admin |
| `dokter1` | `password` | admin |

## Panduan Cepat

### 1. Login
Buka `login.php`, masukkan username & password.

### 2. Dashboard
- **3 kartu statistik** — klik untuk buka modal daftar detail (Total Lansia, Kunjungan Hari Ini, Lansia Sakit)
- **Grafik** — filter harian/mingguan/bulanan
- **Kategori usia** — 4 kartu usia dengan jumlah, klik buka modal daftar

### 3. Data Lansia (role admin)
- **Tambah** — isi form lengkap dengan NIK, nama, tanggal lahir, alamat, dll
- **Edit/Hapus** — dari tabel daftar lansia
- Status kesehatan otomatis dihitung dari kunjungan terakhir

### 4. Input Kunjungan (role admin)
- Pilih lansia dari dropdown
- Isi pemeriksaan: TD, BB, TB, IMT (otomatis), nadi, RR, suhu, SpO2, gula darah, kolesterol, Hb
- Skrining geriatri: penglihatan, pendengaran, risiko jatuh, kemandirian, daya ingat
- Diagnosa, tindakan, obat, rujukan
- Klasifikasi kesehatan (Sehat/Ringan/Berat) otomatis

### 5. Laporan (role super_admin)
- Filter tanggal → lihat data kunjungan
- **Export PDF** — klik → isi nama petugas & kepala puskesmas → preview → cetak/download
- **Export CSV / Excel** — unduh file

### 6. Notifikasi
- Bell icon (🔔) di pojok kanan atas — klik buka dropdown notifikasi
- Notifikasi baru muncul otomatis (6 trigger)
- **Tandai semua dibaca** — dari footer dropdown

### 7. Bantuan
- Gear icon (⚙️) → **Bantuan**
- Berisi: panduan cepat 4 kartu, arti warna status (Sehat/Ringan/Berat/Risti), info sistem

## Keamanan

- **.htaccess** — blokir akses langsung ke `data/`, `*.db`, `*.sql`, `.git/`
- **Prepared Statements** — semua query SQL aman dari SQL Injection
- **Password** — di-hash dengan bcrypt (`password_hash`)
- **Role-Based Access** — setiap halaman cek role sebelum dijalankan
- **Soft-Delete** — data lansia tidak dihapus permanen, hanya dinonaktifkan

---

**Dibuat oleh Kelompok 1:**
- Nur Istiqlaliyah (101230045)
- Eka Febriana Ishak (101230061)
- Talita Zada Aqila (101230003)
- Fina Inayatul Maula (101230036)
