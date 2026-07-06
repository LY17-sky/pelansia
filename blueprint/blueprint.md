# Sistem Pelaporan Kunjungan Harian Lansia Puskesmas — Blueprint

> **Dokumentasi lengkap sistem:** arsitektur, database, API, frontend, integrasi, workflow, role & permission, dan setup.

---

## Daftar Isi

1. [System Overview](#1-system-overview)
2. [Tech Stack & Architecture](#2-tech-stack--architecture)
3. [Database Schema](#3-database-schema)
4. [API Reference](#4-api-reference)
5. [Backend Logic](#5-backend-logic)
6. [Frontend](#6-frontend)
7. [Integration Patterns](#7-integration-patterns)
8. [User Workflows](#8-user-workflows)
9. [Roles & Permissions](#9-roles--permissions)
10. [Setup & Deployment](#10-setup--deployment)

---

## 1. System Overview

Aplikasi web untuk mengelola data kunjungan kesehatan lansia di Puskesmas.

**Fitur Utama:**
- Manajemen data lansia (CRUD dengan profil kesehatan lengkap)
- Pencatatan kunjungan harian (vital signs, diagnosa, rekomendasi, skrining geriatri)
- Skrining Geriatri Dasar (gangguan penglihatan, pendengaran, risiko jatuh, kemandirian, daya ingat)
- Dashboard analytics real-time (total lansia, kunjungan hari ini, status kesehatan, kategori, risiko, trend 7-hari)
- Laporan terstruktur dengan filter & export CSV/Excel/PDF
- Riwayat kunjungan per pasien (timeline) dengan detail skrining geriatri
- Manajemen user dengan role-based access control
- Audit trail (activity logging)
- Klasifikasi kesehatan otomatis berdasarkan parameter vital

**Target Users:**
- **Super Admin** (Kepala Puskesmas) — konfigurasi sistem, manajemen user, akses laporan & aktivitas, **read-only data lansia**
- **Admin** — input kunjungan, **CRUD data lansia**, input kunjungan harian

> **Catatan:** Saat ini hanya ada 2 role aktual (`super_admin` dan `admin`). Role `petugas` dan `dokter` belum diimplementasikan di sistem.

---

## 2. Tech Stack & Architecture

### Tech Stack

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| Backend | PHP | 7.4+ (Apache/Nginx) |
| Frontend | React | 18.3.x (JSX) |
| Build Tool | Vite | ^6.3.5 |
| Styling | Tailwind CSS | ^4.1.12 |
| Database | SQLite 3 | File-based (`lansia.db`) |
| State Management | React Hooks + Context API | - |
| HTTP Client | Fetch API | - |
| Auth | Bearer Token + Session | Dual auth sync (token & session) |
| Routing | react-router | ^7.13.0 |
| Charts | recharts | ^2.15.2 |
| Icons | lucide-react | ^0.487.0 |
| CSS Utility | clsx | ^2.1.1 |

### Arsitektur 3-Tier

```
┌───────────────────────────────────────┐
│   PRESENTATION LAYER                  │
│   React SPA — Pages, Components       │
│   react-router, Recharts, Lucide      │
├───────────────────────────────────────┤
│          ↓ HTTP/JSON + Bearer Token    │
├───────────────────────────────────────┤
│   APPLICATION LAYER                   │
│   PHP REST API — api/index.php        │
│   Legacy PHP Pages (dual auth sync)   │
│   Auth, Business Logic, Validation    │
├───────────────────────────────────────┤
│          ↓ PDO Queries                 │
├───────────────────────────────────────┤
│   DATA LAYER                          │
│   SQLite — 8 Core Tables              │
└───────────────────────────────────────┘
```

### Folder Structure

```
pelaporanlansia/
├── api/index.php              # Main API router (all endpoints)
├── config/database.php        # DB connection (SQLite only)
├── src/
│   ├── pages/                 # 11 React pages
│   ├── components/            # 9 reusable components
│   ├── utils/api.js           # Frontend API client (25+ methods)
│   ├── utils/constants.js     # Constants & helpers (ex-health.js)
│   └── styles/globals.css     # Tailwind directives
├── inc/
│   ├── functions.php          # PHP utility functions (auth + DB)
│   └── layout.php             # PHP layout template
├── data/lansia.db             # SQLite database file (auto-created)
├── database.sql               # MySQL schema (legacy, not actively used)
├── init_db.php                # DB initialization script
├── dist/                      # React SPA build output
├── index.php                  # Frontend entry point (React SPA)
├── router.php                 # PHP dev server router
├── .htaccess                  # Apache rewrite rules
├── vite.config.ts             # Vite build config
├── postcss.config.mjs         # PostCSS configuration
├── package.json               # npm dependencies
├── login.php                  # Legacy PHP login page
├── logout.php                 # Logout handler
├── dashboard.php              # Legacy PHP dashboard
├── lansia.php                 # Legacy PHP lansia CRUD
├── kunjungan.php              # Legacy PHP visit entry + skrining geriatri
├── laporan.php                # Legacy PHP reports + skrining columns
├── detail-lansia.php          # PHP patient detail/history + skrining geriatri detail
├── pengaturan.php             # Account settings
└── setup.php                  # DB setup wizard
```

### Design Patterns

| Pattern | Penerapan |
|---------|-----------|
| **MVC-like** | Model (DB) → Controller (api/index.php) → View (React) |
| **API-First** | Frontend komunikasi via REST API; PHP pages via session |
| **Component-Based** | React komponen reusable dengan props |
| **Singleton** | Database connection dibuat sekali per request |
| **Factory** | Fungsi `respond($data, $status)` untuk response seragam |
| **Middleware** | Token validation (`requireAuth`) sebelum eksekusi endpoint |
| **Soft Delete** | `status_aktif = 'nonaktif'` bukan hard delete |
| **SOLID** | SRP per endpoint, OCP mudah tambah endpoint |

### Key Design Decisions

1. **Dual Auth Sync** — Bearer token (tokens table, 24h expiry) untuk API React, session (`$_SESSION['user']`) untuk PHP pages. Keduanya sinkron: login dari PHP generate token, login dari API set session. PHP pages auto-login via Bearer token header.
2. **API-First** — Frontend React komunikasi via REST API; PHP pages sebagai fallback legacy
3. **Single Database Engine** — SQLite only. Dual engine (SQLite/MySQL) dihapus untuk konsistensi
4. **Role-Based Access** — 2 roles (`super_admin`, `admin`) dengan strict enforcement di route & API
5. **Soft Delete** — `status_aktif` flag untuk audit trail
6. **Health Classification via API** — `GET /api/health-classify` sebagai single source of truth. Frontend panggil API, tidak ada logika duplikat di JS
7. **Timestamp Tracking** — Semua tabel punya `created_at` dan `updated_at`

---

## 3. Database Schema

### Informasi Database

| Property | Value |
|----------|-------|
| Database | **SQLite 3.x** (file-based) |
| Lokasi DB | **`data/lansia.db`** |
| Total Tables | **8** (7 core + `tokens`) |

### ER Diagram

```
puskesmas ──── users       (1:N, FK: id_puskesmas)
puskesmas ──── villages    (1:N, FK: id_puskesmas)
villages  ──── lansia      (1:N, FK: id_desa)
lansia    ──── visits      (1:N, FK: id_lansia)
users     ──── visits      (1:N, FK: id_petugas)
users     ──── activities  (1:N, FK: id_user)
users     ──── tokens      (1:N, FK: user_id)
settings  (key-value store, no FK)
```

### 1. users — User Accounts

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,              -- bcrypt hashed
    nama_lengkap TEXT NOT NULL,
    email TEXT,
    role TEXT NOT NULL DEFAULT 'admin' CHECK(role IN ('super_admin','admin')),
    id_puskesmas INTEGER,               -- FK → puskesmas(id), NULL for super_admin
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','inactive')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_puskesmas) REFERENCES puskesmas(id) ON DELETE SET NULL
);
```

**Sample:** `kepala_puskesmas` (super_admin), `admin` (admin), `petugas1` (admin), `dokter1` (admin)

**Password hash default:** `$2y$12$3rSwJDShGswYPP23FkmE8.i./I0Nl6tc4yWh9nN/JrfRsUCc/CMU.` (plain: `password123`; telah di-reset ke `test123` oleh `temp_superadmin.php` / `temp_reset.php`)

### 2. puskesmas — Health Centers

```sql
CREATE TABLE puskesmas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_puskesmas TEXT NOT NULL,
    alamat TEXT,
    telepon TEXT,
    kode_puskesmas TEXT UNIQUE,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

**Seed:** 1 puskesmas (Puskesmas Utama)

### 3. villages — Villages/Districts

```sql
CREATE TABLE villages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_desa TEXT NOT NULL,
    kode_desa TEXT,
    id_puskesmas INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_puskesmas) REFERENCES puskesmas(id) ON DELETE SET NULL
);
```

**Seed:** **14 desa** (Bligorejo, Doro, Dororejo, Harjosari, Kalimojosari, Kutosari, Larikan, Lemah Abang, Pungangan, Randusari, Rogoselo, Sawangan, Sidoharjo, Wungkal — semuanya id_puskesmas=1)

### 4. lansia — Elderly Patients

```sql
CREATE TABLE lansia (
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
    hubungan_keluarga TEXT,             -- suami/istri/anak/keluarga/lainnya
    no_telepon_keluarga TEXT,
    tanggal_registrasi TEXT DEFAULT CURRENT_DATE,
    status_aktif TEXT DEFAULT 'aktif' CHECK(status_aktif IN ('aktif','nonaktif')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_desa) REFERENCES villages(id) ON DELETE SET NULL
);
```

**Auto-calculated:** `kategori_lansia` dari usia (pra_lansia 45-59, lansia 60-69, lansia_utama 70+)

### 5. visits — Health Visit Records

```sql
CREATE TABLE visits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_lansia INTEGER NOT NULL,
    id_petugas INTEGER NOT NULL,
    tanggal_kunjungan TEXT NOT NULL,
    jam_kunjungan TEXT NOT NULL,
    jenis_kunjungan TEXT DEFAULT 'baru' CHECK(jenis_kunjungan IN ('baru','lama')),
    status_kesehatan TEXT DEFAULT 'sehat' CHECK(status_kesehatan IN ('sehat','sakit_ringan','sakit_berat')),
    -- Vital Signs
    tekanan_darah_sistol INTEGER,
    tekanan_darah_diastol INTEGER,
    berat_badan REAL,
    tinggi_badan REAL,
    imt REAL,                           -- BMI auto-calculated
    nadi INTEGER,
    respiratory_rate INTEGER,
    gula_darah INTEGER,
    kolesterol INTEGER,
    hemoglobin REAL,
    spo2 INTEGER,
    suhu_tubuh REAL,
    -- Clinical Assessment
    status_disabilitas TEXT DEFAULT 'tidak_ada' CHECK(status_disabilitas IN ('tidak_ada','ringan','sedang','berat')),
    kelainan TEXT, keluhan TEXT,
    diagnosa TEXT, tindakan TEXT,
    -- Skrining Geriatri Dasar
    gangguan_penglihatan TEXT DEFAULT 'tidak_ada',  -- tidak_ada / ringan / berat
    gangguan_pendengaran TEXT DEFAULT 'tidak_ada',  -- tidak_ada / ringan / berat
    risiko_jatuh TEXT DEFAULT 'rendah',              -- rendah / sedang / tinggi
    status_kemandirian TEXT DEFAULT 'mandiri',        -- mandiri / bantuan_sebagian / tergantung
    gangguan_daya_ingat TEXT DEFAULT 'tidak_ada',     -- tidak_ada / ada
    -- Referral
    rujukan TEXT, tujuan_rujukan TEXT,
    rekomendasi TEXT DEFAULT 'pemeriksaan_biasa',
    obat TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_lansia) REFERENCES lansia(id) ON DELETE CASCADE,
    FOREIGN KEY (id_petugas) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_visits_lansia ON visits(id_lansia);
CREATE INDEX idx_visits_petugas ON visits(id_petugas);
CREATE INDEX idx_visits_tanggal ON visits(tanggal_kunjungan);
CREATE INDEX idx_visits_status ON visits(status_kesehatan);
```

### 6. activities — Audit Log

```sql
CREATE TABLE activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_user INTEGER NOT NULL,
    aktivitas TEXT NOT NULL,             -- 'login','create_visit',etc
    deskripsi TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_activities_user ON activities(id_user);
CREATE INDEX idx_activities_tanggal ON activities(created_at);
```

**Logged Activities:** login, logout, create_lansia, update_lansia, delete_lansia, create_visit, update_visit, delete_visit, create_user, update_user, delete_user

### 7. settings — System Configuration

```sql
CREATE TABLE settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    "key" TEXT NOT NULL UNIQUE,          -- quoted because key is SQL reserved word
    "value" TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
```

**Sample:** `app_name` → "Sistem Pelaporan Lansia", `app_version` → "1.0.0", `total_lansia_target` → "500"

### 8. tokens — Bearer Token Auth

```sql
CREATE TABLE tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,            -- 24 hours from creation
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Enumerations

| Field | Values |
|-------|--------|
| `users.role` | `super_admin`, `admin` |
| `lansia.kategori_lansia` | `pra_lansia` (45-59), `lansia` (60-69), `lansia_utama` (70+) |
| `lansia.status_risiko` | `risiko_rendah`, `risiko_sedang`, `risiko_tinggi` |
| `lansia.status_kesehatan` / `visits.status_kesehatan` | `sehat`, `sakit_ringan`, `sakit_berat` |
| `visits.jenis_kunjungan` | `baru`, `lama` |
| `visits.rekomendasi` | `pemeriksaan_biasa`, `rawat_inap`, `rujuk_rs`, `rawat_jalan` |
| `visits.status_disabilitas` | `tidak_ada`, `ringan`, `sedang`, `berat` |
| `visits.gangguan_penglihatan` | `tidak_ada`, `ringan`, `berat` |
| `visits.gangguan_pendengaran` | `tidak_ada`, `ringan`, `berat` |
| `visits.risiko_jatuh` | `rendah`, `sedang`, `tinggi` |
| `visits.status_kemandirian` | `mandiri`, `bantuan_sebagian`, `tergantung` |
| `visits.gangguan_daya_ingat` | `tidak_ada`, `ada` |
| `users.status` / `lansia.status_aktif` | `active`/`aktif`, `inactive`/`nonaktif` |

### Constraints & Indexes

**Unique Constraints:** `users.username`, `lansia.nik`, `puskesmas.kode_puskesmas`, `settings.key`, `tokens.token`

**Foreign Keys:** ON DELETE SET NULL untuk puskesmas/villages, CASCADE untuk visits/activities/tokens

**Indexes:** `visits(id_lansia, id_petugas, tanggal_kunjungan, status_kesehatan)`, `activities(id_user, created_at)`, `lansia(nik, status_aktif)`, `users(username)`

---

## 4. API Reference

### Base URL

```
Development: http://localhost:8000/api
Production:  http://your-domain.com/api
```

### Authentication

Semua endpoint (kecuali login dan health-classify) memerlukan Bearer token:

```
Authorization: Bearer <token>
```

Token disimpan di `tokens` table dengan expiry 24 jam.

### Response Format

```json
{
    "success": true/false,
    "message": "Optional message",
    "data": { /* endpoint-specific */ }
}
```

### Endpoint Reference

| Method | Path | Auth | Role Required |
|--------|------|------|---------------|
| POST | `/api/login` | ❌ | - |
| **GET** | **`/api/health-classify`** | **❌ (Public)** | **-** |
| GET | `/api/lansia?search=` | ✅ | any |
| POST | `/api/lansia` | ✅ | **`admin`** |
| PUT | `/api/lansia/{id}` | ✅ | **`admin`** |
| DELETE | `/api/lansia/{id}` | ✅ | **`admin`** |
| GET | `/api/visits?start_date=&end_date=` | ✅ | any |
| POST | `/api/visits` | ✅ | **`admin`** |
| GET | `/api/dashboard` | ✅ | any |
| GET | `/api/laporan?start_date=&end_date=&rekomendasi=&status_risiko=&tujuan_rujukan=` | ✅ | **`super_admin`** |
| GET | `/api/riwayat/{id}` | ✅ | any |
| GET | `/api/users` | ✅ | **`super_admin`** |
| POST | `/api/users` | ✅ | **`super_admin`** |
| PUT | `/api/users/{id}` | ✅ | **`super_admin`** |
| DELETE | `/api/users/{id}` | ✅ | **`super_admin`** |
| GET | `/api/puskesmas` | ✅ | any |
| POST | `/api/puskesmas` | ✅ | **`super_admin`** |
| PUT | `/api/puskesmas/{id}` | ✅ | **`super_admin`** |
| DELETE | `/api/puskesmas/{id}` | ✅ | **`super_admin`** |
| GET | `/api/villages` | ✅ | any |
| POST | `/api/villages` | ✅ | **`super_admin`** |
| PUT | `/api/villages/{id}` | ✅ | **`super_admin`** |
| DELETE | `/api/villages/{id}` | ✅ | **`super_admin`** |
| GET | `/api/villages?id_puskesmas=` | ✅ | any |
| GET | `/api/profile` | ✅ | any |
| PUT | `/api/profile` | ✅ | any |
| GET | `/api/activities` | ✅ | **`super_admin`** |
| GET | `/api/settings` | ✅ | any |
| PUT | `/api/settings` | ✅ | **`super_admin`** |

### Endpoint Detail: Login

**POST /api/login**

```json
// Request
{ "username": "petugas1", "password": "password123" }

// Response 200
{
    "success": true,
    "message": "Login berhasil",
    "data": {
        "token": "abc123def456",
        "user": { "id": 3, "username": "admin", "nama_lengkap": "Petugas Puskesmas", "role": "admin", "id_puskesmas": 1 }
    }
}
```

### Endpoint Detail: Health Classify (NEW — Public)

**GET /api/health-classify?usia=70&td_sistol=160&td_diastol=95&imt=25.5&nadi=80&rr=20&gula_darah=120&kolesterol=200&spo2=98&suhu_tubuh=36.5&jenis_kelamin=L**

**Parameters:** usia, td_sistol, td_diastol, imt, nadi, rr, disabilitas, gula_darah, kolesterol, hemoglobin, spo2, suhu_tubuh, jenis_kelamin

**Response:**
```json
{
    "status": "sakit_ringan",
    "status_db": "sakit_ringan",
    "label": "Sakit Ringan",
    "color": "amber",
    "issues": [
        { "parameter": "TD Sistol", "value": 160, "category": "Hipertensi", "severity": "bahaya" },
        { "parameter": "TD Diastol", "value": 95, "category": "Hipertensi", "severity": "waspada" }
    ],
    "recommendation": "Pemantauan tekanan darah rutin. Konsultasi dengan dokter jika keluhan berlanjut."
}
```

**Age-adjusted thresholds:** TD Sistol, TD Diastol, IMT punya threshold berbeda untuk usia <60, 60-69, dan 70+

### Endpoint Detail: Lansia

**GET /api/lansia?search=Ahmad** — List lansia + filter nama/NIK, join dengan villages

**POST /api/lansia** — Required: `nik`, `nama_lengkap`, `tanggal_lahir`, `jenis_kelamin`. Auto-calculate kategori_lansia.

**PUT /api/lansia/{id}** — Update partial fields

**DELETE /api/lansia/{id}** — Soft delete (`status_aktif = 'nonaktif'`)

### Endpoint Detail: Visits

**POST /api/visits** — **Role: `admin` only** (requireRole('admin'))

Required: `id_lansia`, `tanggal_kunjungan`, `jam_kunjungan`. Auto-calculate BMI.

**Skrining Geriatri Dasar fields** (all optional, have defaults):
- `gangguan_penglihatan`: `tidak_ada` / `ringan` / `berat` (default: `tidak_ada`)
- `gangguan_pendengaran`: `tidak_ada` / `ringan` / `berat` (default: `tidak_ada`)
- `risiko_jatuh`: `rendah` / `sedang` / `tinggi` (default: `rendah`)
- `status_kemandirian`: `mandiri` / `bantuan_sebagian` / `tergantung` (default: `mandiri`)
- `gangguan_daya_ingat`: `tidak_ada` / `ada` (default: `tidak_ada`)

### Endpoint Detail: Dashboard

**GET /api/dashboard**

```json
{
    "success": true,
    "data": {
        "totalLansia": 5,
        "kunjunganHariIni": 2,
        "lansiaSakit": 2,
        "chartData": [ /* 7 hari */ ],
        "kategoriData": { "pra_lansia": 2, "lansia": 5, "lansia_utama": 3 },
        "risikoData": { "risiko_rendah": 4, "risiko_sedang": 3, "risiko_tinggi": 3 },
        "rekomendasiData": { "pemeriksaan_biasa": 5, "rawat_jalan": 3, "rujuk_rs": 1, "rawat_inap": 1 },
        "rujukanPoli": { "Poli Umum": 3, "Poli Mata": 1 }
    }
}
```

### Endpoint Detail: Laporan

**GET /api/laporan** — **Role: `super_admin` only**

Response: `summary` + `groupBy_rekomendasi` + `groupBy_risiko` + `detailed_visits`

### Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden (role mismatch) |
| 404 | Not Found |
| 500 | Server Error |

### Common Errors

| Error | Cause |
|-------|-------|
| `"Token diperlukan"` | Missing Authorization header |
| `"Token tidak valid"` | Token expired/tidak ditemukan |
| `"Forbidden"` | Role tidak diizinkan (403) |
| `"Username tidak ditemukan"` | Invalid username |
| `"Password salah"` | Wrong password |
| `"Endpoint tidak ditemukan"` | Wrong URL |

---

## 5. Backend Logic

### Arsitektur Backend

- **File utama:** `api/index.php` (~1000+ baris, monolithic router)
- **Konfigurasi:** `config/database.php` (class `Database` dengan metode `getConnection`, `query`, `exec`, `lastInsertId`)
- **Response format:** JSON
- **Dual auth:** API → Bearer token (tokens table), PHP pages → session
- **CORS:**

```php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
```

### Authentication Functions (API)

```php
function validateToken($conn) { /* Bearer token from tokens table, 24h expiry */ }
function requireAuth($conn)    { /* Returns userId or 401 */ }
function requireRole($role, $conn, $userId = null) { /* Returns userId or 403 */ }
```

### Authentication Functions (PHP Pages — inc/functions.php)

```php
function validateTokenFromDb($token) { /* Validate token, return userId */ }
function loadUserFromToken()         { /* Auto-login PHP pages via Bearer token header */ }
```

### Router Pattern

```php
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Parse endpoint & id from URI
// Switch on $endpoint:
//   case 'login' → handle login
//   case 'health-classify' → public health classification (no auth)
//   case 'lansia' → CRUD lansia
//   case 'visits' → CRUD visits
//   case 'dashboard' → dashboard KPIs
//   ...
//   default → 404
```

### Business Logic Functions

```php
// Age calculation
function hitungUsia($tanggal_lahir) {
    $lahir = new DateTime($tanggal_lahir);
    $sekarang = new DateTime();
    return $sekarang->diff($lahir)->y;
}

// Elderly category from age
function hitungKategoriLansia($tanggal_lahir) {
    $usia = hitungUsia($tanggal_lahir);
    if ($usia >= 70) return 'lansia_utama';
    if ($usia >= 60) return 'lansia';
    return 'pra_lansia';
}

// BMI calculation
$imt = $berat_badan / (($tinggi_badan / 100) ** 2);

// Health classification (age-adjusted thresholds)
// Digunakan oleh endpoint /api/health-classify
```

### Health Classification Thresholds

Age-adjusted untuk TD Sistol, TD Diastol, dan IMT:

| Parameter | Normal | Waspada | Bahaya |
|-----------|--------|---------|--------|
| TD Sistol | bervariasi per usia | pre-hipertensi | hipertensi/hipotensi |
| TD Diastol | bervariasi per usia | pre-hipertensi | hipertensi/hipotensi |
| IMT | bervariasi per usia | overweight | obesitas/underweight |
| Nadi | 60-100 | <60 atau >100 | <50 atau >110 |
| RR | 16-20 | <16 atau >20 | <12 atau >25 |
| Gula Darah | <140 | ≥140 | <70 atau ≥200 |
| Kolesterol | <200 | ≥200 | ≥240 |
| SpO2 | ≥95 | <95 | ≤90 |
| Suhu Tubuh | <37.6 | ≥37.6 | ≥38.5 atau <35 |

### PHP Pages Functions (`inc/functions.php`)

```php
validateTokenFromDb($token)    // Validate Bearer token from DB
loadUserFromToken()            // Auto-login PHP pages via token header
isLoggedIn()                   // Check $_SESSION['user']
redirect($path)                // Redirect to path
getUser()                      // Get current user from session
getUserRole()                  // Get role from session
isSuperAdmin()                 // role === 'super_admin'
isAdminStaff()                 // role === 'admin'
dbQuery($sql, $params)         // Execute query
dbFetch($sql, $params)         // Fetch single row
hitungUsiaPHP($tanggal_lahir)  // Age from date
hitungKategoriLansiaPHP($tanggal_lahir)
getLabelKategoriLansia($kategori)    // Human-readable label
getColorKategoriLansia($kategori)    // Tailwind color class
isRisti($status_risiko)              // risiko_tinggi check
logActivity($user_id, $activity, $description)
```

### PDO Pattern

```php
$stmt = $conn->prepare("SELECT * FROM lansia WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$count = $conn->query("SELECT COUNT(*) FROM lansia")->fetchColumn();
```

---

## 6. Frontend

### Tech Frontend

- React 18.3.x (JSX) + Vite 6.x + Tailwind CSS 4.x + PostCSS
- react-router v7 (BrowserRouter, Routes, Route, useNavigate, useLocation)
- recharts (Dashboard charts), lucide-react (icons), clsx (class utilities)
- Fetch API dengan custom wrapper (`src/utils/api.js`)
- State Management: Context API + Hooks

### Build

```bash
npm run build  # → dist/assets/index-[hash].js + .css
```

### Pages (11 files in `src/pages/`)

| Page | File | Route | Role Required | Fitur Utama |
|------|------|-------|---------------|-------------|
| Login | `Login.jsx` | `/login` | Public | Form login, simpan token |
| Dashboard | `Dashboard.jsx` | `/dashboard` | any auth | KPIs + kategori/risiko/rekomendasi charts |
| Lansia | `Lansia.jsx` | `/lansia` | any auth | List/search, CRUD modal, statistik kategori, filter risiko |
| Kunjungan | `Kunjungan.jsx` | `/kunjungan` | **`admin`** | Form 6 sections, auto-BMI, health classification |
| Laporan | `Laporan.jsx` | `/laporan` | **`super_admin`** | Filter, group by, export **CSV & Excel** |
| RiwayatLansia | `RiwayatLansia.jsx` | `/lansia/riwayat/:id` | any auth | Timeline kunjungan per pasien |
| Users | `Users.jsx` | `/users` | **`super_admin`** | CRUD user, role assignment |
| Puskesmas | `Puskesmas.jsx` | `/puskesmas` | **`super_admin`** | CRUD puskesmas |
| Profile | `Profile.jsx` | `/profile` | any auth | View/edit profile sendiri |
| Settings | `Settings.jsx` | `/settings` | **`super_admin`** | System settings |
| ActivityLog | `ActivityLog.jsx` | `/activities` | **`super_admin`** | Audit trail |

### Routing (react-router v7)

```jsx
<BrowserRouter>
  <Routes>
    <Route path="/login" element={<LoginPage onLogin={handleLogin} />} />
    <Route path="/dashboard" element={user ? <DashboardPage /> : <Navigate to="/login" />} />
    <Route path="/lansia" element={user ? <LansiaPage /> : <Navigate to="/login" />} />
    <Route path="/kunjungan" element={user?.role === 'admin' ? <KunjunganPage /> : <Navigate to="/dashboard" />} />
    <Route path="/laporan" element={user?.role === 'super_admin' ? <LaporanPage /> : <Navigate to="/login" />} />
    <Route path="/lansia/riwayat/:id" element={user ? <RiwayatLansiaPage /> : <Navigate to="/login" />} />
    <Route path="/users" element={user?.role === 'super_admin' ? <UsersPage /> : <Navigate to="/login" />} />
    <Route path="/puskesmas" element={user?.role === 'super_admin' ? <PuskesmasPage /> : <Navigate to="/login" />} />
    <Route path="/profile" element={user ? <ProfilePage /> : <Navigate to="/login" />} />
    <Route path="/activities" element={user?.role === 'super_admin' ? <ActivityLogPage /> : <Navigate to="/login" />} />
    <Route path="/settings" element={user?.role === 'super_admin' ? <SettingsPage /> : <Navigate to="/login" />} />
    <Route path="*" element={user ? <DashboardPage /> : <LoginPage />} />
  </Routes>
</BrowserRouter>
```

### Components (10 files in `src/components/`)

| Component | Props | Fungsi |
|-----------|-------|--------|
| `Navbar` | `user`, `onLogout` | Top nav dengan user info & logout |
| `Sidebar` | `activeMenu`, `onMenuChange` | Menu navigasi, role-based, ada profile + logout |
| `Table` | `columns`, `data`, `onRowClick`, `onEdit`, `onDelete` | Data table dengan actions |
| `Modal` | `isOpen`, `title`, `onClose`, `onSubmit` | Dialog overlay dengan form |
| `Button` | `variant` (primary/secondary/danger/success), `size` | Styled button |
| `Card` | `title`, `value`, `icon` | KPI card container |
| `Toast` | `message`, `type` (success/error/warning/info), `duration` | Notifikasi |
| `ConfirmDialog` | `message`, `onConfirm`, `onCancel` | Konfirmasi hapus |
| `HealthIndicator` | `td_sistol`, `td_diastol`, `imt`, `nadi`, `rr`, `gula_darah`, `kolesterol`, `hemoglobin`, `spo2`, `suhu_tubuh`, `usia`, `jenis_kelamin`, `disabilitas`, `compact`, `status` | Klasifikasi kesehatan dari vital signs (via API); compact mode = badge, full mode = card dengan issues list |

### Utilities (`src/utils/constants.js`)

```javascript
hitungUsia(tanggal_lahir)          // Age from date string
hitungKategoriLansia(usia)         // { key, label, color }
klasifikasiIMT(imt)                // { label, color }

POLI_INTERNAL = ['Poli Umum', 'Poli Gigi', 'Poli KIA', ...]

statusKesehatanMapping = { sehat: { label, color }, sakit_ringan: { ... }, sakit_berat: { ... } }
```

### Health Classification (via API — single source of truth)

```javascript
// Frontend tidak punya logika klasifikasi sendiri.
// Semua lewat API endpoint /api/health-classify
const res = await api.getHealthClassify({ usia, td_sistol, ... });
// res.data → { status, status_db, label, issues[], recommendation }
```

### State Management

**Global (Context API):** Auth (user, token) — `AuthContext`

**Local (useState):** Form inputs, modal visibility, loading states, filter values

### Component Hierarchy

```
App
├── AuthProvider
├── Navbar (user, onLogout)
├── Sidebar (activeMenu, onMenuChange)
└── Routes (react-router)
    ├── /login → LoginPage
    ├── /dashboard → DashboardPage → Card, recharts
    ├── /lansia → LansiaPage → Table, Modal, ConfirmDialog, HealthIndicator
    ├── /kunjungan → KunjunganPage (role: admin) → Button, Card, Toast, HealthIndicator
    ├── /laporan → LaporanPage (role: super_admin) → Table, Button, Card
    ├── /lansia/riwayat/:id → RiwayatLansiaPage → Card, Table, HealthIndicator
    ├── /users → UsersPage (role: super_admin) → Table, Modal, ConfirmDialog
    ├── /puskesmas → PuskesmasPage (role: super_admin) → Table, Modal
    ├── /profile → ProfilePage
    ├── /activities → ActivityLogPage (role: super_admin)
    ├── /settings → SettingsPage (role: super_admin)
    └── Toast (global, via useToast hook)
```

---

## 7. Integration Patterns

### API Client (`src/utils/api.js`)

```javascript
const API_URL = '/api';

async function request(endpoint, options = {}) {
    const token = localStorage.getItem('token');
    const config = {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            ...(token && { Authorization: `Bearer ${token}` }),
            ...options.headers,
        },
    };
    const response = await fetch(`${API_URL}/${endpoint}`, config);
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan');
    return data;
}
```

**API Methods (25+):**
- `login(credentials)` — POST /api/login
- `getHealthClassify(params)` — GET /api/health-classify (public)
- `getLansia(search)`, `createLansia(data)`, `updateLansia(id, data)`, `deleteLansia(id)`
- `getVisits(startDate, endDate)`, `createVisit(data)`
- `getDashboard()` — GET /api/dashboard
- `getLaporan(startDate, endDate, filters)` — GET /api/laporan (super_admin)
- `getRiwayat(id)` — GET /api/riwayat/{id}
- `getUsers()`, `createUser()`, `updateUser()`, `deleteUser()` — super_admin
- `getPuskesmas()`, `createPuskesmas()`, `updatePuskesmas()`, `deletePuskesmas()` — super_admin
- `getVillages()`, `getVillagesByPuskesmas(id)`, `createVillage()`, `updateVillage()`, `deleteVillage()`
- `getProfile()`, `updateProfile()`
- `getActivities()` — super_admin
- `getSettings()` — any, `updateSettings()` — super_admin

### Authentication Flow

**React SPA:**
```
Login → POST /api/login → Backend verifikasi → Generate token (24h expiry)
→ Simpan token di localStorage → Sertakan di Authorization header
→ Setiap request: ambil token dari localStorage → set Bearer header
→ Backend validasi dari tokens table → Logout: hapus token + session
```

**PHP Pages (Session):**
```
login.php → POST → session_start() + generate token → $_SESSION['user'] = {...}
→ Auto-login via token: jika tidak ada session cek Authorization header
→ Logout: session_destroy() + hapus semua token user dari DB
```

### Error Handling Strategy

| HTTP Code | Handling |
|-----------|----------|
| 401 | Token invalid/expired → Redirect ke login |
| 403 | Forbidden (role mismatch) → Toast error |
| 404 | Resource not found → Toast error |
| 500 | Server error → Toast error + console log |

### Data Synchronization

- **Auto-Refresh Dashboard:** Setiap 5 menit (`setInterval`)
- **Pessimistic Update:** Kirim data → tunggu konfirmasi → update state
- **Optimistic Update:** Update UI langsung → kirim ke backend → rollback jika gagal

### CORS

Backend mengizinkan semua origin (`*`) dengan methods GET, POST, PUT, DELETE, OPTIONS. Preflight request (OPTIONS) di-handle dengan return 200.

---

## 8. User Workflows

### Workflow 1: Login

1. User buka aplikasi → cek localStorage token
2. Jika token valid → Dashboard. Jika tidak → `/login`
3. Input username + password → `POST /api/login`
4. Backend verifikasi kredensial → generate token (24h, simpan di `tokens` table)
5. Simpan token di localStorage → redirect ke Dashboard

### Workflow 2: Create Lansia (Role: Admin)

1. Buka `/lansia` → (tombol "Tambah Lansia" hanya untuk admin) → klik "Tambah Lansia"
2. Isi form (NIK 16 digit, nama, tgl lahir, gender, alamat, desa, kontak, hubungan keluarga)
3. Validasi frontend → `POST /api/lansia`
4. Backend cek NIK unik → auto-calculate `kategori_lansia` dari usia
5. Simpan → toast sukses → list refresh

### Workflow 3: Record Kunjungan (Role: Admin)

1. Buka `/kunjungan` → "Tambah Kunjungan"
2. Pilih pasien (dropdown) → detail pasien terisi otomatis
3. Input tanggal, jam, jenis kunjungan, status kesehatan
4. Input vital signs (TD, BB, TB, nadi, RR, suhu, gula darah, kolesterol, hemoglobin, spo2)
5. Auto-calculate BMI: `BB / (TB/100)^2`
6. Klasifikasi kesehatan otomatis dari parameter vital (via `HealthIndicator` component yg panggil `api.getHealthClassify()`)
7. **Input Skrining Geriatri Dasar** (5 dropdown — gangguan penglihatan, pendengaran, risiko jatuh, kemandirian, daya ingat)
8. Input keluhan, diagnosa, tindakan, rujukan (pilih poli dari `POLI_INTERNAL`), rekomendasi, obat
9. `POST /api/visits` (requireRole 'admin') → toast sukses → Dashboard refresh

**Validation ranges:** TD 50-250/30-150, BB 20-200 kg, TB 100-220 cm, Nadi 40-200 bpm, Suhu 35-42°C

### Workflow 4: Generate Laporan (Role: Super Admin)

1. Buka `/laporan` → pilih date range + filter
2. `GET /api/laporan` (requireRole 'super_admin')
3. Tampilkan: summary cards + group-by tables + detailed visits
4. Export **CSV** atau **Excel**

### Workflow 5: Create User (Role: Super Admin)

1. Buka `/users` → "Tambah User"
2. Isi username, password, nama, email, role (`super_admin`/`admin`), puskesmas
3. `POST /api/users` (requireRole 'super_admin') → backend hash password (bcrypt) → insert
4. User baru bisa login langsung

### Workflow 6: Health Classification

**Via endpoint (public):** `GET /api/health-classify?usia=70&td_sistol=160&...`
- Input: parameter vital + usia + jenis_kelamin
- Output: status (sehat/sakit_ringan/sakit_berat), issues list, recommendation

**Via komponen React:** `HealthIndicator` dengan props parameter vital (internal panggil `api.getHealthClassify()`)
- compact mode → badge warna (hijau/amber/merah)
- full mode → card dengan daftar issues dan rekomendasi

### Workflow 7: Health Risk Assessment

Auto-calculate `kategori_lansia` dari `tanggal_lahir`:
- Usia ≥ 70 → `lansia_utama`
- Usia 60-69 → `lansia`
- Usia 45-59 → `pra_lansia`

---

## 9. Roles & Permissions

### Role Definitions

| Role | DB Value | User | Hak Akses |
|------|----------|------|-----------|
| Super Admin | `super_admin` | Kepala Puskesmas | System config, user mgmt, reports, activities, **read-only lansia** |
| Admin | `admin` | Petugas/Staff | **CRUD lansia**, input kunjungan, dashboard, profile |

### Permission Matrix (Aktual)

| Feature | Super Admin | Admin |
|---------|:-----------:|:-----:|
| **Lansia** | | |
| View/Search | ✅ | ✅ |
| Create | ❌ | ✅ |
| Edit | ❌ | ✅ |
| Delete (soft) | ❌ | ✅ |
| **Visits** | | |
| View all | ✅ | ✅ |
| Create | ✅ | ✅ |
| Edit | ✅ | ✅ |
| Delete | ✅ | ✅ |
| **Laporan** | | |
| View | ✅ | ❌ |
| Generate | ✅ | ❌ |
| Export | ✅ | ❌ |
| **Users** | | |
| CRUD users | ✅ | ❌ |
| **Puskesmas** | ✅ | ❌ |
| **Villages** | ✅ | ❌ |
| **Settings** | ✅ | ❌ |
| **Activities** | ✅ | ❌ |
| **Dashboard** | ✅ | ✅ |
| **Profile** | ✅ (own) | ✅ (own) |

### Route/API Enforcement (Aktual)

| Resource | Frontend Route | Backend API |
|----------|---------------|-------------|
| Dashboard | any auth | any auth |
| Lansia (GET) | any auth | any auth |
| Lansia (POST/PUT/DELETE) | **`admin`** | **requireRole('admin')** |
| Visits (POST) | **`admin`** | **requireRole('admin')** |
| Laporan | **`super_admin`** | **requireRole('super_admin')** |
| Users | **`super_admin`** | **requireRole('super_admin')** |
| Puskesmas CRUD | **`super_admin`** | **requireRole('super_admin')** |
| Villages CRUD | **`super_admin`** | **requireRole('super_admin')** |
| Settings | **`super_admin`** | **requireRole('super_admin')** |
| Activities | **`super_admin`** | **requireRole('super_admin')** |
| Profile | any auth | any auth |

### Default Credentials

| Username | Role | Password |
|----------|------|----------|
| `kepala_puskesmas` | super_admin | `test123` |
| `admin` | admin | `test123` |
| `petugas1` | admin | `test123` |
| `dokter1` | admin | `test123` |

### Backend Enforcement

```php
function requireAuth($conn)           // Validates Bearer token → returns userId or 401
function requireRole($role, $conn)    // Strict role check → returns userId or 403
```

### Menu by Role

**Super Admin:** Dashboard, Lansia, Kunjungan, Laporan, Riwayat Lansia, Users, Puskesmas, Settings, Activity Log, Profile, Logout

**Admin:** Dashboard, Lansia, Kunjungan, Riwayat Lansia, Profile, Logout

---

## 10. Setup & Deployment

### System Requirements

| Komponen | Versi |
|----------|-------|
| PHP | 7.4+ (dengan PDO) |
| Node.js | 14.x+ |
| npm | 6.x+ |
| Database | **SQLite 3** |
| Web Server | Apache / Nginx |

### Development Setup

```bash
# 1. Clone project
cd c:\xampp\htdocs
git clone <repo> pelaporanlansia

# 2. Install frontend dependencies
cd pelaporanlansia
npm install

# 3. Start development servers (2 terminals)
npm run dev         # Frontend: http://localhost:5173 (proxy /api → localhost)
php -S localhost:8000    # Backend API: http://localhost:8000/api

# 4. Login via browser
# Username: admin / Password: test123
# Atau via PHP page: http://localhost:8000/login.php
```

> Database SQLite (`data/lansia.db`) auto-created saat pertama kali akses API/PHP page. Jika ingin seed ulang, hapus file `.db` dan akses kembali.

### Production Deployment (Nginx)

```bash
npm run build

# /etc/nginx/sites-available/pelaporanlansia
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/pelaporanlansia;
    index index.html index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    location /api/ { try_files $uri /api/index.php?$query_string; }
    location / { try_files $uri /index.html; }
}
```

### Configuration (`config/database.php`)

Koneksi database hanya SQLite. Tidak ada konfigurasi MySQL.

```php
class Database {
    public function getConnection() {
        // SQLite file: __DIR__ . '/../data/lansia.db'
        // Auto-create jika belum ada
        // Auto-include init_db.php untuk seed data
    }
}
```

### Seed Data

**14 desa** di seed oleh `init_db.php`: Bligorejo, Doro, Dororejo, Harjosari, Kalimojosari, Kutosari, Larikan, Lemah Abang, Pungangan, Randusari, Rogoselo, Sawangan, Sidoharjo, Wungkal

**5 visits** di seed dengan data skrining geriatri:
- Visit 1 (Hipertensi): penglihatan=ringan, pendengaran=tidak_ada, risiko jatuh=rendah, kemandirian=mandiri, daya ingat=tidak_ada
- Visit 4 (PPOK berat): penglihatan=berat, pendengaran=berat, risiko jatuh=tinggi, kemandirian=tergantung, daya ingat=ada
- Visit 5 (DM): penglihatan=ringan, risiko jatuh=sedang, kemandirian=bantuan_sebagian, daya ingat=ada

### Security

1. Gunakan HTTPS (Let's Encrypt via Certbot)
2. Restrict CORS di production: `header("Access-Control-Allow-Origin: https://yourdomain.com")`
3. **Ganti default password** segera setelah install
4. PHP `display_errors = off`, `log_errors = on`
5. Gunakan PDO prepared statements
6. Password bcrypt (`password_hash` / `password_verify`)

### Troubleshooting

| Issue | Solution |
|-------|----------|
| "Database connection failed" | Cek `data/lansia.db` ada, jalankan `php init_db.php` |
| Read-only banner jarak ke tabel terlalu besar (super_admin) | `lansia.php` dulu punya orphan `</div>` di luar `if (!isSuperAdmin())`. Fix: pindahkan `</div>` ke dalam blok `if (!isSuperAdmin())` (swap baris 448-449) |
| CORS error | Pastikan CORS headers terkirim, handle OPTIONS |
| "Login failed" | Cek users: `php -r "echo password_verify('password123', \$hash);"` |
| npm error | `npm install --legacy-peer-deps` |
| Frontend 404 | Akses `http://localhost:5173/` bukan `index.html` |
| Build fails | Cek Node.js ≥14.x, `rm -rf node_modules && npm install` |

---

> **Dokumen ini adalah gabungan ringkas dari seluruh dokumentasi sistem, diperbarui sesuai kode aktual per 4 Juni 2026.**
>
> **Last Updated:** 2026-06-04 | **Status:** Complete (Synced with actual codebase after refactoring)
