<?php
require_once __DIR__ . '/config.php';

function checkPageAccess($pdo) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Cek Login
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login");
        exit();
    }

    $currentRole = $_SESSION['role'] ?? 'guest';
    
    // 2. Deteksi nama halaman/file yang sedang diakses saat ini
    $currentScript = basename($_SERVER['SCRIPT_NAME'], '.php'); // contoh: 'users', 'laporan_keuangan'

    // 3. Cek apakah halaman tersebut terdaftar di ACL
    global $accessControlList;
    if (isset($accessControlList[$currentScript])) {
        $allowedRoles = $accessControlList[$currentScript];

        // Jika role user saat ini tidak ada dalam daftar yang diizinkan, tolak!
        if (!in_array($currentRole, $allowedRoles)) {
            http_response_code(403);
            require_once __DIR__ . '/../includes/header.php';
            echo '<div class="container-fluid py-5 text-center">
                    <div class="alert alert-danger shadow-sm mx-auto" style="max-width: 600px;">
                        <h5><i class="fa-solid fa-triangle-exclamation me-2"></i>Akses Ditolak!</h5>
                        <p class="mb-0">Maaf, role <strong>' . htmlspecialchars($currentRole) . '</strong> tidak memiliki izin untuk mengakses halaman ini.</p>
                    </div>
                  </div>';
            require_once __DIR__ . '/../includes/footer.php';
            exit();
        }
    }
}