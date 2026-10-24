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
        
        // Ekstrak ke dalam folder temporary khusus agar tidak mengotori root folder
        $tempExtractDir = __DIR__ . '/temp_extracted_folder/';
        if (!is_dir($tempExtractDir)) {
            mkdir($tempExtractDir, 0777, true);
        }
        
        $zip->extractTo($tempExtractDir);
        $zip->close();
        
        // Cari folder pembungkus di dalam temp (misal: kantin-pos-main)
        $subFolders = scandir($tempExtractDir);
        $sourceDir = '';
        foreach ($subFolders as $sub) {
            if ($sub !== '.' && $sub !== '..' && is_dir($tempExtractDir . $sub)) {
                $sourceDir = $tempExtractDir . $sub . '/';
                break;
            }
        }
        
        // Jika folder pembungkus ditemukan, salin isinya ke root folder utama
        if ($sourceDir && is_dir($sourceDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $item) {
                $subPath = substr($item->getPathname(), strlen($sourceDir));
                $targetPath = $extractPath . $subPath;
                
                // Lewati folder config agar database lokal klien aman tidak tertimpa
                if (strpos($subPath, 'config') === 0) {
                    continue;
                }
                
                if ($item->isDir()) {
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0777, true);
                    }
                } else {
                    copy($item->getPathname(), $targetPath);
                }
            }
        }
        
        // Fungsi helper untuk menghapus folder temporary secara total
        function deleteDir($dir) {
            if (!file_exists($dir)) return;
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? deleteDir($path) : unlink($path);
            }
            rmdir($dir);
        }
        
        // Bersihkan folder temp ekstraksi
        deleteDir($tempExtractDir);
        
        // Hapus file ZIP sementara
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