/* ===================================================
   GLOBAL ALERT & CONFIRM SYSTEM (jQuery)
   ================================================   */

// Fungsi untuk menampilkan Global Alert (Success, Error, Warning)
function showGlobalAlert(type, message) {
    // Hapus alert lama jika ada
    $('#global-alert-container').remove();

    let bgClass = 'alert-success';
    let icon = 'fa-circle-check';
    let title = 'Berhasil!';

    if (type === 'error') {
        bgClass = 'alert-danger';
        icon = 'fa-circle-exclamation';
        title = 'Gagal!';
    } else if (type === 'warning') {
        bgClass = 'alert-warning';
        icon = 'fa-triangle-exclamation';
        title = 'Peringatan!';
    }

    let alertHtml = `
        <div id="global-alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div class="alert ${bgClass} alert-dismissible fade show shadow-lg d-flex align-items-center" role="alert" style="min-width: 300px;">
                <i class="fa-solid ${icon} fs-4 me-3"><div></div></i>
                <div>
                    <strong>${title}</strong>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    `;

    $('body').append(alertHtml);

    // Otomatis hilang setelah 4 detik
    setTimeout(function() {
        $('#global-alert-container').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 4000);
}

// Fungsi Global untuk Konfirmasi Hapus (Modal Konfirmasi)
function showConfirmDelete(customMessage, onConfirmCallback) {
    // Hapus modal lama jika ada
    $('#global-confirm-modal').remove();

    let modalHtml = `
        <div class="modal fade" id="global-confirm-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i> Konfirmasi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-4">
                        <p class="mb-0 fs-6">${customMessage || 'Apakah Anda yakin ingin menghapus data ini? Data yang dihapus tidak dapat dikembalikan.'}</p>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-danger btn-sm px-3" id="btn-confirm-yes">Ya</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);

    let confirmModal = new bootstrap.Modal(document.getElementById('global-confirm-modal'));
    confirmModal.show();

    // Jika tombol "Ya, Hapus" diklik
    $('#btn-confirm-yes').off('click').on('click', function() {
        confirmModal.hide();
        if (typeof onConfirmCallback === 'function') {
            onConfirmCallback();
        }
    });
}

$(document).on('click', '#btn-logout', function(e) {
    e.preventDefault();
    
    showConfirmDelete('Apakah Anda yakin ingin keluar dari aplikasi?', function() {
        $.ajax({
            url: './api/proses_auth.php',
            type: 'POST',
            data: { action: 'logout' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showGlobalAlert('success', response.message);
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1000);
                } else {
                    showGlobalAlert('error', 'Gagal melakukan logout.');
                }
            },
            error: function() {
                showGlobalAlert('error', 'Terjadi kesalahan sistem.');
            }
        });
    });
});

function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    sidebar.classList.toggle('show');
    backdrop.classList.toggle('show');
}