// Toggle Pengaturan Stok
function toggleStokInput() {
    const isChecked = $('#kelola_stok').is(':checked');
    if (isChecked) {
        $('#box_stok').slideDown();
        $('#label_stok').text('Lacak Stok (Aktif)');
    } else {
        $('#box_stok').slideUp();
        $('#label_stok').text('Tanpa Stok (Bebas / Racikan)');
    }
}

// Toggle Kepemilikan (Sendiri vs Konsinyasi)
function toggleKepemilikan() {
    const tipe = $('#kategori_kepemilikan').val();
    
    if (tipe === 'sendiri') {
        $('#box_harga_beli').show();
        $('#box_qty_beli').show();
        $('#box_info_hpp').show();
        $('#box_harga_titip').hide();
        $('#box_vendor').slideUp();
        $('#box_saran_harga').slideDown();
        $('#vendor_id').val('');
        
        $('#kelola_stok').prop('disabled', false);
    } else {
        $('#box_harga_beli').hide();
        $('#box_qty_beli').hide();
        $('#box_info_hpp').hide();
        $('#box_harga_titip').show();
        $('#box_vendor').slideDown();
        $('#box_saran_harga').slideUp();
        
        // AUTO-SELECT & LOCK: Wajib Lacak Stok untuk Konsinyasi
        $('#kelola_stok').prop('checked', true).prop('disabled', true);
    }
    
    toggleStokInput();
    hitungKalkulasiHarga();
}

// Hitung Otomatis HPP, Margin, & 4 Saran Harga
function hitungKalkulasiHarga() {
    const tipe = $('#kategori_kepemilikan').val();
    let modalSatuan = 0;

    if (tipe === 'sendiri') {
        const hargaBeli = parseFloat($('#harga_beli').val()) || 0;
        const qtyBeli = parseInt($('#qty_beli').val()) || 1;
        modalSatuan = qtyBeli > 0 ? hargaBeli / qtyBeli : 0;
        
        $('#text_hpp_satuan').text('Rp ' + Math.round(modalSatuan).toLocaleString('id-ID'));
    } else {
        modalSatuan = parseFloat($('#harga_titip').val()) || 0;
    }

    const jual = parseFloat($('#harga_jual').val()) || 0;

    // Hitung Margin
    const marginRp = jual - modalSatuan;
    const marginPersen = modalSatuan > 0 ? (marginRp / modalSatuan) * 100 : 0;

    // Hitung 4 Saran Harga dari Modal Satuan (HPP)
    const s25 = Math.round(modalSatuan * 1.25);
    const s50 = Math.round(modalSatuan * 1.50);
    const s75 = Math.round(modalSatuan * 1.75);
    const s100 = Math.round(modalSatuan * 2.00);

    // ===================================================
    // KONDISI WARNA DINAMIS: JIKA MINUS (MERAH), JIKA POSITIF (HIJAU)
    // ===================================================
    const elMarginRp = $('#text_margin_rp');
    const elBadgePersen = $('#text_margin_persen');

    elMarginRp.text('Rp ' + Math.round(marginRp).toLocaleString('id-ID'));
    elBadgePersen.text(marginPersen.toFixed(1) + '%');

    if (marginRp < 0) {
        // Jika Minus / Rugi -> Ubah ke Merah
        elMarginRp.removeClass('text-success').addClass('text-danger');
        elBadgePersen.removeClass('bg-success').addClass('bg-danger');
    } else {
        // Jika Untung / Normal -> Kembalikan ke Hijau
        elMarginRp.removeClass('text-danger').addClass('text-success');
        elBadgePersen.removeClass('bg-danger').addClass('bg-success');
    }

    // Update Tampilan Saran Harga
    $('#saran_25').text('Rp ' + s25.toLocaleString('id-ID'));
    $('#saran_50').text('Rp ' + s50.toLocaleString('id-ID'));
    $('#saran_75').text('Rp ' + s75.toLocaleString('id-ID'));
    $('#saran_100').text('Rp ' + s100.toLocaleString('id-ID'));

    // Set nilai ke Hidden Input
    $('#input_hpp').val(modalSatuan);
    $('#input_margin_rp').val(marginRp);
    $('#input_margin_persen').val(marginPersen.toFixed(2));
    $('#input_saran_25').val(s25);
    $('#input_saran_50').val(s50);
    $('#input_saran_75').val(s75);
    $('#input_saran_100').val(s100);
}

