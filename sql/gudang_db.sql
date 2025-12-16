CREATE DATABASE IF NOT EXISTS `gudang_db`;
USE `gudang_db`;

-- 1. Tabel usernames
CREATE TABLE `usernames` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel gudang
CREATE TABLE `gudang` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `nama_gudang` VARCHAR(255) NOT NULL,
  `tanggal_buat` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gudang_user_name` (`user_id`, `nama_gudang`),
  CONSTRAINT fk_user_gudang
    FOREIGN KEY (`user_id`) 
    REFERENCES `usernames`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel produk
CREATE TABLE `produk` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `gudang_id` INT(11) NOT NULL, 
  `nama` VARCHAR(255) NOT NULL,
  `harga` DECIMAL(10,0) NOT NULL,
  `stok` INT(11) NOT NULL,
  `deskripsi` TEXT,
  `foto` VARCHAR(255) DEFAULT NULL,
  `tanggal_update` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT fk_gudang_produk
    FOREIGN KEY (`gudang_id`) 
    REFERENCES `gudang`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `usernames` (`name`, `password`) VALUES
('admin', '202cb962ac59075b964b07152d234b70'); -- username: admin  password: 123
