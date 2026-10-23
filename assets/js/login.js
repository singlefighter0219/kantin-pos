$(document).ready(function() {
    $('#form-login').on('submit', function(e) {
        e.preventDefault();

        let btn = $('#btn-login');
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Memproses...');

        $.ajax({
            url: './api/proses_auth.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showGlobalAlert('success', response.message);
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1000);
                } else {
                    showGlobalAlert('error', response.message);
                    btn.prop('disabled', false).html('<i class="fa-solid fa-right-to-bracket me-2"></i> Masuk');
                }
            },
            error: function() {
                showGlobalAlert('error', 'Terjadi kesalahan sistem.');
                btn.prop('disabled', false).html('<i class="fa-solid fa-right-to-bracket me-2"></i> Masuk');
            }
        });
    });
});