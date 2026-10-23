// Submit Form Simpan / Edit Vendor via AJAX
$('#formVendor').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'simpan_vendor' });

    $.ajax({
        url: './api/proses_vendor.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalVendor').modal('hide');
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

// Fungsi untuk membuka modal tambah vendor baru
function resetFormVendor() {
    $('#formVendor')[0].reset();
    $('#vendor_id').val('');
    $('#modalVendorTitle').html('<i class="fa-solid fa-handshake"></i> Tambah Vendor Baru');
    $('#modalVendor').modal('show');
}

// Fungsi untuk membuka modal edit vendor dengan data yang ada
function editVendor(v) {
    $('#formVendor')[0].reset();
    $('#vendor_id').val(v.id);
    $('#nama_vendor').val(v.nama_vendor);
    $('#vendor_nohp').val(v.nohp);
    $('#vendor_alamat').val(v.alamat);
    $('#modalVendorTitle').html('<i class="fa-solid fa-pen-to-square"></i> Edit Data Vendor');
    $('#modalVendor').modal('show');
}

function lihatProdukVendor(vendorId, namaVendor) {
    $('#lblNamaVendor').text(namaVendor);
    $('#list-produk-vendor').html('<tr><td colspan="5" class="text-center py-3 text-muted">Memuat data produk...</td></tr>');
    $('#modalProdukVendor').modal('show');

    $.ajax({
        url: './api/get_produk_vendor.php',
        type: 'GET',
        data: { vendor_id: vendorId },
        dataType: 'json',
        success: function(res) {
            let tbody = $('#list-produk-vendor');
            tbody.empty();

            if (res.status && res.data.length > 0) {
                res.data.forEach(item => {
                    let badgeStatus = item.status === 'aktif' ? '<span class="badge bg-success-subtle text-success">Aktif</span>' : '<span class="badge bg-secondary">Non-Aktif</span>';
                    let row = `
                        <tr>
                            <td class="fw-bold">${item.nama_produk}</td>
                            <td class="text-center">${item.stok} Pcs</td>
                            <td class="text-end text-primary">Rp ${Math.round(parseFloat(item.harga_titip)).toLocaleString('id-ID')}</td>
                            <td class="text-end fw-bold text-success">Rp ${Math.round(parseFloat(item.harga_jual)).toLocaleString('id-ID')}</td>
                            <td class="text-center">${badgeStatus}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            } else {
                tbody.html(`<tr><td colspan="5" class="text-center text-muted fst-italic">Vendor ini belum memiliki produk konsinyasi/titipan aktif.</td></tr>`);
            }
        },
        error: function() {
            $('#list-produk-vendor').html(`<tr><td colspan="5" class="text-center text-danger">Gagal memuat data dari server.</td></tr>`);
        }
    });
}