// Ganti dengan link raw version.json di repository GitHub kamu
const GITHUB_VERSION_URL = 'https://raw.githubusercontent.com/singlefighter0219/kantin-pos/main/version.json';

// Mengambil versi lokal secara dinamis dari database (yang dirender di atribut data-version)
function getLocalCurrentVersion() {
    const versionEl = document.getElementById('current-version');
    return versionEl ? (versionEl.getAttribute('data-version') || '1.0.0') : '1.0.0';
}

function checkUpdate() {
    const btnCheck = document.getElementById('btn-check-update');
    btnCheck.disabled = true;
    btnCheck.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Memeriksa...';

    const localVersion = getLocalCurrentVersion();

    // 1. Cek Koneksi Internet (Client-side)
    if (!navigator.onLine) {
        showGlobalAlert('error', 'Komputer tidak terhubung ke internet. Pastikan jaringan internet aktif untuk memeriksa pembaruan.');
        resetCheckBtn(btnCheck);
        return;
    }

    // 2. Ambil data version.json dari GitHub
    fetch(GITHUB_VERSION_URL, { cache: "no-store" })
        .then(response => {
            if (!response.ok) throw new Error('Gagal mengakses server update.');
            return response.json();
        })
        .then(data => {
            resetCheckBtn(btnCheck);
            
            // Bandingkan versi lokal (dari DB) dengan versi GitHub
            if (isNewerVersion(localVersion, data.version)) {
                document.getElementById('update-container').style.display = 'block';
                document.getElementById('new-version-number').innerText = 'v' + data.version;
                document.getElementById('changelog-text').innerText = data.changelog;
                window.updateDownloadUrl = data.download_url;
                window.latestVersionNumber = data.version;
            } else {
                showGlobalAlert('success', 'Aplikasi Anda sudah menggunakan versi terbaru (v' + localVersion + ').');
            }
        })
        .catch(err => {
            resetCheckBtn(btnCheck);
            showGlobalAlert('error', 'Gagal mengecek pembaruan. Pastikan koneksi stabil.');
            console.error(err);
        });
}

function resetCheckBtn(btn) {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-search me-1"></i> Cek Pembaruan';
}

function isNewerVersion(current, latest) {
    const cParts = current.split('.').map(Number);
    const lParts = latest.split('.').map(Number);
    for (let i = 0; i < Math.max(cParts.length, lParts.length); i++) {
        const c = cParts[i] || 0;
        const l = lParts[i] || 0;
        if (l > c) return true;
        if (l < c) return false;
    }
    return false;
}

// 3. Proses Download dengan Progress Bar Real-Time
function startDownloadUpdate() {
    document.getElementById('update-info-alert').style.display = 'none';
    document.getElementById('progress-wrapper').style.display = 'block';

    const xhr = new XMLHttpRequest();
    const targetUrl = 'updater.php?action=download_and_extract&url=' + encodeURIComponent(window.updateDownloadUrl) + '&version=' + encodeURIComponent(window.latestVersionNumber);

    xhr.open('GET', targetUrl, true);

    xhr.onprogress = function(event) {
        if (event.lengthComputable) {
            const percentComplete = Math.round((event.loaded / event.total) * 100);
            const progressBar = document.getElementById('update-progress-bar');
            progressBar.style.width = percentComplete + '%';
            progressBar.innerText = percentComplete + '%';
            
            if (percentComplete < 100) {
                document.getElementById('update-status-text').innerText = 'Mengunduh file pembaruan... (' + Math.round(event.loaded / 1024) + ' KB)';
            } else {
                document.getElementById('update-status-text').innerText = 'Mengekstrak file dan memperbarui sistem... Jangan tutup halaman.';
            }
        }
    };

    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    document.getElementById('update-status-text').innerText = 'Pembaruan Berhasil! Memuat ulang halaman...';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showGlobalAlert('error', 'Gagal memperbarui: ' + response.message);
                }
            } catch (e) {
                showGlobalAlert('error', 'Terjadi kesalahan format respon dari server.');
            }
        } else {
            showGlobalAlert('error', 'Gagal mengunduh pembaruan dari server.');
        }
    };

    xhr.onerror = function() {
        showGlobalAlert('error', 'Koneksi terputus saat proses pengunduhan.');
    };

    xhr.send();
}