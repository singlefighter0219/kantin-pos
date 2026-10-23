<?php
// Peta Hak Akses Halaman (Mapping Halaman Berdasarkan Role yang Diizinkan)
$accessControlList = [
    'kasir'                         => ['admin', 'kasir'],
    'produk'                        => ['admin', 'kasir'],
    'vendor'                        => ['admin', 'kasir'],
    'riwayat_penjualan'             => ['admin', 'kasir'],
    'riwayat_restock_sendiri'       => ['admin', 'kasir'],
    'riwayat_restock_konsinyasi'    => ['admin', 'kasir'],
    'riwayat_retur_konsinyasi'      => ['admin', 'kasir'],
    'beban_operasional'             => ['admin', 'kasir'],
    'kas_lainnya'                   => ['admin', 'kasir'],
    'laporan_keuangan'              => ['admin'],
    'users'                         => ['admin'],
    'pengaturan'                    => ['admin']
];

$pilihanTema = [
    'biru-muda' => 'Biru Muda',
    'pink'      => 'Pink',
    'ungu-muda' => 'Ungu Muda',
    'hijau'     => 'Hijau',
    'orange'    => 'Orange',
    'dark'      => 'Dark (Gelap)'
];
?>