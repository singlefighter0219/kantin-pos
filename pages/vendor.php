<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/guard.php';
require_once __DIR__ . '/../controllers/VendorController.php';

checkPageAccess($pdo);

require_once __DIR__ . '/../includes/header.php';

$vendorController = new VendorController($pdo);

$keyword = trim($_GET['keyword'] ?? '');
$limit = 12;
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

$totalData = $vendorController->countVendorFiltered($keyword);
$totalPages = ceil($totalData / $limit);
$vendorList = $vendorController->getVendorFiltered($keyword, $limit, $offset);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--text-color);">
                <i class="fa-solid fa-handshake me-2 text-primary"></i> Vendor Konsinyasi
            </h3>
            <p class="text-muted small mb-0">Kelola data mitra vendor, informasi kontak, alamat, dan produk titipan.</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalVendor" onclick="resetFormVendor()">
            <i class="fa-solid fa-plus me-1"></i> Vendor Baru
        </button>
    </div>

    <!-- Kotak Pencarian & Filter -->
    <div class="card border-1 shadow-sm rounded-3 mb-4" style="background-color: var(--card-bg);">
        <div class="card-body p-3">
            <form method="GET" action="" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small fw-bold">Kata Kunci Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                        <input type="text" name="keyword" class="form-control" placeholder="Cari Nama Vendor / No. HP / Alamat..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-50 rounded-2 shadow-sm text-center d-flex align-items-center justify-content-center py-2">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-50 rounded-2 shadow-sm text-center d-flex align-items-center justify-content-center py-2" onClick="window.location.href='vendor'">
                        <i class="fa-solid fa-rotate-right me-1"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tampilan Card Grid Vendor -->
    <div class="row g-3">
        <?php if (empty($vendorList)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3 text-center py-5" style="background-color: var(--card-bg);">
                    <div class="card-body text-muted">
                        <i class="fa-solid fa-user-slash fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-0">Tidak ada data vendor yang ditemukan.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($vendorList as $v): ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="card shadow-sm rounded-3 h-100 d-flex flex-column" style="background-color: var(--card-bg);">
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-transparent border border-secondary text-secondary small fw-bold px-2 py-1">#<?= $v['id'] ?></span>
                                    
                                    <!-- Badge Jumlah Produk yang Dapat Diklik -->
                                    <button class="btn btn-sm btn-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 fw-bold rounded-pill" style="font-size: 11px;" onclick='lihatProdukVendor(<?= $v['id'] ?>, <?= json_encode($v['nama_vendor']) ?>)'>
                                        <i class="fa-solid fa-box me-1"></i> <?= $v['total_produk'] ?> Produk Titipan
                                    </button>
                                </div>
                                <h5 class="fw-bold text-truncate mb-1" style="color: var(--text-color);" title="<?= htmlspecialchars($v['nama_vendor']) ?>">
                                    <?= htmlspecialchars($v['nama_vendor']) ?>
                                </h5>
                                <p class="text-muted small mb-2">
                                    <i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($v['nohp']) ?>
                                </p>
                            </div>

                            <div class="bg-body-tertiary p-2 rounded-2 my-2 small text-muted">
                                <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?= htmlspecialchars($v['alamat']) ?>
                            </div>

                            <div class="d-flex justify-content-end gap-1 mt-2">
                                <button class="btn btn-sm btn-outline-primary px-3" onclick='editVendor(<?= json_encode($v) ?>)' title="Edit Vendor">
                                    <i class="fa-solid fa-pen me-1"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination Pintar -->
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
<!-- MODAL: DAFTAR PRODUK MILIK VENDOR                   -->
<!-- =================================================== -->
<div class="modal fade" id="modalProdukVendor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3 align-items-start">
                <h5 class="modal-title fw-bold text-primary d-flex align-items-start gap-2 m-0">
                    <i class="fa-solid fa-boxes-stacked mt-1 fs-4"></i>
                    <div>
                        <span class="small text-muted d-block fw-normal text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Produk Titipan oleh:</span>
                        <span id="lblNamaVendor" class="text-dark fs-5 fw-bold"></span>
                    </div>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Produk</th>
                                <th class="text-center">Stok Sisa</th>
                                <th class="text-end">Harga Titip (Setor)</th>
                                <th class="text-end">Harga Jual Kasir</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="list-produk-vendor">
                            <!-- Diisi via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- =================================================== -->
<!-- MODAL: TAMBAH / EDIT VENDOR                         -->
<!-- =================================================== -->
<div class="modal fade" id="modalVendor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background-color: var(--card-bg); color: var(--text-color);">
            <div class="modal-header border-bottom border-secondary border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold text-primary d-flex align-items-center gap-2" id="modalVendorTitle">
                    <i class="fa-solid fa-handshake"></i> Tambah Vendor Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formVendor">
                <input type="hidden" name="id" id="vendor_id">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Vendor / Toko</label>
                        <input type="text" name="nama_vendor" id="nama_vendor" class="form-control" placeholder="Contoh: Ibu Siti (Aneka Jajanan)" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nomor HP / WhatsApp</label>
                        <input type="text" name="nohp" id="vendor_nohp" class="form-control" placeholder="Contoh: 081234567890" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold">Alamat Lengkap</label>
                        <textarea name="alamat" id="vendor_alamat" class="form-control" rows="3" placeholder="Masukkan alamat lengkap vendor..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-10 px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>