<?php
class KasLainnyaController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo = $pdo_conn;
    }

    // Simpan atau Edit Kas Lainnya (Modal / Prive / Lain-Lain) ke Buku Kas
    public function simpanKasLainnya($data) {
        try {
            $this->pdo->beginTransaction();

            $id = !empty($data['id']) ? (int)$data['id'] : 0;
            $tipe = $data['tipe'] ?? 'masuk'; // 'masuk' atau 'keluar'
            $kategori = $data['kategori'] ?? 'Lain-Lain'; // 'Modal / Prive' atau 'Lain-Lain'
            $nominal = (float)($data['nominal'] ?? 0);
            $keterangan = trim($data['keterangan'] ?? '');

            // Cek pengaturan tanggal manual
            $stmtSetting = $this->pdo->query("SELECT param_value FROM pengaturan WHERE param_key = 'enable_manual_date'");
            $isManualDateEnabled = ($stmtSetting->fetchColumn() === '1');

            if ($nominal <= 0) {
                $this->pdo->rollBack();
                return ['status' => false, 'message' => 'Nominal wajib diisi dan lebih besar dari 0!'];
            }

            if (!in_array($kategori, ['Modal / Prive', 'Lain-Lain'])) {
                $this->pdo->rollBack();
                return ['status' => false, 'message' => 'Kategori tidak valid!'];
            }

            if ($id > 0) {
                // --- PROSES UPDATE DATA ---
                if ($isManualDateEnabled && !empty($data['tanggal_manual'])) {
                    // Jika manual aktif dan diisi, perbarui data termasuk tanggalnya
                    $tanggalKas = $data['tanggal_manual'] . ' ' . date('H:i:s');
                    
                    $stmtUpd = $this->pdo->prepare("
                        UPDATE buku_kas 
                        SET tanggal = ?, tipe = ?, kategori = ?, nominal = ?, keterangan = ?
                        WHERE id = ? AND ref_tipe = 'kas_lainnya'
                    ");
                    $stmtUpd->execute([$tanggalKas, $tipe, $kategori, $nominal, $keterangan, $id]);
                } else {
                    // Jika manual tidak aktif, UPDATE data KECUALI kolom tanggal (pertahankan tanggal lama)
                    $stmtUpd = $this->pdo->prepare("
                        UPDATE buku_kas 
                        SET tipe = ?, kategori = ?, nominal = ?, keterangan = ?
                        WHERE id = ? AND ref_tipe = 'kas_lainnya'
                    ");
                    $stmtUpd->execute([$tipe, $kategori, $nominal, $keterangan, $id]);
                }

                $this->pdo->commit();
                return ['status' => true, 'message' => 'Data kas berhasil diperbarui!'];

            } else {
                // --- PROSES INSERT DATA BARU ---
                $tanggalKas = date('Y-m-d H:i:s');
                if ($isManualDateEnabled && !empty($data['tanggal_manual'])) {
                    $tanggalKas = $data['tanggal_manual'] . ' ' . date('H:i:s');
                }

                $stmtIns = $this->pdo->prepare("
                    INSERT INTO buku_kas (tanggal, tipe, kategori, nominal, keterangan, ref_tipe, ref_id)
                    VALUES (?, ?, ?, ?, ?, 'kas_lainnya', NULL)
                ");
                $stmtIns->execute([$tanggalKas, $tipe, $kategori, $nominal, $keterangan]);
                
                // Update ref_id agar menunjuk ke ID-nya sendiri untuk kemudahan pelacakan
                $lastId = $this->pdo->lastInsertId();
                $this->pdo->prepare("UPDATE buku_kas SET ref_id = ? WHERE id = ?")->execute([$lastId, $lastId]);

                $this->pdo->commit();
                return ['status' => true, 'message' => 'Transaksi kas berhasil dicatat!'];
            }

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()];
        }
    }

    // Hapus Transaksi Kas Lainnya
    public function hapusKasLainnya($id) {
        try {
            $this->pdo->beginTransaction();

            $id = (int)$id;
            if ($id <= 0) {
                $this->pdo->rollBack();
                return ['status' => false, 'message' => 'ID tidak valid!'];
            }

            $stmt = $this->pdo->prepare("DELETE FROM buku_kas WHERE id = ? AND ref_tipe = 'kas_lainnya'");
            $stmt->execute([$id]);

            $this->pdo->commit();
            return ['status' => true, 'message' => 'Data transaksi kas berhasil dihapus!'];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal menghapus data: ' . $e->getMessage()];
        }
    }

    // Hitung total data kas lainnya berdasarkan filter tanggal & kata kunci
    public function countKasFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "SELECT COUNT(*) FROM buku_kas WHERE ref_tipe = 'kas_lainnya'";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (keterangan LIKE ? OR kategori LIKE ? OR tipe LIKE ? OR nominal LIKE ?)";
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

    // Ambil data kas lainnya terfilter dengan pagination
    public function getKasFiltered($dari, $sampai, $keyword, $semua_tanggal, $limit, $offset) {
        try {
            $sql = "SELECT * FROM buku_kas WHERE ref_tipe = 'kas_lainnya'";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (keterangan LIKE ? OR kategori LIKE ? OR tipe LIKE ? OR nominal LIKE ?)";
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
}