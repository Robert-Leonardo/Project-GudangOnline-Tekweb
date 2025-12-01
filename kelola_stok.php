<?php
// File: kelola_stok.php (FINAL MODIFIED)
include "config.php";
session_start();

// 1. Cek Login & Ambil ID User
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); 
}
$current_user_id = $_SESSION['user_id'];

// 2. Logika UPDATE STOK 
if (isset($_POST['update_stok'])) {
    $id = $_POST['id'];
    $stok_baru = $_POST['stok'];

    $stmt = $connect->prepare("UPDATE produk SET stok = ? WHERE id = ? AND user_id = ?"); 
    $stmt->bind_param("iii", $stok_baru, $id, $current_user_id); 

    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('Stok berhasil diupdate!'); window.location.href='kelola_stok.php';</script>";
    } else {
        $stmt->close();
        echo "<script>alert('Gagal update stok: " . $connect->error . "'); window.location.href='kelola_stok.php';</script>";
    }
}

// 3. Logika HAPUS PRODUK
if (isset($_POST['hapus_produk'])) {
    $id = $_POST['id'];
    
    // Ambil path foto untuk dihapus (Opsional, untuk cleanup)
    $path_stmt = $connect->prepare("SELECT foto FROM produk WHERE id = ? AND user_id = ?");
    $path_stmt->bind_param("ii", $id, $current_user_id);
    $path_stmt->execute();
    $path_stmt->bind_result($foto_path);
    $path_stmt->fetch();
    $path_stmt->close();
    
    // Hapus data dari DB
    $stmt = $connect->prepare("DELETE FROM produk WHERE id = ? AND user_id = ?"); 
    $stmt->bind_param("ii", $id, $current_user_id); 

    if ($stmt->execute()) {
        // Hapus file fisik jika ada
        if ($foto_path && file_exists($foto_path)) {
            unlink($foto_path);
        }
        $stmt->close();
        echo "<script>alert('Produk berhasil dihapus!'); window.location.href='kelola_stok.php';</script>";
    } else {
        $stmt->close();
        echo "<script>alert('Gagal menghapus produk: " . $connect->error . "'); window.location.href='kelola_stok.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Stok</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* UI FIX: Gaya body dan container yang lebih bersih */
        body { 
            font-family: sans-serif; 
            padding: 30px 10px; 
            background: #e9eef2; 
        }
        .container { 
            max-width: 1000px; 
            margin: auto; 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            box-sizing: border-box;
        }

        /* Header Control */
        .header-controls { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 25px; 
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        h2 {
            font-size: 28px;
            color: #0d6efd;
            margin-bottom: 20px;
        }

        /* Tombol */
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

        .btn-add { 
            background: #28a745; 
            color: white; 
            padding: 10px 15px; 
            text-decoration: none; 
            border-radius: 8px; 
            display: inline-block; 
            cursor: pointer; 
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-add:hover { background: #218838; }
        
        /* Tabel */
        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 25px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden; 
        }
        th, td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        th { 
            background: #0d6efd; 
            color: white; 
            font-weight: 600;
            text-align: center;
        }
        td {
            text-align: center;
        }
        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:last-child td { border-bottom: none; }
        
        /* Gambar Produk di Tabel */
        .product-img-col img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        /* Input & Aksi Tabel */
        .btn-update { background: #007bff; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 6px; transition: background 0.2s; }
        .btn-update:hover { background: #0056b3; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 6px; transition: background 0.2s; }
        .btn-delete:hover { background: #c82333; }
        
        input[type="number"] { width: 60px; padding: 6px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
        
        /* --- CSS MODAL --- */
        input[type="text"], input[type="number"], textarea, input[type="file"] { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 6px; transition: border-color 0.3s;}
        input[type="text"]:focus, input[type="number"]:focus, textarea:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25); }
        label { display: block; font-weight: bold; margin-top: 10px; color: #333; }
        .form-group-flex { display: flex; gap: 20px; margin-bottom: 10px; }
        .form-group-flex > div { flex-grow: 1; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px); animation: fadeIn 0.3s;}
        .modal-content { background-color: #fefefe; margin: 40px auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 550px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); position: relative; animation: slideDown 0.4s ease-out;}
        .modal h2 { margin-top: 0; margin-bottom: 25px; color: #0d6efd; font-size: 26px; border-bottom: 2px solid #eee; padding-bottom: 10px;}
        .close-btn { color: #555; float: right; font-size: 36px; font-weight: lighter; line-height: 1; transition: color 0.2s;}
        .close-btn:hover, .close-btn:focus { color: #dc3545; text-decoration: none; cursor: pointer;}
        #addProductForm button[type="submit"] { background: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; padding: 12px; border-radius: 6px; margin-top: 20px; transition: background 0.2s; width: 100%;}
        #addProductForm button[type="submit"]:hover { background: #218838; }
        .product-validation-msg {
            font-size: 12px;
            color: orange;
            margin-top: -10px;
            margin-bottom: 10px;
            display: none;
        }

        @media (max-width: 600px) { 
            .modal-content { margin: 10px auto; padding: 15px; } 
            .form-group-flex { flex-direction: column; gap: 0; } 
            table, thead, tbody, th, td, tr { display: block; } /* Membuat tabel responsif */
            tr { margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px;}
            td { text-align: right; border: none; position: relative; padding-left: 50%; }
            td:before { content: attr(data-label); position: absolute; left: 6px; width: 45%; padding-right: 10px; white-space: nowrap; font-weight: bold; text-align: left; }
            thead { display: none; }
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header-controls">
        <a href="home.php" class="btn-back">← Kembali ke Home</a>
        <a href="#" id="openModalBtn" class="btn-add">+ Tambah Produk Baru</a>
    </div>

    <h2>Kelola Stok & Hapus Produk</h2>

    <table>
        <thead>
            <tr>
                <th>Gambar</th> <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok (Edit Angka)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tampil = mysqli_query($connect, "SELECT * FROM produk WHERE user_id = '$current_user_id' ORDER BY nama ASC");
            if (mysqli_num_rows($tampil) > 0) {
                while ($data = mysqli_fetch_array($tampil)) {
                    $foto_url = $data['foto'] ? $data['foto'] : 'uploads/default.png'; // Fallback jika tidak ada foto
            ?>
            <tr>
                <td data-label="Gambar" class="product-img-col">
                    <img src="<?php echo htmlspecialchars($foto_url); ?>" alt="<?php echo htmlspecialchars($data['nama']); ?>">
                </td>
                <td data-label="Nama Produk"><?php echo htmlspecialchars($data['nama']); ?></td>
                <td data-label="Harga">Rp <?php echo number_format($data['harga']); ?></td>
                
                <td data-label="Stok (Edit Angka)">
                    <form method="POST" style="display:flex; gap:5px; justify-content:center;">
                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                        <input type="number" name="stok" value="<?php echo $data['stok']; ?>" required min="0">
                        <button type="submit" name="update_stok" class="btn-update">Simpan</button>
                    </form>
                </td>

                <td data-label="Aksi">
                    <form method="POST" onsubmit="return confirm('Yakin mau hapus produk <?php echo htmlspecialchars($data['nama']); ?> permanen?');">
                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                        <button type="submit" name="hapus_produk" class="btn-delete">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center; padding: 30px;'>Belum ada produk di gudang Anda.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>


<div id="addProductModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" title="Tutup Modal">&times;</span>
        
        <h2 style="text-align:center;">Tambah Produk Baru</h2>

        <form id="addProductForm" action="add_product.php" method="POST" enctype="multipart/form-data">
            
            <label for="nama">Nama Produk <span style="color:red;">*</span></label>
            <input type="text" id="nama" name="nama" required>
            <div id="productValidation" class="product-validation-msg"></div> <div class="form-group-flex">
                <div>
                    <label for="stok">Stok Awal <span style="color:red;">*</span></label>
                    <input type="number" id="stok" name="stok" required min="0" value="0">
                </div>
                <div>
                    <label for="harga">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" min="0">
                </div>
            </div>

            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4"></textarea>
            
            <label style="margin-top: 10px; display: block;" for="foto">Foto Produk</label>
            <input type="file" id="foto" name="foto" accept="image/*"> <button type="submit">Simpan Produk</button>
        </form>
    </div>
</div>
<script>
$(document).ready(function(){
    var modal = $('#addProductModal');
    var openBtn = $('#openModalBtn');
    var closeBtn = $('.close-btn');
    var namaInput = $('#nama');
    var validationMsg = $('#productValidation');
    
    // Status duplikat
    var isDuplicate = false; 

    openBtn.on('click', function(e) { e.preventDefault(); modal.show(); });
    closeBtn.on('click', function() { modal.hide(); });
    $(window).on('click', function(event) {
        if (event.target.id === 'addProductModal') { modal.hide(); }
    });
    
    // --- AJAX Pre-check untuk Nama Produk ---
    namaInput.on('keyup', function() {
        const productName = $(this).val().trim();
        if (productName.length > 2) {
            $.ajax({
                url: 'check_product.php',
                type: 'GET',
                data: { nama: productName },
                success: function(response) {
                    isDuplicate = response.exists;
                    if (isDuplicate) {
                        validationMsg.text('Produk ini sudah ada di gudang Anda!');
                        validationMsg.show();
                    } else {
                        validationMsg.hide();
                    }
                }
            });
        } else {
            validationMsg.hide();
            isDuplicate = false;
        }
    });

    // --- LOGIKA AJAX SUBMISSION ---
    $('#addProductForm').on('submit', function(e){
        e.preventDefault(); 
        
        // VALIDASI TERAKHIR SEBELUM SUBMIT
        if (isDuplicate) {
            alert('Gagal: Nama produk ini sudah ada. Harap ganti nama produk.');
            return;
        }

        var formData = new FormData(this); 

        $.ajax({
            url: 'add_product.php',
            type: 'POST',
            data: formData,
            contentType: false, 
            processData: false, 
            success: function(response){
                var result;
                try {
                    result = JSON.parse(response);
                } catch (e) {
                    alert('Error parsing server response: ' + response);
                    return;
                }
                
                alert(result.message);

                if(result.status === 'success') {
                    modal.hide();
                    window.location.reload(); 
                }
            },
            error: function(){
                alert('Terjadi kesalahan saat koneksi ke server.');
            }
        });
    });
});
</script>

</body>
</html>