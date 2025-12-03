<?php
// File: home.php (MODIFIED - Hapus Tautan Marketplace)
session_start();
include "config.php";

if (!$connect || $connect->connect_error) {
    $_SESSION = [];
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); 
}

$current_user_id = $_SESSION['user_id'];

// 3. AMBIL DATA STATISTIK GUDANG (Prepared Statements)
$total_produk = 0;
$total_stok = 0;
$stok_habis = 0;

$stmt = $connect->prepare("SELECT COUNT(id) AS total_produk, SUM(stok) AS total_stok FROM produk WHERE user_id = ?");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
    $total_produk = $data['total_produk'] ?? 0;
    $total_stok = $data['total_stok'] ?? 0;
}
$stmt->close();

$stmt_habis = $connect->prepare("SELECT COUNT(id) AS stok_habis FROM produk WHERE user_id = ? AND stok = 0");
$stmt_habis->bind_param("i", $current_user_id);
$stmt_habis->execute();
$result_habis = $stmt_habis->get_result();
if ($data_habis = $result_habis->fetch_assoc()) {
    $stok_habis = $data_habis['stok_habis'] ?? 0;
}
$stmt_habis->close();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
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
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 900px; 
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
            text-align: center;
        }

        h1 {
            margin-bottom: 30px;
            color: #222;
            font-size: 32px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #f8f9fa; 
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        .stat-card.total-product .stat-value { color: #0d6efd; }
        .stat-card.total-stock .stat-value { color: #28a745; }
        .stat-card.stock-empty .stat-value { color: #dc3545; }

        .menu {
            /* DIUBAH: Layout menjadi 2 kolom grid */
            display: grid; 
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        /* CSS untuk menu yang diubah menjadi 2 kolom */
        .menu a:first-child {
            /* Menyesuaikan lebar elemen pertama agar mengisi 1 kolom */
            grid-column: auto;
        }

        .btn {
            display: block;
            width: 100%; /* Dibuat 100% agar mengisi grid */
            padding: 16px;
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
        
        /* Warna Ungu Marketplace dihapus */

        .btn-blue:hover {
            background: #0b5ed7;
        }
        
        .btn-logout {
            background: #dc3545;
        }

        .btn-logout:hover {
            background: #c82333;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .menu {
                grid-template-columns: 1fr; /* Tumpuk menu di mobile */
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Halo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    
    <p>Selamat datang di Dasbor Gudang Anda. Berikut adalah ringkasan stok saat ini:</p>

    <div class="stats-grid">
        <div class="stat-card total-product">
            <div class="stat-value"><?php echo number_format($total_produk); ?></div>
            <div class="stat-label">Total Jenis Produk</div>
        </div>
        <div class="stat-card total-stock">
            <div class="stat-value"><?php echo number_format($total_stok); ?></div>
            <div class="stat-label">Total Kuantitas Stok</div>
        </div>
        <div class="stat-card stock-empty">
            <div class="stat-value"><?php echo number_format($stok_habis); ?></div>
            <div class="stat-label">Produk Stok Habis</div>
        </div>
    </div>
    <p>Silakan pilih menu yang ingin Anda kelola:</p>

    <div class="menu">
        <a href="lihat_stok.php" class="btn btn-blue">Lihat Gudang Anda</a>
        <a href="kelola_stok.php" class="btn btn-blue">Kelola Stok & Tambah Produk</a>
    </div>
    
    <a href="login.php?logout=true" class="btn btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Logout</a>
</div>

</body>
</html>