function pilihSaranHarga(persen) {
    const tipe = $('#kategori_kepemilikan').val();
    let modalSatuan = 0;
    if (tipe === 'sendiri') {
        const hargaBeli = parseFloat($('#harga_beli').val()) || 0;
        const qtyBeli = parseInt($('#qty_beli').val()) || 1;
        modalSatuan = qtyBeli > 0 ? hargaBeli / qtyBeli : 0;
    }
    const hargaSaran = Math.round(modalSatuan * (1 + persen));
    $('#harga_jual').val(hargaSaran);
    hitungKalkulasiHarga();
}

function resetForm() {
    $('#formProduk')[0].reset();
    $('#produk_id').val('');
    $('#modalTitle').html('<i class="fa-solid fa-box-open text-primary me-2"></i> Tambah Produk Baru');
    
    // Munculkan kembali keduanya saat tambah produk baru
    $('#box_kategori_wrapper').show();
    $('#box_pengaturan_stok_wrapper').show();
    $('#kelola_stok').prop('disabled', false);
    
    $('#kelola_stok').prop('checked', true);
    $('#qty_beli').val(1);
    $('#stok').val(0);
    toggleStokInput();
    toggleKepemilikan();
}

function editProduk(data) {
    resetForm();
    $('#modalTitle').html('<i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Produk');
    $('#produk_id').val(data.id);
    $('#nama_produk').val(data.nama_produk);
    
    // Sembunyikan kategori kepemilikan
    $('#box_kategori_wrapper').hide(); 
    
    // Munculkan toggle pengaturan stok KHUSUS produk sendiri, sembunyikan jika konsinyasi
    if (data.kategori_kepemilikan === 'sendiri') {
        $('#box_pengaturan_stok_wrapper').show();
        $('#kelola_stok').prop('disabled', false); 
    } else {
        $('#box_pengaturan_stok_wrapper').hide();
        $('#kelola_stok').prop('disabled', true);
    }

    $('#kategori_kepemilikan').val(data.kategori_kepemilikan);
    $('#vendor_id').val(data.vendor_id || '');
    
    // Hilangkan desimal .00
    $('#harga_beli').val(Math.round(data.harga_beli || 0));
    $('#qty_beli').val(parseInt(data.qty_beli || 1));
    $('#harga_titip').val(Math.round(data.harga_titip || 0));
    $('#harga_jual').val(Math.round(data.harga_jual || 0));
    
    const stokKasir = parseInt(data.stok || 0);
    $('#stok').val(stokKasir);
    
    if (parseInt(data.kelola_stok) === 1) {
        $('#kelola_stok').prop('checked', true);
    } else {
        $('#kelola_stok').prop('checked', false);
    }
    
    // Atur tampilan field input (Milik Sendiri vs Titipan)
    if (data.kategori_kepemilikan === 'sendiri') {
        $('#box_harga_beli').show();
        $('#box_qty_beli').show();
        $('#box_info_hpp').show();
        $('#box_harga_titip').hide();
        $('#box_vendor').hide();
        $('#box_saran_harga').show();
        // Pastikan harga titip tidak ter-lock jika beralih form
        $('#harga_titip').prop('disabled', false);
        $('#info_lock_harga_titip').remove();
    } else {
        $('#box_harga_beli').hide();
        $('#box_qty_beli').hide();
        $('#box_info_hpp').hide();
        $('#box_harga_titip').show();
        $('#box_vendor').show();
        $('#box_saran_harga').hide();

        // VALIDASI: Jika produk konsinyasi dan stok di kasir > 0, Kunci Harga Titip
        $('#info_lock_harga_titip').remove(); // Bersihkan elemen info lama jika ada
        if (stokKasir > 0) {
            $('#harga_titip').prop('disabled', true);
            $('#harga_titip').after('<small id="info_lock_harga_titip" class="text-danger d-block mt-1" style="font-size: 11px;"><i class="fa-solid fa-lock me-1"></i> Harga titip terkunci karena masih ada sisa stok di kasir (' + stokKasir + ' Pcs). Stok harus 0 untuk mengubah harga titip.</small>');
        } else {
            $('#harga_titip').prop('disabled', false);
        }
    }
    
    toggleStokInput();
    hitungKalkulasiHarga();
    $('#modalProduk').modal('show');
}

