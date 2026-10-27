// Function Helper Live Preview Gambar
function setupImagePreview(inputId, imgPreviewId, badgeNoImgId) {
    $(`#${inputId}`).on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(`#${imgPreviewId}`).attr('src', e.target.result).show();
                $(`#${badgeNoImgId}`).hide();
            };
            reader.readAsDataURL(file);
        }
    });
}

// Inisialisasi Live Preview untuk Favicon & Logo
setupImagePreview('inputFavico', 'previewFavico', 'badgeNoFavico');
setupImagePreview('inputLogo', 'previewLogo', 'badgeNoLogo');

// Submit masing-masing form pengaturan secara terpisah via AJAX
$('.form-pengaturan').on('submit', function(e) {
    e.preventDefault();
    
    let formData = new FormData(this);

    // Penanganan khusus untuk form_manual_date (checkbox switch jika dimatikan/unchecked)
    let formTypeInput = $(this).find('input[name="form_type"]').val();
    if (formTypeInput === 'form_manual_date') {
        let isChecked = $(this).find('input[name="enable_manual_date"]').is(':checked');
        formData.set('enable_manual_date', isChecked ? '1' : '0');
    }

    $.ajax({
        url: './api/proses_pengaturan.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                showGlobalAlert('success', res.message);
                setTimeout(() => location.reload(), 1200);
            } else {
                showGlobalAlert('error', res.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error Respon:", xhr.responseText);
            showGlobalAlert('error', 'Terjadi kesalahan sistem pada server.');
        }
    });
});

// Tombol Hapus Gambar (Favicon / Logo) Menggunakan Modal showConfirmDelete
$('.btn-hapus-gambar').on('click', function() {
    let paramKey = $(this).data('key');
    let labelName = paramKey === 'favico' ? 'Favicon' : 'Logo Toko';
    let message = `Apakah Anda yakin ingin menghapus ${labelName}? Gambar yang dihapus tidak dapat dikembalikan.`;

    // Panggil fungsi modal konfirmasi bawaan script.js
    showConfirmDelete(message, function() {
        $.ajax({
            url: './api/proses_pengaturan.php',
            type: 'POST',
            data: { action: 'hapus_gambar', param_key: paramKey },
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    showGlobalAlert('success', res.message);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showGlobalAlert('error', res.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error Respon:", xhr.responseText);
                showGlobalAlert('error', 'Terjadi kesalahan sistem pada server.');
            }
        });
    });
});