<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/ProductController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$productController = new ProductController($pdo);

// Ambil parameter pencarian dari GET
$keyword = trim($_GET['keyword'] ?? '');

// Konfigurasi Pagination (menggunakan parameter 'p')
$limit = 12;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Ambil data total dan list produk dengan filter pencarian
$totalData = $productController->countProdukFiltered($keyword);
$totalPages = ceil($totalData / $limit);
$produkList = $productController->getProdukFiltered($keyword, $limit, $offset);

// Ambil daftar vendor untuk dropdown konsinyasi
try {
    $stmtVendor = $pdo->query("SELECT id, nama_vendor FROM vendor ORDER BY nama_vendor ASC");
    $vendorList = $stmtVendor->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $vendorList = [];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-box me-2 text-primary"></i> Master Produk
            </h3>
            <p class="text-muted small mb-0">Kelola daftar produk kantin, barang titipan, harga, margin, dan stok.</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProduk" onclick="resetForm()">
            <i class="fa-solid fa-plus me-1"></i> Produk Baru
        </button>
    </div>

    <!-- Kotak Pencarian & Filter Sederhana -->
    <div class="card border-1 shadow-sm rounded-3 mb-4" style="background-color: var(--card-bg);">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small fw-bold">Kata Kunci Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                        <input type="text" name="keyword" class="form-control" placeholder="Cari Nama Produk / Nama Vendor..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                </div>

                <!-- Tombol Filter & Tombol Reset -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-50 rounded-2 shadow-sm text-center text-decoration-none d-flex align-items-center justify-content-center py-2">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-50 rounded-2 shadow-sm text-center text-decoration-none d-flex align-items-center justify-content-center py-2" onClick="window.location.href='produk'">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tampilan Card Grid Produk -->
    <div class="row g-3">
        <?php if (empty($produkList)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 text-center py-5" style="background-color: var(--card-bg);">
                    <div class="card-body text-muted">
                        <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-0">Tidak ada data produk yang ditemukan.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($produkList as $p): ?>
                <?php 
                    $isNonAktif = (isset($p['status']) && $p['status'] === 'nonaktif');
                    $cardClass = $isNonAktif ? 'bg-light border border-secondary border-opacity-25 opacity-75' : 'bg-white';
                ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="card shadow-sm rounded-3 h-100 d-flex flex-column <?= $cardClass ?>" style="background-color: var(--card-bg);">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-transparent border border-secondary text-secondary small fw-bold px-2 py-1">#<?= $p['id'] ?></span>
                                    
                                    <div class="d-flex align-items-center gap-1">
                                        <?php if ($isNonAktif): ?>
                                            <span class="badge bg-secondary text-white small">Non-Aktif</span>
                                        <?php endif; ?>

                                        <?php if ($p['kategori_kepemilikan'] === 'sendiri'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Sendiri</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Konsinyasi</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <h5 class="fw-bold text-truncate mb-1 <?= $isNonAktif ? 'text-decoration-line-through text-muted' : '' ?>" style="color: var(--text-color);" title="<?= htmlspecialchars($p['nama_produk']) ?>">
                                    <?= htmlspecialchars($p['nama_produk']) ?>
                                </h5>
                                <?php if ($p['kategori_kepemilikan'] === 'konsinyasi' && !empty($p['nama_vendor'])): ?>
                                    <small class="text-muted d-block mb-2"><i class="fa-solid fa-user me-1"></i>Vendor: <?= htmlspecialchars($p['nama_vendor']) ?></small>
                                <?php else: ?>
                                    <div class="mb-2"></div>
                                <?php endif; ?>
                            </div>

                            <div class="bg-body-tertiary p-2 rounded-2 my-2 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Modal / HPP:</span>
                                    <span class="fw-semibold">Rp <?= number_format($p['kategori_kepemilikan'] === 'sendiri' ? $p['hpp'] : $p['harga_titip'], 0, ',', '.') ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Harga Jual:</span>
                                    <span class="fw-bold text-primary">Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Margin:</span>
                                    <span class="text-success fw-bold">+Rp <?= number_format($p['margin_rp'], 0, ',', '.') ?> (<?= number_format($p['margin_persen'], 1) ?>%)</span>
                                </div>
                            </div>

                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Status Stok:</small>
                                    <?php if ($p['kelola_stok'] == 1): ?>
                                        <span class="badge bg-info text-dark fw-bold"><?= $p['stok'] ?> Pcs</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Bebas Stok</span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex align-items-center gap-2 mt-3">
                                    <?php if ($p['kelola_stok'] == 1 && !$isNonAktif): ?>
                                        <button class="btn btn-sm btn-success flex-grow-1" onclick='bukaModalRestock(<?= json_encode($p) ?>)' title="Tambah Stok / Restock">
                                            <i class="fa-solid fa-plus me-1"></i> Restock
                                        </button>

                                        <?php if ($p['kategori_kepemilikan'] === 'konsinyasi'): ?>
                                            <button class="btn btn-sm btn-warning text-dark px-3" onclick='bukaModalRetur(<?= json_encode($p) ?>)' title="Retur / Setoran Vendor">
                                                <i class="fa-solid fa-rotate-left me-1"></i> Retur
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <div class="d-flex gap-1 <?= ($p['kelola_stok'] == 0 || $isNonAktif) ? 'ms-auto' : '' ?>">
                                        <?php if (!$isNonAktif): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick='editProduk(<?= json_encode($p) ?>)' title="Edit Produk">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($isNonAktif): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick='toggleStatusProduk(<?= $p['id'] ?>)' title="Aktifkan Kembali Produk">
                                                <i class="fa-solid fa-rotate-right me-1"></i> Aktifkan
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick='toggleStatusProduk(<?= $p['id'] ?>)' title="Non-Aktifkan / Arsipkan Produk">
                                                <i class="fa-solid fa-box-archive"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination Pintar (Maksimal 5 Nomor Halaman Terlihat) -->
    <?php if ($totalPages > 1): ?>
        <?php 
            $queryParams = $_GET;
            unset($queryParams['p']);
            $queryString = http_build_query($queryParams);
            $queryStrPrefix = !empty($queryString) ? '&' . $queryString : '';

            $maxVisible = 5;
            $startPage = max(1, $page - floor($maxVisible / 2));
            $endPage = $startPage + $maxVisible - 1;

            if ($endPage > $totalPages) {
                $endPage = $totalPages;
                $startPage = max(1, $endPage - $maxVisible + 1);
            }
        ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=1<?= $queryStrPrefix ?>"><i class="fa-solid fa-angles-left"></i></a>
                </li>
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page - 1 ?><?= $queryStrPrefix ?>"><i class="fa-solid fa-angle-left"></i></a>
                </li>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?p=<?= $i ?><?= $queryStrPrefix ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $page + 1 ?><?= $queryStrPrefix ?>"><i class="fa-solid fa-angle-right"></i></a>
                </li>
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?p=<?= $totalPages ?><?= $queryStrPrefix ?>"><i class="fa-solid fa-angles-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- =================================================== -->
<!-- MODAL 1: MASTER PRODUK (TAMBAH / EDIT)             -->
<!-- =================================================== -->
<div class="modal fade" id="modalProduk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalTitle">
                    <i class="fa-solid fa-box-open text-primary"></i> Tambah Produk Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formProduk">
                <input type="hidden" name="id" id="produk_id">
                
                <input type="hidden" name="hpp" id="input_hpp" value="0">
                <input type="hidden" name="margin_rp" id="input_margin_rp" value="0">
                <input type="hidden" name="margin_persen" id="input_margin_persen" value="0">
                <input type="hidden" name="saran_25" id="input_saran_25" value="0">
                <input type="hidden" name="saran_50" id="input_saran_50" value="0">
                <input type="hidden" name="saran_75" id="input_saran_75" value="0">
                <input type="hidden" name="saran_100" id="input_saran_100" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Nama Produk / Menu</label>
                            <input type="text" name="nama_produk" id="nama_produk" class="form-control" placeholder="Contoh: Es Buah, Nasi Pecel, Teh Botol" required>
                        </div>

                        <!-- 1. Wrapper khusus Kategori Kepemilikan (Akan disembunyikan total saat Edit) -->
                        <div class="row g-3" id="box_kategori_wrapper">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Kategori Kepemilikan</label>
                                <select name="kategori_kepemilikan" id="kategori_kepemilikan" class="form-select" onchange="toggleKepemilikan()">
                                    <option value="sendiri">Milik Sendiri (Internal)</option>
                                    <option value="konsinyasi">Titipan (Konsinyasi Vendor)</option>
                                </select>
                            </div>
                        </div>

                        <!-- 2. Wrapper khusus Pengaturan Stok (Bisa dimunculkan saat Edit khusus produk sendiri) -->
                        <div class="row g-3 mt-1" id="box_pengaturan_stok_wrapper">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Pengaturan Stok</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" name="kelola_stok" id="kelola_stok" value="1" checked onchange="toggleStokInput()">
                                    <label class="form-check-label fw-bold" id="label_stok" for="kelola_stok">Lacak Stok (Aktif)</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12" id="box_vendor" style="display: none;">
                            <label class="form-label small fw-bold text-warning">
                                <i class="fa-solid fa-handshake me-1"></i> Pilih Vendor Titipan
                            </label>
                            <select name="vendor_id" id="vendor_id" class="form-select border-warning">
                                <option value="">-- Pilih Vendor --</option>
                                <?php foreach ($vendorList as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nama_vendor']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Stok Awal Di-Disabled (Pengisian Stok dilakukan via Tombol Restock) -->
                        <div class="col-md-12" id="box_stok">
                            <label class="form-label small fw-bold">Jumlah Stok Awal</label>
                            <input type="number" name="stok" id="stok" class="form-control bg-body-tertiary" value="0" readonly>
                            <small class="text-muted" style="font-size: 11px;">* Stok awal pendaftaran diset 0. Penambahan stok dilakukan melalui tombol <strong>Restock</strong> di card produk.</small>
                        </div>

                        <hr class="my-2 border-secondary border-opacity-10">

                        <div class="col-md-6" id="box_harga_beli">
                            <label class="form-label small fw-bold">Total Harga Beli / Kulakan</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga_beli" id="harga_beli" class="form-control" value="0" oninput="hitungKalkulasiHarga()">
                            </div>
                        </div>

                        <div class="col-md-6" id="box_qty_beli">
                            <label class="form-label small fw-bold">Jumlah (Qty) Beli</label>
                            <input type="number" name="qty_beli" id="qty_beli" class="form-control" value="1" min="1" oninput="hitungKalkulasiHarga()">
                        </div>

                        <div class="col-md-12" id="box_harga_titip" style="display: none;">
                            <label class="form-label small fw-bold">Harga Titip (Setoran ke Vendor per Pcs)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga_titip" id="harga_titip" class="form-control" value="0" oninput="hitungKalkulasiHarga()">
                            </div>
                        </div>

                        <div class="col-md-6" id="box_info_hpp">
                            <label class="form-label small fw-bold text-muted">Hasil HPP (Modal per Satuan)</label>
                            <div class="form-control bg-body-tertiary fw-bold" id="text_hpp_satuan" style="color: var(--text-color);">
                                Rp 0
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Harga Jual Kasir</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="harga_jual" id="harga_jual" class="form-control" value="0" oninput="hitungKalkulasiHarga()">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Margin Keuntungan</label>
                            <div class="form-control bg-body-tertiary d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-success" id="text_margin_rp">Rp 0</span>
                                <span class="badge bg-success px-2 py-1" id="text_margin_persen">0%</span>
                            </div>
                        </div>

                        <div class="col-12 mt-3" id="box_saran_harga">
                            <label class="form-label small fw-bold text-muted d-block">Saran Harga Jual (Klik untuk Menerapkan):</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-sm btn-outline-theme flex-fill shadow-sm" onclick="pilihSaranHarga(0.25)">
                                    +25% <br><strong id="saran_25">Rp 0</strong>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-theme flex-fill shadow-sm" onclick="pilihSaranHarga(0.50)">
                                    +50% <br><strong id="saran_50">Rp 0</strong>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-theme flex-fill shadow-sm" onclick="pilihSaranHarga(0.75)">
                                    +75% <br><strong id="saran_75">Rp 0</strong>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-theme flex-fill shadow-sm" onclick="pilihSaranHarga(1.00)">
                                    +100% <br><strong id="saran_100">Rp 0</strong>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =================================================== -->
<!-- MODAL 2: FORM RESTOCK PRODUK (DINAMIS)               -->
<!-- =================================================== -->
<div class="modal fade" id="modalRestock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold text-success d-flex align-items-center gap-2">
                    <i class="fa-solid fa-boxes-packing"></i> Restock Produk
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRestock">
                <input type="hidden" name="produk_id" id="restock_produk_id">
                <input type="hidden" name="kategori_kepemilikan" id="restock_kategori">
                <input type="hidden" name="vendor_id" id="restock_vendor_id">

                <div class="modal-body p-4">
                    <div class="alert bg-success-subtle border-success-subtle text-success small mb-3">
                        <strong id="restock_nama_produk">-</strong> <br>
                        Stok Saat Ini: <span class="fw-bold" id="restock_stok_sekarang">0</span> Pcs
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jumlah (Qty) Masuk</label>
                        <input type="number" name="qty_masuk" id="restock_qty" class="form-control form-control-lg fw-bold" value="1" min="1" required>
                    </div>

                    <!-- Input Khusus Produk Milik Sendiri (Mencatat Biaya Kulakan / Kas Keluar) -->
                    <div id="box_restock_sendiri">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Total Biaya Kulakan (Kas Keluar)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="total_biaya_kulakan" id="restock_total_biaya" class="form-control" value="0">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">* Nilai ini akan dicatat ke Pengeluaran Kas Kantin.</small>
                        </div>
                    </div>

                    <!-- Informas Khusus Produk Konsinyasi -->
                    <div id="box_restock_konsinyasi" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Harga Setoran Vendor / Pcs</label>
                            <div class="form-control bg-body-tertiary fw-bold" id="restock_text_harga_titip">Rp 0</div>
                            <small class="text-muted" style="font-size: 11px;">* Tidak ada kas keluar saat ini. Penerimaan dicatat di riwayat titipan.</small>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Keterangan / Catatan (Opsional)</label>
                        <input type="text" name="keterangan" id="restock_keterangan" class="form-control" placeholder="Contoh: Titipan Pagi / Beli di Pasar">
                    </div>

                    <?php if (isset($appSettings['enable_manual_date']) && $appSettings['enable_manual_date'] === '1'): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-success">Tanggal Restock (Manual)</label>
                        <input type="date" name="tanggal_manual" id="restock_tanggal_manual" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">Simpan Restock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =================================================== -->
<!-- MODAL 3: FORM RETUR TITIPAN & SETORAN VENDOR        -->
<!-- =================================================== -->
<div class="modal fade" id="modalRetur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold text-warning d-flex align-items-center gap-2">
                    <i class="fa-solid fa-rotate-left"></i> Retur & Setoran Vendor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRetur">
                <input type="hidden" name="produk_id" id="retur_produk_id">
                <input type="hidden" name="vendor_id" id="retur_vendor_id">
                <input type="hidden" name="stok_awal" id="retur_stok_awal">
                <input type="hidden" name="harga_titip" id="retur_harga_titip">
                <input type="hidden" name="qty_terjual" id="retur_qty_terjual">
                <input type="hidden" name="total_setoran" id="retur_total_setoran">

                <div class="modal-body p-4">
                    <!-- Informasi Detail Produk & Stok -->
                    <div class="alert bg-warning-subtle border-warning-subtle text-warning-emphasis small mb-3">
                        <strong id="retur_nama_produk">-</strong> <br>
                        Vendor: <span class="fw-bold" id="retur_nama_vendor">-</span> <br>
                        <div class="mt-1 pt-1 border-top border-warning border-opacity-25 d-flex justify-content-between">
                            <span>Total Qty Masuk (Awal): <strong id="text_stok_awal_masuk">0 Pcs</strong></span>
                            <span>Sisa Stok Kasir: <strong id="text_sisa_stok_kasir">0 Pcs</strong></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jumlah Barang Sisa yang Diretur (Ditarik Vendor)</label>
                        <input type="number" name="qty_retur" id="retur_qty" class="form-control form-control-lg fw-bold border-warning" value="0" min="0" required oninput="hitungKalkulasiRetur()">
                        <small class="text-muted" style="font-size: 11px;">* Masukkan jumlah fisik barang tak laku yang dibawa pulang vendor.</small>
                    </div>
                    
                    <!-- ... bagian selanjutnya tetap sama ... -->

                    <!-- Rekap Kalkulasi Setoran -->
                    <div class="bg-body-tertiary p-3 rounded-3 mb-3 border border-secondary border-opacity-10">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">Jumlah Terjual (Stok - Retur):</span>
                            <span class="fw-bold text-dark" id="text_retur_terjual">0 Pcs</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">Harga Setoran / Pcs:</span>
                            <span class="fw-bold text-dark" id="text_retur_harga_titip">Rp 0</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold small text-primary">Total Setoran Wajib Dibayar:</span>
                            <span class="fs-5 fw-bold text-success" id="text_retur_total_setoran">Rp 0</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Keterangan / Catatan</label>
                        <input type="text" name="keterangan" id="retur_keterangan" class="form-control" placeholder="Contoh: Penarikan sisa titipan sore hari">
                    </div>

                    <?php if (isset($appSettings['enable_manual_date']) && $appSettings['enable_manual_date'] === '1'): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-warning-emphasis">Tanggal Retur (Manual)</label>
                        <input type="date" name="tanggal_manual" id="retur_tanggal_manual" class="form-control border-warning" value="<?= date('Y-m-d') ?>">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark">Proses Retur & Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>