<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/VendorController.php';
require_once __DIR__ . '/../config/guard_api.php';

header('Content-Type: application/json');

$vendor_id = $_GET['vendor_id'] ?? 0;
$controller = new VendorController($pdo);

if ($vendor_id) {
    $produkList = $controller->getProdukByVendor($vendor_id);
    echo json_encode(['status' => true, 'data' => $produkList]);
} else {
    echo json_encode(['status' => false, 'data' => []]);
}