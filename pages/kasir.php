<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/PosController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$posController = new PosController($pdo);
$produkList = $posController->getProdukAktif();

$enableManualDate = isset($appSettings['enable_manual_date']) && $appSettings['enable_manual_date'] === '1';
?>

<div class="container-fluid">
    <div class="row g-3">
        <!-- Kolom Kiri: Daftar Produk (Katalog POS) -->
        <div class="col-lg-7 col-xl-8">
            <div class="card border-0 shadow-sm rounded-3" style="background-color: var(--card-bg);">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0" style="color: var(--text-color);">
                            <i class="fa-solid fa-cash-register text-primary me-2"></i> Kasir / POS
                        </h5>
                        <div class="w-50">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-transparent"><i class="fa-solid fa-search text-muted"></i></span>
                                <input type="text" id="cari-produk" class="form-control" placeholder="Cari nama produk...">
                            </div>
                        </div>
                    </div>

                    <!-- Grid Produk Dipercantik dengan Batas Tinggi & Custom Scroll -->
                    <div class="row g-2 overflow-auto pe-1 align-content-start" style="max-height: 68vh;" id="katalog-produk">
                        <?php if (empty($produkList)): ?>
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="fa-solid fa-box-open fa-3x mb-2 opacity-50"></i>
                                <p class="mb-0">Tidak ada produk aktif saat ini.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($produkList as $p): ?>
                                <?php 
                                    $stok = (int)$p['stok'];
                                    $kelolaStok = (int)$p['kelola_stok'];
                                    $isHabis = ($kelolaStok == 1 && $stok <= 0);
                                    
                                    // Cek kategori kepemilikan produk (sendiri atau konsinyasi)
                                    $kategoriKepemilikan = $p['kategori_kepemilikan'] ?? 'sendiri';
                                    $isKonsinyasi = ($kategoriKepemilikan === 'konsinyasi');

                                    // Styling dinamis berdasarkan status habis dan jenis produk
                                    $cardOpacity = $isHabis ? 'opacity-50 bg-light' : 'hover-scale-card';
                                ?>
                                <div class="col-6 col-md-4 col-xl-3 produk-card-item" data-nama="<?= htmlspecialchars($p['nama_produk']) ?>">
                                    <div class="card border shadow-2xs btn-tambah-produk <?= $cardOpacity ?> rounded-4 transition-all position-relative overflow-hidden" 
                                        style="background-color: var(--product-card-bg) !important; border-color: var(--border-color) !important; cursor: pointer; border-left: 4px solid var(--primary-color) !important;"
                                        <?php if (!$isHabis): ?>
                                        data-id="<?= $p['id'] ?>"
                                        <?php endif; ?>>
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div>
                                                <!-- Baris Atas: Nama Produk & Badge Jenis Kepemilikan -->
                                                <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                                                    <div class="fw-bold text-truncate text-dark" style="font-size: 13.5px; color: var(--text-color) !important;" title="<?= htmlspecialchars($p['nama_produk']) ?>">
                                                        <?= htmlspecialchars($p['nama_produk']) ?>
                                                    </div>
                                                    <?php if ($isKonsinyasi): ?>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-1.5 py-0.5 rounded-2" style="font-size: 9px; font-weight: 600;" title="Produk Titipan / Konsinyasi">
                                                            <i class="fa-solid fa-handshake me-0.5"></i> Titipan
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5 rounded-2" style="font-size: 9px; font-weight: 600;" title="Produk Mandiri / Sendiri">
                                                            <i class="fa-solid fa-store me-0.5"></i> Sendiri
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Harga Jual -->
                                                <div class="text-primary-theme fw-bold mb-3" style="font-size: 14.5px; color: var(--primary-color);">
                                                    Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?>
                                                </div>
                                            </div>

                                            <!-- Baris Bawah: Informasi Stok & Tombol Tambah -->
                                            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-10">
                                                <?php if ($kelolaStok == 1): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 10px;">Stok: <?= $stok ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size: 10px;">Bebas</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($isHabis): ?>
                                                    <span class="badge bg-danger px-2 py-1" style="font-size: 10px;">Habis</span>
                                                <?php else: ?>
                                                    <span class="btn btn-sm btn-primary rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px; background-color: var(--primary-color); border-color: var(--primary-color);">
                                                        <i class="fa-solid fa-plus text-white" style="font-size: 11px;"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Keranjang & Pembayaran -->
        <div class="col-lg-5 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3" style="background-color: var(--card-bg);">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3 border-bottom pb-2" style="color: var(--text-color);">
                        <i class="fa-solid fa-cart-shopping me-2"></i> Keranjang Belanja
                    </h6>

                    <!-- Tabel Keranjang Ringkas -->
                    <div class="table-responsive overflow-auto mb-3" style="max-height: 28vh; min-height: 150px;">
                        <table class="table table-sm align-middle mb-0">
                            <tbody id="cart-items">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Keranjang masih kosong</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bagian Total & Form Pembayaran -->
                    <div class="pt-2 border-top border-secondary border-opacity-10">
                        <form id="form-checkout">
                            <input type="hidden" id="input-total-belanja" value="0">
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold fs-6" style="color: var(--text-color);">Total Belanja:</span>
                                <span class="fw-bold fs-5 text-primary" id="total-belanja">Rp 0</span>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold">Uang Bayar (Rp)</label>
                                <input type="number" id="bayar" class="form-control fw-bold" placeholder="0" min="0" required>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 bg-body-tertiary p-2 rounded-2">
                                <span class="small fw-bold">Kembalian:</span>
                                <span class="fw-bold fs-6 text-success" id="kembalian">Rp 0</span>
                            </div>

                            <?php if ($enableManualDate): ?>
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-primary">Tanggal Transaksi (Manual)</label>
                                <input type="date" id="tanggal_manual" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Catatan Transaksi (Opsional)</label>
                                <input type="text" id="catatan_transaksi" class="form-control form-control-sm" placeholder="Contoh: Meja 3 / Bungkus">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                                <i class="fa-solid fa-check-circle me-1"></i> Proses Pembayaran
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- =================================================== -->
<!-- MODAL PILIHAN CETAK NOTA (STATIC BACKDROP)          -->
<!-- =================================================== -->
<div class="modal fade" id="modalCetakNota" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-body text-center p-4 py-5">
                <div class="mb-3 text-success">
                    <i class="fa-solid fa-circle-check fa-4x"></i>
                </div>
                <h4 class="fw-bold mb-2">Pembayaran Berhasil!</h4>
                <p class="text-muted small mb-4">Transaksi telah tersimpan. Apakah Anda ingin mencetak struk nota untuk pelanggan?</p>
                
                <input type="hidden" id="print-penjualan-id" value="">

                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-bold" onclick="skipCetakNota()">
                        <i class="fa-solid fa-forward me-1"></i> Lewati / Skip
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="prosesCetakNota()">
                        <i class="fa-solid fa-print me-1"></i> Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>