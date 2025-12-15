<?php
session_start();
include "config.php";

// 1. Cek Tiket Login & Ambil ID User dan Gudang Aktif
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['active_gudang_id'])) {
    if (isset($_SESSION['user_id'])) {
        header("Location: pilih_gudang.php");
    } else {
        header("Location: login.php");
    }
    exit(); 
}

$current_user_id = $_SESSION['user_id'];
$active_gudang_id = $_SESSION['active_gudang_id'];

// 2. Hapus Cache 
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ambil Nama Gudang Aktif untuk Judul
$gudang_name_query = $connect->prepare("SELECT nama_gudang FROM gudang WHERE id = ?");
$gudang_name_query->bind_param("i", $active_gudang_id);
$gudang_name_query->execute();
$gudang_name_result = $gudang_name_query->get_result();
$active_gudang_name = "Gudang Tidak Ditemukan";
if ($gudang_data = $gudang_name_result->fetch_assoc()) {
    $active_gudang_name = htmlspecialchars($gudang_data['nama_gudang']);
}
$gudang_name_query->close();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Stok <?php echo $active_gudang_name; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to left, white, rgb(120, 120, 236));
            margin: 0;
            padding: 30px 15px;
        }

        .container { 
            max-width: 1200px; 
            margin: auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }

        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap; 
        }

        h2 { 
            font-size: 28px; 
            color: #333; 
            margin: 0; 
        }
        
        .btn-back {
            background: #6c757d; 
            color: white; 
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        .btn-back:hover { background: #5a6268; }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        .product-image-box {
            height: 200px;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-card > * {
            padding: 15px;
            line-height: 1.4;
        }

        .product-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .product-description {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .product-price {
            font-size: 1.3em;
            color: #28a745;
            font-weight: bold;
            margin-top: 0;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        
        .stock-available {
            color: #0d6efd;
            font-weight: bold;
        }
        .stock-empty {
            color: #dc3545;
            font-weight: bold;
        }
        
        .alert-empty {
            text-align: center;
            grid-column: 1 / -1; 
            padding: 20px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            font-weight: bold;
        }

        @media (max-width: 600px) {
            .header-controls {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-back {
                width: 100%;
                box-sizing: border-box;
                text-align: center;
            }
            h2 {
                text-align: center;
                margin-top: 15px;
            }
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-controls">
        <a href="home.php" class="btn-back">← Kembali ke Beranda</a>
        <h2>Galeri Stok Gudang: <?php echo $active_gudang_name; ?></h2>
    </div>

    <div class="product-grid">
        <?php
        // Query hanya mengambil produk dari gudang aktif (Penting: filter gudang_id)
        $query = $connect->prepare("
            SELECT 
                id, nama, harga, stok, deskripsi, foto 
            FROM 
                produk 
            WHERE 
                gudang_id = ?
            ORDER BY 
                nama ASC
        ");
        $query->bind_param("i", $active_gudang_id);
        $query->execute();
        $result = $query->get_result();
        
        if($result->num_rows > 0){
            while ($data = $result->fetch_array()) {
                $foto_url = $data['foto'] ? htmlspecialchars($data['foto']) : 'uploads/no-image.png'; 
                $stok_status = $data['stok'] > 0 ? 'stock-available' : 'stock-empty';
                $stok_text = $data['stok'] > 0 ? number_format($data['stok']) . ' Tersedia' : 'HABIS';
        ?>
        <div class="product-card">
            <div>
                <div class="product-image-box">
                    <img src="<?php echo $foto_url; ?>" alt="<?php echo htmlspecialchars($data['nama']); ?>" class="product-image">
                </div>
                
                <p class="product-description" style="color: #0d6efd; font-weight: bold; margin-bottom: 2px;">
                    <span class="<?php echo $stok_status; ?>"><?php echo $stok_text; ?></span>
                </p>

                <p class="product-name">
                    <?php echo htmlspecialchars($data['nama']); ?>
                </p>
                
                <p class="product-description">
                    <?php echo htmlspecialchars($data['deskripsi'] ?: 'Deskripsi belum ditambahkan.'); ?>
                </p>
            </div>
            
            <p class="product-price">
                Rp <?php echo number_format($data['harga']); ?>
            </p>
        </div>
        <?php 
            }
        } else {
            echo '<div class="alert-empty">Tidak ada produk yang ditemukan di Gudang Aktif ini.</div>';
        }
        $query->close();
        ?>
    </div>
</div>

</body>
</html>