<?php
// File: kelola_stok.php (MODIFIED - Isolasi Gudang)
include "config.php";
session_start();

// 1. Cek Login & Ambil ID User
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); 
}
// --- BARIS KRUSIAL: AMBIL USER ID DARI SESSION ---
$current_user_id = $_SESSION['user_id'];
// -------------------------------------------------

// 2. Logika UPDATE STOK 
if (isset($_POST['update_stok'])) {
    $id = $_POST['id'];
    $stok_baru = $_POST['stok'];

    // PERUBAHAN KRUSIAL: Tambahkan AND user_id = ?
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

    // PERUBAHAN KRUSIAL: Tambahkan AND user_id = ?
    $stmt = $connect->prepare("DELETE FROM produk WHERE id = ? AND user_id = ?"); 
    $stmt->bind_param("ii", $id, $current_user_id); 

    if ($stmt->execute()) {
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
        /* (CSS Tampilan UI Bagus Anda di sini) */
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: center; vertical-align: middle; }
        th { background: #0d6efd; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn-back { background: #555; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block;}
        .btn-add { background: #0d6efd; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; cursor: pointer; } 
        .btn-update { background: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        input[type="number"] { width: 70px; padding: 5px; text-align: center; }
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
        @media (max-width: 600px) { .modal-content { margin: 10px auto; padding: 15px; } .form-group-flex { flex-direction: column; gap: 0; } }
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
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok (Edit Angka)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // PERUBAHAN KRUSIAL: Hanya tampilkan produk milik user ini
            $tampil = mysqli_query($connect, "SELECT * FROM produk WHERE user_id = '$current_user_id' ORDER BY nama ASC");
            if (mysqli_num_rows($tampil) > 0) {
                while ($data = mysqli_fetch_array($tampil)) {
            ?>
            <tr>
                <td><?php echo $data['nama']; ?></td>
                <td>Rp <?php echo number_format($data['harga']); ?></td>
                
                <td>
                    <form method="POST" style="display:flex; gap:5px; justify-content:center;">
                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                        <input type="number" name="stok" value="<?php echo $data['stok']; ?>" required min="0">
                        <button type="submit" name="update_stok" class="btn-update">Simpan</button>
                    </form>
                </td>

                <td>
                    <form method="POST" onsubmit="return confirm('Yakin mau hapus produk <?php echo $data['nama']; ?> permanen?');">
                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                        <button type="submit" name="hapus_produk" class="btn-delete">Hapus</button>
                    </form>
                </td>
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


<div id="addProductModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" title="Tutup Modal">&times;</span>
        
        <h2 style="text-align:center;">Tambah Produk Baru</h2>

        <form id="addProductForm" action="add_product.php" method="POST" enctype="multipart/form-data">
            
            <label for="nama">Nama Produk</label>
            <input type="text" id="nama" name="nama" required>

            <div class="form-group-flex">
                <div>
                    <label for="harga">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" required min="0">
                </div>
                <div>
                    <label for="stok">Stok Awal</label>
                    <input type="number" id="stok" name="stok" required min="0">
                </div>
            </div>

            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4"></textarea>
            
            <label style="margin-top: 10px; display: block;" for="foto">Foto Produk</label>
            <input type="file" id="foto" name="foto" accept="image/*" required>

            <button type="submit">Simpan Produk</button>
        </form>
    </div>
</div>
<script>
$(document).ready(function(){
    var modal = $('#addProductModal');
    var openBtn = $('#openModalBtn');
    var closeBtn = $('.close-btn');

    openBtn.on('click', function(e) { e.preventDefault(); modal.show(); });
    closeBtn.on('click', function() { modal.hide(); });
    $(window).on('click', function(event) {
        if (event.target.id === 'addProductModal') { modal.hide(); }
    });

    // --- LOGIKA AJAX SUBMISSION ---
    $('#addProductForm').on('submit', function(e){
        e.preventDefault(); 

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
                    alert('Error parsing server response.');
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