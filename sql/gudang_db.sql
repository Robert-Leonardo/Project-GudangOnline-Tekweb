-- 1. Tabel usernames (Tidak Berubah)
CREATE TABLE `usernames` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel GUDANG BARU
CREATE TABLE `gudang` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL, -- Pemilik Gudang
  `nama_gudang` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gudang_user_name` (`user_id`, `nama_gudang`), -- Memastikan 1 user tidak punya gudang dengan nama yang sama
  CONSTRAINT fk_user_gudang
    FOREIGN KEY (`user_id`) 
    REFERENCES `usernames`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel produk (Kolom user_id diubah menjadi gudang_id)
CREATE TABLE `produk` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  -- Foreign Key baru, menghubungkan produk ke GUDANG, bukan langsung ke user
  `gudang_id` INT(11) NOT NULL, 
  `nama` VARCHAR(255) NOT NULL,
  `harga` DECIMAL(10,0) NOT NULL,
  `stok` INT(11) NOT NULL,
  `deskripsi` TEXT,
  `foto` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT fk_gudang_produk
    FOREIGN KEY (`gudang_id`) 
    REFERENCES `gudang`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;