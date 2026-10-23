<?php
class OperationalExpenseController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo =$pdo_conn;
    }

    // Simpan atau Edit Pengeluaran Operasional & Sinkronisasi ke Buku Kas
    public function simpanBeban($data) {
        try {
            $this->pdo->beginTransaction();

            $id = isset($data['id']) ? (int)$data['id'] : 0;
            $nama_beban = trim($data['nama_beban'] ?? '');
            $kategori_beban =$data['kategori_beban'] ?? 'operasional';
            $nominal = (float)($data['nominal'] ?? 0);
            $keterangan = trim($data['keterangan'] ?? '');

            if (empty($nama_beban) || $nominal <= 0) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Nama pengeluaran dan nominal wajib diisi dengan benar!'];
            }

            if ($id > 0) {
                // UPDATE DATA
                $stmtUpd =$this->pdo->prepare("
                    UPDATE beban_operasional 
                    SET nama_beban = ?, kategori_beban = ?, nominal = ?, keterangan = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$nama_beban, $kategori_beban,$nominal, $keterangan,$id]);

                // Update juga pada tabel `buku_kas` yang berelasi
                $ketKas = "Biaya Operasional: " . $nama_beban;
                if (!empty($keterangan)) {
                    $ketKas .= " (" . $keterangan . ")";
                }

                $stmtKasUpd =$this->pdo->prepare("
                    UPDATE buku_kas 
                    SET kategori = 'Biaya Operasional', nominal = ?, keterangan = ? 
                    WHERE ref_tipe = 'beban_operasional' AND ref_id = ?
                ");
                $stmtKasUpd->execute([$nominal, $ketKas,$id]);

                $this->pdo->commit();
                return ['status' => true, 'message' => 'Biaya operasional berhasil diperbarui!'];

            } else {
                // INSERT DATA BARU
                $stmtIns =$this->pdo->prepare("
                    INSERT INTO beban_operasional (tanggal, nama_beban, kategori_beban, nominal, keterangan)
                    VALUES (NOW(), ?, ?, ?, ?)
                ");
                $stmtIns->execute([$nama_beban,$kategori_beban, $nominal,$keterangan]);
                $bebanId =$this->pdo->lastInsertId();

                // Catat ke tabel `buku_kas`
                $ketKas = "Biaya Operasional: " . $nama_beban;
                if (!empty($keterangan)) {
                    $ketKas .= " (" . $keterangan . ")";
                }

                $stmtKas =$this->pdo->prepare("
                    INSERT INTO buku_kas (tanggal, tipe, kategori, nominal, keterangan, ref_tipe, ref_id)
                    VALUES (NOW(), 'keluar', 'Biaya Operasional', ?, ?, 'beban_operasional', ?)
                ");
                $stmtKas->execute([$nominal, $ketKas,$bebanId]);

                $this->pdo->commit();
                return ['status' => true, 'message' => 'Biaya operasional berhasil dicatat ke Buku Kas!'];
            }

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {$this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()];
        }
    }

    // Hapus Pengeluaran (dan hapus pencatatan kas terkait)
    public function hapusBeban($id) {
        try {
            $this->pdo->beginTransaction();

            $id = (int)$id;
            if ($id <= 0) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'ID tidak valid!'];
            }

            // 1. Hapus dari `buku_kas`
            $stmtKas =$this->pdo->prepare("DELETE FROM buku_kas WHERE ref_tipe = 'beban_operasional' AND ref_id = ?");
            $stmtKas->execute([$id]);

            // 2. Hapus dari `beban_operasional`
            $stmtBeban =$this->pdo->prepare("DELETE FROM beban_operasional WHERE id = ?");
            $stmtBeban->execute([$id]);

            $this->pdo->commit();
            return ['status' => true, 'message' => 'Data pengeluaran operasional berhasil dihapus!'];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {$this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()];
        }
    }

    // Hitung total data beban operasional berdasarkan filter tanggal & kata kunci
    public function countBebanFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "SELECT COUNT(*) FROM beban_operasional WHERE 1=1";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (nama_beban LIKE ? OR kategori_beban LIKE ? OR nominal LIKE ? OR keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Ambil data beban operasional terfilter dengan pagination
    public function getBebanFiltered($dari, $sampai, $keyword, $semua_tanggal, $limit, $offset) {
        try {
            $sql = "SELECT * FROM beban_operasional WHERE 1=1";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (nama_beban LIKE ? OR kategori_beban LIKE ? OR nominal LIKE ? OR keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam, $keywordParam);
            }

            $sql .= " ORDER BY tanggal DESC, id DESC LIMIT ? OFFSET ?";
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

    // Hitung total nominal pengeluaran berdasarkan filter aktif
    public function getTotalNominalBebanFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "SELECT SUM(nominal) FROM beban_operasional WHERE 1=1";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (nama_beban LIKE ? OR kategori_beban LIKE ? OR nominal LIKE ? OR keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}