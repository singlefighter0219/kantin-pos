<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PosController.php';
require_once __DIR__ . '/../config/guard_api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$controller = new PosController($pdo);
$user_id = $_SESSION['user_id'] ?? 0;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($user_id <= 0) {
    echo json_encode(['status' => false, 'message' => 'Sesi login habis, silahkan login ulang.']);
    exit();
}

if ($action === 'get_cart') {
    $cart = $controller->getKeranjang($user_id);
    echo json_encode(['status' => true, 'data' => $cart]);
    exit();
} 
else if ($action === 'add_cart') {
    $produk_id = (int)($_POST['produk_id'] ?? 0);
    $res = $controller->tambahKeranjang($user_id, $produk_id);
    echo json_encode($res);
    exit();
} 
else if ($action === 'update_qty') {
    $keranjang_id = (int)($_POST['keranjang_id'] ?? 0);
    $delta = (int)($_POST['delta'] ?? 0);
    $res = $controller->updateQtyKeranjang($user_id, $keranjang_id, $delta);
    echo json_encode($res);
    exit();
} 
else if ($action === 'remove_item') {
    $keranjang_id = (int)($_POST['keranjang_id'] ?? 0);
    $res = $controller->hapusItemKeranjang($user_id, $keranjang_id);
    echo json_encode($res);
    exit();
} 
else if ($action === 'checkout') {
    $res = $controller->prosesCheckout($user_id, $_POST);
    echo json_encode($res);
    exit();
}
else if ($action === 'set_qty') {
    $keranjang_id = (int)($_POST['keranjang_id'] ?? 0);
    $qty_baru = (int)($_POST['qty'] ?? 1);
    $res = $controller->setQtyKeranjang($user_id, $keranjang_id, $qty_baru);
    echo json_encode($res);
    exit();
}
else {
    echo json_encode(['status' => false, 'message' => 'Aksi POS tidak valid!']);
    exit();
}