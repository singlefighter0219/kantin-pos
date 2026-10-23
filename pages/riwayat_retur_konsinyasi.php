<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/ProductController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$controller = new ProductController($pdo);

// Ambil parameter filter dari GET (Default: Bulan Ini)
$defaultDari = date('Y-m-01');
$defaultSampai = date('Y-m-d');

$dari = $_GET['dari'] ?? $defaultDari;
$sampai = $_GET['sampai'] ?? $defaultSampai;
$keyword = trim($_GET['keyword'] ?? '');
$semuaTanggal = isset($_GET['semua_tanggal']) ? true : false;

// Konfigurasi Pagination
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Ambil data dan rekap dengan filter
$totalData = $controller->countRiwayatReturKonsinyasiFiltered($dari, $sampai, $keyword, $semuaTanggal);
$totalPages = ceil($totalData / $limit);
$returList = $controller->getRiwayatReturKonsinyasiFiltered($dari, $sampai, $keyword, $semuaTanggal, $limit, $offset);
$rekapData = $controller->getRekapReturKonsinyasiFiltered($dari, $sampai, $keyword, $semuaTanggal);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-rotate-left me-2 text-primary"></i> Riwayat Retur & Setoran Konsinyasi
            </h3>
            <p class="text-muted small mb-0">Daftar rekap penarikan sisa barang, barang terjual, dan setoran wajib vendor.</p>
        </div>

        <div class="d-flex gap-2">
            <button type="button" onclick="window.open('pages/cetak_laporan.php?modul=retur_konsinyasi&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&keyword=<?= urlencode($keyword) ?>&semua_tanggal=<?= urlencode($semuaTanggal) ? '1' : '0' ?>)', 'CetakReturKonsinyasi', 'width=800,height=600,scrollbars=yes,resizable=yes')" class="btn btn-sm btn-outline-theme rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Card Ringkasan Total Berdasarkan Filter -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-success-subtle text-success">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small fw-bold text-uppercase">Total Setoran Dibayar:</span>
                        <h4 class="fw-bold mb-0">Rp <?= number_format($rekapData['total_setoran'], 0, ',', '.') ?></h4>
                    </div>
                    <div>
                        <i class="fa-solid fa-money-bill-wave fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-primary-subtle text-primary">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small fw-bold text-uppercase">Total Barang Terjual:</span>
                        <h4 class="fw-bold mb-0"><?= number_format($rekapData['total_terjual'], 0, ',', '.') ?> Pcs</h4>
                    </div>
                    <div>
                        <i class="fa-solid fa-bag-shopping fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 bg-warning-subtle text-warning-emphasis">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small fw-bold text-uppercase">Total Barang Diretur:</span>
                        <h4 class="fw-bold mb-0"><?= number_format($rekapData['total_retur'], 0, ',', '.') ?> Pcs</h4>
                    </div>
                    <div>
                        <i class="fa-solid fa-box-archive fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kotak Filter & Shortcut Laporan -->
    <div class="card border-1 shadow-sm rounded-3 mb-4" style="background-color: var(--card-bg);">
        <div class="card-body p-3">
            <?php
            $todayFormatted = date('Y-m-d');
            $firstDayMonth = date('Y-m-01');
            $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));

            $isHariIni = (!$semuaTanggal && $dari === $todayFormatted && $sampai === $todayFormatted);
            $is7Hari = (!$semuaTanggal && $dari === $sevenDaysAgo && $sampai === $todayFormatted);
            $isBulanIni = (!$semuaTanggal && $dari === $firstDayMonth && $sampai === $todayFormatted);
            ?>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted mb-2">Shortcut Laporan:</label>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="btn-hari-ini" class="btn btn-sm rounded-pill px-3 btn-shortcut <?= $isHariIni ? 'active-shortcut fw-bold shadow-sm' : '' ?>" onclick="setShortcut('hari_ini')">Hari Ini</button>
                    <button type="button" id="btn-7-hari" class="btn btn-sm rounded-pill px-3 btn-shortcut <?= $is7Hari ? 'active-shortcut fw-bold shadow-sm' : '' ?>" onclick="setShortcut('7_hari')">7 Hari Terakhir</button>
                    <button type="button" id="btn-bulan-ini" class="btn btn-sm rounded-pill px-3 btn-shortcut <?= $isBulanIni ? 'active-shortcut fw-bold shadow-sm' : '' ?>" onclick="setShortcut('bulan_ini')">Bulan Ini</button>
                </div>
            </div>

            <form method="GET" action="" id="form-filter" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" name="dari" id="filter_dari" class="form-control form-control-sm" value="<?= htmlspecialchars($dari) ?>" <?= $semuaTanggal ? 'disabled' : 'required' ?>>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="sampai" id="filter_sampai" class="form-control form-control-sm" value="<?= htmlspecialchars($sampai) ?>" <?= $semuaTanggal ? 'disabled' : 'required' ?>>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Kata Kunci Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                        <input type="text" name="keyword" class="form-control" placeholder="No. Nota / Produk / Vendor..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-50 rounded-2 shadow-sm text-center d-flex align-items-center justify-content-center py-2">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-50 rounded-2 shadow-sm text-center d-flex align-items-center justify-content-center py-2" onClick="window.location.href='riwayat_retur_konsinyasi'">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </button>
                </div>

                <!-- Checkbox Seluruh Database -->
                <div class="col-12 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="semua_tanggal" id="semua_tanggal" value="1" <?= $semuaTanggal ? 'checked' : '' ?> onchange="toggleTanggal(this)">
                        <label class="form-check-label small fw-bold text-muted" for="semua_tanggal">
                            Cari berdasarkan seluruh data di dalam database (abaikan rentang tanggal)
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tampilan Card Daftar Riwayat Retur -->
    <div class="row g-3">
        <?php if (empty($returList)): ?>
            <div class="col-12">
                <div class="card border-1 shadow-sm rounded-3 text-center py-5" style="background-color: var(--card-bg);">
                    <div class="card-body text-muted">
                        <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-0">Tidak ada riwayat retur konsinyasi yang ditemukan.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($returList as $row): ?>
                <div class="col-12">
                    <div class="card shadow-sm rounded-3 border-1" style="background-color: var(--card-bg);">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="badge bg-transparent border border-secondary text-secondary small fw-bold px-2 py-1">#<?= $row['id'] ?></span>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small fw-bold">
                                        <?= htmlspecialchars($row['no_nota_retur']) ?>
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small fw-bold">
                                        Setoran: Rp <?= number_format($row['total_setoran'], 0, ',', '.') ?>
                                    </span>
                                </div>
                                <small class="text-muted text-nowrap"><i class="fa-regular fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($row['tanggal_retur'])) ?></small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center my-2">
                                <div>
                                    <h5 class="fw-bold mb-1" style="color: var(--text-color);">
                                        <?= htmlspecialchars($row['nama_produk'] ?? 'Produk Dihapus') ?>
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        Vendor: <strong class="text-dark"><?= htmlspecialchars($row['nama_vendor'] ?? '-') ?></strong> | 
                                        Stok Masuk Awal: <strong><?= $row['stok_awal'] ?> Pcs</strong> | 
                                        Barang Terjual: <strong class="text-success"><?= $row['qty_terjual'] ?> Pcs</strong> | 
                                        Barang Diretur: <strong class="text-danger"><?= $row['qty_retur'] ?> Pcs</strong> | 
                                        Harga Titip: <strong>Rp <?= number_format($row['harga_titip'], 0, ',', '.') ?></strong>
                                        <?= !empty($row['keterangan']) ? ' | Keterangan: ' . htmlspecialchars($row['keterangan']) : '' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination Pintar -->
    <?php if ($totalPages > 1): ?>
        <?php 
            $queryParams = $_GET;
            unset($queryParams['p']);
            $queryString = http_build_query($queryParams);
            $queryStrPrefix = !empty($queryString) ? '&' . $queryString : '';

            $maxVisible = 5;
            $startPage = max(1, $page - floor($maxVisible / 2));
            $endPage = $startPage + $maxVisible - 1;

            if ($endPage > $totalPages) {
                $endPage = $totalPages;
                $startPage = max(1, $endPage - $maxVisible + 1);
            }
        ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=1<?= $queryStrPrefix ?>"><i class="fa-solid fa-angles-left"></i></a>
                </li>
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page - 1 ?><?= $queryStrPrefix ?>"><i class="fa-solid fa-angle-left"></i></a>
                </li>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?><?= $queryStrPrefix ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page + 1 ?><?= $queryStrPrefix ?>"><i class="fa-solid fa-angle-right"></i></a>
                </li>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $totalPages ?><?= $queryStrPrefix ?>"><i class="fa-solid fa-angles-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>