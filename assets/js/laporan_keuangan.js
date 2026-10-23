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