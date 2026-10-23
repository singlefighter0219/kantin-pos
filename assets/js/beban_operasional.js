function resetFormBeban() {
    $('#formBeban')[0].reset();
    $('#beban_id').val('');
    $('#modalBebanTitle').html('<i class="fa-solid fa-file-invoice-dollar me-2"></i> Catat Biaya Operasional');
    $('#btnSimpanBeban').text('Simpan & Catat Kas');
}

function editBeban(data) {
    resetFormBeban();
    $('#modalBebanTitle').html('<i class="fa-solid fa-pen-to-square me-2"></i> Edit Biaya Operasional');
    $('#btnSimpanBeban').text('Perbarui Data');

    $('#beban_id').val(data.id);
    $('#nama_beban').val(data.nama_beban);
    $('#kategori_beban').val(data.kategori_beban);
    $('#nominal').val(Math.round(data.nominal || 0));
    $('#keterangan').val(data.keterangan || '');

    $('#modalBeban').modal('show');
}

// Submit Form Simpan / Edit Beban Operasional
$('#formBeban').on('submit', function(e) {
    e.preventDefault();
    
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'simpan_beban' });

    $.ajax({
        url: './api/proses_beban_operasional.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalBeban').modal('hide');
                showGlobalAlert('success', res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showGlobalAlert('error', res.message);
            }
        },
        error: function(xhr, status, error) {
            showGlobalAlert('error', 'Terjadi kesalahan sistem: ' + error);
        }
    });
});

// Hapus Beban Operasional
function hapusBeban(id) {
    showConfirmDelete('Apakah Anda yakin ingin menghapus catatan pengeluaran ini? Pencatatan di Buku Kas terkait juga akan dihapus.', function() {
        $.ajax({
            url: './api/proses_beban_operasional.php',
            type: 'POST',
            data: { 
                action: 'hapus_beban',
                id: id 
            },
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    showGlobalAlert('success', res.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showGlobalAlert('error', res.message);
                }
            },
            error: function(xhr, status, error) {
                showGlobalAlert('error', 'Terjadi kesalahan sistem: ' + error);
            }
        });
    });
}

function setShortcut(type) {
    let today = new Date();
    let yyyy = today.getFullYear();
    let mm = String(today.getMonth() + 1).padStart(2, '0');
    let dd = String(today.getDate()).padStart(2, '0');
    let formattedToday = `${yyyy}-${mm}-${dd}`;

    $('#semua_tanggal').prop('checked', false);
    $('#filter_dari').prop('disabled', false).prop('required', true);
    $('#filter_sampai').prop('disabled', false).prop('required', true);

    $('#btn-hari-ini, #btn-7-hari, #btn-bulan-ini').removeClass('active-shortcut fw-bold shadow-sm');

    if (type === 'hari_ini') {
        $('#filter_dari').val(formattedToday);
        $('#filter_sampai').val(formattedToday);
        $('#btn-hari-ini').addClass('active-shortcut fw-bold shadow-sm');
    } else if (type === '7_hari') {
        let pastDate = new Date();
        pastDate.setDate(today.getDate() - 6);
        let pY = pastDate.getFullYear();
        let pM = String(pastDate.getMonth() + 1).padStart(2, '0');
        let pD = String(pastDate.getDate()).padStart(2, '0');
        $('#filter_dari').val(`${pY}-${pM}-${pD}`);
        $('#filter_sampai').val(formattedToday);
        $('#btn-7-hari').addClass('active-shortcut fw-bold shadow-sm');
    } else if (type === 'bulan_ini') {
        let firstDay = `${yyyy}-${mm}-01`;
        $('#filter_dari').val(firstDay);
        $('#filter_sampai').val(formattedToday);
        $('#btn-bulan-ini').addClass('active-shortcut fw-bold shadow-sm');
    }

    $('#form-filter').submit();
}

function toggleTanggal(checkbox) {
    if (checkbox.checked) {
        $('#filter_dari').prop('disabled', true).prop('required', false);
        $('#filter_sampai').prop('disabled', true).prop('required', false);
        $('#btn-hari-ini, #btn-7-hari, #btn-bulan-ini').removeClass('active-shortcut fw-bold shadow-sm');
    } else {
        $('#filter_dari').prop('disabled', false).prop('required', true);
        $('#filter_sampai').prop('disabled', false).prop('required', true);
    }
}