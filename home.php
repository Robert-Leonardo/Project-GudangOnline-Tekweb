<?php
// File: home.php (FINAL MODIFIED - Multi-Gudang, Responsive, Hapus Marketplace)
session_start();
include "config.php";

// 1. Cek Koneksi dan Session
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
$active_gudang_id = $_SESSION['active_gudang_id'] ?? null;
$active_gudang_name = "Belum Dipilih";

// 2. AMBIL NAMA GUDANG AKTIF (Jika ada)
if ($active_gudang_id) {
    $stmt_name = $connect->prepare("SELECT nama_gudang FROM gudang WHERE id = ? AND user_id = ?");
    $stmt_name->bind_param("ii", $active_gudang_id, $current_user_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    if ($data_name = $result_name->fetch_assoc()) {
        $active_gudang_name = htmlspecialchars($data_name['nama_gudang']);
    } else {
        // Jika gudang ID di sesi tidak valid/tidak ditemukan, hapus ID gudang
        unset($_SESSION['active_gudang_id']);
        $active_gudang_id = null;
        $active_gudang_name = "Tidak Valid, Silakan Pilih Ulang";
    }
    $stmt_name->close();
}


// 3. AMBIL DATA STATISTIK GABUNGAN (Menggunakan JOIN ke semua gudang milik user)
$total_produk = 0;
$total_stok = 0;
$stok_habis = 0;

// Query COUNT dan SUM via JOIN (Menghitung SEMUA produk dari SEMUA gudang user)
$query_stats = "
    SELECT 
        COUNT(p.id) AS total_produk, 
        SUM(p.stok) AS total_stok 
    FROM 
        produk p
    JOIN 
        gudang g ON p.gudang_id = g.id
    WHERE 
        g.user_id = ?
";
$stmt = $connect->prepare($query_stats);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($data = $result->fetch_assoc()) {
    $total_produk = $data['total_produk'] ?? 0;
    $total_stok = $data['total_stok'] ?? 0;
}
$stmt->close();

// Query Stok Habis via JOIN
$query_habis = "
    SELECT 
        COUNT(p.id) AS stok_habis 
    FROM 
        produk p
    JOIN 
        gudang g ON p.gudang_id = g.id
    WHERE 
        g.user_id = ? AND p.stok = 0
";
$stmt_habis = $connect->prepare($query_habis);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
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
            padding: 20px; 
            box-sizing: border-box;
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
            margin-bottom: 20px;
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
            display: grid; 
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            margin-top: 20px;
        }
        
        .btn {
            display: block;
            width: 100%; 
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

        .btn-primary { background: #007bff; } 
        .btn-primary:hover { background: #0056b3; }
        
        .btn-success { background: #28a745; } 
        .btn-success:hover { background: #1e7e34; }
        
        .btn-info { background: #17a2b8; } 
        .btn-info:hover { background: #138496; }

        .btn-logout {
            background: #dc3545;
            padding: 12px; 
            font-size: 16px;
            margin-top: 15px; 
        }

        .btn-logout:hover {
            background: #c82333;
        }

        .active-gudang {
            padding: 10px;
            background: #e9ecef;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 25px;
        }
        
        @media (max-width: 768px) {
            body {
                align-items: flex-start;
                padding-top: 20px;
            }
            
            .container {
                max-width: 100%;
                margin: 0 10px;
            }
            
            h1 {
                font-size: 28px; 
                margin-bottom: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stat-value {
                font-size: 30px; 
            }
            
            .menu {
                grid-template-columns: 1fr; 
                gap: 15px;
            }
            
            .btn {
                font-size: 16px; 
                padding: 14px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Halo, <?php echo htmlspecialchars($_SESSION['username']); ?>! 👋</h1>
    
    <div class="active-gudang">
        Gudang Aktif: <strong><?php echo $active_gudang_name; ?></strong> 
        (ID Gudang: <?php echo $active_gudang_id ?? '-'; ?>)
    </div>

    <p>Ringkasan stok total dari **Semua Gudang** Anda:</p>

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
        <a href="pilih_gudang.php" class="btn btn-primary">Pilih / Kelola Gudang</a> 
        
        <?php if ($active_gudang_id): ?>
            <a href="lihat_stok.php" class="btn btn-success">Lihat Galeri Stok</a> 
            <a href="kelola_stok.php" class="btn btn-info">Kelola Stok & Produk</a>
        <?php else: ?>
            <div class="alert" style="grid-column: span 2; background: #fff3cd; color: #856404; border: 1px solid #ffeeba;">
                Pilih Gudang Aktif untuk mengelola Stok.
            </div>
        <?php endif; ?>
    </div>
    
    <a href="login.php?logout=true" class="btn btn-logout" onclick="return confirm('Apakah Anda yakin ingin keluar?');">Logout</a>
</div>

</body>
</html>