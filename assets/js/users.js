// Buka modal untuk tambah user baru
function bukaModalTambahUser() {
    $('#formUser')[0].reset();
    $('#user_id').val('');
    $('#modalUserTitle').html('<i class="fa-solid fa-user-plus text-primary me-2"></i> Tambah User Baru');
    $('#katasandi').val('').prop('required', true);
    $('#wrapper_password').show();
    $('#username').prop('readonly', false); // Username bisa diisi saat tambah user baru
    $('#role').val('kasir');
    $('#tema').val('biru-muda');
    $('#modalUser').modal('show');
}

// Buka modal untuk edit user
function editUser(u) {
    $('#formUser')[0].reset();
    $('#user_id').val(u.id);
    $('#nama_lengkap').val(u.nama_lengkap);
    $('#username').val(u.username).prop('readonly', true); // Username dibuat READONLY saat edit agar tidak bisa diubah
    $('#katasandi').val('').prop('required', false);
    $('#wrapper_password').hide(); // Sembunyikan field password saat edit
    $('#role').val(u.role);
    $('#tema').val(u.tema || 'biru-muda');
    $('#modalUserTitle').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Data User');
    $('#modalUser').modal('show');
}

// Buka modal reset password
function bukaModalReset(id, nama) {
    $('#formReset')[0].reset();
    $('#reset_user_id').val(id);
    $('#reset_nama_user').text(nama);
    $('#modalReset').modal('show');
}

// Submit Form Simpan / Edit User via AJAX
$('#formUser').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'simpan_user' });

    $.ajax({
        url: './api/proses_user.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalUser').modal('hide');
                showGlobalAlert('success', res.message);
                setTimeout(() => location.reload(), 1200);
            } else {
                showGlobalAlert('error', res.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error Respon:", xhr.responseText);
            showGlobalAlert('error', 'Terjadi kesalahan pada server.');
        }
    });
});

// Submit Form Reset Password via AJAX
$('#formReset').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'reset_password' });

    $.ajax({
        url: './api/proses_user.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalReset').modal('hide');
                showGlobalAlert('success', res.message);
                setTimeout(() => location.reload(), 1200);
            } else {
                showGlobalAlert('error', res.message);
            }
        },
        error: function(xhr, status, error) {
            showGlobalAlert('error', 'Terjadi kesalahan pada server.');
        }
    });
});

// Konfirmasi Hapus User menggunakan showConfirmDelete
function konfirmasiHapusUser(id, nama) {
    showConfirmDelete(`Apakah Anda yakin ingin menghapus user "${nama}"?`, function() {
        $.ajax({
            url: './api/proses_user.php',
            type: 'POST',
            data: {
                action: 'hapus_user',
                id: id
            },
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
                showGlobalAlert('error', 'Terjadi kesalahan pada server.');
            }
        });
    });
}