<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../config/guard_api.php';

header('Content-Type: application/json');

$controller = new UserController($pdo);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $res = $controller->login($username, $password);

    if ($res['status']) {
        echo json_encode([
            'status' => 'success',
            'message' => $res['message'],
            'redirect' => BASE_URL
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $res['message']
        ]);
    }
    exit();
} else if ($action === 'logout') {
    $controller->logout();
    echo json_encode(['status' => 'success', 'message' => 'Logout berhasil', 'redirect' => BASE_URL . 'login']);
    exit();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid!']);
    exit();
}