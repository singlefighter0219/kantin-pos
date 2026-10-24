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
        $extractPath = __DIR__ . '/';
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Cek dan pindahkan isi folder pembungkus dari GitHub (misal: kantin-pos-main)
        $possibleFolder = $extractPath . 'kantin-pos-main/';
        if (is_dir($possibleFolder)) {
            $files = scandir($possibleFolder);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    // Pindahkan file/folder keluar, lewati folder config agar database aman
                    if ($file !== 'config') {
                        $sourcePath = $possibleFolder . $file;
                        $destPath = $extractPath . $file;
                        
                        // Jika sudah ada, hapus dulu file tujuan lama agar bisa ditimpa dengan mulus
                        if (is_dir($destPath)) {
                            // Hapus direktori lama jika perlu atau biarkan fungsi rename menimpanya
                        }
                        @rename($sourcePath, $destPath);
                    }
                }
            }
            // Hapus folder kantin-pos-main yang sekarang sudah kosong
            @rmdir($possibleFolder);
        }
        
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