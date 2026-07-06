# Panduan Penggunaan Sistem Pelaporan Lansia (PELANSIA)

## 1. Login
**Akses:** Semua role

1. Buka `login.php` di browser
2. Masukkan username dan password
3. Klik tombol **Login**

Akun demo tersedia (lihat bagian Akun Demo di README).

---

## 2. Dashboard
**Akses:** super_admin, admin

Halaman utama setelah login menampilkan:
- **3 kartu statistik**: Total Lansia, Kunjungan Hari Ini, Lansia Sakit
- **4 kartu kategori usia**: Pra Lansia (45-59), Lansia (60-69), Lansia Tua (70-79), Risiko Tinggi (80+)
- Klik kartu kategori untuk melihat daftar lansia sesuai kategori tersebut
- **Super admin** juga melihat grafik kunjungan (bar/line/doughnut) dan tabel status kesehatan

---

## 3. Manajemen Lansia
**Akses:** admin (CRUD), super_admin (read-only)

### Admin — Menambah Lansia
1. Buka menu **Lansia** di sidebar
2. Klik tombol **Tambah Lansia**
3. Isi **Data Pribadi**: NIK (16 digit), nama lengkap, tempat & tanggal lahir, jenis kelamin, alamat, desa
4. Isi **Data Kepesertaan**: nomor BPJS (opsional)
5. Isi **Data Wali**: nama, hubungan, nomor telepon keluarga
6. Klik **Simpan**

### Admin — Mengedit Lansia
1. Cari lansia dengan mengetik NIK/nama di kolom pencarian
2. Klik ikon pensil pada baris lansia yang akan diedit
3. Ubah data yang diperlukan
4. Klik **Simpan**

### Admin — Menghapus Lansia
1. Klik ikon tong sampah pada baris lansia
2. Konfirmasi penghapusan (data menjadi nonaktif, tidak hilang permanen)

### Super Admin
- Hanya bisa melihat daftar lansia (tidak ada tombol tambah/edit/hapus)

---

## 4. Kunjungan
**Akses:** admin

1. Buka menu **Kunjungan** di sidebar
2. Pilih lansia dari daftar yang tersedia
3. Isi **Data Kunjungan**: tanggal, jam, jenis kunjungan (baru/lama)
4. Isi **Hasil Pemeriksaan**:
   - Tekanan darah (sistol/diastol)
   - Berat badan, tinggi badan (IMT otomatis)
   - Nadi, respiratory rate
   - Status disabilitas
5. Isi **Skrining Geriatri Dasar**:
   - Gangguan penglihatan
   - Gangguan pendengaran
   - Risiko jatuh
   - Status kemandirian
   - Gangguan daya ingat
6. Isi **Hasil Lab** (opsional): gula darah, kolesterol, hemoglobin, SpO2, suhu tubuh
7. Isi **Keluhan, Diagnosa, Tindakan, Obat**
8. Pilih **Rujukan** jika diperlukan (poli tujuan)
9. Klik **Simpan**

---

## 5. Detail Riwayat Lansia
**Akses:** super_admin, admin

1. Dari daftar lansia (menu Lansia), klik tombol **Detail** pada lansia yang dipilih
2. Bagian atas menampilkan profil lengkap lansia (data pribadi, kontak, wali)
3. Bagian bawah menampilkan tabel riwayat kunjungan
4. Klik baris kunjungan untuk melihat detail lengkap:
   - Vital sign, hasil lab, skrining geriatri
   - Diagnosa, tindakan, obat
   - Status klasifikasi kesehatan
5. Lansia dengan risiko tinggi menampilkan banner peringatan merah **RISTI**

---

## 6. Laporan & Export
**Akses:** super_admin

1. Buka menu **Laporan** di sidebar
2. Pilih rentang tanggal (mulai dan selesai)
3. Filter tambahan (opsional): rekomendasi, status risiko
4. Klik tombol export sesuai kebutuhan:
   - **PDF**: pratinjau tampilan cetak → klik Cetak/Download
   - **CSV**: download file .csv (buka di Excel)
   - **Excel**: download file .xls

---

## 7. User Management
**Akses:** super_admin

1. Buka menu **Pengaturan** di sidebar
2. Gulir ke bagian **Kelola User**
3. **Tambah user baru**: klik Tambah → isi username, password, nama lengkap, role (super_admin/admin), puskesmas → Simpan
4. **Edit user**: klik ikon pensil pada baris user
5. **Nonaktifkan user**: klik ikon nonaktif → user tidak bisa login
6. Tabel menampilkan semua user beserta role dan statusnya

---

## 8. Puskesmas & Desa
**Akses:** super_admin

1. Buka menu **Pengaturan** di sidebar
2. **Puskesmas**: tambah/edit/hapus data puskesmas (nama, alamat, telepon, kode)
3. **Desa**: pilih puskesmas → kelola desa (tambah/edit/hapus) yang berada di puskesmas tersebut

---

## 9. Activity Log
**Akses:** super_admin

1. Buka menu **Activity Log** di sidebar
2. Lihat daftar aktivitas semua user (login, logout, input data, dll)
3. Gunakan navigasi halaman untuk melihat data lebih lama

---

## 10. Pengaturan Sistem
**Akses:** super_admin

1. Buka menu **Pengaturan** di sidebar
2. Gulir ke bagian **Pengaturan Sistem**
3. Ubah nilai yang diperlukan: nama aplikasi, target jumlah lansia
4. Klik **Simpan**

---

## 11. Profil & Ganti Password
**Akses:** super_admin, admin

1. Buka menu **Pengaturan** di sidebar
2. Gulir ke bagian **Profil**
3. Ubah username, nama lengkap, atau email (opsional)
4. Untuk ganti password:
   - Masukkan password lama
   - Masukkan password baru
   - Konfirmasi password baru
5. Klik **Simpan**

---

## Catatan Umum
- **Database** (SQLite) otomatis dibuat saat pertama kali login — tidak perlu instalasi atau konfigurasi manual
- **Soft-delete**: data lansia yang dihapus hanya menjadi tidak aktif, tidak hilang permanen
- **Dark mode**: klik toggle di pojok kanan atas sidebar untuk beralih tema gelap
- **Logout**: klik nama user di pojok kanan atas → pilih Logout
