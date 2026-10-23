<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/KasLainnyaController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$kasController = new KasLainnyaController($pdo);

// Ambil parameter filter dari GET (Default: Bulan Ini)
$defaultDari = date('Y-m-01');$defaultSampai = date('Y-m-d');

$dari = $_GET['dari'] ?? $defaultDari;
$sampai = $_GET['sampai'] ?? $defaultSampai;
$keyword = trim($_GET['keyword'] ?? '');
$semuaTanggal = isset($_GET['semua_tanggal']) ? true : false;

// Konfigurasi Pagination (menggunakan parameter 'p')
$limit = 10;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = max($page, 1);$offset = ($page - 1) *$limit;

// Ambil data dengan filter
$totalData = $kasController->countKasFiltered($dari, $sampai,$keyword, $semuaTanggal);$totalPages = ceil($totalData / $limit);
$kasList =$kasController->getKasFiltered($dari,$sampai, $keyword,$semuaTanggal, $limit,$offset);
?>


<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-wallet me-2 text-primary"></i> Modal, Prive & Kas Lainnya
            </h3>
            <p class="text-muted small mb-0">Kelola setoran/penarikan modal pemilik dan kas masuk/keluar insidentil.</p>
        </div>

        <!-- Bungkus tombol aksi di kanan atas -->
        <div class="d-flex gap-2">
            <button type="button" onclick="window.open('pages/cetak_laporan.php?modul=kas_lainnya&dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&keyword=<?= urlencode($keyword) ?>&semua_tanggal=<?= urlencode($semuaTanggal) ? '1' : '0' ?>)', 'CetakKasLainnya', 'width=800,height=600,scrollbars=yes,resizable=yes')" class="btn btn-sm btn-outline-theme rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Cetak Laporan
            </button>
            
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKas" onclick="resetFormKas()">
                <i class="fa-solid fa-plus me-1"></i> Catat Transaksi
            </button>
        </div>

    </div>

    <!-- Kotak Filter & Shortcut Laporan -->
    <div class="card border-1 shadow-sm rounded-3 mb-4" style="background-color: var(--card-bg);">
        <div class="card-body p-3">
            <?php
            $todayFormatted = date('Y-m-d');
            $firstDayMonth = date('Y-m-01');$sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));

            $isHariIni = (!$semuaTanggal && $dari ===$todayFormatted && $sampai ===$todayFormatted);
            $is7Hari = (!$semuaTanggal && $dari ===$sevenDaysAgo && $sampai ===$todayFormatted);
            $isBulanIni = (!$semuaTanggal && $dari ===$firstDayMonth && $sampai ===$todayFormatted);
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
                        <input type="text" name="keyword" class="form-control" placeholder="Keterangan / Kategori / Tipe / Nominal..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                </div>

                <!-- Tombol Filter & Tombol Reset -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-50 rounded-2 shadow-sm text-center text-decoration-none d-flex align-items-center justify-content-center py-2">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-50 rounded-2 shadow-sm text-center text-decoration-none d-flex align-items-center justify-content-center py-2" onClick="window.location.href='kas_lainnya'">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </button>
                </div>

                <!-- Checkbox Opsi Pencarian Seluruh Database -->
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

    <!-- Tampilan Card 1 Kolom per Baris -->
    <div class="row g-3">
        <?php if (empty($kasList)): ?>
            <div class="col-12">
                <div class="card border-1 shadow-sm rounded-3 text-center py-5" style="background-color: var(--card-bg);">
                    <div class="card-body text-muted">
                        <i class="fa-solid fa-file-invoice-dollar fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-0">Tidak ada catatan modal atau kas lainnya yang ditemukan.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($kasList as$k): ?>
                <?php 
                    $isMasuk = ($k['tipe'] === 'masuk');
                    $badgeColor =$isMasuk ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle';
                    $nominalColor =$isMasuk ? 'text-success' : 'text-danger';
                    $sign =$isMasuk ? '+' : '-';
                ?>
                <div class="col-12">
                    <div class="card shadow-sm rounded-3 border-1" style="background-color: var(--card-bg);">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-transparent border border-secondary text-secondary small fw-bold px-2 py-1">#<?= $k['id'] ?></span>
                                    <span class="badge <?= $badgeColor ?> border text-uppercase small">
                                        <?= htmlspecialchars($k['tipe']) ?>
                                    </span>
                                    <span class="badge bg-secondary-subtle text-dark border small">
                                        <?= htmlspecialchars($k['kategori']) ?>
                                    </span>
                                </div>
                                <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($k['tanggal'])) ?></small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center my-2">
                                <div>
                                    <h5 class="fw-bold mb-1" style="color: var(--text-color);">
                                        <?= htmlspecialchars($k['keterangan'] ?: 'Tanpa keterangan') ?>
                                    </h5>
                                </div>
                                <div class="text-end">
                                    <span class="fs-5 fw-bold <?= $nominalColor ?>"><?= $sign ?> Rp <?= number_format($k['nominal'], 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end align-items-center gap-2 mt-3 pt-2 border-top border-secondary border-opacity-10">
                                <button class="btn btn-sm btn-outline-primary" onclick='editKas(<?= json_encode($k) ?>)' title="Edit Transaksi">
                                    <i class="fa-solid fa-pen me-1"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="hapusKas(<?= $k['id'] ?>)" title="Hapus Transaksi">
                                    <i class="fa-solid fa-trash me-1"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination Pintar (Maksimal 5 Nomor Halaman Terlihat) -->
    <?php if ($totalPages > 1): ?>
        <?php 
            $queryParams =$_GET;
            unset($queryParams['p']);$queryString = http_build_query($queryParams);$queryStrPrefix = !empty($queryString) ? '&' .$queryString : '';

            $maxVisible = 5;
            $startPage = max(1,$page - floor($maxVisible / 2));$endPage = $startPage +$maxVisible - 1;

            if ($endPage >$totalPages) {
                $endPage =$totalPages;
                $startPage = max(1, $endPage -$maxVisible + 1);
            }
        ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=1<?= $queryStrPrefix ?>"><i class="fa-solid fa-angles-left"></i></a>
                </li>
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page - 1 ?><?=$queryStrPrefix ?>"><i class="fa-solid fa-angle-left"></i></a>
                </li>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= ($page ==$i) ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?><?= $queryStrPrefix ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >=$totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page + 1 ?><?=$queryStrPrefix ?>"><i class="fa-solid fa-angle-right"></i></a>
                </li>
                <li class="page-item <?= ($page >=$totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $totalPages ?><?= $queryStrPrefix ?>"><i class="fa-solid fa-angles-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- =================================================== -->
<!-- MODAL: FORM TAMBAH / EDIT KAS LAINNYA                -->
<!-- =================================================== -->
<div class="modal fade" id="modalKas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold text-primary d-flex align-items-center gap-2" id="modalKasTitle">
                    <i class="fa-solid fa-wallet"></i> Tambah Kas Lainnya
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formKas">
                <input type="hidden" name="id" id="kas_id">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tipe Arus Kas</label>
                        <select name="tipe" id="tipe" class="form-select">
                            <option value="masuk">Kas Masuk (Pemasukan)</option>
                            <option value="keluar">Kas Keluar (Pengeluaran)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kategori</label>
                        <select name="kategori" id="kategori" class="form-select">
                            <option value="Modal / Prive">Modal / Prive (Penyetoran/Penarikan Pemilik)</option>
                            <option value="Lain-Lain">Lain-Lain (Insidentil / Tak Terduga)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nominal (Rp)</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="nominal" id="nominal" class="form-control fw-bold" placeholder="0" min="1" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Keterangan / Catatan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Contoh: Tambah modal awal kembalian laci / Ambil uang untuk keperluan pribadi" required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm" id="btnSimpanKas">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>