<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/UserController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$userController = new UserController($pdo);
$currentUser = $userController->getUserById($_SESSION['user_id']);

?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-user-pen me-2 text-primary"></i> Profil Pengguna
            </h3>
            <p class="text-muted small mb-0">Kelola informasi akun Anda dan perbarui kata sandi secara berkala.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Form 1: Edit Informasi Profil, Username & Tema -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: var(--card-bg);">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <h5 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-id-card me-2"></i>Informasi Akun</h5>
                </div>
                <div class="card-body p-4">
                    <form id="formProfil">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                            <small class="text-muted d-mt-1" style="font-size: 11px;">
                                <i class="fa-solid fa-circle-info me-1"></i> 4–20 karakter. Hanya huruf kecil, angka, titik (.), dan underscore (_). Tidak boleh menggunakan kata seperti <code>admin</code> atau <code>root</code>.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" value="<?= htmlspecialchars($currentUser['nama_lengkap']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Tema Tampilan</label>
                            <select name="tema" id="tema" class="form-select">
                                <?php foreach ($pilihanTema as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= ($currentUser['tema'] === $key) ? 'selected' : '' ?>><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Form 2: Ganti Password (Wajib Masukkan Password Lama) -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: var(--card-bg);">
                <div class="card-header bg-transparent border-bottom px-4 py-3">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-key me-2 text-warning"></i>Keamanan (Ganti Password)</h5>
                </div>
                <div class="card-body p-4">
                    <form id="formPassword">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Lama</label>
                            <input type="password" name="password_lama" id="password_lama" class="form-control" placeholder="Masukkan password saat ini" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Baru</label>
                            <input type="password" name="password_baru" id="password_baru" class="form-control" placeholder="Masukkan password baru" required>
                            <!-- Informasi Ketentuan Password -->
                            <div class="form-text text-muted mt-1" style="font-size: 11px;">
                                <i class="fa-solid fa-circle-info me-1"></i> Ketentuan password baru:
                                <ul class="ps-3 mb-0 mt-1">
                                    <li>Minimal terdiri dari 6 karakter.</li>
                                    <li>Disarankan mengkombinasikan huruf dan angka.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Konfirmasi Password Baru</label>
                            <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control" placeholder="Ulangi password baru" required>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark">
                                <i class="fa-solid fa-shield-halved me-1"></i> Perbarui Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>