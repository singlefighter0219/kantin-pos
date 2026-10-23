<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/SettingController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login");
    exit();
}

$currentUserId   = $_SESSION['user_id'];
$userController  = new UserController($pdo);
$settingController = new SettingController($pdo);

// Ambil data terbaru langsung dari database agar selalu sinkron
$currentUser     = $userController->getUserById($currentUserId);

if (!$currentUser) {
    // Jika user tiba-tiba dihapus dari database, paksa logout
    session_destroy();
    header("Location: " . BASE_URL . "login");
    exit();
}

// Sinkronkan data sesi secara real-time dengan database
$_SESSION['nama_lengkap'] = $currentUser['nama_lengkap'];
$_SESSION['role']         = $currentUser['role'];
$_SESSION['user_theme']   = $currentUser['tema'] ?? 'biru-muda';

$currentNamaUser = $_SESSION['nama_lengkap'];
$currentRoleUser = $_SESSION['role'];
$userTheme       = $_SESSION['user_theme'];

$appSettings     = $settingController->getAllSettings();
$namaAplikasi    = $appSettings['nama_aplikasi'] ?? 'Kantin POS';
$favicoFile      = $appSettings['favico'] ?? '';
$logoToko        = $appSettings['logo'] ?? '';

// Mendeteksi halaman atau path URL saat ini
$currentUri = $_SERVER['REQUEST_URI'];
$currentScript = basename($_SERVER['SCRIPT_NAME']);

// Fungsi pembantu untuk mengecek apakah link aktif
function isMenuAktif($keywords) {
    global $currentUri, $currentScript;
    foreach ((array)$keywords as $keyword) {
        if (strpos($currentUri, $keyword) !== false || $currentScript === $keyword) {
            return true;
        }
    }
    return false;
}

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
?>
<!DOCTYPE html>
<html lang="id" data-theme="<?= htmlspecialchars($userTheme) ?>">
<head>
<?php include __DIR__ . '/../config/author2.html'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($namaAplikasi) ?></title>

    <?php if (!empty($favicoFile)): ?>
    
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/uploads/<?= htmlspecialchars($favicoFile) ?>">
    
    <?php endif; ?>
    
    <!-- CSS Offline -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css">
</head>
<body>

