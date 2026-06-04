# Fitur Kategori Usia Lansia

## Deskripsi Fitur
Sistem telah diperbarui dengan fitur kategori usia lansia otomatis yang mengelompokkan data lansia berdasarkan rentang usia dan status kesehatan.

## Kategori Usia Lansia

### 1. **Pra Lansia** (45-59 tahun)
- Usia mulai dari 45 hingga 59 tahun
- Kelompok persiapan untuk lansia
- Memerlukan monitoring kesehatan berkala

### 2. **Lansia** (60-69 tahun)
- Usia mulai dari 60 hingga 69 tahun
- Kelompok lansia aktif
- Memerlukan monitoring kesehatan intensif

### 3. **Lansia Tua** (≥70 tahun)
- Usia 70 tahun ke atas
- Kelompok lansia lanjut usia
- Memerlukan monitoring dan dukungan intensif

### 4. **Lansia Risiko Tinggi (Risti)**
- Lansia dengan status risiko tinggi berdasarkan kondisi kesehatan
- Bisa berasal dari kategori usia manapun
- Ditandai dengan badge merah "Risti" di halaman daftar
- Memerlukan penanganan dan monitoring khusus

## Fitur-Fitur yang Diimplementasikan

### 1. **Perhitungan Usia Otomatis**
- Sistem secara otomatis menghitung usia berdasarkan tanggal lahir
- Kategori ditentukan secara real-time saat input/edit data
- Tidak perlu input manual kategori

### 2. **Penentuan Kategori Otomatis**
- Kategori ditentukan berdasarkan perhitungan usia
- Kategori terupdate otomatis setiap tahun
- Tersimpan di database untuk tracking sejarah

### 3. **Status Risiko (Risti)**
- Lapangan "Status Risiko" untuk menandai lansia dengan kondisi khusus
- Opsi: Risiko Rendah, Risiko Sedang, Risiko Tinggi (Risti)
- Ditampilkan dengan warna berbeda untuk identifikasi cepat:
  - **Hijau**: Risiko Rendah
  - **Kuning**: Risiko Sedang
  - **Merah**: Risiko Tinggi (Risti)

### 4. **Tampilan Daftar Lansia yang Diperbarui**
Halaman Data Lansia menampilkan:

#### Statistik Kategori
- **Card Statistik** di bagian atas menampilkan:
  - Jumlah Pra Lansia (45-59 tahun)
  - Jumlah Lansia (60-69 tahun)
  - Jumlah Lansia Tua (≥70 tahun)
  - Jumlah Risti (Risiko Tinggi)
  - Total semua lansia

#### Kolom Tabel
- **Nama**: Nama lansia + badge "Risti" (jika ada)
- **NIK**: Nomor identitas
- **Usia**: Usia dalam tahun (baru)
- **Kategori**: Badge dengan warna:
  - Biru untuk Pra Lansia
  - Ungu untuk Lansia
  - Oranye untuk Lansia Tua
- **Status**: Status risiko dengan warna
- **Alamat**: Alamat tempat tinggal
- **Aksi**: Tombol edit, hapus, riwayat

### 5. **Filter dan Grouping**
- Tombol filter untuk menampilkan kategori tertentu:
  - "Semua" - menampilkan semua data
  - "Pra Lansia" - hanya kategori 45-59 tahun
  - "Lansia" - hanya kategori 60-69 tahun
  - "Lansia Tua" - hanya kategori ≥70 tahun
- Jumlah data ditampilkan di setiap tombol filter
- Highlight baris untuk lansia dengan status Risti

### 6. **Form Input/Edit yang Diperbarui**
Modal form untuk tambah/edit data lansia:

#### Field Baru/Diperbarui:
- **Tanggal Lahir** (Date Input)
- **Kategori Usia** (Read-only, otomatis)
  - Menampilkan kategori berdasarkan tanggal lahir
  - Real-time preview saat input
- **Status Risiko** (Dropdown)
  - Risiko Rendah (default)
  - Risiko Sedang
  - Risiko Tinggi (Risti)
  - **Peringatan**: Saat memilih Risti, tampil pesan:
    > "⚠️ Status Risti akan ditandai untuk lansia dengan kondisi kesehatan tertentu atau keterbatasan signifikan yang memerlukan monitoring intensif."

## Implementasi Teknis

### Backend (PHP)

#### Helper Functions (di `inc/functions.php`)
```php
hitungUsiaPHP($tanggal_lahir)           // Menghitung usia
hitungKategoriLansiaPHP($tanggal_lahir)  // Menentukan kategori
getLabelKategoriLansia($kategori)        // Label kategori
getColorKategoriLansia($kategori)        // Warna kategori
isRisti($status_risiko)                  // Cek jika Risti
```

#### API Endpoints
- `GET /api/lansia` - Mengembalikan data lansia dengan field tambahan:
  - `usia`: Usia dalam tahun
  - `kategori_lansia_nama`: Label kategori
  - `is_risti`: Boolean flag untuk Risti

