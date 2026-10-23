<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/PosController.php';
require_once __DIR__ . '/../controllers/SettingController.php';

// Ambil data Pengaturan Kantin
$settingController = new SettingController($pdo);
$settings          = $settingController->getAllSettings();

$namaAplikasi  = $settings['nama_aplikasi'] ?? 'Kantin POS';
$namaPemilik   = $settings['nama_pemilik'] ?? '';
$alamatLengkap = $settings['alamat_lengkap'] ?? '';
$noTelepon     = $settings['no_telepon'] ?? '';
$pesanNota     = $settings['pesan_nota'] ?? 'Terima kasih atas kunjungan Anda!';
$logoToko      = $settings['logo'] ?? '';

$controller = new PosController($pdo);
$id = $_GET['id'] ?? 0;

// Ambil data utama penjualan
$stmt = $pdo->prepare("SELECT * FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$penjualan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    echo "<h3 style='text-align:center; margin-top:50px;'>Data transaksi tidak ditemukan.</h3>";
    exit;
}

// Ambil detail item produk
$detailStmt = $pdo->prepare("SELECT d.*, p.nama_produk FROM detail_penjualan d JOIN produk p ON d.produk_id = p.id WHERE d.penjualan_id = ?");
$detailStmt->execute([$id]);
$items = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Penjualan A4 - <?= htmlspecialchars($penjualan['no_nota']) ?></title>
    <!-- Muat FontAwesome untuk ikon pada tombol -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; margin: 30px; background: #f8f9fa; }
        
        /* Border luar dihapus total (menggunakan border: none) */
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: none; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.05); border-radius: 8px; }
        
        .header-table, .detail-table { width: 100%; border-collapse: collapse; }
        
        /* Garis border tabel produk tetap diperjelas & dipertebal */
        .detail-table th, .detail-table td { 
            border: 1.5px solid #495057; 
            padding: 10px; 
        }
        .detail-table th { 
            background-color: #e9ecef; 
            font-weight: bold; 
        }
        
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        
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
            body { background: none; margin: 0; }
            .no-print { display: none; }
            
            /* Saat dicetak, border luar benar-benar bersih tanpa garis kotak */
            .invoice-box { border: none; padding: 0; box-shadow: none; max-width: 100%; }
            
            /* Border tabel dicetak hitam pekat agar kontras & jelas */
            .detail-table th, .detail-table td { border: 1px solid #000 !important; }
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

    <div class="invoice-box">
        <!-- Header Nota A4 Dinamis dari Pengaturan -->
        <table class="header-table" style="margin-bottom: 25px;">
            <tr>
                <td style="vertical-align: top;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <?php if (!empty($logoToko)): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/<?= htmlspecialchars($logoToko) ?>" alt="Logo" style="max-height: 50px; object-fit: contain;">
                        <?php endif; ?>
                        <div>
                            <h2 style="margin:0; color: #0d6efd; text-transform: uppercase;"><?= htmlspecialchars($namaAplikasi) ?></h2>
                            <?php if (!empty($namaPemilik)): ?>
                                <p style="margin: 4px 0 0 0; font-size: 12px;">Pengelola: <strong><?= htmlspecialchars($namaPemilik) ?></strong></p>
                            <?php endif; ?>
                            <?php if (!empty($alamatLengkap)): ?>
                                <p style="margin: 2px 0 0 0; font-size: 12px; color: #555;"><?= htmlspecialchars($alamatLengkap) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($noTelepon)): ?>
                                <p style="margin: 2px 0 0 0; font-size: 12px; color: #555;">Telp/WA: <?= htmlspecialchars($noTelepon) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="text-end" style="vertical-align: top;">
                    <h3 style="margin:0; color: #333;"><?= htmlspecialchars($penjualan['no_nota']) ?></h3>
                    <p style="margin: 5px 0 0 0; font-size: 12px;">Tanggal: <?= date('d/m/Y H:i:s', strtotime($penjualan['tanggal'])) ?></p>
                    <p style="margin: 2px 0 0 0; font-size: 12px;">Kasir: <strong><?= htmlspecialchars($penjualan['kasir']) ?></strong></p>
                </td>
            </tr>
        </table>

        <hr style="border: none; border-top: 1px solid #eee; margin-bottom: 20px;">

        <!-- Tabel Detail Produk -->
        <table class="detail-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>Nama Produk</th>
                    <th class="text-center" style="width: 80px;">Qty</th>
                    <th class="text-end" style="width: 120px;">Harga Jual</th>
                    <th class="text-end" style="width: 140px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($items as $item): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($item['nama_produk']) ?></td>
                        <td class="text-center"><?= $item['qty'] ?></td>
                        <td class="text-end">Rp <?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                        <td class="text-end fw-bold">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end fw-bold">Total Belanja:</td>
                    <td class="text-end fw-bold" style="font-size: 15px; color: #0d6efd;">Rp <?= number_format($penjualan['total_belanja'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">Tunai / Bayar:</td>
                    <td class="text-end">Rp <?= number_format($penjualan['bayar'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">Kembalian:</td>
                    <td class="text-end">Rp <?= number_format($penjualan['kembalian'], 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($penjualan['catatan'])): ?>
            <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px;">
                <strong>Catatan Transaksi:</strong> <?= htmlspecialchars($penjualan['catatan']) ?>
            </div>
        <?php endif; ?>

        <!-- Footer / Pesan Nota Dinamis dari Pengaturan -->
        <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px dashed #ddd; padding-top: 15px;">
            <?= nl2br(htmlspecialchars($pesanNota)) ?>
        </div>
    </div>

</body>
</html>