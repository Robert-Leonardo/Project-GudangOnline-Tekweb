<?php
// File: lihat_stok.php (MODIFIED - Isolasi Gudang)
// --- SATPAM (SECURITY CHECK) ---
session_start();

// 1. Cek Tiket Login & Ambil ID User
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); 
}
// --- BARIS KRUSIAL: AMBIL USER ID DARI SESSION ---
$current_user_id = $_SESSION['user_id'];
// -------------------------------------------------

// 2. Hapus Cache 
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
        th, td { padding: 12px; border: 1px solid #ddd; text-align: center; vertical-align: middle; }
        th { background: #0d6efd; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn-back { background: #555; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 15px;}
        .btn-back:hover { background: #333; }
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
            // PERUBAHAN KRUSIAL: Hanya ambil data milik user ini
            $query = mysqli_query($connect, "SELECT * FROM produk WHERE user_id = '$current_user_id'");
            
            // Cek apakah ada data?
            if(mysqli_num_rows($query) > 0){
                while ($data = mysqli_fetch_array($query)) {
            ?>
            <tr>
                <td><?php echo $data['nama']; ?></td>
                <td>Rp <?php echo number_format($data['harga']); ?></td>
                
                <td style="font-weight: bold; <?php if($data['stok'] == 0) echo 'color:red;'; ?>">
                    <?php echo $data['stok']; ?>
                </td>
                
                <td><?php echo $data['deskripsi']; ?></td>
            </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='4'>Belum ada produk di gudang Anda.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>