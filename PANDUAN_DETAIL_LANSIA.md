# Fitur Detail Lansia - Klik Nama untuk Lihat Detail & Riwayat

## Deskripsi Fitur

Sekarang Anda dapat mengklik nama lansia di halaman daftar untuk langsung masuk ke halaman detail yang menampilkan:
1. **Data Pribadi Lengkap** - Informasi identitas dan demografi lansia
2. **Riwayat Kunjungan** - Daftar semua kunjungan dengan hasil pemeriksaan
3. **Detail Pemeriksaan** - Hasil lab dan vital sign dari setiap kunjungan

## Cara Menggunakan

### 1. Halaman Daftar Lansia (lansia.php)
- Buka halaman "Data Lansia"
- **Nama lansia sudah menjadi link berwarna biru** (akan berubah saat hover)
- Klik nama lansia untuk membuka halaman detail

### 2. Halaman Detail Lansia (detail-lansia.php)
Halaman detail terbagi menjadi beberapa section:

#### A. Data Pribadi
Menampilkan:
- Nama Lengkap
- NIK (Nomor Identitas)
- Tanggal Lahir
- Usia (dihitung otomatis)
- Jenis Kelamin
- Tempat Lahir
- **Kategori Usia** (Pra Lansia/Lansia/Lansia Tua) dengan warna
- **Status Risiko** (Risiko Rendah/Sedang/Tinggi)
- **Badge Risti** (jika status risiko tinggi)
- Status Kesehatan
- Alamat
- Desa/Kelurahan
- No. BPJS

#### B. Informasi Kontak
- No. Telepon Lansia
- No. Telepon Wali/Keluarga

#### C. Data Wali/Keluarga
- Nama Wali
- Hubungan dengan Lansia (Suami/Istri/Anak/Keluarga)

#### D. Riwayat Kunjungan
Menampilkan tabel dengan kolom:
- **Tanggal Kunjungan** - Tanggal dalam format DD/MM/YYYY
- **Jam Kunjungan** - Jam dalam format HH:MM WIB
- **Jenis Kunjungan** - Badge (Baru/Lama)
- **Petugas** - Nama petugas yang melakukan kunjungan
- **Status Kesehatan** - Kondisi kesehatan saat kunjungan
- **TD (Sistol/Diastol)** - Tekanan darah
- **BB/TB** - Berat badan dan tinggi badan
- **IMT** - Indeks Massa Tubuh
- **Diagnosa** - Diagnosis singkat
- **Rekomendasi** - Rekomendasi tindakan lanjutan

### 3. Melihat Detail Kunjungan

Klik pada baris kunjungan untuk **memperluas detail lengkap** yang mencakup:

#### Pemeriksaan Fisik:
- Nadi (bpm)
- Respiratory Rate (x/menit)
- Suhu Tubuh (°C)
- Gula Darah (mg/dL)
- Kolesterol (mg/dL)
- Hemoglobin (g/dL)
- SpO₂ (%)

#### Kondisi Kesehatan:
- Disabilitas (Tidak Ada/Ringan/Sedang/Berat)
- Kelainan
- Keluhan
- Diagnosa
- Tindakan yang diberikan
- Obat yang diberikan

#### Rujukan (jika ada):
- Jenis rujukan
- Tujuan rujukan
- Fasilitas rujukan

## Fitur Interaktif

### Navigasi
- **Tombol Kembali** (↑ Di atas) - Kembali ke daftar lansia
- **Link Nama Lansia** (di daftar) - Warna berubah saat hover
- **Tombol Kembali** (di bawah) - Kembali ke daftar lansia

### Ekspansi Detail Kunjungan
- Klik **baris kunjungan** untuk menampilkan detail lengkap
- Klik lagi untuk menyembunyikan detail
- Setiap kunjungan bisa dibuka/ditutup independently