<!-- Backdrop untuk HP saat sidebar terbuka -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<div class="d-flex w-100">
    <!-- Sidebar Navigation -->
    <div class="sidebar p-3 d-flex flex-column justify-content-between" id="appSidebar">
        <div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="sidebar-title fs-5 fw-bold m-0 text-truncate" title="<?= htmlspecialchars($namaAplikasi) ?>">
                        <?php if (!empty($logoToko)): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/<?= htmlspecialchars($logoToko) ?>" alt="Logo" style="height: 24px; object-fit: contain;" class="me-2">
                        <?php else: ?>
                            <i class="fa-solid fa-utensils me-2"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($namaAplikasi) ?>
                    </h4>
                    <!-- Informasi Nama Sekolah / Instansi di Sidebar -->
                    <?php if (!empty($appSettings['nama_pemilik'])): ?>
                        <small class="text-muted d-block mt-1 ps-1" style="font-size: 10.5px; font-weight: 600;">
                            <i class="fa-solid fa-school me-1 text-primary"></i> <?= htmlspecialchars($appSettings['nama_pemilik']) ?>
                        </small>
                    <?php endif; ?>
                </div>
                <!-- Tombol Close khusus Tampilan HP -->
                <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="toggleSidebar()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Menu Navigasi yang Dikelompokkan & Ditata Rapi -->
            <div class="menu-category">Utama</div>
            <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['index.php', 'dashboard']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a>
            
            <?php if (canAccess('kasir')): ?>
            <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['kasir']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>kasir"><i class="fa-solid fa-cash-register me-2"></i> Kasir (POS)</a>
            <?php endif; ?>

            <!-- Master Data (Hanya tampil jika punya akses ke salah satu menu di bawahnya) -->
            <?php if (canAccess('produk') || canAccess('vendor')): ?>
                <div class="menu-category">Master Data</div>
                <?php if (canAccess('produk')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['produk']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>produk"><i class="fa-solid fa-box me-2"></i> Master Produk</a>
                <?php endif; ?>
                <?php if (canAccess('vendor')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['vendor']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>vendor"><i class="fa-solid fa-users me-2"></i> Vendor Konsinyasi</a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Riwayat & Transaksi -->
            <?php if (canAccess('riwayat_penjualan') || canAccess('riwayat_restock_sendiri') || canAccess('riwayat_restock_konsinyasi') || canAccess('riwayat_retur_konsinyasi')): ?>
                <div class="menu-category">Riwayat & Transaksi</div>
                <?php if (canAccess('riwayat_penjualan')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['riwayat_penjualan']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>riwayat_penjualan"><i class="fa-solid fa-receipt me-2"></i> Riwayat Penjualan</a>
                <?php endif; ?>
                <?php if (canAccess('riwayat_restock_sendiri')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['riwayat_restock_sendiri']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>riwayat_restock_sendiri"><i class="fa-solid fa-cart-shopping me-2"></i> Restock Sendiri</a>
                <?php endif; ?>
                <?php if (canAccess('riwayat_restock_konsinyasi')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['riwayat_restock_konsinyasi']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>riwayat_restock_konsinyasi"><i class="fa-solid fa-cart-shopping me-2"></i> Restock Konsinyasi</a>
                <?php endif; ?>
                <?php if (canAccess('riwayat_retur_konsinyasi')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['riwayat_retur_konsinyasi']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>riwayat_retur_konsinyasi"><i class="fa-solid fa-rotate-left me-2"></i> Retur & Setoran</a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Keuangan -->
            <?php if (canAccess('beban_operasional') || canAccess('kas_lainnya') || canAccess('laporan_keuangan')): ?>
                <div class="menu-category">Keuangan</div>
                <?php if (canAccess('beban_operasional')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['beban_operasional']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>beban_operasional"><i class="fa-solid fa-wallet me-2"></i> Beban Operasional</a>
                <?php endif; ?>
                <?php if (canAccess('kas_lainnya')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['kas_lainnya']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>kas_lainnya"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Modal & Kas Lainnya</a>
                <?php endif; ?>
                <?php if (canAccess('laporan_keuangan')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['laporan_keuangan']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>laporan_keuangan"><i class="fa-solid fa-chart-pie me-2"></i> Laporan Keuangan</a>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Pengaturan & Sistem (Otomatis tersembunyi untuk Kasir karena isinya khusus Admin) -->
            <?php if (canAccess('users') || canAccess('pengaturan')): ?>
                <div class="menu-category">Pengaturan & Sistem</div>
                <?php if (canAccess('users')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['users']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>users"><i class="fa-solid fa-user-gear me-2"></i> Manajemen Users</a>
                <?php endif; ?>
                <?php if (canAccess('pengaturan')): ?>
                <a class="nav-link py-2 px-3 rounded mb-1 <?= isMenuAktif(['pengaturan']) ? 'active fw-bold bg-primary text-white' : '' ?>" href="<?= BASE_URL ?>pengaturan"><i class="fa-solid fa-gears me-2"></i> Pengaturan Kantin</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Bagian Bawah Sidebar (Profil User Dipercantik & Bisa diklik) -->
        <div class="pt-3 border-top border-secondary border-opacity-25 mt-3">
            <a href="<?= BASE_URL ?>profil" class="d-flex align-items-center gap-2 p-2 rounded bg-light border border-secondary border-opacity-10 text-decoration-none shadow-sm transition-hover">
                <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="background-color: var(--primary-color); width: 38px; height: 38px; font-size: 15px;">
                    <?= strtoupper(substr($currentNamaUser, 0, 1)) ?>
                </div>
                <div class="overflow-hidden flex-fill">
                    <div class="fw-bold text-truncate text-dark" style="font-size: 13px; line-height: 1.2;" title="<?= htmlspecialchars($currentNamaUser) ?>">
                        <?= htmlspecialchars($currentNamaUser) ?>
                    </div>
                    <!-- Badge Role Sidebar Dipercantik -->
                    <span class="badge <?= $currentRoleUser === 'admin' ? 'bg-danger text-white' : 'bg-info text-dark' ?> text-capitalize mt-1 px-2 py-1 rounded-pill" style="font-size: 9px; font-weight: 600; letter-spacing: 0.5px;">
                        <?= htmlspecialchars($currentRoleUser) ?>
                    </span>
                </div>
                <i class="fa-solid fa-chevron-right text-muted small me-1"></i>
            </a>
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="flex-fill d-flex flex-column min-vh-100 overflow-hidden">
        
        <!-- Topbar / Header Atas -->
        <nav class="navbar navbar-expand px-3 px-md-4 py-3 shadow-sm" style="background-color: var(--card-bg);">
            <div class="container-fluid px-0">
                <div class="d-flex align-items-center gap-2">
                    <!-- Tombol Hamburger untuk Toggle Sidebar di HP -->
                    <button class="btn btn-sm btn-outline-theme d-lg-none" onclick="toggleSidebar()" type="button">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <span class="navbar-brand mb-0 h6 fw-bold text-truncate" style="color: var(--text-color);">
                        <i class="fa-solid fa-store me-2 text-primary"></i> <?= htmlspecialchars($namaAplikasi) ?>
                    </span>
                </div>
                
                <!-- Menu Kanan Topbar (Profil & Tombol Keluar Dipercantik) -->
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= BASE_URL ?>profil" class="d-flex align-items-center gap-2 d-none d-sm-flex text-decoration-none p-1 rounded hover-bg" title="Buka Halaman Profil">
                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="background-color: var(--primary-color); width: 36px; height: 36px; font-size: 14px;">
                            <?= strtoupper(substr($currentNamaUser, 0, 1)) ?>
                        </div>
                        <div class="text-start">
                            <div class="fw-bold lh-1 mb-1 text-primary" style="font-size: 13px;"><?= htmlspecialchars($currentNamaUser) ?></div>
                            <!-- Badge Role Topbar Dipercantik -->
                            <span class="badge <?= $currentRoleUser === 'admin' ? 'bg-danger text-white' : 'bg-info text-dark' ?> text-capitalize px-2 py-1 rounded-pill" style="font-size: 8.5px; font-weight: 600; letter-spacing: 0.5px;"><?= htmlspecialchars($currentRoleUser) ?></span>
                        </div>
                    </a>
                    
                    <div class="vr mx-1 text-muted d-none d-sm-block opacity-25"></div>

                    <button type="button" id="btn-logout" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 fw-semibold" title="Keluar dari Aplikasi">
                        <i class="fa-solid fa-right-from-bracket"></i> <span class="d-none d-md-inline">Keluar</span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Content Body -->
        <div class="p-3 p-md-4 flex-fill overflow-auto">