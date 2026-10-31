<?php
class ProductController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo =$pdo_conn;
    }

    // Simpan atau Edit Master Produk
    public function simpanProduk($data) {
        try {
            $id = isset($data['id']) ? trim($data['id']) : '';
            $nama_produk = trim($data['nama_produk'] ?? '');
            $kategori_kepemilikan = trim($data['kategori_kepemilikan'] ?? 'sendiri');
            $kelola_stok = isset($data['kelola_stok']) ? 1 : 0;
            $vendor_id = !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null;
            
            $stok = isset($data['stok']) ? (int)$data['stok'] : 0;
            $harga_jual = isset($data['harga_jual']) ? (float)$data['harga_jual'] : 0;
            
            if ($kategori_kepemilikan === 'sendiri') {$harga_beli = isset($data['harga_beli']) ? (float)$data['harga_beli'] : 0;
                $qty_beli = isset($data['qty_beli']) ? (int)$data['qty_beli'] : 1;
                $harga_titip = 0;
                $vendor_id = null;
            } else {
                $harga_beli = 0;
                $qty_beli = 0;
                $harga_titip = isset($data['harga_titip']) ? (float)$data['harga_titip'] : 0;
                $kelola_stok = 1; // Konsinyasi wajib lacak stok
            }

            $hpp = isset($data['hpp']) ? (float)$data['hpp'] : 0;
            $margin_rp = isset($data['margin_rp']) ? (float)$data['margin_rp'] : 0;
            $margin_persen = isset($data['margin_persen']) ? (float)$data['margin_persen'] : 0;
            $saran_25 = isset($data['saran_25']) ? (float)$data['saran_25'] : 0;
            $saran_50 = isset($data['saran_50']) ? (float)$data['saran_50'] : 0;
            $saran_75 = isset($data['saran_75']) ? (float)$data['saran_75'] : 0;
            $saran_100 = isset($data['saran_100']) ? (float)$data['saran_100'] : 0;

            if (empty($nama_produk)) {
                return ['status' => false, 'message' => 'Nama produk tidak boleh kosong!'];
            }

            if ($kategori_kepemilikan === 'konsinyasi' && empty($vendor_id)) {
                return ['status' => false, 'message' => 'Vendor wajib dipilih untuk produk konsinyasi!'];
            }

            if (!empty($id)) {
                // Ambil data produk lama di database untuk validasi stok dan kategori
                $stmtOld = $this->pdo->prepare("SELECT kategori_kepemilikan, harga_titip, stok FROM produk WHERE id = ?");
                $stmtOld->execute([$id]);
                $oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

                // Jika produk konsinyasi dan stok masih > 0, pastikan harga_titip tidak berubah dari harga lama
                if ($oldData && $oldData['kategori_kepemilikan'] === 'konsinyasi' && (int)$oldData['stok'] > 0) {
                    $harga_titip_baru = isset($data['harga_titip']) ? (float)$data['harga_titip'] : 0;
                    if ((float)$oldData['harga_titip'] !== $harga_titip_baru) {
                        return ['status' => false, 'message' => 'Gagal! Harga titip tidak dapat diubah selama masih ada sisa stok di kasir (' . $oldData['stok'] . ' Pcs).'];
                    }
                }

                // UPDATE
                $sql = "UPDATE produk SET 
                            nama_produk = ?, kategori_kepemilikan = ?, kelola_stok = ?, vendor_id = ?, 
                            harga_beli = ?, qty_beli = ?, harga_titip = ?, hpp = ?, harga_jual = ?, 
                            margin_rp = ?, margin_persen = ?, saran_25 = ?, saran_50 = ?, saran_75 = ?, saran_100 = ?
                        WHERE id = ?";
                $stmt =$this->pdo->prepare($sql);$stmt->execute([
                    $nama_produk,$kategori_kepemilikan, $kelola_stok,$vendor_id,
                    $harga_beli,$qty_beli, $harga_titip,$hpp, $harga_jual,$margin_rp, $margin_persen,$saran_25, $saran_50,$saran_75, $saran_100,$id
                ]);
                return ['status' => true, 'message' => 'Produk berhasil diperbarui!'];
            } else {
                // INSERT BARU
                $sql = "INSERT INTO produk (
                            nama_produk, kategori_kepemilikan, kelola_stok, vendor_id, 
                            stok, harga_beli, qty_beli, harga_titip, hpp, harga_jual, 
                            margin_rp, margin_persen, saran_25, saran_50, saran_75, saran_100
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt =$this->pdo->prepare($sql);$stmt->execute([
                    $nama_produk,$kategori_kepemilikan, $kelola_stok,$vendor_id,
                    $stok,$harga_beli, $qty_beli,$harga_titip, $hpp,$harga_jual,
                    $margin_rp,$margin_persen, $saran_25,$saran_50, $saran_75,$saran_100
                ]);
                return ['status' => true, 'message' => 'Produk baru berhasil disimpan!'];
            }
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Database Error: ' . $e->getMessage()];
        }
    }

    // Proses Restock dengan Aturan: Blokir Restock Baru Jika Stok Lama Belum Diretur/Dilunasi
    public function prosesRestock($data) {
        try {
            $this->pdo->beginTransaction();

            $produk_id = (int)($data['produk_id'] ?? 0);$kategori = $data['kategori_kepemilikan'] ?? 'sendiri';$vendor_id = !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null;
            $qty_masuk = (int)($data['qty_masuk'] ?? 0);
            $total_biaya = (float)($data['total_biaya_kulakan'] ?? 0);
            $keterangan = trim($data['keterangan'] ?? 'Restock produk');

            // --- CEK PENGATURAN TANGGAL MANUAL ---
            $stmtSetting = $this->pdo->query("SELECT param_value FROM pengaturan WHERE param_key = 'enable_manual_date'");
            $isManualDateEnabled = ($stmtSetting->fetchColumn() === '1');

            $tanggalRestock = date('Y-m-d H:i:s');
            if ($isManualDateEnabled && !empty($data['tanggal_manual'])) {
                $tanggalRestock = $data['tanggal_manual'] . ' ' . date('H:i:s');
            }

            if ($produk_id <= 0 || $qty_masuk <= 0) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Data restock tidak valid!'];
            }

            // --- VALIDASI KEAMANAN: CEK APAKAH KELOLA STOK AKTIF ---
            $stmtCek =$this->pdo->prepare("SELECT kelola_stok, kategori_kepemilikan, nama_produk FROM produk WHERE id = ?");
            $stmtCek->execute([$produk_id]);
            $produk =$stmtCek->fetch();

            if (!$produk) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Produk tidak ditemukan di database!'];
            }

            if ((int)$produk['kelola_stok'] !== 1) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Gagal! Produk ini diset "Bebas Stok", sehingga tidak dapat direstock.'];
            }

            // --- ATURAN BARU: JIKA KONSINYASI, CEK APAKAH ADA BATCH AKTIF (BELUM SETTLED) ---
            if ($kategori === 'konsinyasi') {
                $stmtCekAktif =$this->pdo->prepare("SELECT COUNT(*) FROM penerimaan_titipan WHERE produk_id = ? AND is_settled = 0");
                $stmtCekAktif->execute([$produk_id]);
                $adaBatchAktif =$stmtCekAktif->fetchColumn();

                if ($adaBatchAktif > 0) {$this->pdo->rollBack();
                    return ['status' => false, 'message' => 'Gagal Restock! Selesaikan retur dan setoran sisa stok sebelumnya terlebih dahulu sebelum melakukan restock baru.'];
                }
            }

            // 1. Tambah stok produk saat ini
            $stmt =$this->pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
            $stmt->execute([$qty_masuk,$produk_id]);

            // 2. Jika Konsinyasi, catat ke tabel `penerimaan_titipan`
            if ($kategori === 'konsinyasi') {
                $stmtP =$this->pdo->prepare("SELECT harga_titip FROM produk WHERE id = ?");
                $stmtP->execute([$produk_id]);
                $prod =$stmtP->fetch();
                $harga_titip =$prod['harga_titip'] ?? 0;

                // Ambil prefix_konsinyasi dari tabel pengaturan database
                $stmtPrefix = $this->pdo->prepare("SELECT param_value FROM pengaturan WHERE param_key = 'prefix_konsinyasi'");
                $stmtPrefix->execute();
                $prefixVal = $stmtPrefix->fetchColumn();
                $prefixKonsinyasi = !empty($prefixVal) ? $prefixVal : 'KONSI-';

                // Generate nomor nota restock konsinyasi
                $no_nota = $prefixKonsinyasi . str_replace('.', '', microtime(true));

                $stmtIns =$this->pdo->prepare("
                  INSERT INTO penerimaan_titipan (no_nota_terima, vendor_id, produk_id, qty_masuk, qty_sisa, harga_titip, keterangan, is_settled, tanggal_terima) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
                ");
                $stmtIns->execute([$no_nota, $vendor_id,$produk_id, $qty_masuk,$qty_masuk, $harga_titip,$keterangan, $tanggalRestock]);
            } 
            // 3. Jika Milik Sendiri DAN ada biaya kulakan (> 0), catat otomatis ke tabel `buku_kas`
            else if ($kategori === 'sendiri' && $total_biaya > 0) {$ketKas = "Restock " . $produk['nama_produk'] . " (+" . $qty_masuk . " Pcs)";
                if (!empty($keterangan)) {
                    $ketKas .= " - " . $keterangan;
                }

                $stmtKas =$this->pdo->prepare("
                  INSERT INTO buku_kas (tanggal, tipe, kategori, nominal, keterangan, ref_tipe, ref_id)
                  VALUES (?, 'keluar', 'Belanja Produk', ?, ?, 'restock', ?)
                ");
                $stmtKas->execute([$tanggalRestock, $total_biaya, $ketKas, $produk_id]);
            }

            $this->pdo->commit();
            return [
                'status' => true, 
                'message' => 'Restock berhasil disimpan' . ($total_biaya > 0 ? ' dan biaya kulakan otomatis tercatat di Buku Kas!' : '!')
            ];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {$this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal Restock: ' . $e->getMessage()];
        }
    }

    // Proses Retur Mutlak: Sisa Stok Wajib Ditarik Habis & Batch Ditutup Tuntas
   public function prosesRetur($data) {
        // Ambil prefix_retur dari tabel pengaturan database
        $stmtPrefix = $this->pdo->prepare("SELECT param_value FROM pengaturan WHERE param_key = 'prefix_retur'");
        $stmtPrefix->execute();
        $prefixVal = $stmtPrefix->fetchColumn();
        $prefixRetur = !empty($prefixVal) ? $prefixVal : 'RETUR-';

        // Generate nomor nota retur konsinyasi
        $no_nota = $prefixRetur . str_replace('.', '', microtime(true));
        
        try {
            $this->pdo->beginTransaction();

            $produk_id = (int)($data['produk_id'] ?? 0);
            $vendor_id = (int)($data['vendor_id'] ?? 0);
            $keterangan = trim($data['keterangan'] ?? 'Retur sisa titipan');

            // --- CEK PENGATURAN TANGGAL MANUAL ---
            $stmtSetting = $this->pdo->query("SELECT param_value FROM pengaturan WHERE param_key = 'enable_manual_date'");
            $isManualDateEnabled = ($stmtSetting->fetchColumn() === '1');

            $tanggalRetur = date('Y-m-d H:i:s');
            if ($isManualDateEnabled && !empty($data['tanggal_manual'])) {
                $tanggalRetur = $data['tanggal_manual'] . ' ' . date('H:i:s');
            }

            if ($produk_id <= 0) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Data retur tidak valid!'];
            }

            $stmtProd =$this->pdo->prepare("SELECT nama_produk FROM produk WHERE id = ?");
            $stmtProd->execute([$produk_id]);
            $prodInfo =$stmtProd->fetch(PDO::FETCH_ASSOC);
            
            if (!$prodInfo) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Produk tidak ditemukan!'];
            }
            $namaProduk =$prodInfo['nama_produk'];

            // 1. Ambil batch aktif yang belum lunas (is_settled = 0) untuk produk ini
            $stmtBatch =$this->pdo->prepare("
                SELECT id, qty_masuk, qty_sisa, harga_titip FROM penerimaan_titipan 
                WHERE produk_id = ? AND is_settled = 0 
                ORDER BY tanggal_terima ASC, id ASC LIMIT 1
            ");
            $stmtBatch->execute([$produk_id]);
            $batchAktif =$stmtBatch->fetch(PDO::FETCH_ASSOC);

            if (!$batchAktif) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Tidak ada batch aktif atau sisa stok untuk diretur!'];
            }

            $batchId =$batchAktif['id'];
            $stokAwalBatch = (int)$batchAktif['qty_masuk'];
            $sisaStokKasir = (int)$batchAktif['qty_sisa'];
            $harga_titip = (float)$batchAktif['harga_titip'];

            // Aturan Baru: Sisa stok di kasir wajib ditarik habis semua (Full Retur)
            $qty_retur =$sisaStokKasir;
            $qty_terjual = $stokAwalBatch -$sisaStokKasir;
            $total_setoran = $qty_terjual * $harga_titip;

            // 2. Tutup tuntas batch penerimaan (qty_sisa menjadi 0, is_settled menjadi 1)
            $stmtUpdBatch =$this->pdo->prepare("UPDATE penerimaan_titipan SET qty_sisa = 0, is_settled = 1 WHERE id = ?");
            $stmtUpdBatch->execute([$batchId]);

            // 3. Update stok fisik di tabel produk menjadi 0
            $stmtUpdProduk =$this->pdo->prepare("UPDATE produk SET stok = 0 WHERE id = ?");
            $stmtUpdProduk->execute([$produk_id]);

            // 4. Catat riwayat ke tabel `retur_titipan`
            $stmtInsRetur =$this->pdo->prepare("INSERT INTO retur_titipan (no_nota_retur, vendor_id, produk_id, stok_awal, qty_retur, qty_terjual, harga_titip, total_setoran, keterangan, tanggal_retur) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsRetur->execute([$no_nota, $vendor_id, $produk_id, $stokAwalBatch, $qty_retur, $qty_terjual, $harga_titip, $total_setoran, $keterangan, $tanggalRetur]);
            $returId =$this->pdo->lastInsertId();

            // 5. Catat otomatis ke `buku_kas` jika ada setoran wajib dibayar
            if ($total_setoran > 0) {$ketKas = "Setoran Vendor untuk " . $namaProduk . " (Terjual: " . $qty_terjual . " Pcs)";
                if (!empty($keterangan)) {
                    $ketKas .= " - " . $keterangan;
                }

                $stmtKas =$this->pdo->prepare("INSERT INTO buku_kas (tanggal, tipe, kategori, nominal, keterangan, ref_tipe, ref_id) VALUES (?, 'keluar', 'Setoran Vendor', ?, ?, 'retur_vendor', ?)");
                $stmtKas->execute([$tanggalRetur, $total_setoran, $ketKas, $returId]);
            }

            $this->pdo->commit();
            return [
                'status' => true, 
                'message' => 'Retur berhasil diproses. Sisa stok ditarik habis, batch dilunasi, dan stok kasir kini menjadi 0 Pcs.'
            ];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {$this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal Retur: ' . $e->getMessage()];
        }
    }

    // Ubah Status Produk (Aktif / Non-Aktif / Arsip)
    public function toggleStatus($data) {
        try {
            $produk_id = (int)($data['produk_id'] ?? 0);
            if ($produk_id <= 0) {
                return ['status' => false, 'message' => 'ID Produk tidak valid!'];
            }

            // Ambil status saat ini
            $stmtCek = $this->pdo->prepare("SELECT status, nama_produk FROM produk WHERE id = ?");
            $stmtCek->execute([$produk_id]);
            $produk = $stmtCek->fetch();

            if (!$produk) {
                return ['status' => false, 'message' => 'Produk tidak ditemukan!'];
            }

            // Balik statusnya (jika aktif jadi nonaktif, jika nonaktif jadi aktif)
            $statusBaru = ($produk['status'] === 'nonaktif') ? 'aktif' : 'nonaktif';

            $stmtUp = $this->pdo->prepare("UPDATE produk SET status = ? WHERE id = ?");
            $stmtUp->execute([$statusBaru, $produk_id]);

            $pesan = ($statusBaru === 'nonaktif') 
                ? "Produk '{$produk['nama_produk']}' berhasil dinonaktifkan (diarsipkan)." 
                : "Produk '{$produk['nama_produk']}' berhasil diaktifkan kembali.";

            return ['status' => true, 'message' => $pesan];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Gagal mengubah status: ' . $e->getMessage()];
        }
    }

    // Hitung total produk berdasarkan pencarian kata kunci
    public function countProdukFiltered($keyword) {
        try {
            $sql = "SELECT COUNT(p.id) FROM produk p LEFT JOIN vendor v ON p.vendor_id = v.id WHERE 1=1";
            $params = [];

            if (!empty($keyword)) {
                $sql .= " AND (p.nama_produk LIKE ? OR v.nama_vendor LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Ambil data produk terfilter dengan pagination
    public function getProdukFiltered($keyword, $limit, $offset) {
        try {
            $sql = "
                SELECT p.*, v.nama_vendor, 
                       COALESCE((SELECT SUM(qty_masuk) FROM penerimaan_titipan WHERE produk_id = p.id AND is_settled = 0), p.stok) AS stok_awal_masuk 
                FROM produk p 
                LEFT JOIN vendor v ON p.vendor_id = v.id 
                WHERE 1=1
            ";
            $params = [];

            if (!empty($keyword)) {
                $sql .= " AND (p.nama_produk LIKE ? OR v.nama_vendor LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam);
            }

            // Tambahkan limit dan offset langsung ke array params agar tereksekusi dengan aman
            $sql .= " ORDER BY p.nama_produk LIMIT ? OFFSET ?";
            array_push($params, (int)$limit, (int)$offset);

            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameter secara dinamis berdasarkan tipenya
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

    // Ambil riwayat restock produk sendiri dengan filter dan pagination
    public function getRiwayatRestockSendiriFiltered($dari, $sampai, $keyword, $semua_tanggal, $limit, $offset) {
        try {
            $sql = "
                SELECT bk.*, p.nama_produk 
                FROM buku_kas bk
                LEFT JOIN produk p ON bk.ref_id = p.id
                WHERE bk.ref_tipe = 'restock' AND p.kategori_kepemilikan = 'sendiri'
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(bk.tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (p.nama_produk LIKE ? OR bk.keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam);
            }

            $sql .= " ORDER BY bk.tanggal DESC, bk.id DESC LIMIT ? OFFSET ?";
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

    // Hitung total data riwayat restock sendiri untuk pagination
    public function countRiwayatRestockSendiriFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "
                SELECT COUNT(bk.id) 
                FROM buku_kas bk
                LEFT JOIN produk p ON bk.ref_id = p.id
                WHERE bk.ref_tipe = 'restock' AND p.kategori_kepemilikan = 'sendiri'
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(bk.tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (p.nama_produk LIKE ? OR bk.keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Hitung total nominal biaya restock sendiri berdasarkan filter
    public function getTotalBiayaRestockSendiriFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "
                SELECT SUM(bk.nominal) 
                FROM buku_kas bk
                LEFT JOIN produk p ON bk.ref_id = p.id
                WHERE bk.ref_tipe = 'restock' AND p.kategori_kepemilikan = 'sendiri'
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(bk.tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (p.nama_produk LIKE ? OR bk.keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Ambil riwayat restock konsinyasi dengan filter, pencarian, dan pagination
    public function getRiwayatRestockKonsinyasiFiltered($dari, $sampai, $keyword, $semua_tanggal, $limit, $offset) {
        try {
            $sql = "
                SELECT pt.*, p.nama_produk, v.nama_vendor 
                FROM penerimaan_titipan pt
                JOIN produk p ON pt.produk_id = p.id
                LEFT JOIN vendor v ON pt.vendor_id = v.id
                WHERE 1=1
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(pt.tanggal_terima) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (pt.no_nota_terima LIKE ? OR p.nama_produk LIKE ? OR v.nama_vendor LIKE ? OR pt.keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam, $keywordParam);
            }

            $sql .= " ORDER BY pt.tanggal_terima DESC, pt.id DESC LIMIT ? OFFSET ?";
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

    // Hitung total data riwayat restock konsinyasi untuk pagination
    public function countRiwayatRestockKonsinyasiFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "
                SELECT COUNT(pt.id) 
                FROM penerimaan_titipan pt
                JOIN produk p ON pt.produk_id = p.id
                LEFT JOIN vendor v ON pt.vendor_id = v.id
                WHERE 1=1
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(pt.tanggal_terima) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (pt.no_nota_terima LIKE ? OR p.nama_produk LIKE ? OR v.nama_vendor LIKE ? OR pt.keterangan LIKE ?)";
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

    // Ambil rekap total qty masuk restock konsinyasi berdasarkan filter
    public function getRekapRestockKonsinyasiFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "
                SELECT SUM(pt.qty_masuk) as total_qty, COUNT(pt.id) as total_nota 
                FROM penerimaan_titipan pt
                JOIN produk p ON pt.produk_id = p.id
                LEFT JOIN vendor v ON pt.vendor_id = v.id
                WHERE 1=1
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(pt.tanggal_terima) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (pt.no_nota_terima LIKE ? OR p.nama_produk LIKE ? OR v.nama_vendor LIKE ? OR pt.keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_qty' => $res['total_qty'] ?? 0,
                'total_nota' => $res['total_nota'] ?? 0
            ];
        } catch (PDOException $e) {
            return ['total_qty' => 0, 'total_nota' => 0];
        }
    }

    // Ambil riwayat retur konsinyasi dengan filter, pencarian, dan pagination
    public function getRiwayatReturKonsinyasiFiltered($dari, $sampai, $keyword, $semua_tanggal, $limit, $offset) {
        try {
            $sql = "
                SELECT rt.*, p.nama_produk, v.nama_vendor 
                FROM retur_titipan rt
                JOIN produk p ON rt.produk_id = p.id
                LEFT JOIN vendor v ON rt.vendor_id = v.id
                WHERE 1=1
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(rt.tanggal_retur) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (rt.no_nota_retur LIKE ? OR p.nama_produk LIKE ? OR v.nama_vendor LIKE ? OR rt.keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam, $keywordParam);
            }

            $sql .= " ORDER BY rt.tanggal_retur DESC, rt.id DESC LIMIT ? OFFSET ?";
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

    // Hitung total data riwayat retur konsinyasi untuk pagination
    public function countRiwayatReturKonsinyasiFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "
                SELECT COUNT(rt.id) 
                FROM retur_titipan rt
                JOIN produk p ON rt.produk_id = p.id
                LEFT JOIN vendor v ON rt.vendor_id = v.id
                WHERE 1=1
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(rt.tanggal_retur) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (rt.no_nota_retur LIKE ? OR p.nama_produk LIKE ? OR v.nama_vendor LIKE ? OR rt.keterangan LIKE ?)";
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

    // Ambil rekap total setoran dan barang terjual berdasarkan filter
    public function getRekapReturKonsinyasiFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "
                SELECT SUM(rt.total_setoran) as total_setoran, SUM(rt.qty_terjual) as total_terjual, SUM(rt.qty_retur) as total_retur 
                FROM retur_titipan rt
                JOIN produk p ON rt.produk_id = p.id
                LEFT JOIN vendor v ON rt.vendor_id = v.id
                WHERE 1=1
            ";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(rt.tanggal_retur) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (rt.no_nota_retur LIKE ? OR p.nama_produk LIKE ? OR v.nama_vendor LIKE ? OR rt.keterangan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_setoran' => $res['total_setoran'] ?? 0,
                'total_terjual' => $res['total_terjual'] ?? 0,
                'total_retur' => $res['total_retur'] ?? 0
            ];
        } catch (PDOException $e) {
            return ['total_setoran' => 0, 'total_terjual' => 0, 'total_retur' => 0];
        }
    }
}