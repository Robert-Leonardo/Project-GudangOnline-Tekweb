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
            display: flex; /* Biar posisi selalu di tengah layar */
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 600px; /* Lebarkan dikit biar enak dilihat */
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
    </style>
</head>
<body>

<div class="container">
    <h1>Gudang Online</h1>

    <a href="lihat_stok.php" class="btn btn-blue">Lihat Stok Produk</a>
    
    <a href="kelola_stok.php" class="btn btn-blue">Tambah / Hapus Stok Produk</a>
    
    <a href="tambah_produk.php" class="btn btn-blue">Tambah Produk Baru</a>
    
    <br>
    <a href="login.php" style="color: red; text-decoration: none; font-weight: bold;">Logout</a>
</div>

</body>
</html>