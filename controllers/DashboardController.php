<?php
class DashboardController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo = $pdo_conn;
    }

    // 1. Ringkasan Omset, Laba, Transaksi, dan Kas (Hari Ini, Minggu Ini, Bulan Ini)
    public function getStatistikRingkasan() {
        $today = date('Y-m-d');
        $startWeek = date('Y-m-d', strtotime('monday this week'));
        $startMonth = date('Y-m-01');

        return [
            'hari_ini' => $this->getPeriodeStats($today, $today),
            'minggu_ini' => $this->getPeriodeStats($startWeek, $today),
            'bulan_ini' => $this->getPeriodeStats($startMonth, $today),
        ];
    }

    private function getPeriodeStats($startDate, $endDate) {
        try {
            // Penjualan (Omset, Laba, Transaksi)
            $stmtP = $this->pdo->prepare("
                SELECT SUM(total_belanja) as omset, SUM(total_laba) as laba, COUNT(id) as jml_transaksi 
                FROM penjualan 
                WHERE DATE(tanggal) BETWEEN ? AND ?
            ");
            $stmtP->execute([$startDate, $endDate]);
            $resP = $stmtP->fetch(PDO::FETCH_ASSOC);

            // Kas Masuk
            $stmtKM = $this->pdo->prepare("
                SELECT SUM(nominal) as total_masuk 
                FROM buku_kas 
                WHERE tipe = 'masuk' AND DATE(tanggal) BETWEEN ? AND ?
            ");
            $stmtKM->execute([$startDate, $endDate]);
            $masuk = $stmtKM->fetchColumn() ?? 0;

            // Kas Keluar
            $stmtKK = $this->pdo->prepare("
                SELECT SUM(nominal) as total_keluar 
                FROM buku_kas 
                WHERE tipe = 'keluar' AND DATE(tanggal) BETWEEN ? AND ?
            ");
            $stmtKK->execute([$startDate, $endDate]);
            $keluar = $stmtKK->fetchColumn() ?? 0;

            return [
                'omset' => $resP['omset'] ?? 0,
                'laba' => $resP['laba'] ?? 0,
                'jml_transaksi' => $resP['jml_transaksi'] ?? 0,
                'kas_masuk' => $masuk,
                'kas_keluar' => $keluar
            ];
        } catch (PDOException $e) {
            return ['omset' => 0, 'laba' => 0, 'jml_transaksi' => 0, 'kas_masuk' => 0, 'kas_keluar' => 0];
        }
    }

    // 2. Grafik Penjualan 7 Hari Terakhir
    public function getGrafik7Hari() {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $tanggal = date('Y-m-d', strtotime("-$i days"));
            $label = date('d M', strtotime($tanggal));

            $stmt = $this->pdo->prepare("SELECT SUM(total_belanja) FROM penjualan WHERE DATE(tanggal) = ?");
            $stmt->execute([$tanggal]);
            $total = $stmt->fetchColumn() ?? 0;

            $data['labels'][] = $label;
            $data['values'][] = (float)$total;
        }
        return $data;
    }

    // 2. Grafik Penjualan 6 Bulan Terakhir
    public function getGrafik6Bulan() {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $tahunBulan = date('Y-m', strtotime("-$i months"));
            $label = date('M Y', strtotime("-$i months"));

            $stmt = $this->pdo->prepare("SELECT SUM(total_belanja) FROM penjualan WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?");
            $stmt->execute([$tahunBulan]);
            $total = $stmt->fetchColumn() ?? 0;

            $data['labels'][] = $label;
            $data['values'][] = (float)$total;
        }
        return $data;
    }

    // 3. Ringkasan Produk Konsinyasi Aktif di Etalase
    public function getRingkasanKonsinyasiAktif() {
        try {
            $stmt = $this->pdo->query("
                SELECT p.nama_produk, v.nama_vendor, p.stok, p.harga_titip, p.harga_jual
                FROM produk p
                LEFT JOIN vendor v ON p.vendor_id = v.id
                WHERE p.kategori_kepemilikan = 'konsinyasi' AND p.status = 'aktif'
                ORDER BY p.stok DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // 4. Aktivitas Terakhir: 5 Riwayat Kas Terbaru
    public function getRiwayatKasTerbaru() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM buku_kas 
                ORDER BY tanggal DESC, id DESC 
                LIMIT 5
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}