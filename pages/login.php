<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SettingController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika pengguna sudah login, langsung arahkan ke Dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL);
    exit();
}

// Ambil pengaturan kantin (Nama Aplikasi, Logo, & Nama Pemilik/Instansi) dari database
$settingController = new SettingController($pdo);
$appSettings       = $settingController->getAllSettings();
$namaAplikasi      = $appSettings['nama_aplikasi'] ?? 'Kantin POS';
$namaPemilik       = $appSettings['nama_pemilik'] ?? ''; // Menyimpan nama sekolah/instansi
$logoToko          = $appSettings['logo'] ?? '';
$favicoFile        = $appSettings['favico'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" data-theme="biru-muda">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - <?= htmlspecialchars($namaAplikasi) ?></title>

    <?php if (!empty($favicoFile)): ?>
        <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/uploads/<?= htmlspecialchars($favicoFile) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/theme.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }
        .login-card {
            background-color: var(--card-bg, #ffffff);
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .login-header-icon {
            width: 72px;
            height: 72px;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px auto;
            font-size: 28px;
            overflow: hidden;
        }
        .login-header-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .school-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(13, 110, 253, 0.06);
            color: #0d6efd;
            font-size: 11.5px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(13, 110, 253, 0.12);
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }
        .input-group-text {
            background-color: transparent;
            border-right: none;
            color: #6c757d;
        }
        .form-control {
            border-left: none;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            border-radius: 6px;
        }
        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: #0d6efd;
        }
        .btn-login-submit {
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="card login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="login-header-icon shadow-sm">
                <?php if (!empty($logoToko)): ?>
                    <img src="<?= BASE_URL ?>assets/uploads/<?= htmlspecialchars($logoToko) ?>" alt="Logo Kantin">
                <?php else: ?>
                    <i class="fa-solid fa-utensils"></i>
                <?php endif; ?>
            </div>

            <h4 class="fw-bold mb-1" style="color: var(--text-color, #212529);"><?= htmlspecialchars($namaAplikasi) ?></h4>
            
            <?php if (!empty($namaPemilik)): ?>
                <div class="mt-2">
                    <span class="school-badge shadow-sm">
                        <i class="fa-solid fa-school"></i> <?= htmlspecialchars($namaPemilik) ?>
                    </span>
                </div>
            <?php endif; ?>

            <p class="text-muted small mb-0">Silakan masuk menggunakan akun Anda</p>
        </div>

        <form id="form-login">
            <input type="hidden" name="action" value="login">
            
            <div class="mb-3">
                <label for="username" class="form-label small fw-bold text-secondary">Username</label>
                <div class="input-group rounded-2 border bg-light">
                    <span class="input-group-text border-0 bg-transparent"><i class="fa-solid fa-user"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent py-2 shadow-none" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-bold text-secondary">Kata Sandi</label>
                <div class="input-group rounded-2 border bg-light">
                    <span class="input-group-text border-0 bg-transparent"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control border-0 bg-transparent py-2 shadow-none" id="password" name="password" placeholder="Masukkan kata sandi" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-login-submit" id="btn-login">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk ke Sistem
            </button>
        </form>
    </div>
    
    <div class="text-center mt-4 text-muted small" style="font-size: 11px;">
        &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($namaAplikasi) ?></strong>
        <?php if (!empty($namaPemilik)): ?>
            <br>Dikelola oleh <?= htmlspecialchars($namaPemilik) ?>
        <?php endif; ?>
        <br>All rights reserved.
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/jquery-3.7.0.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/all.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/script.js"></script>
<script src="<?= BASE_URL ?>assets/js/login.js"></script>

</body>
</html>