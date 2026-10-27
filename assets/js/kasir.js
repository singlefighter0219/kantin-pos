$(document).ready(function() {
    loadCart();

    // Event Klik Tambah Produk ke Keranjang
    $(document).on('click', '.btn-tambah-produk', function() {
        let produkId = $(this).data('id');$.ajax({
            url: './api/proses_pos.php',
            type: 'POST',
            data: { action: 'add_cart', produk_id: produkId },
            dataType: 'json',
            success: function(res) {
                if (res.status) {
                    loadCart();
                } else {
                    showGlobalAlert('error', res.message);
                }
            }
        });
    });
});

// Load Isi Keranjang dari Database
function loadCart() {
    $.ajax({
        url: './api/proses_pos.php',
        type: 'GET',
        data: { action: 'get_cart' },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                renderCartTable(res.data);
            }
        }
    });
}

// Render Tabel Keranjang
function renderCartTable(cartItems) {
    let cartTableBody = $('#cart-items');
    cartTableBody.empty();

    if (cartItems.length === 0) {
        cartTableBody.html(`<tr><td colspan="4" class="text-center py-4 text-muted">Keranjang masih kosong</td></tr>`);
        $('#total-belanja').text('Rp 0');
        $('#input-total-belanja').val(0);
        hitungKembalian();
        return;
    }

    let totalBelanja = 0;
    cartItems.forEach(item => {
        let subtotal = item.harga_jual * item.qty;
        totalBelanja += subtotal;

        let row = `
            <tr>
                <td>
                    <div class="fw-bold small text-truncate" style="max-width: 130px;" title="${item.nama_produk}">${item.nama_produk}</div>
                    <small class="text-muted">Rp ${parseFloat(item.harga_jual).toLocaleString('id-ID')}</small>
                </td>
                <td class="text-center" style="width: 110px;">
                    <div class="input-group input-group-sm">
                        <button class="btn btn-outline-secondary px-1" onclick="updateQty(${item.keranjang_id}, -1)">-</button>
                        <input type="number" class="form-control text-center p-0 input-qty-manual" data-id="${item.keranjang_id}" value="${item.qty}" min="1">
                        <button class="btn btn-outline-secondary px-1" onclick="updateQty(${item.keranjang_id}, 1)">+</button>
                    </div>
                </td>
                <td class="text-end small fw-bold">Rp ${subtotal.toLocaleString('id-ID')}</td>
                <td class="text-center">
                    <button class="btn btn-sm text-danger p-0" onclick="removeItem(${item.keranjang_id})"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
        `;
        cartTableBody.append(row);
    });

    $('#total-belanja').text('Rp ' + totalBelanja.toLocaleString('id-ID'));
    $('#input-total-belanja').val(totalBelanja);
    hitungKembalian();
}

// Event Handler Input Qty Ketik Manual
$(document).on('change', '.input-qty-manual', function() {
    let keranjangId = $(this).data('id');
    let qtyBaru = parseInt($(this).val()) || 1;

    if (qtyBaru <= 0) {
        removeItem(keranjangId);
        return;
    }

    $.ajax({
        url: './api/proses_pos.php',
        type: 'POST',
        data: { action: 'set_qty', keranjang_id: keranjangId, qty: qtyBaru },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                loadCart();
            } else {
                showGlobalAlert('error', res.message);
                loadCart(); // Reset ke nilai valid sebelumnya jika stok kurang
            }
        }
    });
});

// Update Qty via AJAX
function updateQty(keranjangId, delta) {
    $.ajax({
        url: './api/proses_pos.php',
        type: 'POST',
        data: { action: 'update_qty', keranjang_id: keranjangId, delta: delta },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                loadCart();
            } else {
                showGlobalAlert('error', res.message);
            }
        }
    });
}

// Hapus Item via AJAX
function removeItem(keranjangId) {
    $.ajax({
        url: './api/proses_pos.php',
        type: 'POST',
        data: { action: 'remove_item', keranjang_id: keranjangId },
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                loadCart();
            } else {
                showGlobalAlert('error', res.message);
            }
        }
    });
}

// Hitung Kembalian Otomatis
function hitungKembalian() {
    let total = parseFloat($('#input-total-belanja').val()) || 0;
    let bayar = parseFloat($('#bayar').val()) || 0;
    let kembalian = bayar - total;

    if (kembalian >= 0) {
        $('#kembalian').text('Rp ' + kembalian.toLocaleString('id-ID')).removeClass('text-danger').addClass('text-success');
    } else {
        $('#kembalian').text('Rp ' + kembalian.toLocaleString('id-ID')).removeClass('text-success').addClass('text-danger');
    }
}

$('#bayar').on('input', function() {
    hitungKembalian();
});

// Proses Checkout
$('#form-checkout').on('submit', function(e) {
    e.preventDefault();

    let total = parseFloat($('#input-total-belanja').val()) || 0;
    let bayar = parseFloat($('#bayar').val()) || 0;

    if (total <= 0) {
        showGlobalAlert('error', 'Keranjang belanja masih kosong!');
        return;
    }

    if (bayar < total) {
        showGlobalAlert('error', 'Jumlah uang bayar kurang dari total belanja!');
        return;
    }

    let formData = {
        action: 'checkout',
        total_belanja: total,
        bayar: bayar,
        kembalian: bayar - total,
        catatan: $('#catatan_transaksi').val(),
        tanggal_manual: $('#tanggal_manual').val()
    };

    $.ajax({
        url: './api/proses_pos.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                showGlobalAlert('success', res.message);
                $('#bayar').val('');
                $('#catatan_transaksi').val('');
                loadCart();
                $('#print-penjualan-id').val(res.penjualan_id);
                let modalCetak = new bootstrap.Modal(document.getElementById('modalCetakNota'));
                modalCetak.show();
            } else {
                showGlobalAlert('error', res.message);
            }
        },
    });
});

// Fitur Pencarian Produk Cepat
$('#cari-produk').on('keyup', function() {
    let keyword = $(this).val().toLowerCase();$('.produk-card-item').each(function() {
        let namaProd = $(this).data('nama').toLowerCase();
        if (namaProd.includes(keyword)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});

// Fungsi jika user memilih cetak nota
function prosesCetakNota() {
    let penjualanId = $('#print-penjualan-id').val();
    if (penjualanId) {
        // Buka file cetak_nota_penjualan.php di tab baru
        window.open('pages/cetak_nota_penjualan.php?id=' + penjualanId, '_blank');
    }
    location.reload();
}

function skipCetakNota() {
    location.reload();
}