CREATE TABLE `usernames` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE, -- Tambahkan UNIQUE constraint agar username tidak duplikat
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `produk` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL, -- Kolom baru untuk mengidentifikasi pemilik produk
  `nama` VARCHAR(255) NOT NULL,
  `harga` DECIMAL(10,0) NOT NULL,
  `stok` INT(11) NOT NULL,
  `deskripsi` TEXT,
  `foto` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT fk_user_produk
    FOREIGN KEY (`user_id`) 
    REFERENCES `usernames`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
