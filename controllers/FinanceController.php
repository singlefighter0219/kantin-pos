<?php
class FinanceController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo = $pdo_conn;
    }

    // Ambil rekap Laba Rugi beserta rincian beban berdasarkan filter tanggal
    public function getLaporanLabaRugi($dari, $sampai) {
        try {
            // 1. Total Omset & Laba Kotor dari tabel penjualan
            $stmtPenjualan = $this->pdo->prepare("
                SELECT SUM(total_belanja) as total_omset, SUM(total_modal) as total_hpp, SUM(total_laba) as laba_kotor 
                FROM penjualan 
                WHERE DATE(tanggal) BETWEEN ? AND ?
            ");
            $stmtPenjualan->execute([$dari, $sampai]);
            $penjualan = $stmtPenjualan->fetch(PDO::FETCH_ASSOC);

            $totalOmset = $penjualan['total_omset'] ?? 0;
            $totalHpp = $penjualan['total_hpp'] ?? 0;
            $labaKotor = $penjualan['laba_kotor'] ?? 0;

            // 2. Ambil rincian individual beban/biaya operasional dan lain-lain dari buku_kas
            $stmtBeban = $this->pdo->prepare("
                SELECT kategori, keterangan, nominal 
                FROM buku_kas 
                WHERE tipe = 'keluar' AND kategori IN ('Biaya Operasional', 'Lain-Lain') AND DATE(tanggal) BETWEEN ? AND ?
                ORDER BY tanggal ASC
            ");
            $stmtBeban->execute([$dari, $sampai]);
            $daftarBeban = $stmtBeban->fetchAll(PDO::FETCH_ASSOC);

            $totalBeban = 0;
            foreach ($daftarBeban as $beban) {
                $totalBeban += $beban['nominal'];
            }

            $labaBersih = $labaKotor - $totalBeban;

            return [
                'total_omset' => $totalOmset,
                'total_hpp' => $totalHpp,
                'laba_kotor' => $labaKotor,
                'daftar_beban' => $daftarBeban,
                'total_beban' => $totalBeban,
                'laba_bersih' => $labaBersih
            ];
        } catch (PDOException $e) {
            return [
                'total_omset' => 0, 'total_hpp' => 0, 'laba_kotor' => 0,
                'daftar_beban' => [], 'total_beban' => 0, 'laba_bersih' => 0
            ];
        }
    }

    // Ambil rekap Arus Kas berdasarkan filter tanggal
    public function getLaporanArusKas($dari, $sampai) {
        try {
            // Kas Masuk dikelompokkan berdasarkan kategori
            $stmtMasuk = $this->pdo->prepare("
                SELECT kategori, SUM(nominal) as total_nominal 
                FROM buku_kas 
                WHERE tipe = 'masuk' AND DATE(tanggal) BETWEEN ? AND ?
                GROUP BY kategori
            ");
            $stmtMasuk->execute([$dari, $sampai]);
            $kasMasuk = $stmtMasuk->fetchAll(PDO::FETCH_ASSOC);

            $totalMasuk = 0;
            foreach ($kasMasuk as $m) {
                $totalMasuk += $m['total_nominal'];
            }

            // Kas Keluar dikelompokkan berdasarkan kategori
            $stmtKeluar = $this->pdo->prepare("
                SELECT kategori, SUM(nominal) as total_nominal 
                FROM buku_kas 
                WHERE tipe = 'keluar' AND DATE(tanggal) BETWEEN ? AND ?
                GROUP BY kategori
            ");
            $stmtKeluar->execute([$dari, $sampai]);
            $kasKeluar = $stmtKeluar->fetchAll(PDO::FETCH_ASSOC);

            $totalKeluar = 0;
            foreach ($kasKeluar as $k) {
                $totalKeluar += $k['total_nominal'];
            }

            $kenaikanKas = $totalMasuk - $totalKeluar;

            return [
                'kas_masuk' => $kasMasuk,
                'total_masuk' => $totalMasuk,
                'kas_keluar' => $kasKeluar,
                'total_keluar' => $totalKeluar,
                'kenaikan_kas' => $kenaikanKas
            ];
        } catch (PDOException $e) {
            return [
                'kas_masuk' => [], 'total_masuk' => 0,
                'kas_keluar' => [], 'total_keluar' => 0,
                'kenaikan_kas' => 0
            ];
        }
    }
}