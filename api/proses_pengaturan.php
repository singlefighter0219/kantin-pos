<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SettingController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => false, 'message' => 'Akses ditolak!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak diizinkan!']);
    exit();
}

$controller = new SettingController($pdo);
$action = $_POST['action'] ?? '';

if ($action === 'hapus_gambar') {
    $paramKey = $_POST['param_key'] ?? '';
    if ($paramKey !== 'favico' && $paramKey !== 'logo') {
        echo json_encode(['status' => false, 'message' => 'Parameter tidak valid!']);
        exit();
    }
    $result = $controller->removeImage($paramKey);
    echo json_encode($result);
    exit();
}

$formType = $_POST['form_type'] ?? '';
$result = $controller->updateSettings($formType, $_POST, $_FILES);

echo json_encode($result);
exit();