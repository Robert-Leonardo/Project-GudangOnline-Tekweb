<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit(); 
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, textarea, button { width: 100%; padding: 10px; margin-top: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; }
        button { background: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; }
        button:hover { background: #218838; }
        .btn-back { text-decoration: none; color: #555; display: block; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="container">
        <a href="kelola_stok.php" class="btn-back">← Kembali</a>
        <h2 style="text-align:center;">Tambah Produk Baru</h2>

        <form action="add_product.php" method="POST" enctype="multipart/form-data">
            <label>Nama Produk</label>
            <input type="text" name="nama" required>

            <label>Harga (Rp)</label>
            <input type="number" name="harga" required min="0">

            <label>Stok Awal</label>
            <input type="number" name="stok" required min="0">

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="3"></textarea>
            
            <label style="margin-top: 10px; display: block;">Foto Produk</label>
            <input type="file" name="foto" accept="image/*" required>

            <button type="submit">Simpan Produk</button>
        </form>
    </div>

</body>
</html>