// ===================================================
// LOGIKA MODAL RESTOCK PRODUK
// ===================================================
function bukaModalRestock(data) {
    if (parseInt(data.kelola_stok) === 0) {
        showGlobalAlert('error', 'Produk ini diset "Bebas Stok", sehingga tidak memerlukan restock!');
        return;
    }

    $('#formRestock')[0].reset();
    $('#restock_produk_id').val(data.id);
    $('#restock_kategori').val(data.kategori_kepemilikan);
    $('#restock_vendor_id').val(data.vendor_id || '');
    $('#restock_nama_produk').text(data.nama_produk);
    $('#restock_stok_sekarang').text(data.stok);
    $('#restock_qty').val(1);

    if (data.kategori_kepemilikan === 'sendiri') {
        $('#box_restock_sendiri').show();
        $('#box_restock_konsinyasi').hide();
        
        const estBiaya = Math.round(parseFloat(data.hpp) || 0);
        $('#restock_total_biaya').val(estBiaya);
    } else {
        $('#box_restock_sendiri').hide();
        $('#box_restock_konsinyasi').show();
        
        const hrgTitip = Math.round(parseFloat(data.harga_titip) || 0);
        $('#restock_text_harga_titip').text('Rp ' + hrgTitip.toLocaleString('id-ID'));
    }

    $('#modalRestock').modal('show');
}

// ===================================================
// LOGIKA MODAL RETUR TITIPAN (DIPERBAIKI)
// ===================================================
function bukaModalRetur(data) {
    $('#formRetur')[0].reset();
    
    $('#retur_produk_id').val(data.id);
    $('#retur_vendor_id').val(data.vendor_id);
    $('#retur_nama_produk').text(data.nama_produk);
    $('#retur_nama_vendor').text(data.nama_vendor || '-');
    
    const hargaTitip = Math.round(data.harga_titip || 0);
    $('#retur_harga_titip').val(hargaTitip);
    $('#text_retur_harga_titip').text('Rp ' + hargaTitip.toLocaleString('id-ID'));

    const totalQtyMasukAktif = parseInt(data.stok_awal_masuk || 0); 
    const sisaStokKasir = parseInt(data.stok || 0); 

    $('#retur_stok_awal').val(totalQtyMasukAktif);
    
    // Tampilkan pada teks UI modal
    $('#text_stok_awal_masuk').text(totalQtyMasukAktif + ' Pcs');
    $('#text_sisa_stok_kasir').text(sisaStokKasir + ' Pcs');

    // Karena sisa stok wajib ditarik semua, input qty retur dikunci nilainya sama dengan sisa stok kasir
    $('#retur_qty').val(sisaStokKasir);

    hitungKalkulasiRetur();
    $('#modalRetur').modal('show');
}

function hitungKalkulasiRetur() {
    const totalQtyMasuk = parseInt($('#retur_stok_awal').val()) || 0; 
    const sisaStokKasir = parseInt($('#text_sisa_stok_kasir').text()) || 0;     

    // Qty retur mutlak sama dengan sisa stok kasir (wajib ditarik semua)
    const qtyRetur = sisaStokKasir;
    $('#retur_qty').val(qtyRetur);

    // Qty terjual murni adalah selisih dari total masuk awal dikurangi sisa stok yang ditarik
    const qtyTerjual = totalQtyMasuk - sisaStokKasir; 

    const hargaTitip = parseInt($('#retur_harga_titip').val()) || 0;
    const totalSetoran = qtyTerjual * hargaTitip;

    $('#retur_qty_terjual').val(qtyTerjual);
    $('#retur_total_setoran').val(totalSetoran);

    $('#text_retur_terjual').text(qtyTerjual + ' Pcs');
    $('#text_retur_total_setoran').text('Rp ' + totalSetoran.toLocaleString('id-ID'));
}

// 1. Submit Form Master Produk
$('#formProduk').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'simpan_produk' });

    $.ajax({
        url: './api/proses_produk.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalProduk').modal('hide');
                showGlobalAlert('success', res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showGlobalAlert('error', res.message);
            }
        }
    });
});

// 2. Submit Form Restock
$('#formRestock').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'proses_restock' });

    $.ajax({
        url: './api/proses_produk.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalRestock').modal('hide');
                showGlobalAlert('success', res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showGlobalAlert('error', res.message);
            }
        }
    });
});

// 3. Submit Form Retur
$('#formRetur').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serializeArray();
    formData.push({ name: 'action', value: 'proses_retur' });

    $.ajax({
        url: './api/proses_produk.php',
        type: 'POST',
        data: $.param(formData),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                $('#modalRetur').modal('hide');
                showGlobalAlert('success', res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                showGlobalAlert('error', res.message);
            }
        },
        error: function(xhr, status, error) {
            showGlobalAlert('error', 'Terjadi kesalahan sistem pada server. Cek console browser.');
        }
    });
});

// 4. Toggle Status Produk
function toggleStatusProduk(id) {
    showConfirmDelete('Apakah Anda yakin ingin mengubah status produk ini (Arsip / Aktifkan)?', function() {
        $.ajax({
            url: './api/proses_produk.php',
            type: 'POST',
            data: { 
                action: 'toggle_status',
                produk_id: id 
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
                showGlobalAlert('error', 'Terjadi kesalahan sistem: ' + error);
            }
        });
    });
}