### Indikator Status
- **Badge Warna** untuk kategori usia
- **Badge Warna** untuk status risiko
- **Badge Warna** untuk jenis kunjungan
- **Badge Warna** untuk rekomendasi tindakan
- **Alert Merah** untuk lansia Risti

## File yang Berubah

### 1. **lansia.php** (Diperbarui)
- Mengubah nama lansia menjadi link
- Link mengarah ke `detail-lansia.php?id=LANSIA_ID`
- Menambahkan CSS styling untuk link
- Hover effect dengan warna biru (#4A90D9)

### 2. **detail-lansia.php** (Baru)
- Halaman detail lansia yang baru
- Menampilkan data pribadi lansia
- Menampilkan riwayat kunjungan
- Menampilkan detail hasil pemeriksaan
- Fitur ekspansi/kolaps detail kunjungan
- Navigasi kembali ke daftar lansia

## Styling dan Warna

### Kategori Usia
- **Pra Lansia** - Badge Biru (#d1ecf1)
- **Lansia** - Badge Ungu (#e8d4f8)
- **Lansia Tua** - Badge Oranye (#fff3cd)

### Status Risiko
- **Risiko Rendah** - Badge Hijau
- **Risiko Sedang** - Badge Kuning/Warning
- **Risiko Tinggi (Risti)** - Badge Merah

### Status Kesehatan
- **Sehat** - Badge Hijau
- **Sakit Ringan** - Badge Kuning
- **Sakit Berat** - Badge Merah

### Jenis Kunjungan
- **Baru** - Badge Biru
- **Lama** - Badge Abu-abu

### Rekomendasi
- **Pemeriksaan Umum** - Badge Biru
- **Rawat Inap** - Badge Merah
- **Rujuk RS** - Badge Oranye
- **Rawat Jalan** - Badge Hijau

## Pengalaman Pengguna

### Saat Melihat Daftar Lansia
```
1. Nama lansia berwarna normal (hitam)
2. Saat hover, berubah menjadi biru dengan garis bawah
3. Klik untuk membuka halaman detail
```

### Saat di Halaman Detail
```
1. Data pribadi terlihat dengan rapi di section atas
2. Indikator Risti (jika ada) ditampilkan di atas
3. Riwayat kunjungan ditampilkan dalam tabel
4. Klik baris untuk melihat detail lengkap
5. Klik lagi untuk menyembunyikan detail
```

## Catatan Penting

1. **Hanya Lansia Aktif** - Halaman detail hanya menampilkan lansia dengan status aktif
2. **Permission** - User harus login untuk melihat halaman detail
3. **Riwayat** - Riwayat kunjungan diurutkan dari yang paling baru
4. **Link Tracking** - Setiap akses ke detail lansia dapat di-track untuk analytics (jika diperlukan)

## Troubleshooting

### Link Tidak Bekerja
- Pastikan URL benar: `detail-lansia.php?id=LANSIA_ID`
- Pastikan file `detail-lansia.php` sudah ada
- Check browser console untuk error

### Data Tidak Tampil
- Pastikan lansia masih aktif (tidak dihapus)
- Pastikan ada koneksi ke database
- Check file `inc/functions.php` untuk helper functions

### Detail Kunjungan Tidak Bisa Dibuka
- Refresh halaman
- Check browser console untuk JavaScript error
- Pastikan JavaScript tidak di-disable

## Pengembangan Lebih Lanjut

Fitur yang dapat dikembangkan:
1. **Export to PDF** - Ekspor data dan riwayat ke PDF
2. **Print Detail** - Cetak halaman detail
3. **Edit dari Detail** - Edit data lansia langsung dari halaman detail
4. **Add Visit** - Tambah kunjungan baru dari halaman detail
5. **Graph/Chart** - Visualisasi trend kesehatan dari riwayat
6. **Compare Visit** - Membandingkan hasil pemeriksaan antar kunjungan
7. **Timeline View** - Tampilan timeline untuk riwayat kunjungan
