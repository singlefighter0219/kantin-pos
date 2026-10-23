<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/SettingController.php';
require_once __DIR__ . '/../controllers/PosController.php';
require_once __DIR__ . '/../controllers/OperationalExpenseController.php';
require_once __DIR__ . '/../controllers/KasLainnyaController.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// Ambil Pengaturan Kantin untuk Header Laporan
$settingController = new SettingController($pdo);
$settings          = $settingController->getAllSettings();
$namaAplikasi      = $settings['nama_aplikasi'] ?? 'Kantin POS';
$namaPemilik       = $settings['nama_pemilik'] ?? '';
$alamatLengkap     = $settings['alamat_lengkap'] ?? '';
$noTelepon         = $settings['no_telepon'] ?? '';
$logoToko          = $settings['logo'] ?? '';

// Ambil parameter filter umum
$modul        = $_GET['modul'] ?? '';
$dari         = $_GET['dari'] ?? date('Y-m-01');
$sampai       = $_GET['sampai'] ?? date('Y-m-d');
$keyword      = trim($_GET['keyword'] ?? '');
$semuaTanggal = isset($_GET['semua_tanggal']) && $_GET['semua_tanggal'] == '1' ? true : false;

// Inisialisasi variabel penampung data & summary
$dataList     = [];
$judulLaporan = '';
$summary      = [];

