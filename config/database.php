<?php
// Mengatur zona waktu default PHP ke Waktu Indonesia Barat (GMT+7)
date_default_timezone_set('Asia/Jakarta');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projectFolder = str_replace('\\', '/', dirname(__DIR__));
$basePath = str_replace($docRoot, '', $projectFolder);

define('BASE_URL', $protocol . $host . $basePath . '/');

// Cek status instalasi, jika file kunci installed.lock belum ada, arahkan ke install.php
if (!file_exists(__DIR__ . '/installed.lock') && file_exists(__DIR__ . '/../install.php')) {
    header("Location: " . BASE_URL . "install.php");
    exit();
}

// Konfigurasi Database PDO
$db_host = 'localhost';
$db_name = 'kantin_db';
$db_user = 'root';
$db_pass = '(*guitar#)';
$charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    if (!empty($db_host) && !empty($db_name)) {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        $pdo->exec("SET time_zone = '+07:00'");
    }
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>