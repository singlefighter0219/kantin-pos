-- Skrip Inisialisasi Database Kantin POS Clean Installation
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `beban_operasional` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` datetime NOT NULL DEFAULT current_timestamp(),
  `nama_beban` varchar(100) NOT NULL,
  `kategori_beban` enum('operasional','utilitas','bahan_baku','lainnya') DEFAULT 'operasional',
  `nominal` decimal(12,0) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `buku_kas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` datetime NOT NULL DEFAULT current_timestamp(),
  `tipe` enum('masuk','keluar') NOT NULL,
  `kategori` enum('Penjualan','Belanja Produk','Setoran Vendor','Biaya Operasional','Modal / Prive','Lain-Lain') NOT NULL,
  `nominal` decimal(12,0) NOT NULL DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `ref_tipe` varchar(50) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `penjualan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_nota` varchar(50) NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT current_timestamp(),
  `total_belanja` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_modal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_laba` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `kembalian` decimal(12,2) NOT NULL DEFAULT 0.00,
  `kasir` varchar(100) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_nota` (`no_nota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detail_penjualan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penjualan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `hpp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_jual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `qty` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal_laba` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `penjualan_id` (`penjualan_id`),
  CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `vendor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_vendor` varchar(150) NOT NULL,
  `nohp` varchar(25) NOT NULL,
  `alamat` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `produk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_produk` varchar(150) NOT NULL,
  `kategori_kepemilikan` enum('sendiri','konsinyasi') NOT NULL DEFAULT 'sendiri',
  `kelola_stok` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `stok` int(11) NOT NULL DEFAULT 0,
  `harga_beli` decimal(12,2) NOT NULL DEFAULT 0.00,
  `qty_beli` int(11) NOT NULL DEFAULT 1,
  `hpp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_titip` decimal(12,2) NOT NULL DEFAULT 0.00,
  `harga_jual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `margin_rp` decimal(12,2) NOT NULL DEFAULT 0.00,
  `margin_persen` decimal(5,2) NOT NULL DEFAULT 0.00,
  `saran_25` decimal(12,2) NOT NULL DEFAULT 0.00,
  `saran_50` decimal(12,2) NOT NULL DEFAULT 0.00,
  `saran_75` decimal(12,2) NOT NULL DEFAULT 0.00,
  `saran_100` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vendor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `keranjang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `produk_id` (`produk_id`),
  CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `penerimaan_titipan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_nota_terima` varchar(50) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `qty_masuk` int(11) NOT NULL DEFAULT 0,
  `qty_sisa` int(11) NOT NULL DEFAULT 0,
  `harga_titip` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_settled` tinyint(1) NOT NULL DEFAULT 0,
  `tanggal_terima` datetime DEFAULT current_timestamp(),
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `retur_titipan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_nota_retur` varchar(50) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `stok_awal` int(11) NOT NULL,
  `qty_retur` int(11) NOT NULL,
  `qty_terjual` int(11) NOT NULL,
  `harga_titip` decimal(12,2) NOT NULL,
  `total_setoran` decimal(12,2) NOT NULL,
  `tanggal_retur` datetime DEFAULT current_timestamp(),
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `pengaturan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `param_key` varchar(100) NOT NULL,
  `param_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `param_key` (`param_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Pengisian Nilai Default Tabel Pengaturan
INSERT INTO `pengaturan` (`param_key`, `param_value`) VALUES
('nama_aplikasi', ''),
('tagline', ''),
('nama_pemilik', ''),
('alamat_lengkap', ''),
('no_telepon', ''),
('prefix_penjualan', 'POS-'),
('prefix_konsinyasi', 'KONSI-'),
('prefix_retur', 'RETUR-'),
('pesan_nota', 'Terima kasih atas kunjungan Anda! Barang yang sudah dibeli tidak dapat ditukar kembali.'),
('favico', ''),
('logo', ''),
('app_version', '1.0.0')
ON DUPLICATE KEY UPDATE `param_key` = VALUES(`param_key`);

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `katasandi` varchar(255) NOT NULL,
  `role` enum('admin','kasir') NOT NULL DEFAULT 'kasir',
  `tema` varchar(20) NOT NULL DEFAULT 'biru-muda',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;