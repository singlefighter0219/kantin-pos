<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/includes/header.php';

$dashboard = new DashboardController($pdo);
$stats = $dashboard->getStatistikRingkasan();
$grafik7Hari = $dashboard->getGrafik7Hari();
$grafik6Bulan = $dashboard->getGrafik6Bulan();
$konsinyasiAktif = $dashboard->getRingkasanKonsinyasiAktif();
$riwayatKas = $dashboard->getRiwayatKasTerbaru();
?>

<div class="container-fluid">
    <!-- Header Dashboard & Akses Cepat -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-chart-pie me-2 text-primary"></i> Dashboard Utama Kantin
            </h3>
            <p class="text-muted small mb-0">Pusat kendali operasional, keuangan, dan pemantauan produk kantin.</p>
        </div>
        
        <!-- 5. Akses Cepat -->
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>kasir" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-cash-register me-1"></i> Buka Kasir
            </a>
            <a href="<?= BASE_URL ?>vendor" class="btn btn-warning btn-sm text-dark rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-handshake me-1"></i> Kelola Vendor
            </a>
            <a href="<?= BASE_URL ?>laporan_keuangan" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i> Lihat Laporan Keuangan
            </a>
        </div>
    </div>

    <!-- 1. Ringkasan Statistik (Hari Ini, Minggu Ini, Bulan Ini) -->
    <div class="row g-3 mb-4">
        <?php 
        $periodeLabels = [
            'hari_ini' => 'Hari Ini',
            'minggu_ini' => 'Minggu Ini',
            'bulan_ini' => 'Bulan Ini'
        ];
        foreach ($periodeLabels as $key => $label): 
            $data = $stats[$key];
        ?>
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: var(--card-bg);">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-uppercase text-muted mb-0" style="font-size: 12px;"><?= $label ?></h6>
                            <span class="badge bg-primary-subtle text-primary small"><?= number_format($data['jml_transaksi'], 0, ',', '.') ?> Transaksi</span>
                        </div>
                        <h4 class="fw-bold text-primary mb-1">Rp <?= number_format($data['omset'], 0, ',', '.') ?></h4>
                        <div class="small text-muted mb-3">Laba Bersih: <strong class="text-success">Rp <?= number_format($data['laba'], 0, ',', '.') ?></strong></div>
                        
                        <div class="bg-body-tertiary p-2 rounded-2 small d-flex justify-content-between">
                            <div>
                                <span class="text-muted d-block" style="font-size: 10px;">KAS MASUK</span>
                                <span class="fw-bold text-success">+Rp <?= number_format($data['kas_masuk'], 0, ',', '.') ?></span>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block" style="font-size: 10px;">KAS KELUAR</span>
                                <span class="fw-bold text-danger">-Rp <?= number_format($data['kas_keluar'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 2. Grafik Penjualan (7 Hari & 6 Bulan Terakhir) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: var(--card-bg);">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-line me-2 text-primary"></i> Grafik Penjualan 7 Hari Terakhir</h6>
                    <div style="position: relative; height: 240px;">
                        <canvas id="chart7Hari"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: var(--card-bg);">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-column me-2 text-warning"></i> Grafik Penjualan 6 Bulan Terakhir</h6>
                    <div style="position: relative; height: 240px;">
                        <canvas id="chart6Bulan"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Informasi Stok Konsinyasi Aktif & 4. Aktivitas Kas Terbaru -->
    <div class="row g-3">
        <!-- 3. Stok Konsinyasi Aktif -->
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: var(--card-bg);">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-boxes-stacked me-2 text-success"></i> Stok Barang Titipan Vendor Aktif</h6>
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Produk</th>
                                    <th>Vendor</th>
                                    <th class="text-center">Sisa Stok</th>
                                    <th class="text-end">Harga Titip</th>
                                    <th class="text-end">Harga Jual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($konsinyasiAktif)): ?>
                                    <tr><td colspan="5" class="text-center text-muted fst-italic py-3">Tidak ada produk konsinyasi aktif.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($konsinyasiAktif as $item): ?>
                                        <tr class="border-bottom">
                                            <td class="fw-bold"><?= htmlspecialchars($item['nama_produk']) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($item['nama_vendor'] ?? '-') ?></td>
                                            <td class="text-center"><span class="badge bg-warning-subtle text-warning-emphasis fw-bold"><?= $item['stok'] ?> Pcs</span></td>
                                            <td class="text-end">Rp <?= number_format($item['harga_titip'], 0, ',', '.') ?></td>
                                            <td class="text-end fw-bold text-primary">Rp <?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Aktivitas Kas Terbaru -->
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: var(--card-bg);">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-danger"></i> 5 Riwayat Kas Terbaru</h6>
                    <div class="d-flex flex-column gap-2" style="max-height: 280px; overflow-y: auto;">
                        <?php if (empty($riwayatKas)): ?>
                            <p class="text-muted small fst-italic text-center py-3">Belum ada riwayat kas.</p>
                        <?php else: ?>
                            <?php foreach ($riwayatKas as $kas): ?>
                                <?php 
                                    $isMasuk = $kas['tipe'] === 'masuk';
                                    $badgeColor = $isMasuk ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                                    $icon = $isMasuk ? 'fa-arrow-down' : 'fa-arrow-up';
                                ?>
                                <div class="p-2 rounded-2 bg-body-tertiary d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <div class="p-2 rounded-circle <?= $badgeColor ?> d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; flex-shrink: 0;">
                                            <i class="fa-solid <?= $icon ?> small"></i>
                                        </div>
                                        <div class="text-truncate">
                                            <span class="fw-bold d-block text-truncate small" style="color: var(--primary-color);"><?= htmlspecialchars($kas['keterangan'] ?: $kas['kategori']) ?></span>
                                            <span class="text-muted" style="font-size: 10px;"><?= date('d/m/Y H:i', strtotime($kas['tanggal'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <span class="fw-bold small <?= $isMasuk ? 'text-success' : 'text-danger' ?>">
                                            <?= $isMasuk ? '+' : '-' ?>Rp <?= number_format($kas['nominal'], 0, ',', '.') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>