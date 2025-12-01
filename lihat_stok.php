<?php
session_start();

// 1. Cek apakah user login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. Mencegah Browser Cache (Supaya tombol Back gak bisa dipake pas logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include "config.php";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lihat Stok</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: center; }
        th { background: #0d6efd; color: white; }
        .btn-back { background: #555; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 15px;}
    </style>
</head>
<body>

<div class="container">
    <a href="home.php" class="btn-back">← Kembali ke Home</a>
    <h2>Daftar Stok Produk</h2>

    <table>
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = mysqli_query($connect, "SELECT * FROM produk");
            while ($data = mysqli_fetch_array($query)) {
            ?>
            <tr>
                <td><?php echo $data['nama']; ?></td>
                <td>Rp <?php echo number_format($data['harga']); ?></td>
                <td><?php echo $data['stok']; ?></td>
                <td><?php echo $data['deskripsi']; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>