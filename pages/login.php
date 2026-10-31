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
$tagline           = $appSettings['tagline'] ?? '';
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
            /* Latar belakang Mesh Gradient yang cantik dan elegan */
            background: 
                radial-gradient(at 0% 0%, rgba(224, 195, 252, 0.4) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(142, 197, 252, 0.4) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(255, 209, 255, 0.4) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(161, 196, 253, 0.4) 0px, transparent 50%),
                #f8f9fc;
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
            max-width: 420px;
        }
        .login-card {
            /* Efek Glassmorphism (Kaca Buram) */
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
        }
        .login-header-icon {
            width: 75px;
            height: 75px;
            background: rgba(255, 255, 255, 0.9);
            color: #0ea5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 22px;
            margin: 0 auto 15px auto;
            font-size: 30px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,1);
        }
        .login-header-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .school-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(255, 255, 255, 0.7);
            color: #0369a1;
            font-size: 11.5px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .form-control {
            padding: 12px 15px;
            font-size: 14px;
            background: transparent;
            border: none;
        }
        .input-group {
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        .input-group-text {
            background-color: transparent;
            border: none;
            color: #64748b;
            padding-left: 16px;
        }
        .input-group:focus-within {
            background: rgba(255, 255, 255, 0.95);
            border-color: #bae6fd;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
        }
        .btn-login-submit {
            padding: 12px;
            font-weight: 600;
            font-size: 14.5px;
            letter-spacing: 0.5px;
            border-radius: 12px;
            background: #0ea5e9;
            border: none;
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
        .btn-login-submit:hover {
            background: #0284c7;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="card login-card p-4 p-md-5">
        <div class="text-center mb-1">
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

            <!-- Tagline diletakkan tepat di bawah nama pemilik / badge instansi -->
            <?php if (!empty($tagline)): ?>
                <div class="fst-italic text-secondary small mb-3" style="font-size: 11.5px;">
                    "<?= htmlspecialchars($tagline) ?>"
                </div>
            <?php endif; ?>

            <p class="text-muted small mb-0">Silahkan masuk menggunakan akun Anda</p>
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