# Kantin POS - (Point of Sale) 🛒

Aplikasi Kasir / Point of Sale berbasis PHP dan MySQL yang dirancang ringan, aman, serta dilengkapi fitur **In-App Updater** untuk pembaruan otomatis dan migrasi database tanpa menghilangkan konfigurasi sensitif.

---

## ⚙️ Persyaratan Sistem (System Requirements)

Sebelum menginstal aplikasi ini, pastikan server lokal Anda (seperti XAMPP) memenuhi spesifikasi berikut:
* **PHP Versi:** 8.0 atau yang lebih baru.
* **Database:** MySQL / MariaDB.
* **Ekstensi PHP yang Wajib Aktif:** `curl`, `zip`, `fileinfo`, `mbstring`.

---

## 🔒 Keamanan Database Lokal (Memberikan Password Root)

Jika database lokal MySQL/MariaDB Anda masih menggunakan password kosong (bawaan *default*), amankan terlebih dahulu menggunakan salah satu metode berikut:

### Opsi A: Melalui GUI phpMyAdmin
1. Buka browser dan akses **`http://localhost/phpmyadmin/`**.
2. Klik tab **User Accounts** (Akun Pengguna) pada menu atas.
3. Cari pengguna `root` dengan host `localhost`, lalu klik **Edit privileges** / **Change password**.
4. Masukkan *password* baru Anda, konfirmasi, lalu klik **Go**.

### Opsi B: Melalui Perintah SQL
Buka tab **SQL** di phpMyAdmin dan jalankan perintah:
```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'password_baru_anda';
FLUSH PRIVILEGES;
```

---

## 🔑 Menampilkan Halaman Login phpMyAdmin

Secara *default*, phpMyAdmin pada XAMPP langsung terbuka tanpa meminta masukan akun. Untuk memunculkan form login resmi (Authentication Prompt):

1. Buka file **`config.inc.php`** di folder phpMyAdmin (contoh: `C:\xampp\phpMyAdmin\config.inc.php`).
2. Cari baris berikut:
   ```php
   $cfg['Servers'][$i]['auth_type'] = 'config';
   ```
3. Ubah nilainya menjadi `'cookie'`:
   ```php
   $cfg['Servers'][$i]['auth_type'] = 'cookie';
   ```
4. Simpan file. Saat Anda mengakses `http://localhost/phpmyadmin/`, halaman login yang meminta *username* dan *password* akan otomatis ditampilkan.

---

## 📥 Panduan Instalasi & Konfigurasi

### 1. Penyesuaian Folder Proyek (`.htaccess`)
* Ekstrak file proyek ke dalam folder server lokal Anda, misalnya `htdocs/kantin/`.
* Jika Anda menggunakan nama folder yang berbeda, buka file `.htaccess` dan sesuaikan *path* `RewriteBase`:
  ```apache
  RewriteBase /kantin/
  ```
  *(Ubah `/kantin/` sesuai nama folder Anda, contoh: `/pos-kantin/`)*

### 2. Ganti Nama File Konfigurasi Database
* Masuk ke folder `config/`.
* Cari file bernama `database_default.php`, lalu ubah nama file (*rename*) tersebut menjadi **`database.php`**.
* Sesuaikan username, password, dan nama database di dalam file `database.php` sesuai dengan pengaturan server lokal Anda pada saat instalasi awal.

### 3. Proses Instalasi Awal (Web Installer)
1. Buka browser dan akses **`http://localhost/nama-folder/`** (sesuaikan `nama-folder` dengan direktori proyek Anda).
2. Untuk akses pertama kali, sistem akan secara otomatis mengalihkan Anda ke halaman instalasi: **`http://localhost/nama-folder/install.php`**.
3. Isi seluruh formulir yang tersedia pada halaman instalasi (seperti konfigurasi database dan akun administrator).
4. Klik tombol **Jalankan Instalasi**.
5. Setelah instalasi selesai dan berhasil, Anda akan otomatis dibawa ke halaman login.
6. Silakan login menggunakan akun admin yang baru saja Anda buat pada formulir instalasi.

---

## 🛠️ Troubleshooting & Kendala Umum

### 1. Error: `Call to undefined function curl_init()` atau `Class 'ZipArchive' not found`
Kendala ini terjadi karena ekstensi `cURL` atau `Zip` pada PHP Anda belum aktif di `php.ini`.

* **Solusi & Cara Aktifkan:**
  1. Buka file konfigurasi PHP utama yaitu **`php.ini`** (contoh: `C:\xampp\php\php.ini`).
  2. Cari baris berikut (`Ctrl + F`):
     ```ini
     ;extension=curl
     ;extension=zip
     ```
  3. Hapus tanda titik koma (`;`) di depan kedua baris tersebut:
     ```ini
     extension=curl
     extension=zip
     ```
  4. Simpan file `php.ini`.
  5. Buka **XAMPP Control Panel**, klik **Stop** pada modul **Apache**, lalu klik **Start** kembali.

### 2. Error 404 / Halaman Tidak Ditemukan pada Menu Tertentu
* Pastikan modul `mod_rewrite` pada Apache sudah aktif.
* Periksa kembali pengaturan `RewriteBase` pada file `.htaccess` agar sesuai dengan nama direktori folder proyek Anda.

---

## 🔒 Catatan Keamanan & Pemeliharaan
* **Pencadangan (*Backup*):** Lakukan pencadangan database secara berkala sebelum menjalankan fitur pembaruan otomatis (*In-App Update*).
* **Izin Folder:** Pastikan folder aplikasi memiliki izin akses tulis (*writable*) yang cukup agar sistem dapat mengunduh dan mengekstrak pembaruan dengan lancar.