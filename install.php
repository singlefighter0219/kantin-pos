<?php
// Deteksi jika aplikasi sudah terinstal melalui file kunci installed.lock
$lockFile = __DIR__ . '/config/installed.lock';
if (file_exists($lockFile)) {
    // Deteksi BASE_URL dinamis untuk tombol kembali
    $protocol = (!empty($_SERVER['HTTPS']) &&$_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];$dirPath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');$activeBaseUrl = $protocol .$host . ($dirPath ? $dirPath . '/' : '/');

    die("
        <div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>
            <h2 style='color: #dc3545;'>Aplikasi Sudah Terinstal!</h2>
            <p>Sistem Kantin POS telah berhasil terinstal di server ini.</p>
            <p>Jika Anda ingin melakukan instalasi ulang, silakan hapus file <code>config/installed.lock</code> terlebih dahulu.</p>
            <br>
            <a href='{$activeBaseUrl}' style='background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Kembali ke Aplikasi</a>
        </div>
    ");
}

// Deteksi lokasi path XAMPP/MySQL di komputer pengguna secara dinamis
$docRoot =$_SERVER['DOCUMENT_ROOT'] ?? 'C:/xampp/htdocs';
$xamppPath = dirname($docRoot); // Mendapatkan misal: F:/xampp atau C:/xampp
$myIniPath = str_replace('/', DIRECTORY_SEPARATOR,$xamppPath . '/mysql/bin/my.ini');

$errorMessage = '';$warningPasswordKosong = false;
$successMessage = '';$dynamicBaseUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Tangkap Input Form
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? 'root');
    $db_pass = trim($_POST['db_pass'] ?? '');
    $db_name = trim($_POST['db_name'] ?? 'kantin_db');

    $nama_aplikasi = trim($_POST['nama_aplikasi'] ?? 'Kantin Sekolah');
    $tagline       = trim($_POST['tagline'] ?? 'Kelola Kantin Lebih Cepat & Akurat');
    $nama_pemilik  = trim($_POST['nama_pemilik'] ?? '');
    $alamat_lengkap= trim($_POST['alamat_lengkap'] ?? '');
    $no_telepon    = trim($_POST['no_telepon'] ?? '');

    $admin_user = trim($_POST['admin_user'] ?? '');
    $admin_nama = trim($_POST['admin_nama'] ?? '');
    $admin_pass = trim($_POST['admin_pass'] ?? '');

    // Validasi Keamanan: Jika user root dan password masih kosong
    if (strtolower($db_user) === 'root' && empty($db_pass)) {$warningPasswordKosong = true;
    } else {
        try {
            // Test Koneksi ke Server MySQL
            $pdoTest = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user,$db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Create Database jika belum ada sesuai input user
            $pdoTest->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdoTest->exec("USE `$db_name`");

            // Import Schema SQL
            $schemaFile = __DIR__ . '/config/schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("File schema database (config/schema.sql) tidak ditemukan!");
            }
            $sqlQueries = file_get_contents($schemaFile);
            $pdoTest->exec($sqlQueries);

            // Update Pengaturan Instansi
            $stmtSet =$pdoTest->prepare("UPDATE pengaturan SET param_value = ? WHERE param_key = ?");
            $stmtSet->execute([$nama_aplikasi, 'nama_aplikasi']);
            $stmtSet->execute([$tagline, 'tagline']);
            $stmtSet->execute([$nama_pemilik, 'nama_pemilik']);
            $stmtSet->execute([$alamat_lengkap, 'alamat_lengkap']);
            $stmtSet->execute([$no_telepon, 'no_telepon']);

            // Insert Super Admin (Reserved ID 1)
            $hashedPass = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmtAdmin =$pdoTest->prepare("
                INSERT INTO users (id, username, nama_lengkap, katasandi, role, tema)
                VALUES (1, ?, ?, ?, 'admin', 'biru-muda')
                ON DUPLICATE KEY UPDATE 
                    username = VALUES(username),
                    nama_lengkap = VALUES(nama_lengkap),
                    katasandi = VALUES(katasandi)
            ");
            $stmtAdmin->execute([$admin_user, $admin_nama,$hashedPass]);

            // Tentukan BASE_URL secara otomatis menggunakan metode berbasis direktori server yang akurat
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
            $projectFolder = str_replace('\\', '/', __DIR__); // Mengambil direktori root project tempat install.php berada
            $basePath = str_replace($docRoot, '', $projectFolder);
            $dynamicBaseUrl = $protocol . $host . $basePath . '/';

            // Tulis ulang file config/database.php dengan struktur BASE_URL yang konsisten
            $dbConfigContent = "<?php\n" .
                "// Mengatur zona waktu default PHP ke Waktu Indonesia Barat (GMT+7)\n" .
                "date_default_timezone_set('Asia/Jakarta');\n\n" .
                "\$protocol = (!empty(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] !== 'off') ? \"https://\" : \"http://\";\n" .
                "\$host = \$_SERVER['HTTP_HOST'];\n\n" .
                "\$docRoot = rtrim(str_replace('\\\\', '/', \$_SERVER['DOCUMENT_ROOT']), '/');\n" .
                "\$projectFolder = str_replace('\\\\', '/', dirname(__DIR__));\n" .
                "\$basePath = str_replace(\$docRoot, '', \$projectFolder);\n\n" .
                "define('BASE_URL', \$protocol . \$host . \$basePath . '/');\n\n" .
                "// Cek status instalasi, jika file kunci installed.lock belum ada, arahkan ke install.php\n" .
                "if (!file_exists(__DIR__ . '/installed.lock') && file_exists(__DIR__ . '/../install.php')) {\n" .
                "    header(\"Location: \" . BASE_URL . \"install.php\");\n" .
                "    exit();\n" .
                "}\n\n" .
                "// Konfigurasi Database PDO\n" .
                "\$db_host = '$db_host';\n" .
                "\$db_name = '$db_name';\n" .
                "\$db_user = '$db_user';\n" .
                "\$db_pass = '$db_pass';\n" .
                "\$charset = 'utf8mb4';\n\n" .
                "\$dsn = \"mysql:host=\$db_host;dbname=\$db_name;charset=\$charset\";\n" .
                "\$options = [\n" .
                "    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n" .
                "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n" .
                "    PDO::ATTR_EMULATE_PREPARES   => false,\n" .
                "];\n\n" .
                "try {\n" .
                "    if (!empty(\$db_host) && !empty(\$db_name)) {\n" .
                "        \$pdo = new PDO(\$dsn, \$db_user, \$db_pass, \$options);\n" .
                "        \$pdo->exec(\"SET time_zone = '+07:00'\");\n" .
                "    }\n" .
                "} catch (\\PDOException \$e) {\n" .
                "    throw new \\PDOException(\$e->getMessage(), (int)\$e->getCode());\n" .
                "}\n" .
                "?>";

            file_put_contents(__DIR__ . '/config/database.php', $dbConfigContent);

            // Buat File Kunci (installed.lock) dengan Kredit Developer
            $lockContent = "Installed on " . date('Y-m-d H:i:s') . "\n";
            $lockContent .= "App: Kantin POS\n";
            $lockContent .= "Dev: SingleFighter";

            file_put_contents($lockFile, $lockContent);

            $successMessage = "Instalasi berhasil! Aplikasi siap digunakan.";

        } catch (Exception $e) {$errorMessage = "Gagal Instalasi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php include './config/author.html'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wizard Instalasi - Kantin POS</title>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/css/all.min.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .wizard-card { max-width: 750px; margin: 40px auto; border-radius: 12px; border: none; }
        .wizard-header { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: white; border-top-left-radius: 12px; border-top-right-radius: 12px; }
        .section-title { border-left: 4px solid #0ea5e9; padding-left: 10px; font-weight: 600; color: #334155; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container py-3">
    <div class="card wizard-card shadow-sm">
        <div class="wizard-header p-4 text-center">
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-cash-register me-2"></i>KantPOS Installer</h3>
            <p class="mb-0 opacity-75">Wizard Pemasangan & Konfigurasi Otomatis</p>
        </div>
        <div class="card-body p-4">

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success text-center py-4">
                    <i class="fa-solid fa-circle-check fa-3x mb-3 text-success"></i>
                    <h4>Instalasi Berhasil!</h4>
                    <p><?= $successMessage ?></p>
                    <a href="<?= $dynamicBaseUrl ?>" class="btn btn-primary rounded-pill px-4 fw-bold mt-2">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Masuk ke Aplikasi Kasir
                    </a>
                </div>
            <?php else: ?>

                <?php if ($warningPasswordKosong): ?>
                    <div class="alert alert-warning border-0 shadow-sm rounded-3 p-4 mb-4">
                        <h5 class="fw-bold text-dark mb-2"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Peringatan Keamanan Database!</h5>
                        <p class="small text-secondary mb-3">User MySQL <strong>root</strong> Anda terdeteksi belum memiliki password (kosong). Demi keamanan data transaksi, Anda wajib memberikan password pada MySQL server Anda terlebih dahulu.</p>
                        
                        <div class="bg-white p-3 rounded-3 border mb-3 small">
                            <span class="fw-bold d-block text-primary mb-1"><i class="fa-solid fa-folder-open me-1"></i>Panduan Lokasi File Konfigurasi:</span>
                            Silakan buka file konfigurasi MySQL di komputer Anda pada lokasi berikut:
                            <div class="input-group my-1">
                                <input type="text" id="pathMyIni" class="form-control form-control-sm font-monospace text-danger fw-bold bg-light" value="<?= htmlspecialchars($myIniPath) ?>" readonly>
                                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="salinTeks('pathMyIni', this)" title="Salin Path">
                                    <i class="fa-regular fa-copy"></i> Salin
                                </button>
                            </div>
                        </div>

                        <!-- Kolom Interaktif Pembuat Password SQL Otomatis -->
                        <div class="bg-white p-3 rounded-3 border mb-3 small">
                            <label class="fw-bold text-dark mb-1"><i class="fa-solid fa-key text-warning me-1"></i>Buat Password Baru Anda di Sini:</label>
                            <input type="text" id="inputPasswordBaru" class="form-control form-control-sm mb-2" placeholder="Ketik password baru yang diinginkan..." oninput="updatePerintahSql()">
                            
                            <span class="text-muted d-block mb-1">Atau jalankan perintah SQL berikut pada <strong>phpMyAdmin</strong> Anda:</span>
                            
                            <div class="position-relative">
                                <pre id="sqlQueryBox" class="bg-dark text-light p-3 rounded mt-1 mb-0 font-monospace" style="font-size: 12.5px;">ALTER USER 'root'@'localhost' IDENTIFIED BY 'PasswordBaruAnda';
FLUSH PRIVILEGES;</pre>
                                <button type="button" class="btn btn-sm btn-light position-absolute top-0 end-0 m-2 shadow-sm" onclick="salinKodeSql(this)" title="Salin Kode SQL">
                                    <i class="fa-regular fa-copy me-1"></i> Salin SQL
                                </button>
                            </div>
                        </div>

                        <p class="small mb-0">Setelah password dibuat dan MySQL di-<em>restart</em>, masukkan password baru tersebut pada kolom <strong>Password Database</strong> di form bawah, lalu klik <strong>Jalankan Instalasi</strong> kembali.</p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger p-3 mb-4 small">
                        <i class="fa-solid fa-circle-xmark me-1"></i> <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- Bagian 1: Database -->
                    <div class="section-title">1. Konfigurasi Koneksi Database</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Database Host</label>
                            <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Database</label>
                            <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? 'kantin_db') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Username Database</label>
                            <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Password Database</label>
                            <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>" placeholder="Masukkan password MySQL">
                        </div>
                    </div>

                    <!-- Bagian 2: Pengaturan Instansi -->
                    <div class="section-title">2. Profil Instansi & Kantin</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Aplikasi</label>
                            <input type="text" name="nama_aplikasi" class="form-control" value="<?= htmlspecialchars($_POST['nama_aplikasi'] ?? 'Kantin Sekolah') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tagline</label>
                            <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($_POST['tagline'] ?? 'Kelola Kantin Lebih Cepat & Akurat') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Pemilik / Sekolah / Instansi</label>
                            <input type="text" name="nama_pemilik" class="form-control" value="<?= htmlspecialchars($_POST['nama_pemilik'] ?? '') ?>" placeholder="Contoh: SDN Tamansari 01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>" placeholder="08123456789">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Alamat Lengkap Instansi</label>
                            <textarea name="alamat_lengkap" class="form-control" rows="2" placeholder="Jl. Raya No. 12..."><?= htmlspecialchars($_POST['alamat_lengkap'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Bagian 3: Akun Administrator (ID 1) -->
                    <div class="section-title">3. Akun Super Admin (User ID 1)</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Username Admin</label>
                            <input type="text" name="admin_user" class="form-control" value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>" placeholder="admin" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="admin_nama" class="form-control" value="<?= htmlspecialchars($_POST['admin_nama'] ?? '') ?>" placeholder="Administrator Utama" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Password Admin</label>
                            <input type="password" name="admin_pass" class="form-control" value="" placeholder="Minimal 6 Karakter" required minlength="6">
                        </div>
                    </div>

                    <div class="text-end border-top pt-3">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                            <i class="fa-solid fa-gears me-1"></i> Jalankan Instalasi
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    // Fungsi untuk menyalin teks dari input path
    function salinTeks(elementId, btn) {
        const input = document.getElementById(elementId);
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value);

        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check text-success"></i> Disalin!';
        setTimeout(() => {
            btn.innerHTML = originalHTML;
        }, 2000);
    }

    // Fungsi untuk memperbarui perintah SQL secara otomatis saat password diketik
    function updatePerintahSql() {
        const passInput = document.getElementById('inputPasswordBaru').value;
        const sqlBox = document.getElementById('sqlQueryBox');
        
        const pwdClean = passInput.trim() === '' ? 'PasswordBaruAnda' : passInput;
        
        sqlBox.textContent = `ALTER USER 'root'@'localhost' IDENTIFIED BY '${pwdClean}';\nFLUSH PRIVILEGES;`;
    }

    // Fungsi untuk menyalin teks SQL dari blok kode
    function salinKodeSql(btn) {
        const sqlText = document.getElementById('sqlQueryBox').textContent;
        navigator.clipboard.writeText(sqlText);

        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i> Disalin!';
        setTimeout(() => {
            btn.innerHTML = originalHTML;
        }, 2000);
    }
</script>

</body>
</html>