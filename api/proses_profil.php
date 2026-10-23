<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Akses ditolak, silakan login ulang!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak diizinkan!']);
    exit();
}

$userController = new UserController($pdo);
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_profil':
        $data = [
            'username'     => strtolower(trim($_POST['username'] ?? '')), // Ubah otomatis jadi huruf kecil agar seragam
            'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
            'tema'         => $_POST['tema'] ?? 'biru-muda'
        ];

        if (empty($data['username']) || empty($data['nama_lengkap'])) {
            echo json_encode(['status' => false, 'message' => 'Username dan nama lengkap wajib diisi!']);
            exit();
        }

        $result = $userController->updateProfilLengkap($userId, $data);
        echo json_encode($result);
        break;

    case 'ganti_password':
        $passwordLama  = $_POST['password_lama'] ?? '';
        $passwordBaru  = $_POST['password_baru'] ?? '';
        $konfirmasiPwd = $_POST['konfirmasi_password'] ?? '';

        if (empty($passwordLama) || empty($passwordBaru) || empty($konfirmasiPwd)) {
            echo json_encode(['status' => false, 'message' => 'Semua kolom password wajib diisi!']);
            exit();
        }

        // Validasi minimal 6 karakter
        if (strlen($passwordBaru) < 6) {
            echo json_encode(['status' => false, 'message' => 'Password baru minimal harus terdiri dari 6 karakter!']);
            exit();
        }

        if ($passwordBaru !== $konfirmasiPwd) {
            echo json_encode(['status' => false, 'message' => 'Konfirmasi password baru tidak cocok!']);
            exit();
        }

        $success = $userController->gantiPassword($userId, $passwordLama, $passwordBaru);
        if ($success) {
            echo json_encode(['status' => true, 'message' => 'Kata sandi berhasil diubah!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Password lama salah atau gagal mengubah password.']);
        }
        break;

    default:
        echo json_encode(['status' => false, 'message' => 'Aksi tidak valid!']);
        break;
}