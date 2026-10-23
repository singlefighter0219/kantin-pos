<?php
class PosController {
    private $pdo;

    public function __construct($pdo_conn) {
        $this->pdo =$pdo_conn;
    }

    // Ambil daftar produk aktif untuk katalog POS
    public function getProdukAktif() {
        try {
            $stmt =$this->pdo->query("SELECT * FROM produk WHERE status != 'nonaktif' ORDER BY nama_produk ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Ambil isi keranjang berdasarkan user yang sedang login
    public function getKeranjang($user_id) {
        try {
            $stmt =$this->pdo->prepare("
                SELECT k.id as keranjang_id, k.qty, p.id as produk_id, p.nama_produk, p.harga_jual, p.stok, p.kelola_stok
                FROM keranjang k
                JOIN produk p ON k.produk_id = p.id
                WHERE k.user_id = ?
                ORDER BY k.created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Tambah atau Update Produk ke Keranjang Database
    public function tambahKeranjang($user_id,$produk_id) {
        try {
            // Cek stok produk terlebih dahulu
            $stmtP =$this->pdo->prepare("SELECT stok, kelola_stok, nama_produk FROM produk WHERE id = ?");
            $stmtP->execute([$produk_id]);
            $prod =$stmtP->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                return ['status' => false, 'message' => 'Produk tidak ditemukan!'];
            }

            // Cek apakah produk sudah ada di keranjang user ini
            $stmtCek =$this->pdo->prepare("SELECT id, qty FROM keranjang WHERE user_id = ? AND produk_id = ?");
            $stmtCek->execute([$user_id,$produk_id]);
            $item =$stmtCek->fetch(PDO::FETCH_ASSOC);

            $current_qty_in_cart = $item ? (int)$item['qty'] : 0;
            $new_qty =$current_qty_in_cart + 1;

            if ($prod['kelola_stok'] == 1 && $prod['stok'] <$new_qty) {
                return ['status' => false, 'message' => 'Stok produk ' . $prod['nama_produk'] . ' tidak mencukupi!'];
            }

            if ($item) {
                // Update Qty
                $stmtUpd =$this->pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?");
                $stmtUpd->execute([$new_qty,$item['id']]);
            } else {
                // Insert Baru
                $stmtIns =$this->pdo->prepare("INSERT INTO keranjang (user_id, produk_id, qty) VALUES (?, ?, 1)");
                $stmtIns->execute([$user_id,$produk_id]);
            }

            return ['status' => true, 'message' => 'Produk ditambahkan ke keranjang'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Gagal menambah keranjang: ' . $e->getMessage()];
        }
    }

    // Ubah Qty item di keranjang
    public function updateQtyKeranjang($user_id, $keranjang_id,$delta) {
        try {
            $stmt =$this->pdo->prepare("
                SELECT k.id, k.qty, p.stok, p.kelola_stok, p.nama_produk 
                FROM keranjang k 
                JOIN produk p ON k.produk_id = p.id 
                WHERE k.id = ? AND k.user_id = ?
            ");
            $stmt->execute([$keranjang_id,$user_id]);
            $item =$stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                return ['status' => false, 'message' => 'Item keranjang tidak ditemukan!'];
            }

            $new_qty = $item['qty'] +$delta;

            if ($new_qty <= 0) {
                // Hapus jika qty <= 0
                return $this->hapusItemKeranjang($user_id,$keranjang_id);
            }

            if ($item['kelola_stok'] == 1 && $item['stok'] <$new_qty) {
                return ['status' => false, 'message' => 'Stok tidak mencukupi untuk ' . $item['nama_produk']];
            }

            $stmtUpd =$this->pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?");
            $stmtUpd->execute([$new_qty,$keranjang_id]);

            return ['status' => true, 'message' => 'Jumlah diperbarui'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Gagal mengubah qty'];
        }
    }

    // Set Qty item di keranjang secara langsung
    public function setQtyKeranjang($user_id, $keranjang_id, $qty_baru) {
        try {
            if ($qty_baru <= 0) {
                return $this->hapusItemKeranjang($user_id, $keranjang_id);
            }

            $stmt = $this->pdo->prepare("
                SELECT k.id, p.stok, p.kelola_stok, p.nama_produk 
                FROM keranjang k 
                JOIN produk p ON k.produk_id = p.id 
                WHERE k.id = ? AND k.user_id = ?
            ");
            $stmt->execute([$keranjang_id, $user_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                return ['status' => false, 'message' => 'Item keranjang tidak ditemukan!'];
            }

            if ($item['kelola_stok'] == 1 && $item['stok'] < $qty_baru) {
                return ['status' => false, 'message' => 'Stok tidak mencukupi untuk ' . $item['nama_produk'] . ' (Sisa: ' . $item['stok'] . ')'];
            }

            $stmtUpd = $this->pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?");
            $stmtUpd->execute([$qty_baru, $keranjang_id]);

            return ['status' => true, 'message' => 'Jumlah diperbarui'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Gagal mengubah qty: ' . $e->getMessage()];
        }
    }

    // Hapus satu item dari keranjang
    public function hapusItemKeranjang($user_id,$keranjang_id) {
        try {
            $stmt =$this->pdo->prepare("DELETE FROM keranjang WHERE id = ? AND user_id = ?");
            $stmt->execute([$keranjang_id,$user_id]);
            return ['status' => true, 'message' => 'Item dihapus dari keranjang'];
        } catch (PDOException $e) {
            return ['status' => false, 'message' => 'Gagal menghapus item'];
        }
    }

    // Proses Checkout Penjualan POS
    public function prosesCheckout($user_id,$data) {
        try {
            
            $this->pdo->beginTransaction();

            // Ambil semua isi keranjang user saat ini langsung dari database
            $cart_items =$this->getKeranjang($user_id);$total_belanja = (float)($data['total_belanja'] ?? 0);$bayar = (float)($data['bayar'] ?? 0);$kembalian = (float)($data['kembalian'] ?? 0);$catatan = trim($data['catatan'] ?? '');$nama_kasir = $_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'Kasir');

            if (empty($cart_items)) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Keranjang belanja masih kosong!'];
            }

            if ($bayar < $total_belanja) {$this->pdo->rollBack();
                return ['status' => false, 'message' => 'Jumlah pembayaran kurang dari total belanja!'];
            }

            // Ambil prefix_penjualan dari tabel pengaturan di database
            $stmtPrefix = $this->pdo->prepare("SELECT param_value FROM pengaturan WHERE param_key = 'prefix_penjualan'");
            $stmtPrefix->execute();
            $prefixVal = $stmtPrefix->fetchColumn();
            
            // Gunakan prefix dari database, fallback ke "POS-" jika kosong
            $prefixPenjualan = !empty($prefixVal) ? $prefixVal : 'POS-';

            // Generate Nomor Nota sesuai pengaturan database
            $no_nota = $prefixPenjualan . str_replace('.', '', microtime(true));
            
            $total_modal = 0;
            $total_laba = 0;
            $validated_items = [];

            // Validasi ulang stok dan ambil HPP/harga jual serta kategori kepemilikan terkini dari tabel produk
            foreach ($cart_items as$item) {
                $produk_id =$item['produk_id'];
                $qty_beli = (int)$item['qty'];

                $stmtP =$this->pdo->prepare("SELECT id, nama_produk, hpp, harga_jual, stok, kelola_stok, kategori_kepemilikan FROM produk WHERE id = ?");
                $stmtP->execute([$produk_id]);
                $prod =$stmtP->fetch(PDO::FETCH_ASSOC);

                if ($prod['kelola_stok'] == 1 &&$prod['stok'] < $qty_beli) {$this->pdo->rollBack();
                    return ['status' => false, 'message' => 'Stok tidak mencukupi untuk: ' . $prod['nama_produk']];
                }

                $hpp_satuan = (float)($prod['hpp'] ?? 0);$harga_jual_satuan = (float)$prod['harga_jual'];$subtotal = $harga_jual_satuan * $qty_beli;
                $subtotal_laba = ($harga_jual_satuan - $hpp_satuan) *$qty_beli;

                $total_modal += ($hpp_satuan * $qty_beli);
                $total_laba +=$subtotal_laba;

                $validated_items[] = [
                    'produk_id' => $produk_id,
                    'nama_produk' => $prod['nama_produk'],
                    'hpp' => $hpp_satuan,
                    'harga_jual' => $harga_jual_satuan,
                    'qty' => $qty_beli,
                    'subtotal' => $subtotal,
                    'subtotal_laba' => $subtotal_laba,
                    'kelola_stok' => $prod['kelola_stok'],
                    'kategori_kepemilikan' => $prod['kategori_kepemilikan']
                ];
            }

            // 1. Insert ke tabel `penjualan`
            $stmtInsPenjualan =$this->pdo->prepare("
                INSERT INTO penjualan (no_nota, tanggal, total_belanja, total_modal, total_laba, bayar, kembalian, kasir, catatan)
                VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsPenjualan->execute([
                $no_nota,$total_belanja, $total_modal,$total_laba, $bayar,$kembalian, $nama_kasir,$catatan
            ]);
            $penjualan_id =$this->pdo->lastInsertId();

            // 2. Insert ke `detail_penjualan`, Kurangi Stok Produk, & FIFO Penerimaan Titipan
            $stmtInsDetail =$this->pdo->prepare("
                INSERT INTO detail_penjualan (penjualan_id, produk_id, nama_produk, hpp, harga_jual, qty, subtotal, subtotal_laba)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtUpdStok =$this->pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");

            // Statement FIFO untuk penerimaan titipan konsinyasi
            $stmtBatch =$this->pdo->prepare("
                SELECT id, qty_sisa FROM penerimaan_titipan 
                WHERE produk_id = ? AND is_settled = 0 
                ORDER BY tanggal_terima ASC, id ASC
            ");
            $stmtUpdBatch =$this->pdo->prepare("UPDATE penerimaan_titipan SET qty_sisa = ?, is_settled = ? WHERE id = ?");

            foreach ($validated_items as $item) {$stmtInsDetail->execute([
                    $penjualan_id,$item['produk_id'],
                    $item['nama_produk'],$item['hpp'],
                    $item['harga_jual'],$item['qty'],
                    $item['subtotal'],$item['subtotal_laba']
                ]);

                if ($item['kelola_stok'] == 1) {$stmtUpdStok->execute([$item['qty'],$item['produk_id']]);

                    // Jika produk berjenis konsinyasi, kurangi qty_sisa di penerimaan_titipan secara FIFO
                    if (isset($item['kategori_kepemilikan']) &&$item['kategori_kepemilikan'] === 'konsinyasi') {
                        $stmtBatch->execute([$item['produk_id']]);
                        $batches =$stmtBatch->fetchAll(PDO::FETCH_ASSOC);

                        $sisaKurang = (int)$item['qty'];

                        foreach ($batches as$batch) {
                            if ($sisaKurang <= 0) break;

                            $batchId =$batch['id'];
                            $batchQtySisa = (int)$batch['qty_sisa'];

                            if ($sisaKurang >=$batchQtySisa) {
                                // Batch ini habis terjual -> qty_sisa jadi 0, tandai lunas (is_settled = 1)
                                $sisaKurang -=$batchQtySisa;
                                $stmtUpdBatch->execute([0, 1,$batchId]);
                            } else {
                                // Batch ini terjual sebagian -> qty_sisa berkurang, belum lunas
                                $newQtySisa = $batchQtySisa -$sisaKurang;
                                $sisaKurang = 0;
                                $stmtUpdBatch->execute([$newQtySisa, 0,$batchId]);
                            }
                        }
                    }
                }
            }

            // 3. Catat ke `buku_kas`
            $ketKas = "Penjualan Kasir (Nota: " . $no_nota . ")";
            if (!empty($catatan)) {
                $ketKas .= " - " . $catatan;
            }

            $stmtKas =$this->pdo->prepare("
                INSERT INTO buku_kas (tanggal, tipe, kategori, nominal, keterangan, ref_tipe, ref_id)
                VALUES (NOW(), 'masuk', 'Penjualan', ?, ?, 'penjualan_pos', ?)
            ");
            $stmtKas->execute([$total_belanja, $ketKas,$penjualan_id]);

            // 4. Kosongkan keranjang user ini setelah checkout berhasil
            $stmtClear =$this->pdo->prepare("DELETE FROM keranjang WHERE user_id = ?");
            $stmtClear->execute([$user_id]);

            $this->pdo->commit();
            return [
                'status' => true,
                'message' => 'Transaksi berhasil! Kembalian: Rp ' . number_format($kembalian, 0, ',', '.'), 'no_nota' =>$no_nota,
                'no_nota' => $no_nota,
                'penjualan_id'  => $penjualan_id
            ];

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {$this->pdo->rollBack();
            }
            return ['status' => false, 'message' => 'Gagal memproses transaksi: ' . $e->getMessage()];
        }
    }

    // Ambil daftar riwayat penjualan (bisa difilter tanggal)
    function getRiwayatPenjualan($limit = 100) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM penjualan 
                ORDER BY tanggal DESC 
                LIMIT ?
            ");
            // Bind parameter limit sebagai integer
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Ambil detail produk dari suatu transaksi penjualan
    function getDetailPenjualan($penjualan_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM detail_penjualan 
                WHERE penjualan_id = ?
            ");
            $stmt->execute([$penjualan_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Ambil riwayat penjualan dengan opsi filter tanggal aktif/nonaktif & pencarian
    public function getRiwayatPenjualanFiltered($dari, $sampai, $keyword, $semua_tanggal, $limit, $offset) {
        try {
            $sql = "SELECT * FROM penjualan WHERE 1=1";
            $params = [];

            // Jika checkbox "semua tanggal" tidak dicentang, batasi berdasarkan tanggal
            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (no_nota LIKE ? OR kasir LIKE ? OR catatan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam);
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

    // Hitung total data untuk pagination dengan opsi semua tanggal
    public function countRiwayatFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "SELECT COUNT(*) FROM penjualan WHERE 1=1";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (no_nota LIKE ? OR kasir LIKE ? OR catatan LIKE ?)";
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

    // Ambil rekap total (omset & laba) berdasarkan filter pencarian riwayat penjualan
    public function getRekapRiwayatFiltered($dari, $sampai, $keyword, $semua_tanggal) {
        try {
            $sql = "SELECT SUM(total_belanja) as total_omset, SUM(total_laba) as total_laba, COUNT(id) as total_transaksi FROM penjualan WHERE 1=1";
            $params = [];

            if (!$semua_tanggal) {
                $sql .= " AND DATE(tanggal) BETWEEN ? AND ?";
                array_push($params, $dari, $sampai);
            }

            if (!empty($keyword)) {
                $sql .= " AND (no_nota LIKE ? OR kasir LIKE ? OR catatan LIKE ?)";
                $keywordParam = "%{$keyword}%";
                array_push($params, $keywordParam, $keywordParam, $keywordParam);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total_omset' => $result['total_omset'] ?? 0,
                'total_laba' => $result['total_laba'] ?? 0,
                'total_transaksi' => $result['total_transaksi'] ?? 0
            ];
        } catch (PDOException $e) {
            return ['total_omset' => 0, 'total_laba' => 0, 'total_transaksi' => 0];
        }
    }
}