// Ambil data spesifik berdasarkan modul
if ($modul === 'riwayat_penjualan') {
    $ctrl = new PosController($pdo);
    $dataList = $ctrl->getRiwayatPenjualanFiltered($dari, $sampai, $keyword, $semuaTanggal, 10000, 0);
    
    $totalOmset = 0;
    $totalLabaBersih = 0;
    foreach ($dataList as $r) {
        $totalOmset += $r['total_belanja'];
        $totalLabaBersih += $r['total_laba'];
    }
    
    $judulLaporan = 'LAPORAN REKAP PENJUALAN';
    $summary = [
        'Total Transaksi' => count($dataList) . ' Nota',
        'Total Omset Penjualan' => 'Rp ' . number_format($totalOmset, 0, ',', '.'),
        'Total Laba Bersih' => 'Rp ' . number_format($totalLabaBersih, 0, ',', '.')
    ];
    $dataList = array_reverse($dataList);
}
else if ($modul === 'beban_operasional') {
    $ctrl = new OperationalExpenseController($pdo);
    $dataList = $ctrl->getBebanFiltered($dari, $sampai, $keyword, $semuaTanggal, 10000, 0);
    $totalNominal = $ctrl->getTotalNominalBebanFiltered($dari, $sampai, $keyword, $semuaTanggal);
    $judulLaporan = 'LAPORAN BIAYA OPERASIONAL';
    $summary = ['Total Biaya' => 'Rp ' . number_format($totalNominal, 0, ',', '.')];
    $dataList = array_reverse($dataList);
} 
else if ($modul === 'kas_lainnya') {
    $ctrl = new KasLainnyaController($pdo);
    $dataList = $ctrl->getKasFiltered($dari, $sampai, $keyword, $semuaTanggal, 10000, 0);
    $judulLaporan = 'LAPORAN MODAL, PRIVE & KAS LAINNYA';
    $dataList = array_reverse($dataList);
} 
else if ($modul === 'restock_konsinyasi') {
    $ctrl = new ProductController($pdo);
    $dataList = $ctrl->getRiwayatRestockKonsinyasiFiltered($dari, $sampai, $keyword, $semuaTanggal, 10000, 0);
    $rekap = $ctrl->getRekapRestockKonsinyasiFiltered($dari, $sampai, $keyword, $semuaTanggal);
    $judulLaporan = 'LAPORAN RESTOCK PRODUK KONSINYASI';
    $summary = [
        'Total Qty Masuk' => number_format($rekap['total_qty'], 0, ',', '.') . ' Pcs',
        'Total Nota' => number_format($rekap['total_nota'], 0, ',', '.') . ' Nota'
    ];
    $dataList = array_reverse($dataList);
}
else if ($modul === 'restock_sendiri') {
    $ctrl = new ProductController($pdo);
    $dataList = $ctrl->getRiwayatRestockSendiriFiltered($dari, $sampai, $keyword, $semuaTanggal, 10000, 0);
    $totalBiaya = $ctrl->getTotalBiayaRestockSendiriFiltered($dari, $sampai, $keyword, $semuaTanggal);
    $judulLaporan = 'LAPORAN RESTOCK PRODUK SENDIRI';
    $summary = ['Total Biaya Kulakan' => 'Rp ' . number_format($totalBiaya, 0, ',', '.')];
    $dataList = array_reverse($dataList);
}
else if ($modul === 'retur_konsinyasi') {
    $ctrl = new ProductController($pdo);
    $dataList = $ctrl->getRiwayatReturKonsinyasiFiltered($dari, $sampai, $keyword, $semuaTanggal, 10000, 0);
    $rekap = $ctrl->getRekapReturKonsinyasiFiltered($dari, $sampai, $keyword, $semuaTanggal);
    $judulLaporan = 'LAPORAN RETUR & SETORAN KONSINYASI';
    $summary = [
        'Total Setoran Dibayar' => 'Rp ' . number_format($rekap['total_setoran'], 0, ',', '.'),
        'Total Barang Terjual' => number_format($rekap['total_terjual'], 0, ',', '.') . ' Pcs',
        'Total Barang Diretur' => number_format($rekap['total_retur'], 0, ',', '.') . ' Pcs'
    ];
    $dataList = array_reverse($dataList);
} else {
    echo "<h3 style='text-align:center; margin-top:50px;'>Modul laporan tidak valid.</h3>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $judulLaporan ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 30px; background: #f8f9fa; }
        .report-box { max-width: 950px; margin: auto; padding: 30px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.05); border-radius: 8px; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.data-table th, table.data-table td { border: 1.5px solid #495057; padding: 7px 10px; }
        table.data-table th { background-color: #e9ecef; font-weight: bold; }
        .summary { margin-top: 20px; font-size: 13px; background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #dee2e6; }
        .summary p { margin: 4px 0; }
        .no-print { max-width: 950px; margin: 0 auto 20px auto; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; border-radius: 6px; color: #fff; text-decoration: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-action:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-print { background: #212529; }
        .btn-close-page { background: #dc3545; }
        @media print {
            body { background: none; margin: 0; }
            .no-print { display: none; }
            .report-box { padding: 0; box-shadow: none; max-width: 100%; }
            table.data-table th, table.data-table td { border: 1px solid #000 !important; }
        }
    </style>
</head>
<body>

    <!-- Tombol Aksi -->
    <div class="no-print">
        <button class="btn-action btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
        <button class="btn-action btn-close-page" onclick="window.close()"><i class="fa-solid fa-circle-xmark"></i> Tutup Halaman</button>
    </div>

    <div class="report-box">
        <!-- Header Identitas Usaha -->
        <table class="header-table">
            <tr>
                <td style="vertical-align: top;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <?php if (!empty($logoToko)): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/<?= htmlspecialchars($logoToko) ?>" alt="Logo" style="max-height: 45px; object-fit: contain;">
                        <?php endif; ?>
                        <div>
                            <h3 style="margin:0; color: #0d6efd; text-transform: uppercase;"><?= htmlspecialchars($namaAplikasi) ?></h3>
                            <?php if (!empty($namaPemilik)): ?>
                                <p style="margin: 3px 0 0 0; font-size: 11px;">Pengelola: <strong><?= htmlspecialchars($namaPemilik) ?></strong></p>
                            <?php endif; ?>
                            <?php if (!empty($alamatLengkap)): ?>
                                <p style="margin: 2px 0 0 0; font-size: 11px; color: #555;"><?= htmlspecialchars($alamatLengkap) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($noTelepon)): ?>
                                <p style="margin: 2px 0 0 0; font-size: 11px; color: #555;">Telp/WA: <?= htmlspecialchars($noTelepon) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="text-end" style="vertical-align: top;">
                    <h4 style="margin:0; color: #333;"><?= $judulLaporan ?></h4>
                    <?php if ($semuaTanggal): ?>
                        <p style="margin: 4px 0 0 0; font-size: 11px;">Periode: Seluruh Data</p>
                    <?php else: ?>
                        <p style="margin: 4px 0 0 0; font-size: 11px;">Periode: <?= date('d/m/Y', strtotime($dari)) ?> s/d <?= date('d/m/Y', strtotime($sampai)) ?></p>
                    <?php endif; ?>
                    <?php if(!empty($keyword)): ?>
                        <p style="margin: 2px 0 0 0; font-size: 11px;">Keyword: "<?= htmlspecialchars($keyword) ?>"</p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <hr style="border: none; border-top: 1px solid #dee2e6; margin-bottom: 15px;">

        <!-- Render Tabel Data Sesuai Modul -->
        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 40px;">No</th>
                    <?php if ($modul === 'riwayat_penjualan'): ?>
                        <th style="width: 140px;">No. Nota</th><th style="width: 120px;">Tanggal</th><th style="width: 100px;">Kasir</th><th>Catatan</th><th class="text-end" style="width: 120px;">Total Belanja</th><th class="text-end" style="width: 110px;">Total Laba</th>
                    <?php elseif ($modul === 'beban_operasional'): ?>
                        <th>Tanggal</th><th>Nama Beban</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Nominal</th>
                    <?php elseif ($modul === 'kas_lainnya'): ?>
                        <th>Tanggal</th><th>Tipe</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Nominal</th>
                    <?php elseif ($modul === 'restock_konsinyasi'): ?>
                        <th>Tanggal Terima</th><th>No. Nota</th><th>Produk</th><th>Vendor</th><th class="text-center">Qty Masuk</th><th class="text-center">Sisa Stok</th><th class="text-end">Harga Titip</th>
                    <?php elseif ($modul === 'restock_sendiri'): ?>
                        <th>Tanggal</th><th>Produk / Kategori</th><th>Keterangan</th><th class="text-end">Nominal Biaya</th>
                    <?php elseif ($modul === 'retur_konsinyasi'): ?>
                        <th>Tanggal Retur</th><th>No. Nota</th><th>Produk</th><th>Vendor</th><th class="text-center">Terjual</th><th class="text-center">Retur</th><th class="text-end">Total Setoran</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataList)): ?>
                    <tr><td colspan="7" class="text-center">Tidak ada data untuk ditampilkan.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($dataList as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <?php if ($modul === 'riwayat_penjualan'): ?>
                                <td><?= htmlspecialchars($row['no_nota']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['kasir']) ?></td>
                                <td><?= htmlspecialchars($row['catatan'] ?: '-') ?></td>
                                <td class="text-end">Rp <?= number_format($row['total_belanja'], 0, ',', '.') ?></td>
                                <td class="text-end">Rp <?= number_format($row['total_laba'], 0, ',', '.') ?></td>
                            <?php elseif ($modul === 'beban_operasional'): ?>
                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_beban']) ?></td>
                                <td class="text-capitalize"><?= htmlspecialchars($row['kategori_beban']) ?></td>
                                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                <td class="text-end">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                            <?php elseif ($modul === 'kas_lainnya'): ?>
                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                <td class="text-uppercase"><?= htmlspecialchars($row['tipe']) ?></td>
                                <td><?= htmlspecialchars($row['kategori']) ?></td>
                                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                <td class="text-end"><?= $row['tipe'] === 'masuk' ? '+' : '-' ?> Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                            <?php elseif ($modul === 'restock_konsinyasi'): ?>
                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_terima'])) ?></td>
                                <td><?= htmlspecialchars($row['no_nota_terima']) ?></td>
                                <td><?= htmlspecialchars($row['nama_produk'] ?? 'Dihapus') ?></td>
                                <td><?= htmlspecialchars($row['nama_vendor'] ?? '-') ?></td>
                                <td class="text-center"><?= $row['qty_masuk'] ?></td>
                                <td class="text-center"><?= $row['qty_sisa'] ?></td>
                                <td class="text-end">Rp <?= number_format($row['harga_titip'], 0, ',', '.') ?></td>
                            <?php elseif ($modul === 'restock_sendiri'): ?>
                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></td>
                                <td><strong><?= htmlspecialchars($row['nama_produk'] ?? 'Dihapus') ?></strong><br><small><?= htmlspecialchars($row['kategori']) ?></small></td>
                                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                <td class="text-end">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                            <?php elseif ($modul === 'retur_konsinyasi'): ?>
                                <td><?= date('d/m/Y H:i', strtotime($row['tanggal_retur'])) ?></td>
                                <td><?= htmlspecialchars($row['no_nota_retur']) ?></td>
                                <td><?= htmlspecialchars($row['nama_produk'] ?? 'Dihapus') ?></td>
                                <td><?= htmlspecialchars($row['nama_vendor'] ?? '-') ?></td>
                                <td class="text-center"><?= $row['qty_terjual'] ?> Pcs</td>
                                <td class="text-center"><?= $row['qty_retur'] ?> Pcs</td>
                                <td class="text-end">Rp <?= number_format($row['total_setoran'], 0, ',', '.') ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Ringkasan / Summary -->
        <?php if (!empty($summary)): ?>
            <div class="summary">
                <?php foreach ($summary as $label => $val): ?>
                    <p><strong><?= $label ?>:</strong> <?= $val ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>