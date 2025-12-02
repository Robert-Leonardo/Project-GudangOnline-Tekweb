<?php
session_start();
include "config.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit(); 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Produk Semua Toko</title>
    <style>
        /* CSS Umum dan Background Selaras */
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
        }

        h2 { 
            font-size: 28px;
            color: #6f42c1;
            margin: 0;
        }
        
        .btn-back { 
            background: #6c757d; 
            color: white; 
            padding: 10px 15px; 
            text-decoration: none; 
            border-radius: 8px; 
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-back:hover { background: #5a6268; }

        /* --- Tampilan Galeri Produk (Grid) --- */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px;
        }

        .product-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            text-align: left;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            overflow: hidden;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .product-image-box {
            height: 180px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f8f8;
        }

        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            height: 40px;
            overflow: hidden;
        }

        .product-seller {
            font-size: 13px;
            color: #0d6efd;
            font-weight: 500;
        }

        .product-description {
            font-size: 14px;
            color: #777;
            margin-bottom: 10px;
            height: 36px;
            overflow: hidden;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #dc3545; /* Harga dibuat merah untuk menarik perhatian */
            margin-top: 10px;
        }
        
        .alert-empty {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #6c757d;
            border: 1px dashed #ccc;
            border-radius: 10px;
            margin-top: 30px;
        }

        /* Responsif */
        @media (max-width: 992px) {
            .product-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .product-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 576px) {
            .product-grid { grid-template-columns: 1fr; }
            .container { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header-controls">
        <h2>Marketplace</h2>
        <a href="home.php" class="btn-back">← Kembali ke Dasbor</a>
    </div>

    <div class="product-grid">
        <?php
        // PERUBAHAN KRUSIAL: JOIN untuk mendapatkan nama user/toko
        // Filter: Hanya tampilkan produk yang stoknya > 0
        $query = "
            SELECT 
                p.*, 
                u.name as user_name 
            FROM 
                produk p
            JOIN 
                usernames u ON p.user_id = u.id
            WHERE 
                p.stok > 0
            ORDER BY 
                p.nama ASC
        ";
        $result = mysqli_query($connect, $query);
        
        if(mysqli_num_rows($result) > 0){
            while ($data = mysqli_fetch_array($result)) {
                $foto_url = $data['foto'] ? htmlspecialchars($data['foto']) : 'uploads/no-image.png'; 
        ?>
        <div class="product-card">
            <div class="product-image-box">
                <img src="<?php echo $foto_url; ?>" alt="<?php echo htmlspecialchars($data['nama']); ?>" class="product-image">
            </div>
            
            <p class="product-seller">
                Toko: <?php echo htmlspecialchars($data['user_name']); ?>
            </p>

            <p class="product-name">
                <?php echo htmlspecialchars($data['nama']); ?>
            </p>
            
            <p class="product-description">
                <?php echo htmlspecialchars($data['deskripsi'] ?: 'Tidak ada deskripsi.'); ?>
            </p>
            
            <p class="product-price">
                Rp <?php echo number_format($data['harga']); ?>
            </p>
        </div>
        <?php 
            }
        } else {
            echo '<div class="alert-empty" style="grid-column: 1 / -1;">Saat ini tidak ada produk yang dijual di marketplace.</div>';
        }
        ?>
    </div>
</div>

</body>
</html>