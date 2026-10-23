<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Proteksi: Hanya admin
if (!isset($_SESSION['role'] ) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => false, 'message' => 'Akses ditolak!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak diizinkan!']);
    exit();
}

$userController = new UserController($pdo);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'simpan_user':
        $id = $_POST['id'] ?? '';
        $data = [
            'username'     => strtolower(trim($_POST['username'] ?? '')),
            'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
            'role'         => $_POST['role'] ?? 'kasir',
            'tema'         => $_POST['tema'] ?? 'biru-muda'
        ];

        if (!empty($id)) {
            // Edit User (jika ada logika edit terpisah atau abaikan username)
            $data['id'] = $id;
            $success = $userController->updateUser($data);
            $msg = $success ? 'Data user berhasil diperbarui!' : 'Gagal memperbarui data user.';
            echo json_encode(['status' => $success, 'message' => $msg]);
        } else {
            // Tambah User Baru
            $data['katasandi'] = $_POST['katasandi'] ?? '';
            if (empty($data['katasandi'])) {
                echo json_encode(['status' => false, 'message' => 'Kata sandi wajib diisi untuk user baru!']);
                exit();
            }
            
            // Panggil method yang mengembalikan array status & message
            $result = $userController->tambahUser($data);
            echo json_encode($result);
        }
        break;

    case 'reset_password':
        $id = $_POST['id'] ?? '';
        $passwordBaru = $_POST['password_baru'] ?? '';
        
        if (empty($id) || empty($passwordBaru)) {
            echo json_encode(['status' => false, 'message' => 'Data tidak lengkap!']);
            exit();
        }

        $success = $userController->resetPassword($id, $passwordBaru);
        $msg = $success ? 'Password user berhasil direset!' : 'Gagal mereset password.';
        
        echo json_encode(['status' => $success, 'message' => $msg]);
        break;

    case 'hapus_user':
        $id = $_POST['id'] ?? '';
        
        if ($id == 1) {
            echo json_encode(['status' => false, 'message' => 'Administrator utama (ID 1) tidak dapat dihapus!']);
            exit();
        }

        $success = $userController->hapusUser($id);
        $msg = $success ? 'User berhasil dihapus!' : 'Gagal menghapus user.';
        
        echo json_encode(['status' => $success, 'message' => $msg]);
        break;

    default:
        echo json_encode(['status' => false, 'message' => 'Aksi tidak valid!']);
        break;
}