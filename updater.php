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

/**
 * Jalankan migrasi database secara berurutan dari folder database/migrations/
 */
function runDatabaseMigrations($pdo) {
    // 1. Buat tabel migrations jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(255) NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Ambil daftar migrasi yang sudah pernah dijalankan
    $stmt = $pdo->query("SELECT migration_name FROM migrations");
    $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // 3. Ambil semua file .sql dari folder database/migrations/
    $migrationDir = __DIR__ . '/database/migrations/';
    if (!is_dir($migrationDir)) {
        return; // Jika folder migrasi belum ada, lewati
    }

    $files = scandir($migrationDir);
    sort($files); // Urutkan secara alfabetis/numerik (misal: 001_..., 002_...)

    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            // Jika file migrasi belum pernah dijalankan
            if (!in_array($file, $executedMigrations)) {
                $sqlPath = $migrationDir . $file;
                $sqlContent = file_get_contents($sqlPath);

                if (trim($sqlContent) !== '') {
                    try {
                        $pdo->beginTransaction();
                        
                        // Eksekusi skrip SQL
                        $pdo->exec($sqlContent);

                        // Catat file yang telah selesai dijalankan
                        $stmtLog = $pdo->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
                        $stmtLog->execute([$file]);

                        $pdo->commit();
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw new Exception("Gagal menjalankan migrasi {$file}: " . $e->getMessage());
                    }
                }
            }
        }
    }
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
        
        // Ekstrak ke folder temporary khusus
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
                
                // Normalisasi path separator untuk pengecekan lintas platform
                $normalizedSubPath = str_replace('\\', '/', $subPath);
                
                // Daftar file yang dikecualikan agar tidak tertimpa saat update
                $excludedFiles = [
                    'config/database.php',
                    'config/installed.lock'
                ];

                if (in_array($normalizedSubPath, $excludedFiles)) {
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
        
        // Helper untuk menghapus folder temporary secara total
        function deleteDir($dir) {
            if (!file_exists($dir)) return;
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? deleteDir($path) : unlink($path);
            }
            rmdir($dir);
        }
        
        // Bersihkan folder temp ekstraksi & ZIP
        deleteDir($tempExtractDir);
        @unlink($tmpZipPath);

        try {
            // 3. Jalankan Sequential Migration Database secara otomatis
            runDatabaseMigrations($pdo);

            // 4. Perbarui Versi Aplikasi di Database Lokal Klien
            $stmt = $pdo->prepare("UPDATE pengaturan SET param_value = ? WHERE param_key = 'app_version'");
            $stmt->execute([$newVersion]);

            echo json_encode(['status' => 'success', 'message' => 'Aplikasi dan database berhasil diperbarui ke versi ' . $newVersion]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Pembaruan berkas berhasil, tetapi gagal pada migrasi database: ' . $e->getMessage()]);
        }

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