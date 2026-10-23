<?php
require_once __DIR__ . '/../config/database.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/kantin/');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class UserController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo = $pdo_conn;
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['katasandi'])) {
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['user_theme']    = $user['tema'] ?? 'biru-muda';
            
            return ['status' => true, 'message' => 'Login berhasil!'];
        }

        return ['status' => false, 'message' => 'Username atau kata sandi salah.'];
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([intval($userId)]);
        return $stmt->fetch();
    }

    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }

    // ==========================================
    // FOTO / TAMBAHAN FITUR MANAJEMEN USERS
    // ==========================================

    // Ambil semua daftar user
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM users ORDER BY id ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Tambah User Baru dengan Validasi
    public function tambahUser($data) {
        try {
            // Validasi format username (hanya huruf kecil, angka, titik, dan underscore)
            if (!preg_match('/^[a-z0-9_\.]+$/', $data['username'])) {
                return ['status' => false, 'message' => 'Username hanya boleh berisi huruf kecil, angka, titik, dan underscore tanpa spasi!'];
            }

            // Validasi panjang username (minimal 4, maksimal 20 karakter)
            if (strlen($data['username']) < 4 || strlen($data['username']) > 20) {
                return ['status' => false, 'message' => 'Username harus terdiri dari 4 sampai 20 karakter!'];
            }

            // Larangan kata-kata sensitif / tidak aman
            $blacklist = ['admin', 'administrator', 'root', 'system', 'superuser', 'support'];
            if (in_array(strtolower($data['username']), $blacklist)) {
                return ['status' => false, 'message' => 'Username tersebut tidak diizinkan karena alasan keamanan!'];
            }

            // Validasi minimal panjang password (minimal 6 karakter)
            if (strlen($data['katasandi']) < 6) {
                return ['status' => false, 'message' => 'Kata sandi minimal harus terdiri dari 6 karakter!'];
            }

            $hashedPassword = password_hash($data['katasandi'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (username, nama_lengkap, katasandi, role, tema) VALUES (?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $data['username'],
                $data['nama_lengkap'],
                $hashedPassword,
                $data['role'],
                $data['tema']
            ]);

            return ['status' => $success, 'message' => $success ? 'User baru berhasil ditambahkan!' : 'Gagal menambahkan user (username mungkin sudah digunakan).'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Terjadi kesalahan pada database.'];
        }
    }

    // Edit Data & Role User
    public function updateUser($data) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, role = ?, tema = ? WHERE id = ?");
            return $stmt->execute([
                $data['username'],
                $data['nama_lengkap'],
                $data['role'],
                $data['tema'],
                $data['id']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Reset Password User
    public function resetPassword($id, $passwordBaru) {
        try {
            $hashedPassword = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET katasandi = ? WHERE id = ?");
            return $stmt->execute([$hashedPassword, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Hapus User
    public function hapusUser($id) {
        try {
            // Mencegah penghapusan Administrator Utama (ID 1)
            if ($id == 1) return false;

            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Update Profil Pengguna Sendiri (Nama Lengkap & Tema)
    public function updateProfil($id, $data) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET nama_lengkap = ?, tema = ? WHERE id = ?");
            $success = $stmt->execute([
                $data['nama_lengkap'],
                $data['tema'],
                $id
            ]);
            
            if ($success) {
                // Perbarui sesi agar perubahan langsung terasa tanpa relogin
                $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
                $_SESSION['user_theme']   = $data['tema'];
            }
            return $success;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Ganti Password dengan Verifikasi Password Lama
    public function gantiPassword($id, $passwordLama, $passwordBaru) {
        try {
            // Ambil data password lama dari database
            $stmt = $this->pdo->prepare("SELECT katasandi FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();

            // Verifikasi kecocokan password lama
            if ($user && password_verify($passwordLama, $user['katasandi'])) {
                $hashedPassword = password_hash($passwordBaru, PASSWORD_DEFAULT);
                $updateStmt = $this->pdo->prepare("UPDATE users SET katasandi = ? WHERE id = ?");
                return $updateStmt->execute([$hashedPassword, $id]);
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Cek apakah username sudah digunakan oleh user lain
    public function isUsernameAvailable($username, $currentUserId) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $currentUserId]);
        return $stmt->fetch() === false;
    }

    // Update Profil (Termasuk Username dengan Validasi)
    public function updateProfilLengkap($id, $data) {
        try {
            // Validasi format username (hanya huruf kecil, angka, underscore, titik)
            if (!preg_match('/^[a-z0-9_\.]+$/', $data['username'])) {
                return ['status' => false, 'message' => 'Username hanya boleh berisi huruf kecil, angka, titik, dan underscore tanpa spasi!'];
            }

            // Validasi panjang username (minimal 4, maksimal 20 karakter)
            if (strlen($data['username']) < 4 || strlen($data['username']) > 20) {
                return ['status' => false, 'message' => 'Username harus terdiri dari 4 sampai 20 karakter!'];
            }

            // Larangan kata-kata sensitif / tidak aman
            $blacklist = ['admin', 'administrator', 'root', 'system', 'superuser', 'support'];
            if (in_array(strtolower($data['username']), $blacklist)) {
                return ['status' => false, 'message' => 'Username tersebut tidak diizinkan karena alasan keamanan!'];
            }

            // Cek apakah username sudah dipakai orang lain
            if (!$this->isUsernameAvailable($data['username'], $id)) {
                return ['status' => false, 'message' => 'Username sudah digunakan oleh akun lain!'];
            }

            // Eksekusi update database
            $stmt = $this->pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, tema = ? WHERE id = ?");
            $success = $stmt->execute([
                $data['username'],
                $data['nama_lengkap'],
                $data['tema'],
                $id
            ]);
            
            if ($success) {
                $_SESSION['username']     = $data['username'];
                $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
                $_SESSION['user_theme']   = $data['tema'];
            }

            return ['status' => $success, 'message' => $success ? 'Profil dan username berhasil diperbarui!' : 'Gagal memperbarui profil.'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Terjadi kesalahan pada database.'];
        }
    }
}