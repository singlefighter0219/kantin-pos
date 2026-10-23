<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PosController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$controller = new PosController($pdo);
$penjualan_id = (int)($_GET['id'] ?? 0);

if ($penjualan_id <= 0) {
    echo json_encode(['status' => false, 'message' => 'ID Transaksi tidak valid']);
    exit();
}

$details = $controller->getDetailPenjualan($penjualan_id);

if (!empty($details)) {
    echo json_encode(['status' => true, 'data' => $details]);
} else {
    echo json_encode(['status' => false, 'message' => 'Detail transaksi tidak ditemukan']);
}