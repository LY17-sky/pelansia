# Panduan Deploy ke InfinityFree

## 1. Daftar Akun InfinityFree

1. Buka https://www.infinityfree.net
2. Klik **Get Premium Free Hosting**
3. Isi email, password, dan captcha
4. Verifikasi email (cek inbox/spam)
5. Login ke panel InfinityFree

## 2. Buat Hosting Account

1. Klik **Create Account**
2. Isi:
   - **Domain**: pilih subdomain gratis (contoh: `pelaporanlansia`)
   - **Username**: terserah (contoh: `pelaporanlansia`)
   - **Password**: buat password kuat
   - **PHP Version**: pilih **PHP 8.2**
3. Klik **Create**
4. Tunggu beberapa saat hingga akun aktif
5. Klik **Control Panel** (atau **Login to cPanel**)

## 3. Upload File Project

### Via File Manager (Mudah)
1. Di cPanel, buka **File Manager**
2. Masuk ke folder `htdocs`
3. Klik **New Folder** → buat folder `pelaporanlansia`
4. Klik folder `pelaporanlansia` → klik **Upload**
5. Upload file ZIP dari project (atau upload file satu per satu)
6. Setelah upload, klik **Extract** jika pakai ZIP

### Via FTP (FileZilla — Lebih Cepat)
1. Di cPanel, lihat **FTP Accounts** untuk mendapatkan:
   - Host: `ftp.epizy.com` atau similar
   - Username: sesuai akun
   - Password: sesuai akun
2. Buka FileZilla → isi Host, Username, Password, Port 21
3. Konek → masuk folder `htdocs/pelaporanlansia/`
4. Drag & drop semua file project dari komputer ke sana

## 4. Set Permission Folder data/

1. Di File Manager, klik kanan folder `data`
2. Pilih **Change Permissions**
3. Set ke **755** (centang: Owner Read/Write/Exec, Group Read/Exec, Public Read/Exec)
4. Klik OK

## 5. Akses Website

1. Buka browser
2. Buka: `https://pelaporanlansia.infinityfreeapp.com/login.php`
   (ganti `pelaporanlansia` dengan subdomain Anda)
3. Login dengan akun demo:
   - `kepala_puskesmas` / `password` (super_admin)
   - `admin` / `password` (admin)

## 6. Troubleshooting

### Error "500 Internal Server Error"
- Buka cPanel → **htaccess Editor** → pastikan file `.htaccess` terbaca
- Jika masih error, hapus sementara `.htaccess` dan coba akses lagi

### Error "SQLite driver not found"
- Buka cPanel → **PHP Settings**
- Pastikan ekstensi `pdo_sqlite` dan `sqlite3` dicentang (On)

### Halaman putih/blank
- Cek error log: cPanel → **Error Log**
- Pastikan PHP version minimal 8.0

### Login gagal
- Pastikan folder `data/` bisa di-write (permission 755)
- Hapus file `data/lansia.db` jika ada, lalu akses ulang (database akan dibuat ulang otomatis)
