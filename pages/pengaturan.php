<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/SettingController.php';

// Proteksi Halaman (Hanya Admin)
checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$settingController = new SettingController($pdo);
$settings = $settingController->getAllSettings();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-gears me-2 text-primary"></i> Pengaturan Kantin
            </h3>
            <p class="text-muted small mb-0">Kelola informasi umum usaha, format nota transaksi, logo, dan favicon aplikasi secara terpisah.</p>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- FORM 1: Informasi Umum Usaha -->
        <div class="col-xl-6">
            <form class="form-pengaturan h-100" enctype="multipart/form-data">
                <input type="hidden" name="form_type" value="form_umum">
                <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column" style="background-color: var(--card-bg);">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <h5 class="fw-bold m-0 text-primary fs-6">
                            <i class="fa-solid fa-store me-2"></i> 1. Informasi Umum Usaha
                        </h5>
                    </div>
                    <div class="card-body p-4 flex-fill">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Aplikasi / Kantin</label>
                            <input type="text" name="nama_aplikasi" class="form-control" value="<?= htmlspecialchars($settings['nama_aplikasi'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tagline / Slogan</label>
                            <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($settings['tagline'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Pemilik / Pengelola</label>
                            <input type="text" name="nama_pemilik" class="form-control" value="<?= htmlspecialchars($settings['nama_pemilik'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Alamat Lengkap</label>
                            <textarea name="alamat_lengkap" class="form-control" rows="2"><?= htmlspecialchars($settings['alamat_lengkap'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($settings['no_telepon'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3 text-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Informasi Umum
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FORM 2: Pengaturan Nota & Struk -->
        <div class="col-xl-6">
            <form class="form-pengaturan h-100" enctype="multipart/form-data">
                <input type="hidden" name="form_type" value="form_nota">
                <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column" style="background-color: var(--card-bg);">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <h5 class="fw-bold m-0 text-primary fs-6">
                            <i class="fa-solid fa-receipt me-2"></i> 2. Pengaturan Nota & Struk
                        </h5>
                    </div>
                    <div class="card-body p-4 flex-fill">
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Prefix Penjualan</label>
                                <input type="text" name="prefix_penjualan" class="form-control" value="<?= htmlspecialchars($settings['prefix_penjualan'] ?? 'POS-') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Prefix Konsinyasi</label>
                                <input type="text" name="prefix_konsinyasi" class="form-control" value="<?= htmlspecialchars($settings['prefix_konsinyasi'] ?? 'KONS-') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Prefix Retur</label>
                                <input type="text" name="prefix_retur" class="form-control" value="<?= htmlspecialchars($settings['prefix_retur'] ?? 'RTR-') ?>" required>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Pesan Nota (Footer Struk)</label>
                            <textarea name="pesan_nota" class="form-control" rows="4" placeholder="Contoh:&#10;Terima kasih atas kunjungan Anda!&#10;Barang yang sudah dibeli tidak dapat ditukar."><?= htmlspecialchars($settings['pesan_nota'] ?? '') ?></textarea>
                            
                            <!-- Petunjuk Format Baris Baru -->
                            <div class="alert alert-info py-2 px-3 mt-2 mb-0 d-flex align-items-center gap-2" style="font-size: 11px;">
                                <i class="fa-solid fa-circle-info fs-6"></i>
                                <div>
                                    <strong>Petunjuk Baris Baru:</strong> Cukup tekan tombol <strong>Enter</strong> di keyboard untuk membuat baris baru. Tidak perlu mengetik <code>&lt;br&gt;</code> atau <code>\n</code> secara manual.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3 text-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Pengaturan Nota
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FORM 3: Favicon Browser -->
        <div class="col-xl-6">
            <form class="form-pengaturan h-100" enctype="multipart/form-data">
                <input type="hidden" name="form_type" value="form_favico">
                <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column" style="background-color: var(--card-bg);">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <h5 class="fw-bold m-0 text-primary fs-6">
                            <i class="fa-solid fa-globe me-2"></i> 3. Favicon Browser
                        </h5>
                    </div>
                    <div class="card-body p-4 d-flex flex-column justify-content-between flex-fill">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Unggah Favicon Baru (.ico / .png)</label>
                            <input type="file" name="favico" id="inputFavico" class="form-control" accept=".ico, .png, .jpg, .jpeg" required>
                            <div class="form-text small text-muted">Rekomendasi ukuran: 32x32 piksel.</div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <span class="d-block small fw-bold text-muted mb-1">Preview Favicon:</span>
                                    <img id="previewFavico" src="<?= !empty($settings['favico']) ? BASE_URL . 'assets/uploads/' . htmlspecialchars($settings['favico']) : '' ?>" alt="Favicon" style="width: 32px; height: 32px; object-fit: contain; display: <?= !empty($settings['favico']) ? 'inline-block' : 'none' ?>;">
                                    <span id="badgeNoFavico" class="badge bg-secondary" style="display: <?= empty($settings['favico']) ? 'inline-block' : 'none' ?>;">Belum ada favicon</span>
                                </div>
                            </div>
                            <?php if (!empty($settings['favico'])): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-hapus-gambar" data-key="favico">
                                    <i class="fa-solid fa-trash-can me-1"></i> Hapus
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3 text-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Perbarui Favicon
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- FORM 4: Logo Toko -->
        <div class="col-xl-6">
            <form class="form-pengaturan h-100" enctype="multipart/form-data">
                <input type="hidden" name="form_type" value="form_logo">
                <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column" style="background-color: var(--card-bg);">
                    <div class="card-header bg-transparent border-bottom px-4 py-3">
                        <h5 class="fw-bold m-0 text-primary fs-6">
                            <i class="fa-solid fa-image me-2"></i> 4. Logo Toko / Kantin
                        </h5>
                    </div>
                    <div class="card-body p-4 d-flex flex-column justify-content-between flex-fill">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Unggah Logo Baru (.png / .jpg)</label>
                            <input type="file" name="logo" id="inputLogo" class="form-control" accept=".png, .jpg, .jpeg" required>
                            <div class="form-text small text-muted">Ditampilkan pada header laporan atau cetak struk.</div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                            <div class="d-flex align-items-center gap-3">
                                <div>
                                    <span class="d-block small fw-bold text-muted mb-1">Preview Logo:</span>
                                    <img id="previewLogo" src="<?= !empty($settings['logo']) ? BASE_URL . 'assets/uploads/' . htmlspecialchars($settings['logo']) : '' ?>" alt="Logo Toko" style="max-height: 45px; object-fit: contain; display: <?= !empty($settings['logo']) ? 'inline-block' : 'none' ?>;">
                                    <span id="badgeNoLogo" class="badge bg-secondary" style="display: <?= empty($settings['logo']) ? 'inline-block' : 'none' ?>;">Belum ada logo</span>
                                </div>
                            </div>
                            <?php if (!empty($settings['logo'])): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 btn-hapus-gambar" data-key="logo">
                                    <i class="fa-solid fa-trash-can me-1"></i> Hapus
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3 text-end">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Perbarui Logo
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>