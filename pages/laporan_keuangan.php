<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/FinanceController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$controller = new FinanceController($pdo);

// Ambil parameter filter dari GET (Default: Bulan Ini)
$defaultDari = date('Y-m-01');
$defaultSampai = date('Y-m-d');

$dari = $_GET['dari'] ?? $defaultDari;
$sampai = $_GET['sampai'] ?? $defaultSampai;
$tab = $_GET['tab'] ?? 'labarugi'; // Default tab: labarugi atau aruskas

// Ambil data laporan
$labaRugi = $controller->getLaporanLabaRugi($dari, $sampai);
$arusKas = $controller->getLaporanArusKas($dari, $sampai);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i> Laporan Keuangan
            </h3>
            <p class="text-muted small mb-0">Analisis Laporan Laba Rugi dan Arus Kas operasional kantin.</p>
        </div>
        <div>
            <button type="button" onclick="window.open('pages/cetak_laporan_keuangan.php?tab=<?= $tab ?>&dari=<?= $dari ?>&sampai=<?= $sampai ?>', '_blank', 'width=850,height=700')" class="btn btn-sm btn-outline-theme rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Kotak Filter & Shortcut Periode -->
    <div class="card border-1 shadow-sm rounded-3 mb-4" style="background-color: var(--card-bg);">
        <div class="card-body p-3">
            <?php
            $todayFormatted = date('Y-m-d');
            $firstDayMonth = date('Y-m-01');
            $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));

            $isHariIni = ($dari === $todayFormatted && $sampai === $todayFormatted);
            $is7Hari = ($dari === $sevenDaysAgo && $sampai === $todayFormatted);
            $isBulanIni = ($dari === $firstDayMonth && $sampai === $todayFormatted);
            ?>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted mb-2">Shortcut Periode Laporan:</label>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="btn-hari-ini" class="btn btn-sm rounded-pill px-3 btn-shortcut <?= $isHariIni ? 'active-shortcut fw-bold shadow-sm' : '' ?>" onclick="setShortcut('hari_ini')">Hari Ini</button>
                    <button type="button" id="btn-7-hari" class="btn btn-sm rounded-pill px-3 btn-shortcut <?= $is7Hari ? 'active-shortcut fw-bold shadow-sm' : '' ?>" onclick="setShortcut('7_hari')">7 Hari Terakhir</button>
                    <button type="button" id="btn-bulan-ini" class="btn btn-sm rounded-pill px-3 btn-shortcut <?= $isBulanIni ? 'active-shortcut fw-bold shadow-sm' : '' ?>" onclick="setShortcut('bulan_ini')">Bulan Ini</button>
                </div>
            </div>

            <form method="GET" action="" id="form-filter" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Dari Tanggal</label>
                    <input type="date" name="dari" id="filter_dari" class="form-control form-control-sm" value="<?= htmlspecialchars($dari) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="sampai" id="filter_sampai" class="form-control form-control-sm" value="<?= htmlspecialchars($sampai) ?>" required>
                </div>

                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-50 rounded-2 shadow-sm text-center d-flex align-items-center justify-content-center py-2">
                        <i class="fa-solid fa-filter me-1"></i> Tampilkan
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-50 rounded-2 shadow-sm text-center d-flex align-items-center justify-content-center py-2" onClick="window.location.href='laporan_keuangan'">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Navigasi Tab (Laba Rugi vs Arus Kas) dengan Penyesuaian Warna Tema Aktif -->
    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 <?= $tab === 'labarugi' ? 'active fw-bold text-white shadow-sm' : 'bg-body-tertiary text-dark border' ?>" 
               style="<?= $tab === 'labarugi' ? 'background-color: var(--primary-color) !important;' : '' ?>" 
               href="?tab=labarugi&dari=<?= $dari ?>&sampai=<?= $sampai ?>">
                <i class="fa-solid fa-chart-line me-1"></i> Laporan Laba Rugi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-4 <?= $tab === 'aruskas' ? 'active fw-bold text-white shadow-sm' : 'bg-body-tertiary text-dark border' ?>" 
               style="<?= $tab === 'aruskas' ? 'background-color: var(--primary-color) !important;' : '' ?>" 
               href="?tab=aruskas&dari=<?= $dari ?>&sampai=<?= $sampai ?>">
                <i class="fa-solid fa-wallet me-1"></i> Laporan Arus Kas (Cash Flow)
            </a>
        </li>
    </ul>

    <!-- KONTEN TAB 1: LABA RUGI -->
    <?php if ($tab === 'labarugi'): ?>
        <div class="card border-1 shadow-sm rounded-3" style="background-color: var(--card-bg);">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3 border-bottom pb-2 text-primary">Laporan Laba Rugi</h4>
                <p class="text-muted small mb-4">Periode Tanggal: <strong><?= date('d/m/Y', strtotime($dari)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($sampai)) ?></strong></p>

                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <tbody>
                            <tr class="border-bottom">
                                <td class="fw-bold">1. Pendapatan / Omset Kotor (Penjualan POS)</td>
                                <td class="text-end fw-bold text-primary">Rp <?= number_format($labaRugi['total_omset'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="border-bottom text-muted">
                                <td class="ps-3">Harga Pokok Penjualan (HPP / Modal Barang Terjual)</td>
                                <td class="text-end">(Rp <?= number_format($labaRugi['total_hpp'], 0, ',', '.') ?>)</td>
                            </tr>
                            <tr class="table-light fw-bold border-bottom">
                                <td>LABA KOTOR</td>
                                <td class="text-end text-success">Rp <?= number_format($labaRugi['laba_kotor'], 0, ',', '.') ?></td>
                            </tr>
                            <tr class="border-bottom">
                                <td colspan="2" class="fw-bold pt-3 text-secondary">2. Beban / Biaya Operasional & Lain-Lain:</td>
                            </tr>
                            <?php if (empty($labaRugi['daftar_beban'])): ?>
                                <tr>
                                    <td class="ps-3 text-muted fst-italic" colspan="2">- Tidak ada pengeluaran beban operasional pada periode ini -</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($labaRugi['daftar_beban'] as $beban): ?>
                                    <tr class="border-bottom text-muted">
                                        <td class="ps-3">
                                            <?= htmlspecialchars(!empty($beban['keterangan']) ? $beban['keterangan'] : $beban['kategori']) ?>
                                        </td>
                                        <td class="text-end">Rp <?= number_format($beban['nominal'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr class="table-light fw-bold border-bottom">
                                <td>TOTAL BEBAN OPERASIONAL</td>
                                <td class="text-end text-danger">(Rp <?= number_format($labaRugi['total_beban'], 0, ',', '.') ?>)</td>
                            </tr>
                            <tr class="table-primary fw-bold fs-5">
                                <td class="py-3">LABA BERSIH (NET PROFIT)</td>
                                <td class="text-end py-3 text-success">Rp <?= number_format($labaRugi['laba_bersih'], 0, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- KONTEN TAB 2: ARUS KAS -->
    <?php if ($tab === 'aruskas'): ?>
        <div class="card border-1 shadow-sm rounded-3" style="background-color: var(--card-bg);">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3 border-bottom pb-2 text-primary">Laporan Arus Kas (Cash Flow)</h4>
                <p class="text-muted small mb-4">Periode Tanggal: <strong><?= date('d/m/Y', strtotime($dari)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($sampai)) ?></strong></p>

                <div class="row g-4">
                    <!-- Kas Masuk -->
                    <div class="col-md-6">
                        <div class="card border border-success border-opacity-25 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-success-subtle text-success fw-bold py-2">
                                <i class="fa-solid fa-arrow-trend-up me-1"></i> ARUS KAS MASUK
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <?php if (empty($arusKas['kas_masuk'])): ?>
                                        <tr><td class="text-muted fst-italic">Tidak ada pemasukan kas.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($arusKas['kas_masuk'] as $m): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($m['kategori']) ?></td>
                                                <td class="text-end fw-bold text-success">Rp <?= number_format($m['total_nominal'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="card-footer bg-light fw-bold d-flex justify-content-between">
                                <span>Total Kas Masuk:</span>
                                <span class="text-success">Rp <?= number_format($arusKas['total_masuk'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Kas Keluar -->
                    <div class="col-md-6">
                        <div class="card border border-danger border-opacity-25 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-danger-subtle text-danger fw-bold py-2">
                                <i class="fa-solid fa-arrow-trend-down me-1"></i> ARUS KAS KELUAR
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <?php if (empty($arusKas['kas_keluar'])): ?>
                                        <tr><td class="text-muted fst-italic">Tidak ada pengeluaran kas.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($arusKas['kas_keluar'] as $k): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($k['kategori']) ?></td>
                                                <td class="text-end fw-bold text-danger">Rp <?= number_format($k['total_nominal'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="card-footer bg-light fw-bold d-flex justify-content-between">
                                <span>Total Kas Keluar:</span>
                                <span class="text-danger">Rp <?= number_format($arusKas['total_keluar'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rekap Kenaikan Kas -->
                <div class="card border-0 bg-body-tertiary mt-4 rounded-3 shadow-sm">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0 text-secondary">KENAIKAN / PENURUNAN KAS BERSIH:</h6>
                            <small class="text-muted">Total Kas Masuk dikurangi Total Kas Keluar pada periode ini.</small>
                        </div>
                        <h3 class="fw-bold mb-0 <?= $arusKas['kenaikan_kas'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            Rp <?= number_format($arusKas['kenaikan_kas'], 0, ',', '.') ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>