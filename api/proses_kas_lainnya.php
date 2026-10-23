<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/KasLainnyaController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$controller = new KasLainnyaController($pdo);
$action = $_POST['action'] ?? '';

if ($action === 'simpan_kas') {
    $result = $controller->simpanKasLainnya($_POST);
    echo json_encode($result);
} else if ($action === 'hapus_kas') {
    $id = $_POST['id'] ?? 0;
    $result = $controller->hapusKasLainnya($id);
    echo json_encode($result);
} else {
    echo json_encode(['status' => false, 'message' => 'Aksi tidak valid!']);
}