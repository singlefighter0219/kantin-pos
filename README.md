# Kantin POS - (Point of Sale) 🛒

Aplikasi Kasir / Point of Sale berbasis PHP dan MySQL yang dirancang ringan, aman, serta dilengkapi fitur **In-App Updater** untuk pembaruan otomatis dan migrasi database tanpa menghilangkan konfigurasi sensitif.

---

## ⚙️ Prasyarat Sistem (System Requirements)
Sebelum menginstal aplikasi ini, pastikan server lokal Anda (seperti XAMPP) memenuhi spesifikasi berikut:
* **PHP Versi:** 8.0 atau yang lebih baru.
* **Database:** MySQL / MariaDB.
* **Ekstensi PHP yang Wajib Aktif:** `curl`, `zip`, `fileinfo`, `mbstring`.

---

## 📥 Panduan Instalasi & Konfigurasi

### 1. Penyesuaian Folder Proyek (`.htaccess`)
* Ekstrak file proyek ke dalam folder `kantin` di direktori server lokal Anda (contoh: `htdocs/kantin/`).
* Jika Anda menggunakan nama folder yang berbeda, sesuaikan konfigurasi *path* pada file `.htaccess`:
  
  RewriteBase /kantin/

*(Ubah `/kantin/` menjadi nama folder Anda, misalnya `/pos-kantin/`)*

### 2. Konfigurasi File Database

* Cari file bernama `database_default.php` di dalam folder konfigurasi aplikasi.
* Ganti nama file tersebut menjadi **`database.php`**.
* Sesuaikan username, password, dan nama database di dalamnya sesuai dengan pengaturan server lokal Anda pada saat instalasi awal.

### 3. Keamanan Database Lokal (Memberikan Password Root)

Jika database lokal Anda masih kosong tanpa sandi, ikuti langkah berikut untuk mengamankannya:

1. Buka browser dan akses **`http://localhost/phpmyadmin/`**.
2. Pada menu *tab* atas, klik **User Accounts** (Akun Pengguna).
3. Cari pengguna dengan nama `root` yang memiliki *host* `localhost`, lalu klik **Change password** (Ubah sandi) di sebelah kanannya.
4. Masukkan *password* baru pilihan Anda, ketik ulang untuk konfirmasi, lalu klik **Go** di bagian bawah.
5. Perbarui *password* tersebut ke dalam file `database.php` pada langkah nomor 2 agar aplikasi terhubung kembali.

---

## 🛠️ Troubleshooting & Kendala Umum

### 1. Error: `Call to undefined function curl_init()` atau `Class 'ZipArchive' not found`

Kendala ini terjadi karena ekstensi `cURL` atau `Zip` di PHP Anda belum aktif.

* **Cara Mengaktifkannya:**
1. Buka file konfigurasi PHP utama yaitu **`php.ini`** (biasanya terletak di `C:\xampp\php\php.ini` atau `D:\xampp\php\php.ini`).
2. Gunakan fitur cari (`Ctrl + F`) untuk menemukan baris berikut:

;extension=curl
;extension=zip


3. Hapus tanda titik koma (`;`) di depan kedua baris tersebut sehingga menjadi:

extension=curl
extension=zip

4. Simpan perubahan pada file `php.ini`.
5. Buka **XAMPP Control Panel**, klik **Stop** pada modul **Apache**, lalu klik **Start** kembali untuk memuat ulang sistem.

### 2. Error 404 / Halaman Tidak Ditemukan pada Menu Tertentu

* Pastikan modul `mod_rewrite` pada Apache sudah aktif.
* Periksa kembali pengaturan `RewriteBase` pada file `.htaccess` agar sesuai dengan nama folder direktori proyek Anda.

---

## 🔒 Catatan Keamanan & Pemeliharaan

* **Pencadangan (*Backup*):** Selalu lakukan *backup* database secara berkala melalui phpMyAdmin sebelum Anda menjalankan fitur pembaruan otomatis (*In-App Update*).
* **Izin Folder:** Pastikan folder aplikasi memiliki hak akses tulis (*writable*) yang memadai agar sistem dapat mengunduh dan mengekstrak pembaruan dengan lancar.
