<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Metode request tidak diizinkan!']);
    exit;
}

$controller = new ProductController($pdo);
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'simpan_produk':
        $result = $controller->simpanProduk($_POST);
        break;

    case 'proses_restock':
        $result = $controller->prosesRestock($_POST);
        break;

    case 'proses_retur':
        $result = $controller->prosesRetur($_POST);
        break;

    case 'toggle_status':
        $result = $controller->toggleStatus($_POST);
        break;

    default:
        $result = ['status' => false, 'message' => 'Aksi tidak valid!'];
        break;
}

echo json_encode($result);