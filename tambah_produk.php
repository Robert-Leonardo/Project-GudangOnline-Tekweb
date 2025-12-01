<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
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
        <a href="home.php" class="btn-back">← Batal</a>
        <h2 style="text-align:center;">Tambah Produk Baru</h2>
        <form action="add_product.php" method="POST">
            <label>Nama Produk</label>
            <input type="text" name="nama" required>
            <label>Harga (Rp)</label>
            <input type="number" name="harga" required>
            <label>Stok Awal</label>
            <input type="number" name="stok" required>
            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="3"></textarea>
            <button type="submit">Simpan Produk</button>
        </form>
    </div>
</body>
</html>