// Submit Form Edit Profil via AJAX
$('#formProfil').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'update_profil' });

    $.ajax({
        url: './api/proses_profil.php',
        type: 'POST',
        data: $.param(formData),
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
            console.error("AJAX Error:", xhr.responseText);
            showGlobalAlert('error', 'Terjadi kesalahan pada server.');
        }
    });
});

// Submit Form Ganti Password via AJAX
$('#formPassword').on('submit', function(e) {
    e.preventDefault();
    let passwordBaru = $('#password_baru').val();
    let konfirmasiPwd = $('#konfirmasi_password').val();

    // Validasi panjang minimal 6 karakter
    if (passwordBaru.length < 6) {
        showGlobalAlert('error', 'Password baru minimal harus terdiri dari 6 karakter!');
        return;
    }

    if (passwordBaru !== konfirmasiPwd) {
        showGlobalAlert('error', 'Konfirmasi password baru tidak cocok!');
        return;
    }

    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'ganti_password' });

    $.ajax({
        url: './api/proses_profil.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#formPassword')[0].reset();
                showGlobalAlert('success', res.message);
            } else {
                showGlobalAlert('error', res.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", xhr.responseText);
            showGlobalAlert('error', 'Terjadi kesalahan pada server.');
        }
    });
});