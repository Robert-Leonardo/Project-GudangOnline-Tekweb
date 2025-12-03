<?php
// File: kelola_stok.php (FINAL MODIFIED - Multi-Gudang Logic & UI Clean-up)
error_reporting(0); 
ini_set('display_errors', 0); 

include "config.php";
session_start();

// Cek Gudang Aktif
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

// --- LOGIKA UPDATE STOK CEPAT ---
if (isset($_POST['update_stok'])) {
    $id = $_POST['id'];
    $stok_baru = $_POST['stok'];

    $stmt = $connect->prepare("UPDATE produk SET stok = ? WHERE id = ? AND gudang_id = ?"); 
    $stmt->bind_param("iii", $stok_baru, $id, $active_gudang_id); 

    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('Stok berhasil diupdate!'); window.location.href='kelola_stok.php';</script>";
    } else {
        $stmt->close();
        echo "<script>alert('Gagal update stok: " . $connect->error . "'); window.location.href='kelola_stok.php';</script>";
    }
}

// --- LOGIKA HAPUS PRODUK ---
if (isset($_POST['hapus_produk'])) {
    $id = $_POST['id'];
    
    $path_stmt = $connect->prepare("SELECT foto FROM produk WHERE id = ? AND gudang_id = ?");
    $path_stmt->bind_param("ii", $id, $active_gudang_id);
    $path_stmt->execute();
    $result_path = $path_stmt->get_result();
    $old_foto_path = null;
    if ($data = $result_path->fetch_assoc()) {
        $old_foto_path = $data['foto'];
    }
    $path_stmt->close();

    $stmt = $connect->prepare("DELETE FROM produk WHERE id = ? AND gudang_id = ?"); 
    $stmt->bind_param("ii", $id, $active_gudang_id); 

    if ($stmt->execute()) {
        if ($old_foto_path && $old_foto_path !== 'uploads/no-image.png' && file_exists($old_foto_path)) {
            unlink($old_foto_path);
        }
        $stmt->close();
        echo "<script>alert('Produk berhasil dihapus!'); window.location.href='kelola_stok.php';</script>";
    } else {
        $stmt->close();
        echo "<script>alert('Gagal menghapus produk: " . $connect->error . "'); window.location.href='kelola_stok.php';</script>";
    }
}

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
    <title>Kelola Stok</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* CSS DISESUAIAN DENGAN TAMPILAN STANDAR (non-responsive mobile) */
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to left, white, rgb(120, 120, 236));
            margin: 0;
            padding: 30px 10px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        
        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .header-controls a, .header-controls button {
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .btn-add { background: #28a745; color: white; }
        .btn-add:hover { background: #218838; }

        .btn-back { background: #6c757d; color: white; }
        .btn-back:hover { background: #5a6268; }

        .active-gudang-info {
            background: #e9f5ff;
            border: 1px solid #b8daff;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
            color: #004085;
        }

        /* Tabel yang Ditingkatkan */
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
            background-color: #0d6efd;
            color: white;
            font-weight: 600;
            text-align: center;
        }

        td {
            text-align: center;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .stok-input {
            width: 70px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 3px;
        }

        .btn-update-stok, .btn-delete, .btn-edit {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin: 2px;
            font-size: 14px;
        }

        .btn-update-stok { background: #007bff; color: white; }
        .btn-update-stok:hover { background: #0056b3; }
        
        .btn-edit { background: #ffc107; color: #333; }
        .btn-edit:hover { background: #e0a800; }

        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        /* Modal CSS */
        .modal {
            display: none; 
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            position: relative;
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-content input, .modal-content textarea, .modal-content button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .modal-content button[type="submit"] {
            background: #28a745; 
            color: white; 
            border: none;
        }
        .product-validation-msg { font-size: 12px; color: orange; margin-top: -10px; margin-bottom: 10px; display: none;}

    </style>
</head>
<body>

<div class="container">
    <div class="active-gudang-info">
        Mengelola Gudang Aktif: <strong><?php echo $active_gudang_name; ?></strong>
    </div>

    <div class="header-controls">
        <a href="home.php" class="btn-back">← Kembali ke Beranda</a>
        <h2>Daftar Produk</h2>
        <button id="openAddModalBtn" class="btn-add">Tambah Produk Baru</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Deskripsi</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query Tampil data di tabel (Filter gudang_id)
            $tampil = $connect->prepare("SELECT * FROM produk WHERE gudang_id = ? ORDER BY nama ASC");
            $tampil->bind_param("i", $active_gudang_id);
            $tampil->execute();
            $result_tampil = $tampil->get_result();

            if ($result_tampil->num_rows > 0) {
                while ($data = $result_tampil->fetch_array()) {
                    $foto_url = $data['foto'] ? htmlspecialchars($data['foto']) : 'uploads/no-image.png';
            ?>
                <tr>
                    <td data-label="Foto"><img src="<?php echo $foto_url; ?>" alt="Foto Produk" class="product-img"></td>
                    <td data-label="Nama Produk"><?php echo htmlspecialchars($data['nama']); ?></td>
                    <td data-label="Harga">Rp <?php echo number_format($data['harga']); ?></td>
                    <td data-label="Stok">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin update stok produk ini?');">
                            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                            <input type="number" name="stok" class="stok-input" value="<?php echo $data['stok']; ?>" min="0" required>
                            <button type="submit" name="update_stok" class="btn-update-stok">Stok</button>
                        </form>
                    </td>
                    <td data-label="Deskripsi"><?php echo htmlspecialchars(substr($data['deskripsi'] ?? 'Tidak ada deskripsi', 0, 50) . (strlen($data['deskripsi'] ?? '') > 50 ? '...' : '')); ?></td>
                    <td data-label="Aksi">
                        <button class="btn-edit" data-id="<?php echo $data['id']; ?>">Edit Detail</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus produk <?php echo addslashes(htmlspecialchars($data['nama'])); ?>?');">
                            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                            <button type="submit" name="hapus_produk" class="btn-delete">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="6" style="text-align:center; padding: 30px;">Tidak ada produk dalam gudang aktif ini.</td></tr>';
            }
            $tampil->close();
            ?>
        </tbody>
    </table>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" data-modal="addModal">&times;</span>
            <h3 style="text-align: center;">Tambah Produk Baru</h3>

            <form id="addProductForm" enctype="multipart/form-data">
                <label>Nama Produk *</label>
                <input type="text" id="nama" name="nama" required>
                <div id="productValidation" class="product-validation-msg"></div>

                <label>Harga (Rp)</label>
                <input type="number" name="harga" required min="0" value="0">

                <label>Stok Awal *</label>
                <input type="number" name="stok" required min="0" value="0">

                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3"></textarea>
                
                <label>Foto Produk</label>
                <input type="file" name="foto" accept="image/*" required> 

                <button type="submit" class="modal-button" style="background: #28a745;">Simpan Produk</button>
            </form>
        </div>
    </div>


    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" data-modal="editModal">&times;</span>
            <h3 style="text-align: center;">Edit Detail Produk</h3>
            <form id="editForm" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="edit_id">
                <input type="hidden" id="edit_old_foto_path" name="edit_old_foto_path">

                <label>Nama Produk</label>
                <input type="text" id="edit_nama" name="edit_nama" required>

                <label>Harga (Rp)</label>
                <input type="number" id="edit_harga" name="edit_harga" required min="0">

                <label>Stok</label>
                <input type="number" id="edit_stok" name="edit_stok" required min="0">
                
                <label>Deskripsi</label>
                <textarea id="edit_deskripsi" name="edit_deskripsi" rows="3"></textarea>
                
                <label>Foto Saat Ini</label>
                <div style="text-align:center; margin-bottom: 10px;">
                    <img id="current_foto" src="" alt="Foto Produk Saat Ini" style="max-width: 100px; max-height: 100px; display: block; margin: 5px auto;">
                </div>
                
                <label>Ganti Foto Baru (Opsional)</label>
                <input type="file" name="edit_foto" id="edit_foto" accept="image/*">

                <button type="submit" id="submitEditBtn" style="background: #007bff;">Simpan Perubahan</button>
            </form>
        </div>
    </div>

</div>

<script>
$(document).ready(function() {
    var addModal = document.getElementById('addModal');
    var editModal = document.getElementById('editModal');

    // --- Modal Control Functions ---
    function openModal(modalElement) { modalElement.style.display = "block"; }
    function closeModal(modalElement) { modalElement.style.display = "none"; }
    
    // Open Add Modal
    $('#openAddModalBtn').on('click', function() {
        $('#addProductForm')[0].reset(); 
        $('#productValidation').hide(); 
        openModal(addModal);
    });

    // Close Modals using X button or outside click
    $('.close-btn').on('click', function() {
        var targetId = $(this).attr('data-modal');
        closeModal(document.getElementById(targetId));
    });

    window.onclick = function(event) {
      if (event.target == addModal) { closeModal(addModal); }
      if (event.target == editModal) { closeModal(editModal); }
    }

    // --- LOGIKA AJAX TAMBAH PRODUK (add_product.php) ---
    var namaInput = $('#nama');
    var validationMsg = $('#productValidation');
    var isDuplicate = false; 

    // Pengecekan Duplikat Real-Time
    namaInput.on('keyup', function() {
        const productName = $(this).val().trim();
        if (productName.length > 2) {
            $.ajax({
                url: 'check_product.php',
                type: 'GET',
                data: { nama: productName },
                dataType: 'json',
                success: function(response) {
                    isDuplicate = response.exists;
                    if (isDuplicate) {
                        validationMsg.text('Produk ini sudah ada di gudang aktif Anda!');
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

    // Submit Form Tambah Produk
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
                    result = (typeof response === 'string') ? JSON.parse(response) : response;
                } catch (e) {
                    alert('Error parsing server response: ' + response);
                    return;
                }
                
                alert(result.message);

                if(result.status === 'success') {
                    closeModal(addModal);
                    window.location.reload(); 
                }
            },
            error: function(xhr, status, error){
                alert('Terjadi kesalahan saat koneksi ke server Tambah Produk.');
                console.error("AJAX Error (Add):", status, error, xhr.responseText);
            }
        });
    });

    // --- LOGIKA AJAX EDIT DETAIL PRODUK (get_product_data.php & edit_product.php) ---

    // 1. Ambil Data Produk saat tombol Edit diklik
    $('.btn-edit').on('click', function() {
        var productId = $(this).data('id');
        
        $.ajax({
            url: 'get_product_data.php?id=' + productId,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                if(data.status === 'success') {
                    // Isi form modal dengan data yang diterima
                    $('#edit_id').val(data.id);
                    $('#edit_nama').val(data.nama);
                    $('#edit_harga').val(data.harga);
                    $('#edit_stok').val(data.stok);
                    $('#edit_deskripsi').val(data.deskripsi);
                    $('#edit_old_foto_path').val(data.foto); 
                    
                    var fotoPath = data.foto || 'uploads/no-image.png';
                    $('#current_foto').attr('src', fotoPath).show();
                    
                    $('#edit_foto').val(''); 

                    openModal(editModal);
                } else {
                    alert(data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error (Get Data):", status, error);
                alert('Gagal mengambil data produk. Cek konsol browser.');
            }
        });
    });

    // 2. Submit Form Edit 
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this); 
        var formActionUrl = 'edit_product.php'; 

        $.ajax({
            url: formActionUrl,
            type: 'POST',
            data: formData,
            contentType: false, 
            processData: false, 
            success: function(response) {
                var result;
                try {
                    result = (typeof response === 'string') ? JSON.parse(response) : response;
                } catch (e) {
                    console.error("AJAX Error: Failed to parse JSON response.", e);
                    console.log("Raw Response:", response);
                    alert('Error parsing server response. Cek konsol browser.');
                    return;
                }
                
                alert(result.message);

                if(result.status === 'success') {
                    closeModal(editModal);
                    window.location.reload(); 
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error (Submit Edit):", status, error);
                console.error("Response Text:", xhr.responseText);
                alert('Terjadi kesalahan koneksi atau server internal error saat update. Cek konsol browser untuk detail.');
            }
        });
    });
});
</script>
</body>
</html>