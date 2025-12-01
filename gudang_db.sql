-- Database: gudang_db

-- Tabel untuk User Login
CREATE TABLE `usernames` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk Produk
CREATE TABLE `produk` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(255) NOT NULL,
  `harga` DECIMAL(10,0) NOT NULL,
  `stok` INT(11) NOT NULL,
  `deskripsi` TEXT,
  `foto` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tambahkan satu akun admin (password: admin123)
-- Catatan: Password 'admin123' sudah di-hash menggunakan PASSWORD_DEFAULT
INSERT INTO `usernames` (`name`, `password`) VALUES
('admin', '$2y$10$w85xN8fQ79lV1z5rN0m2lO/W0f1l2G9z1rV0jN1k9dY2dG9x9eY6o');