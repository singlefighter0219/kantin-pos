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

$controller = new PosController($pdo);
$id = $_GET['id'] ?? 0;

// Ambil data utama penjualan berdasarkan ID
$stmt = $pdo->prepare("SELECT * FROM penjualan WHERE id = ?");
$stmt->execute([$id]);
$penjualan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$penjualan) {
    echo "<h3 style='text-align:center; margin-top:50px;'>Data transaksi tidak ditemukan.</h3>";
    exit;
}

// Ambil detail item produk yang dibeli
$detailStmt = $pdo->prepare("SELECT d.*, p.nama_produk FROM detail_penjualan d JOIN produk p ON d.produk_id = p.id WHERE d.penjualan_id = ?");
$detailStmt->execute([$id]);
$items = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Nota - <?= htmlspecialchars($penjualan['no_nota']) ?></title>
    <!-- Muat html2canvas untuk fitur konversi gambar -->
    <script src="<?= BASE_URL ?>assets/js/html2canvas.min.js"></script>
    <style>
        /* Desain khusus kertas thermal 58mm (lebar sekitar 58mm / ~200-220px) */
        body {
            /* Mengganti Courier New menjadi font Sans-Serif yang jauh lebih tebal dan tajam di thermal */
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            background: #eee;
            margin: 0;
            padding: 10px;
            /* Memaksa ketebalan teks lebih tinggi untuk printer thermal */
            font-weight: bold; 
        }
        .struk-container {
            width: 58mm;
            max-width: 58mm;
            background: #fff;
            padding: 8px;
            margin: 0 auto;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        
        /* Membuat garis putus-putus lebih hitam tekat */
        .line { 
            border-top: 1.5px dashed #000; 
            margin: 6px 0; 
        }
        
        table { width: 100%; font-size: 11px; border-collapse: collapse; }
        th, td { padding: 2.5px 0; vertical-align: top; }

        .no-print {
            margin-top: 20px;
            text-align: center;
            max-width: 58mm;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Desain Tombol Modern & Elegan */
        .btn-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 14px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            box-sizing: border-box;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
        }
        .btn-action:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        .btn-print { background: #212529; color: #fff; }
        .btn-image { background: #0d6efd; color: #fff; } /* Warna Biru Profesional */
        .btn-close-page { background: #dc3545; color: #fff; }

        /* Tambahan agar saat dicetak langsung oleh driver printer, tint hitamnya maksimal */
        @media print {
            body { 
                background: none; 
                padding: 0; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none; }
            .struk-container { box-shadow: none; width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

    <!-- Area Struk yang akan dicetak atau di-capture jadi gambar -->
    <div class="struk-container" id="strukArea">
        
        <!-- Header Nota Dinamis dari Pengaturan -->
        <div class="text-center">
            <span class="fw-bold" style="font-size: 13px; text-transform: uppercase;"><?= htmlspecialchars($namaAplikasi) ?></span><br>
            <?php if (!empty($namaPemilik)): ?>
                <span><?= htmlspecialchars($namaPemilik) ?></span><br>
            <?php endif; ?>
            <?php if (!empty($alamatLengkap)): ?>
                <span><?= htmlspecialchars($alamatLengkap) ?></span><br>
            <?php endif; ?>
            <?php if (!empty($noTelepon)): ?>
                <span>Telp/WA: <?= htmlspecialchars($noTelepon) ?></span>
            <?php endif; ?>
        </div>

        <div class="line"></div>
        <div>
            <span>No Nota : <?= htmlspecialchars($penjualan['no_nota']) ?></span><br>
            <span>Tgl     : <?= date('d/m/Y H:i', strtotime($penjualan['tanggal'])) ?></span><br>
            <span>Kasir   : <?= htmlspecialchars($penjualan['kasir']) ?></span>
        </div>
        <div class="line"></div>
        <table>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td colspan="3"><strong><?= htmlspecialchars($item['nama_produk']) ?></strong></td>
                </tr>
                <tr>
                    <td><?= $item['qty'] ?> x <?= number_format($item['harga_jual'], 0, ',', '.') ?></td>
                    <td></td>
                    <td class="text-end">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="line"></div>
        <table>
            <tr>
                <td><strong>Total:</strong></td>
                <td class="text-end"><strong>Rp <?= number_format($penjualan['total_belanja'], 0, ',', '.') ?></strong></td>
            </tr>
            <tr>
                <td>Bayar:</td>
                <td class="text-end">Rp <?= number_format($penjualan['bayar'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Kembali:</td>
                <td class="text-end">Rp <?= number_format($penjualan['kembalian'], 0, ',', '.') ?></td>
            </tr>
        </table>
        <?php if (!empty($penjualan['catatan'])): ?>
            <div class="line"></div>
            <div>Catatan: <?= htmlspecialchars($penjualan['catatan']) ?></div>
        <?php endif; ?>
        
        <div class="line"></div>
        
        <!-- Footer Nota / Pesan Struk Dinamis dari Pengaturan -->
        <div class="text-center" style="font-size: 10px; margin-top: 5px;">
            <?= nl2br(htmlspecialchars($pesanNota)) ?>
        </div>
    </div>

    <!-- Tombol Aksi (Disembunyikan saat dicetak) -->
    <div class="no-print">
        <button class="btn-action btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Cetak Struk (Thermal)
        </button>
        <button class="btn-action btn-image" onclick="downloadStrukImage()">
            <i class="fa-solid fa-file-image"></i> Jadikan Gambar
        </button>
        <button class="btn-action btn-close-page" onclick="tutupTab()">
            <i class="fa-solid fa-circle-xmark"></i> Tutup Halaman
        </button>
    </div>

    <script>
    function downloadStrukImage() {
        let element = document.getElementById('strukArea');
        
        // Konversi elemen HTML struk menjadi gambar Canvas menggunakan html2canvas
        html2canvas(element, { scale: 2 }).then(canvas => {
            let imageLink = document.createElement('a');
            imageLink.download = 'Struk-<?= htmlspecialchars($penjualan['no_nota']) ?>.png';
            imageLink.href = canvas.toDataURL('image/png');
            imageLink.click();
        }).catch(err => {
            alert('Gagal mengonversi struk ke gambar.');
            console.error(err);
        });
    }

    function tutupTab() {
        // Menutup tab browser yang dibuka via window.open()
        window.close();
        
        // Cadangan/Fallback: Jika karena kebijakan browser tab gagal tertutup otomatis, 
        // arahkan kembali ke riwayat penjualan agar pengguna tidak terjebak di halaman kosong.
        setTimeout(function() {
            window.location.href = '<?= BASE_URL ?>riwayat_penjualan';
        }, 300);
    }
    </script>

</body>
</html>