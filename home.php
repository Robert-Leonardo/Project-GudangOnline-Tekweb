<?php
// File: home.php
// --- START SYSTEM ---
session_start();

// 1. CEK KONEKSI SERVER & DATABASE
include "config.php";

// Cek apakah koneksi database gagal setelah server dinyalakan
// $connect adalah objek mysqli dari config.php
if (!$connect || $connect->connect_error) {
    // Jika koneksi gagal, paksa logout
    $_SESSION = [];
    session_unset();
    session_destroy();
    
    // Redirect ke login.php
    header("Location: login.php");
    exit();
}
// ------------------------------------------

// 2. CEK APAKAH BAWA TIKET? (Session)
if (!isset($_SESSION['username'])) {
    // Kalau gak punya tiket, TENDANG ke login
    header("Location: login.php");
    exit(); 
}

// 3. MATIKAN CACHE (Supaya gak bisa di-Back setelah logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ... sisa kode HTML di bawah ...
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Gudang Online Home</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to left,white,rgb(120, 120, 236));
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 {
            margin-bottom: 40px;
            color: #222;
            font-size: 32px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            margin: 15px 0;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            color: white;
            transition: 0.2s;
            box-sizing: border-box;
        }

        .btn-blue {
            background: #0d6efd;
        }

        .btn-blue:hover {
            background: #0b5ed7;
        }
        
        .btn-logout {
            background: #dc3545; /* Merah */
            margin-top: 30px;
        }

        .btn-logout:hover {
            background: #c82333;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>Silakan pilih menu yang ingin Anda kelola:</p>

    <div class="menu">
        <a href="lihat_stok.php" class="btn btn-blue">Lihat Semua Stok Produk (AJAX)</a>
        <a href="kelola_stok.php" class="btn btn-blue">Kelola Stok & Hapus Produk</a>
    </div>
    
    <a href="login.php?logout=true" class="btn btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Logout</a>
</div>

</body>
</html>