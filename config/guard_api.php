<?php
/**
 * Cek apakah file diakses secara langsung melalui URL (bukan via AJAX / Post / Include)
 * atau jika metode request bukan POST (sesuaikan kebutuhan, misal endpoint API khusus POST).
 */
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Jika diakses langsung di browser (bukan lewat AJAX POST)
if (!$isAjax && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL);
    exit();
}
?>