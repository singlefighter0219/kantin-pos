<?php
class VendorController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo = $pdo_conn;
    }

    // Ambil daftar vendor dengan pencarian dan hitung jumlah produknya
    public function getVendorFiltered($keyword, $limit, $offset) {
        try {
            $sql = "
                SELECT v.*, 
                       (SELECT COUNT(*) FROM produk p WHERE p.vendor_id = v.id AND p.kategori_kepemilikan = 'konsinyasi') as total_produk
                FROM vendor v
                WHERE 1=1
            ";
            $params = [];

            if (!empty($keyword)) {
                $sql .= " AND (v.nama_vendor LIKE ? OR v.nohp LIKE ? OR v.alamat LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam);
            }

            $sql .= " ORDER BY v.nama_vendor ASC LIMIT ? OFFSET ?";
            array_push($params, (int)$limit, (int)$offset);

            $stmt = $this->pdo->prepare($sql);
            $i = 1;
            foreach ($params as $val) {
                if (is_int($val)) {
                    $stmt->bindValue($i, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($i, $val, PDO::PARAM_STR);
                }
                $i++;
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Hitung total vendor untuk pagination
    public function countVendorFiltered($keyword) {
        try {
            $sql = "SELECT COUNT(*) FROM vendor WHERE 1=1";
            $params = [];

            if (!empty($keyword)) {
                $sql .= " AND (nama_vendor LIKE ? OR nohp LIKE ? OR alamat LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Ambil produk milik vendor tertentu (untuk isi modal detail)
    public function getProdukByVendor($vendor_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT nama_produk, harga_titip, harga_jual, stok, status 
                FROM produk 
                WHERE vendor_id = ? AND kategori_kepemilikan = 'konsinyasi'
                ORDER BY nama_produk ASC
            ");
            $stmt->execute([$vendor_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Simpan atau Edit Data Vendor
    public function simpanVendor($data) {
        try {
            $id = trim($data['id'] ?? '');
            $nama_vendor = trim($data['nama_vendor'] ?? '');
            $nohp = trim($data['nohp'] ?? '');
            $alamat = trim($data['alamat'] ?? '');

            if (empty($nama_vendor) || empty($nohp) || empty($alamat)) {
                return ['status' => false, 'message' => 'Semua kolom form vendor wajib diisi!'];
            }

            if (!empty($id)) {
                // UPDATE VENDOR
                $stmt = $this->pdo->prepare("UPDATE vendor SET nama_vendor = ?, nohp = ?, alamat = ? WHERE id = ?");
                $success = $stmt->execute([$nama_vendor, $nohp, $alamat, $id]);
                
                return [
                    'status' => $success, 
                    'message' => $success ? 'Data vendor berhasil diperbarui!' : 'Gagal memperbarui data vendor.'
                ];
            } else {
                // TAMBAH VENDOR BARU
                $stmt = $this->pdo->prepare("INSERT INTO vendor (nama_vendor, nohp, alamat) VALUES (?, ?, ?)");
                $success = $stmt->execute([$nama_vendor, $nohp, $alamat]);
                
                return [
                    'status' => $success, 
                    'message' => $success ? 'Vendor baru berhasil ditambahkan!' : 'Gagal menambahkan vendor.'
                ];
            }
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }
}