let currentPenjualanId = null;

function lihatDetail(transaksi) {
    currentPenjualanId = transaksi.id;

    $('#lblNoNota').text(transaksi.no_nota);
    $('#lblTanggal').text(transaksi.tanggal);
    $('#lblKasir').text(transaksi.kasir);
    $('#lblBayar').text('Rp ' + Math.round(parseFloat(transaksi.bayar)).toLocaleString('id-ID'));
    $('#lblKembalian').text('Rp ' + Math.round(parseFloat(transaksi.kembalian)).toLocaleString('id-ID'));
    $('#lblCatatan').text(transaksi.catatan ? transaksi.catatan : '-');

    $('#list-detail-item').html('<tr><td colspan="5" class="text-center py-3 text-muted">Memuat data...</td></tr>');
    $('#modalDetailPenjualan').modal('show');

    $.ajax({
        url: './api/get_riwayat_penjualan.php',
        type: 'GET',
        data: { id: transaksi.id },
        dataType: 'json',
        success: function(res) {
            let tbody = $('#list-detail-item');
            tbody.empty();

            if (res.status && res.data.length > 0) {
                let grandTotalSubtotal = 0;
                let grandTotalLaba = 0;

                res.data.forEach(item => {
                    let subtotal = parseFloat(item.subtotal) || 0;
                    let laba = parseFloat(item.subtotal_laba) || 0;
                    
                    grandTotalSubtotal += subtotal;
                    grandTotalLaba += laba;

                    let row = `
                        <tr>
                            <td class="fw-bold">${item.nama_produk}</td>
                            <td class="text-center">${item.qty}</td>
                            <td class="text-end">Rp ${Math.round(parseFloat(item.harga_jual)).toLocaleString('id-ID')}</td>
                            <td class="text-end fw-bold">Rp ${Math.round(subtotal).toLocaleString('id-ID')}</td>
                            <td class="text-end text-success">Rp ${Math.round(laba).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });

                let totalRow = `
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end">Jumlah Total:</td>
                        <td class="text-end">Rp ${Math.round(grandTotalSubtotal).toLocaleString('id-ID')}</td>
                        <td class="text-end text-success">Rp ${Math.round(grandTotalLaba).toLocaleString('id-ID')}</td>
                    </tr>
                `;
                tbody.append(totalRow);

            } else {
                tbody.html(`<tr><td colspan="5" class="text-center text-danger">Gagal memuat detail item atau data kosong.</td></tr>`);
            }
        },
        error: function() {
            $('#list-detail-item').html(`<tr><td colspan="5" class="text-center text-danger">Terjadi kesalahan koneksi sistem.</td></tr>`);
        }
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
        // Reset warna shortcut aktif
        $('#btn-hari-ini, #btn-7-hari, #btn-bulan-ini').removeClass('btn-warning text-dark fw-bold shadow-sm').addClass('btn-outline-secondary');
    } else {
        $('#filter_dari').prop('disabled', false).prop('required', true);
        $('#filter_sampai').prop('disabled', false).prop('required', true);
    }
}

function printModalNota() {
    if (currentPenjualanId) {
        window.open('./pages/cetak_nota_penjualan.php?id=' + currentPenjualanId, '_blank', 'width=380,height=600');
    } else {
        alert('Data transaksi tidak ditemukan.');
    }
}

function printNotaA4() {
    if (currentPenjualanId) {
        // Membuka nota cetak versi A4 / PDF di tab baru
        window.open('./pages/cetak_nota_penjualan_a4.php?id=' + currentPenjualanId, '_blank');
    } else {
        alert('Data transaksi tidak ditemukan.');
    }
}