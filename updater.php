<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/guard.php';

if (!function_exists('canAccess')) {
    function canAccess($pageKey) {
        global $accessControlList;
        $currentRole = $_SESSION['role'] ?? 'guest';
        
        // Jika halaman tidak terdaftar di ACL, anggap boleh diakses (seperti Dashboard)
        if (!isset($accessControlList[$pageKey])) {
            return true;
        }
        
        return in_array($currentRole, $accessControlList[$pageKey]);
    }
}

// Pastikan hanya Admin yang bisa mengakses aksi update ini
if (!canAccess('admin')) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'download_and_extract') {
    header('Content-Type: application/json');
    
    $downloadUrl = $_GET['url'] ?? '';
    $newVersion  = $_GET['version'] ?? '';

    if (empty($downloadUrl) || empty($newVersion)) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
        exit;
    }

    $tmpZipPath = __DIR__ . '/tmp_update.zip';

    // 1. Unduh file ZIP dari GitHub menggunakan cURL
    $ch = curl_init($downloadUrl);
    $fp = fopen($tmpZipPath, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'KantinPOS-AutoUpdater');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $success = curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    if (!$success || !file_exists($tmpZipPath)) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunduh file update dari GitHub.']);
        exit;
    }

    // 2. Ekstrak ZIP menggunakan ZipArchive
    $zip = new ZipArchive();
    $res = $zip->open($tmpZipPath);
    
    if ($res === TRUE) {
        // Ekstrak langsung menimpa direktori proyek lokal
        $zip->extractTo(__DIR__ . '/');
        $zip->close();
        
        // Hapus file ZIP sementara setelah selesai
        @unlink($tmpZipPath);

        // 3. Perbarui Versi Aplikasi di Database Lokal Klien
        $stmt = $pdo->prepare("UPDATE pengaturan SET param_value = ? WHERE param_key = 'app_version'");
        $stmt->execute([$newVersion]);

        echo json_encode(['status' => 'success', 'message' => 'Aplikasi berhasil diperbarui ke versi ' . $newVersion]);
    } else {
        if (file_exists($tmpZipPath)) @unlink($tmpZipPath);
        
        echo json_encode([
            'status' => 'error', 
            'message' => 'Gagal membuka file ZIP pembaruan. Kode error: ' . $res
        ]);
    }
    exit;
}
?>