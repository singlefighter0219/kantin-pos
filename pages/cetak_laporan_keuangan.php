<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/FinanceController.php';
require_once __DIR__ . '/../controllers/SettingController.php';

// Ambil data Pengaturan Kantin
$settingController = new SettingController($pdo);
$settings          = $settingController->getAllSettings();

$namaAplikasi  = $settings['nama_aplikasi'] ?? 'Kantin POS';
$namaPemilik   = $settings['nama_pemilik'] ?? '';
$alamatLengkap = $settings['alamat_lengkap'] ?? '';
$noTelepon     = $settings['no_telepon'] ?? '';
$logoToko      = $settings['logo'] ?? '';

$controller = new FinanceController($pdo);

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$tab = $_GET['tab'] ?? 'labarugi';

$labaRugi = $controller->getLaporanLabaRugi($dari, $sampai);
$arusKas = $controller->getLaporanArusKas($dari, $sampai);

$judulLaporan = ($tab === 'labarugi') ? 'Laporan Laba Rugi' : 'Laporan Arus Kas (Cash Flow)';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $judulLaporan ?> - <?= htmlspecialchars($namaAplikasi) ?></title>
    <!-- Muat FontAwesome untuk ikon pada tombol -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
            margin: 30px;
            background: #f8f9fa;
        }
        .report-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            color: #0d6efd;
            border-bottom: 1.5px solid #dee2e6;
            padding-bottom: 4px;
        }
        .row-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed #e9ecef;
        }
        .row-item.sub {
            padding-left: 15px;
            color: #555;
        }
        .row-item.total {
            font-weight: bold;
            border-bottom: 1.5px solid #495057;
            border-top: 1.5px solid #495057;
            padding: 7px 0;
            margin-top: 5px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
        }
        .row-item.grand-total {
            font-weight: bold;
            font-size: 15px;
            border-bottom: 2px solid #212529;
            border-top: 2px solid #212529;
            padding: 9px 10px;
            margin-top: 15px;
            background-color: #e9ecef;
        }
        .text-right {
            text-align: right;
        }
        
        /* Tombol Aksi Modern */
        .no-print {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            border-radius: 6px;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
            text-decoration: none;
        }
        .btn-action:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-print { background: #212529; }
        .btn-close-page { background: #dc3545; }

        @media print {
            body { background: none; margin: 0; padding: 0; }
            .report-container { border: none; box-shadow: none; padding: 0; max-width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<!-- Tombol Aksi -->
<div class="no-print">
    <button class="btn-action btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
    </button>
    <button class="btn-action btn-close-page" onclick="window.close()">
        <i class="fa-solid fa-circle-xmark"></i> Tutup Halaman
    </button>
</div>

<div class="report-container">
    <!-- Header Identitas Usaha Dinamis dari Pengaturan -->
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
            <td class="text-right" style="vertical-align: top;">
                <h4 style="margin:0; color: #333; text-transform: uppercase;"><?= $judulLaporan ?></h4>
                <p style="margin: 4px 0 0 0; font-size: 11px; color: #555;">Periode: <?= date('d/m/Y', strtotime($dari)) ?> s/d <?= date('d/m/Y', strtotime($sampai)) ?></p>
            </td>
        </tr>
    </table>

    <hr style="border: none; border-top: 1px solid #dee2e6; margin-bottom: 20px;">

    <?php if ($tab === 'labarugi'): ?>
        <!-- FORMAT LAPORAN LABA RUGI -->
        <div class="row-item">
            <span><strong>1. Pendapatan / Omset Penjualan POS</strong></span>
            <span class="text-right fw-bold">Rp <?= number_format($labaRugi['total_omset'], 0, ',', '.') ?></span>
        </div>

        <div style="margin-top: 15px;">
            <div class="section-title">Harga Pokok Penjualan (HPP)</div>
            <div class="row-item sub">
                <span>Modal Barang Terjual</span>
                <span class="text-right">(Rp <?= number_format($labaRugi['total_hpp'], 0, ',', '.') ?>)</span>
            </div>
            <div class="row-item total">
                <span>Total HPP</span>
                <span class="text-right">(Rp <?= number_format($labaRugi['total_hpp'], 0, ',', '.') ?>)</span>
            </div>
        </div>

        <div class="row-item grand-total">
            <span>Laba Kotor</span>
            <span class="text-right" style="color: #198754;">Rp <?= number_format($labaRugi['laba_kotor'], 0, ',', '.') ?></span>
        </div>

        <div style="margin-top: 15px;">
            <div class="section-title">2. Beban & Biaya Operasional</div>
            <?php if (empty($labaRugi['daftar_beban'])): ?>
                <div class="row-item sub"><span>- Tidak ada beban operasional pada periode ini -</span><span class="text-right">Rp 0</span></div>
            <?php else: ?>
                <?php foreach ($labaRugi['daftar_beban'] as $beban): ?>
                    <div class="row-item sub">
                        <span><?= htmlspecialchars(!empty($beban['keterangan']) ? $beban['keterangan'] : $beban['kategori']) ?></span>
                        <span class="text-right">Rp <?= number_format($beban['nominal'], 0, ',', '.') ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="row-item total">
                <span>Total Beban Operasional & Lain-Lain</span>
                <span class="text-right">(Rp <?= number_format($labaRugi['total_beban'], 0, ',', '.') ?>)</span>
            </div>
        </div>

        <div class="row-item grand-total" style="margin-top: 25px; background-color: #cfe2ff;">
            <span>Laba Bersih (Net Profit)</span>
            <span class="text-right" style="color: #0f5132;">Rp <?= number_format($labaRugi['laba_bersih'], 0, ',', '.') ?></span>
        </div>

    <?php else: ?>
        <!-- FORMAT LAPORAN ARUS KAS -->
        <div class="section-title">Arus Kas Masuk</div>
        <?php if (empty($arusKas['kas_masuk'])): ?>
            <div class="row-item sub"><span>Tidak ada pemasukan kas.</span><span class="text-right">Rp 0</span></div>
        <?php else: ?>
            <?php foreach ($arusKas['kas_masuk'] as $m): ?>
                <div class="row-item sub">
                    <span><?= htmlspecialchars($m['kategori']) ?></span>
                    <span class="text-right" style="color: #198754;">Rp <?= number_format($m['total_nominal'], 0, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="row-item total">
            <span>Total Kas Masuk</span>
            <span class="text-right" style="color: #198754;">Rp <?= number_format($arusKas['total_masuk'], 0, ',', '.') ?></span>
        </div>

        <div class="section-title" style="margin-top: 20px;">Arus Kas Keluar</div>
        <?php if (empty($arusKas['kas_keluar'])): ?>
            <div class="row-item sub"><span>Tidak ada pengeluaran kas.</span><span class="text-right">Rp 0</span></div>
        <?php else: ?>
            <?php foreach ($arusKas['kas_keluar'] as $k): ?>
                <div class="row-item sub">
                    <span><?= htmlspecialchars($k['kategori']) ?></span>
                    <span class="text-right" style="color: #dc3545;">Rp <?= number_format($k['total_nominal'], 0, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="row-item total">
            <span>Total Kas Keluar</span>
            <span class="text-right" style="color: #dc3545;">Rp <?= number_format($arusKas['total_keluar'], 0, ',', '.') ?></span>
        </div>

        <div class="row-item grand-total" style="margin-top: 25px; background-color: #f8f9fa;">
            <span>Kenaikan / Penurunan Kas Bersih</span>
            <span class="text-right <?= $arusKas['kenaikan_kas'] >= 0 ? 'text-success' : 'text-danger' ?>">
                Rp <?= number_format($arusKas['kenaikan_kas'], 0, ',', '.') ?>
            </span>
        </div>
    <?php endif; ?>

</div>

</body>
</html>