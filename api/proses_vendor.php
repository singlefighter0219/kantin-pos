<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/VendorController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak diizinkan!']);
    exit();
}

$vendorController = new VendorController($pdo);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'simpan_vendor':
        $result = $vendorController->simpanVendor($_POST);
        echo json_encode($result);
        break;

    default:
        echo json_encode(['status' => false, 'message' => 'Aksi tidak valid!']);
        break;
}