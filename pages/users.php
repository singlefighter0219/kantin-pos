<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/UserController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$userController = new UserController($pdo);
$listUsers = $userController->getAllUsers();

?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-user-gear me-2 text-primary"></i> Manajemen Users
            </h3>
            <p class="text-muted small mb-0">Kelola akun pengguna, hak akses role (admin/kasir), dan preferensi tema.</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="bukaModalTambahUser()">
            <i class="fa-solid fa-user-plus me-1"></i> User Baru
        </button>
    </div>

    <!-- Tabel Daftar User -->
    <div class="card border-0 shadow-sm rounded-3" style="background-color: var(--card-bg);">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" width="60">No. Urut</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th class="text-center">Role</th>
                            <th class="text-center">Tema Pilihan</th>
                            <th>Tanggal Dibuat</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($listUsers)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted fst-italic">Belum ada data user.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($listUsers as $u): ?>
                                <tr>
                                    <td class="text-center fw-bold text-secondary"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold" style="color: var(--text-color);"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                    </td>
                                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                                    <td class="text-center">
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-danger-subtle text-danger px-2 py-1">Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-info-subtle text-info-emphasis px-2 py-1">Kasir</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 text-capitalize"><?= htmlspecialchars($u['tema']) ?></span>
                                    </td>
                                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <?php if ($u['id'] != 1): ?>    
                                            <button class="btn btn-sm btn-outline-primary" onclick='editUser(<?= json_encode($u) ?>)' title="Edit User">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-outline-warning text-dark" onclick='bukaModalReset(<?= $u['id'] ?>, "<?= htmlspecialchars($u['nama_lengkap']) ?>")' title="Reset Password">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-outline-danger" onclick='konfirmasiHapusUser(<?= $u['id'] ?>, "<?= htmlspecialchars($u['nama_lengkap']) ?>")' title="Hapus User">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- =================================================== -->
<!-- MODAL: TAMBAH / EDIT USER                           -->
<!-- =================================================== -->
<div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold text-primary" id="modalUserTitle">Tambah User Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUser">
                <input type="hidden" name="id" id="user_id">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Contoh: budi_kasir" required>
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">
                            <i class="fa-solid fa-circle-info me-1"></i> 4–20 karakter. Hanya huruf kecil, angka, titik (.), dan underscore (_). Tidak boleh menggunakan kata seperti <code>admin</code> atau <code>root</code>.
                        </small>
                    </div>

                    <div class="mb-3" id="wrapper_password">
                        <label class="form-label small fw-bold">Kata Sandi (Password)</label>
                        <input type="password" name="katasandi" id="katasandi" class="form-control" placeholder="Masukkan password akun">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">
                            <i class="fa-solid fa-circle-info me-1"></i> Minimal terdiri dari 6 karakter, disarankan mengkombinasikan huruf dan angka.
                        </small>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Hak Akses (Role)</label>
                            <select name="role" id="role" class="form-select">
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Tema Tampilan</label>
                            <select name="tema" id="tema" class="form-select">
                                <?php foreach ($pilihanTema as $key => $val): ?>
                                    <option value="<?= $key ?>"><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =================================================== -->
<!-- MODAL: RESET PASSWORD                               -->
<!-- =================================================== -->
<div class="modal fade" id="modalReset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-key me-2"></i>Reset Password User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formReset">
                <input type="hidden" name="id" id="reset_user_id">

                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">Reset password untuk user: <strong id="reset_nama_user" class="text-dark"></strong></p>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Kata Sandi Baru</label>
                        <input type="password" name="password_baru" class="form-control" placeholder="Masukkan password baru" required>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm text-dark">Simpan Password Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>