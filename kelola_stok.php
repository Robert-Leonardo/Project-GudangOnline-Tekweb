<?php
// File: kelola_stok.php (FINAL MODIFIED - Edit Produk Lengkap)
include "config.php";
session_start();

// 1. Cek Login & Ambil ID User
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); 
}
$current_user_id = $_SESSION['user_id'];

// 2. Logika UPDATE STOK CEPAT (Tetap dipertahankan)
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

// 3. Logika HAPUS PRODUK (Tetap dipertahankan)
if (isset($_POST['hapus_produk'])) {
    $id = $_POST['id'];
    
    $path_stmt = $connect->prepare("SELECT foto FROM produk WHERE id = ? AND user_id = ?");
    $path_stmt->bind_param("ii", $id, $current_user_id);
    $path_stmt->execute();
    $path_stmt->bind_result($foto_path);
    $path_stmt->fetch();
    $path_stmt->close();
    
    $stmt = $connect->prepare("DELETE FROM produk WHERE id = ? AND user_id = ?"); 
    $stmt->bind_param("ii", $id, $current_user_id); 

    if ($stmt->execute()) {
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
        /* (CSS DARI UPDATE UI SEBELUMNYA HAMPIR SAMA) */
        body { font-family: sans-serif; padding: 30px 10px; background: #e9eef2; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); box-sizing: border-box;}
        .header-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee;}
        h2 { font-size: 28px; color: #0d6efd; margin-bottom: 20px;}
        .btn-back { background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 8px; display: inline-block; transition: background 0.2s;}
        .btn-back:hover { background: #5a6268; }
        .btn-add { background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 8px; display: inline-block; cursor: pointer; font-weight: bold; transition: background 0.2s; }
        .btn-add:hover { background: #218838; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 10px; overflow: hidden;}
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #dee2e6; vertical-align: middle;}
        th { background: #0d6efd; color: white; font-weight: 600; text-align: center;}
        td { text-align: center; }
        tr:nth-child(even) { background-color: #f8f9fa; }
        tr:last-child td { border-bottom: none; }
        .product-img-col img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;}
        .btn-update { background: #007bff; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 6px; transition: background 0.2s; font-size: 14px; }
        .btn-update:hover { background: #0056b3; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 6px; transition: background 0.2s; font-size: 14px; }
        .btn-delete:hover { background: #c82333; }
        
        /* TOMBOL BARU EDIT DETAIL */
        .btn-edit-detail { background: #ffc107; color: #333; border: none; padding: 6px 12px; cursor: pointer; border-radius: 6px; margin-left: 5px; font-size: 14px; }
        .btn-edit-detail:hover { background: #e0a800; }
        
        input[type="number"] { width: 60px; padding: 6px; text-align: center; border: 1px solid #ccc; border-radius: 4px;}
        
        /* CSS MODAL */
        input[type="text"], input[type="number"], textarea, input[type="file"] { width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 6px; transition: border-color 0.3s;}
        input[type="text"]:focus, input[type="number"]:focus, textarea:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);}
        label { display: block; font-weight: bold; margin-top: 10px; color: #333;}
        .form-group-flex { display: flex; gap: 20px; margin-bottom: 10px;}
        .form-group-flex > div { flex-grow: 1;}
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px); animation: fadeIn 0.3s;}
        .modal-content { background-color: #fefefe; margin: 40px auto; padding: 30px; border-radius: 12px; width: 90%; max-width: 550px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); position: relative; animation: slideDown 0.4s ease-out;}
        .modal h2 { margin-top: 0; margin-bottom: 25px; color: #0d6efd; font-size: 26px; border-bottom: 2px solid #eee; padding-bottom: 10px;}
        .close-btn { color: #555; float: right; font-size: 36px; font-weight: lighter; line-height: 1; transition: color 0.2s;}
        .close-btn:hover, .close-btn:focus { color: #dc3545; text-decoration: none; cursor: pointer;}
        .modal-button { background: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; padding: 12px; border-radius: 6px; margin-top: 20px; transition: background 0.2s; width: 100%;}
        .modal-button:hover { background: #218838; }
        .product-validation-msg { font-size: 12px; color: orange; margin-top: -10px; margin-bottom: 10px; display: none;}
        .current-image-preview { margin-top: 10px; text-align: center; }
        .current-image-preview img { max-width: 100px; height: auto; border: 1px solid #ccc; padding: 5px; border-radius: 5px; }

        @media (max-width: 600px) { 
            .modal-content { margin: 10px auto; padding: 15px; } 
            .form-group-flex { flex-direction: column; gap: 0; } 
            /* (Responsive table CSS sebelumnya) */
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
                <th>Gambar</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tampil = mysqli_query($connect, "SELECT * FROM produk WHERE user_id = '$current_user_id' ORDER BY nama ASC");
            if (mysqli_num_rows($tampil) > 0) {
                while ($data = mysqli_fetch_array($tampil)) {
                    $foto_url = $data['foto'] ? htmlspecialchars($data['foto']) : 'uploads/no-image.png';
            ?>
            <tr>
                <td data-label="Gambar" class="product-img-col">
                    <img src="<?php echo $foto_url; ?>" alt="<?php echo htmlspecialchars($data['nama']); ?>">
                </td>
                <td data-label="Nama Produk"><?php echo htmlspecialchars($data['nama']); ?></td>
                <td data-label="Harga">Rp <?php echo number_format($data['harga']); ?></td>
                <td data-label="Stok"><?php echo $data['stok']; ?></td>
                
                <td data-label="Aksi">
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                        <input type="number" name="stok" value="<?php echo $data['stok']; ?>" required min="0" style="width:55px;">
                        <button type="submit" name="update_stok" class="btn-update">Stok</button>
                    </form>
                    
                    <button type="button" class="btn-edit-detail" data-id="<?php echo $data['id']; ?>">Edit Detail</button>
                    
                    <form method="POST" onsubmit="return confirm('Yakin mau hapus produk <?php echo htmlspecialchars($data['nama']); ?> permanen?');" style="display:inline-block;">
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
        <span class="close-btn" data-modal-target="addProductModal">&times;</span>
        <h2 style="text-align:center;">Tambah Produk Baru</h2>

        <form id="addProductForm" action="add_product.php" method="POST" enctype="multipart/form-data">
            
            <label for="nama">Nama Produk <span style="color:red;">*</span></label>
            <input type="text" id="nama" name="nama" required>
            <div id="productValidation" class="product-validation-msg"></div>

            <div class="form-group-flex">
                <div>
                    <label for="stok">Stok Awal <span style="color:red;">*</span></label>
                    <input type="number" id="stok" name="stok" required min="0" value="0">
                </div>
                <div>
                    <label for="harga">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" min="0" value="0">
                </div>
            </div>

            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4"></textarea>
            
            <label style="margin-top: 10px; display: block;" for="foto">Foto Produk</label>
            <input type="file" id="foto" name="foto" accept="image/*">

            <button type="submit" class="modal-button">Simpan Produk</button>
        </form>
    </div>
</div>
<div id="editProductModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" data-modal-target="editProductModal">&times;</span>
        <h2 style="text-align:center;">Edit Detail Produk</h2>

        <form id="editProductForm" method="POST" enctype="multipart/form-data">
            
            <input type="hidden" id="edit_id" name="edit_id">
            <input type="hidden" id="edit_old_foto_path" name="edit_old_foto_path">

            <label for="edit_nama">Nama Produk <span style="color:red;">*</span></label>
            <input type="text" id="edit_nama" name="edit_nama" required>

            <div class="form-group-flex">
                <div>
                    <label for="edit_stok">Stok <span style="color:red;">*</span></label>
                    <input type="number" id="edit_stok" name="edit_stok" required min="0">
                </div>
                <div>
                    <label for="edit_harga">Harga (Rp)</label>
                    <input type="number" id="edit_harga" name="edit_harga" min="0">
                </div>
            </div>

            <label for="edit_deskripsi">Deskripsi</label>
            <textarea id="edit_deskripsi" name="edit_deskripsi" rows="4"></textarea>

            <div class="current-image-preview">
                <p>Gambar Saat Ini:</p>
                <img id="current_foto_display" src="" alt="Current Photo">
            </div>
            
            <label style="margin-top: 10px; display: block;" for="edit_foto">Ganti Foto Produk</label>
            <input type="file" id="edit_foto" name="edit_foto" accept="image/*">

            <button type="submit" class="modal-button">Simpan Perubahan</button>
        </form>
    </div>
</div>
<script>
$(document).ready(function(){
    var addModal = $('#addProductModal');
    var editModal = $('#editProductModal');
    
    // --- OPEN MODAL TAMBAH PRODUK ---
    $('#openModalBtn').on('click', function(e) {
        e.preventDefault();
        addModal.show();
    });

    // --- CLOSE MODALS ---
    $('.close-btn').on('click', function() {
        $(this).closest('.modal').hide();
    });
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            $(event.target).hide();
        }
    });

    // --- LOGIKA AJAX TAMBAH PRODUK (Sama seperti sebelumnya) ---
    // ... (Kode AJAX Tambah Produk Form di sini) ...
    var namaInput = $('#nama');
    var validationMsg = $('#productValidation');
    var isDuplicate = false; 

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
    
    $('#addProductForm').on('submit', function(e){
        e.preventDefault(); 
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
                    addModal.hide();
                    window.location.reload(); 
                }
            },
            error: function(){
                alert('Terjadi kesalahan saat koneksi ke server.');
            }
        });
    });
    
    // --- LOGIKA AJAX EDIT DETAIL PRODUK ---
    $('.btn-edit-detail').on('click', function() {
        const productId = $(this).data('id');
        
        // 1. Ambil Data Produk dari Server
        $.ajax({
            url: 'get_product_data.php',
            type: 'GET',
            data: { id: productId },
            dataType: 'json',
            success: function(data) {
                if (data.status === 'success') {
                    // 2. Isi Form Edit dengan Data yang Diterima
                    $('#editProductForm #edit_id').val(data.id);
                    $('#editProductForm #edit_nama').val(data.nama);
                    $('#editProductForm #edit_stok').val(data.stok);
                    $('#editProductForm #edit_harga').val(data.harga);
                    $('#editProductForm #edit_deskripsi').val(data.deskripsi);
                    
                    // Simpan path foto lama untuk proses penghapusan
                    $('#editProductForm #edit_old_foto_path').val(data.foto); 
                    
                    // Tampilkan gambar saat ini
                    const imgUrl = data.foto ? data.foto : 'uploads/no-image.png';
                    $('#current_foto_display').attr('src', imgUrl);
                    
                    // 3. Tampilkan Modal Edit
                    editModal.show();
                } else {
                    alert('Gagal mengambil data produk: ' + data.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan koneksi saat mencoba mengambil data produk.');
            }
        });
    });
    
// 4. Submit Form Edit (Update Data)
    $('#editProductForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this); 
        var formActionUrl = 'edit_product.php'; 

        $.ajax({
            url: formActionUrl,
            type: 'POST',
            data: formData,
            contentType: false, 
            processData: false, 
            dataType: 'json', // <--- PERBAIKAN KRUSIAL: Memaksa jQuery untuk mengharapkan JSON
            success: function(result) {
                
                // PERBAIKAN: Tidak perlu lagi try/catch JSON.parse() karena menggunakan dataType: 'json'
                alert(result.message);

                if(result.status === 'success') {
                    editModal.hide();
                    window.location.reload(); 
                }
            },
            error: function(xhr, status, error) {
                // Tampilkan pesan error yang lebih detail jika parsing gagal
                console.error("AJAX Error:", status, error);
                console.error("Response Text:", xhr.responseText);
                alert('Terjadi kesalahan saat koneksi atau mengurai respons server. Cek konsol browser untuk detail.');
            }
        });
    });
});
</script>

</body>
</html>