#### Database
- Tabel `lansia` sudah memiliki fields:
  - `kategori_lansia`: ENUM(pra_lansia, lansia, lansia_utama)
  - `status_risiko`: ENUM(risiko_rendah, risiko_sedang, risiko_tinggi)
  - Diupdate otomatis saat create/update record

### Frontend (React)

#### Komponen Diperbarui
- `Lansia.jsx`: 
  - Statistik kategori
  - Filter buttons
  - Kolom usia
  - Badge Risti
  - Form dengan kategori otomatis

#### State Management
- `categoryFilter`: Filter kategori yang sedang aktif
- `stats`: Objek untuk menghitung jumlah setiap kategori

## Cara Menggunakan

### 1. **Tambah Data Lansia Baru**
1. Klik tombol "Tambah Data" di halaman Data Lansia
2. Isi form dengan data lansia:
   - NIK
   - Nama Lengkap
   - Jenis Kelamin
   - **Tanggal Lahir** (penting untuk kategori otomatis)
   - Status Kesehatan
   - Status Risiko (default: Risiko Rendah)
   - Data lainnya (Alamat, Desa, dll)
3. Lihat "Kategori Usia" yang terupdate otomatis
4. Jika lansia memiliki kondisi khusus, pilih "Risiko Tinggi (Risti)"
5. Klik "Simpan"

### 2. **Edit Data Lansia**
1. Klik icon Pen (Edit) pada baris lansia
2. Ubah data sesuai kebutuhan
3. Kategori akan terupdate otomatis jika tanggal lahir berubah
4. Klik "Simpan Perubahan"

### 3. **Filter Data Berdasarkan Kategori**
1. Di halaman Data Lansia, klik tombol kategori yang ingin dilihat
2. Tabel akan menampilkan hanya data dari kategori tersebut
3. Statistik dan jumlah data akan terupdate

### 4. **Identifikasi Lansia Risti**
- Cari badge merah "Risti" pada nama lansia di tabel
- Atau pilih status "Risiko Tinggi" di filter
- Seluruh baris akan di-highlight dengan latar belakang merah
- Alert warning akan ditampilkan di atas tabel

### 5. **Melihat Detail Kategori**
- Hover atau lihat kolom "Kategori" untuk melihat badge kategori
- Warna menunjukkan kategori:
  - Biru = Pra Lansia (45-59)
  - Ungu = Lansia (60-69)
  - Oranye = Lansia Tua (≥70)

## Validasi Data

### Tanggal Lahir
- Tidak boleh kosong saat membuat/edit data
- Format: YYYY-MM-DD (untuk React) atau DD/MM/YYYY (untuk PHP form)
- Kategori akan otomatis dihitung berdasarkan tanggal

### Status Risiko
- Default: "Risiko Rendah"
- Hanya untuk dokumentasi dan monitoring intensif
- Tidak mengubah kategori usia
- Dapat diubah kapan saja

## Laporan dan Dashboard

Fitur ini terintegrasi dengan:
- Dashboard: Menampilkan statistik jumlah lansia per kategori
- Laporan: Dapat mengelompokkan laporan berdasarkan kategori
- Riwayat Kunjungan: Menampilkan kategori dan status risiko lansia

## Catatan Penting

1. **Perhitungan Usia**: Usia dihitung dari tanggal lahir hingga tanggal hari ini. Kategori akan berubah otomatis ketika lansia memasuki ulang tahun ke kategori berikutnya.

2. **Status Risti**: Berbeda dengan kategori usia. Risti adalah status kesehatan khusus yang memerlukan monitoring intensif, bisa untuk lansia dari kategori usia manapun.

3. **Update Otomatis**: Data kategori tersimpan di database tetapi akan diperbarui saat dilakukan edit, menjamin akurasi.

4. **Pelaporan**: Sistem ini siap untuk membuat laporan berdasarkan kategori usia dan status risiko untuk analisis kesehatan lansia.

## Troubleshooting

### Kategori Tidak Berubah
- Pastikan tanggal lahir sudah benar
- Cek kembali ulang tahun untuk memastikan perhitungan usia

### Status Risti Tidak Muncul
- Pastikan sudah memilih "Risiko Tinggi (Risti)" di form
- Refresh halaman untuk melihat perubahan
- Cek di tabel untuk badge "Risti" di nama lansia

### Filter Tidak Berfungsi
- Refresh halaman
- Pastikan ada data dalam kategori yang dipilih
- Cek koneksi internet

## Pengembangan Lebih Lanjut

Fitur yang dapat dikembangkan:
1. Ekspor data ke Excel berdasarkan kategori
2. Laporan grafik distribusi kategori usia
3. Alert otomatis untuk lansia Risti
4. Integrasi dengan sistem reminder untuk kunjungan berkala
5. Analisis tren kesehatan berdasarkan kategori
