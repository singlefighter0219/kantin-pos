        </div> <!-- End Content Body -->

        <!-- Footer Utama -->
        <footer class="text-center py-3 mt-auto border-top text-muted small" style="background-color: var(--card-bg); font-size: 11.5px;">
            &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($appSettings['nama_aplikasi'] ?? 'Kantin POS') ?></strong> 
            <?php if (!empty($appSettings['nama_pemilik'])): ?>
                &bull; Dikelola oleh <?= htmlspecialchars($appSettings['nama_pemilik']) ?>
            <?php endif; ?>
        </footer>
    </div> <!-- End Main Content Wrapper -->
</div> <!-- End Wrapper D-flex -->

<!-- Core JS Offline (Bootstrap & jQuery) -->
<script src="<?= BASE_URL ?>assets/js/jquery-3.7.0.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/html2canvas.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/all.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/chart.umd.js"></script>
<script src="<?= BASE_URL ?>assets/js/script.js"></script>
<script src="<?= BASE_URL ?>assets/js/updater.js"></script>

<?php
// Deteksi nama file halaman yang sedang aktif saat ini
$currentPageScript = basename($_SERVER['PHP_SELF'], '.php');
$customJsPath = __DIR__ . '/../assets/js/' . $currentPageScript . '.js';
$customJsUrl  = BASE_URL . 'assets/js/' . $currentPageScript . '.js';

// Jika file .js khusus untuk halaman ini benar-benar ada di folder assets/js/, muat secara otomatis
if (file_exists($customJsPath)) {
    echo '<script src="' . $customJsUrl . '"></script>' . "\n";
}
?>

<?php 
// Cek apakah halaman saat ini adalah halaman utama (index.php)
$currentScript = basename($_SERVER['SCRIPT_NAME']);
if ($currentScript === 'index.php' || $currentScript === ''): 
?>
<script>
    // Grafik 7 Hari Terakhir
    const ctx7Hari = document.getElementById('chart7Hari').getContext('2d');
    new Chart(ctx7Hari, {
        type: 'line',
        data: {
            labels: <?= json_encode($grafik7Hari['labels']) ?>,
            datasets: [{
                label: 'Omset Penjualan (Rp)',
                data: <?= json_encode($grafik7Hari['values']) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Grafik 6 Bulan Terakhir
    const ctx6Bulan = document.getElementById('chart6Bulan').getContext('2d');
    new Chart(ctx6Bulan, {
        type: 'bar',
        data: {
            labels: <?= json_encode($grafik6Bulan['labels']) ?>,
            datasets: [{
                label: 'Omset Penjualan (Rp)',
                data: <?= json_encode($grafik6Bulan['values']) ?>,
                backgroundColor: '#ffc107',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
<?php endif; ?>

</body>
</html>