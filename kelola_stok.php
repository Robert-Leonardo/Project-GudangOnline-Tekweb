<?php
// File: kelola_stok.php (FINAL MODIFIED - Multi-Gudang Logic & Tanggal Update Stok)
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

    // MODIFIKASI: Tambahkan 'tanggal_update' = NOW() agar update tanggal saat stok diubah
    $stmt = $connect->prepare("UPDATE produk SET stok = ?, tanggal_update = NOW() WHERE id = ? AND gudang_id = ?"); 
    $stmt->bind_param("iii", $stok_baru, $id, $active_gudang_id); 

    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('Stok berhasil diupdate dan tanggal pembaruan dicatat!'); window.location.href='kelola_stok.php';</script>";
    } else {
        $stmt->close();
        echo "<script>alert('Gagal mengupdate stok!'); window.location.href='kelola_stok.php';</script>";
    }
    exit();
}

// --- LOGIKA HAPUS PRODUK ---
if (isset($_POST['hapus_produk'])) {
    $id = $_POST['id'];
    
    // 1. Ambil path foto lama sebelum hapus
    $stmt_old = $connect->prepare("SELECT foto FROM produk WHERE id = ? AND gudang_id = ?");
    $stmt_old->bind_param("ii", $id, $active_gudang_id);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    $old_data = $result_old->fetch_assoc();
    $old_foto_path = $old_data['foto'] ?? null;
    $stmt_old->close();

    // 2. Hapus dari database (Pastikan hanya produk di gudang aktif yang terhapus)
    $stmt_del = $connect->prepare("DELETE FROM produk WHERE id = ? AND gudang_id = ?");
    $stmt_del->bind_param("ii", $id, $active_gudang_id);

    if ($stmt_del->execute()) {
        $stmt_del->close();
        
        // 3. Hapus file foto jika ada
        if ($old_foto_path && $old_foto_path !== 'uploads/no-image.png' && file_exists($old_foto_path)) {
            unlink($old_foto_path);
        }

        echo "<script>alert('Produk berhasil dihapus!'); window.location.href='kelola_stok.php';</script>";
    } else {
        $stmt_del->close();
        echo "<script>alert('Gagal menghapus produk!'); window.location.href='kelola_stok.php';</script>";
    }
    exit();
}

// --- AMBIL DATA PRODUK ---
$products = [];
// Ambil juga kolom tanggal_update untuk ditampilkan
$stmt_products = $connect->prepare("SELECT id, nama, harga, stok, deskripsi, foto, tanggal_update FROM produk WHERE gudang_id = ? ORDER BY id DESC");
$stmt_products->bind_param("i", $active_gudang_id);
$stmt_products->execute();
$result_products = $stmt_products->get_result();

while ($row = $result_products->fetch_assoc()) {
    $products[] = $row;
}

// Ambil Nama Gudang Aktif untuk Judul
$gudang_name_query = $connect->prepare("SELECT nama_gudang FROM gudang WHERE id = ?");
$gudang_name_query->bind_param("i", $active_gudang_id);
$gudang_name_query->execute();
$gudang_name_result = $gudang_name_query->get_result();
$active_gudang_name = $gudang_name_result->fetch_assoc()['nama_gudang'] ?? 'Gudang Tidak Ditemukan';
$gudang_name_query->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Stok | <?php echo htmlspecialchars($active_gudang_name); ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* CSS yang disesuaikan */
        body { font-family: sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.05); }
        h2 { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; color: white; text-decoration: none; font-weight: bold; margin-right: 5px; }
        .btn-success { background: #28a745; }
        .btn-info { background: #17a2b8; }
        .btn-danger { background: #dc3545; }
        .btn-secondary { background: #6c757d; }
        .btn-warning { background: #ffc107; color: #343a40; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; vertical-align: middle; }
        th { background: #343a40; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        input[type="number"] { width: 70px; padding: 5px; border: 1px solid #ccc; border-radius: 3px; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 10px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .form-group button { margin-top: 10px; width: auto; }
        .alert-empty { text-align: center; color: #6c757d; padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; background: #e9ecef; }
        .last-update { font-size: 0.8em; color: #6c757d; margin-top: 5px; display: block; }
    </style>
</head>
<body>

<div class="container">
    <a href="home.php" class="btn btn-secondary" style="margin-bottom: 20px; display: inline-block;">← Kembali ke Beranda</a>
    <a href="lihat_stok.php" class="btn btn-info" style="margin-bottom: 20px; display: inline-block;">Lihat Galeri Stok</a>
    
    <h2>Kelola Stok Gudang: <?php echo htmlspecialchars($active_gudang_name); ?></h2>
    
    <button class="btn btn-success" id="openAddModal">Tambah Produk Baru</button>

    <?php if (empty($products)): ?>
        <div class="alert-empty">Tidak ada produk di gudang ini.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Foto</th>
                    <th>Nama Produk</th>
                    <th>Harga (Rp)</th>
                    <th>Stok</th>
                    <th>Deskripsi</th>
                    <th>Update Terakhir</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): 
                    $foto_url = $product['foto'] ? htmlspecialchars($product['foto']) : 'uploads/no-image.png';
                ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><img src="<?php echo $foto_url; ?>" alt="<?php echo htmlspecialchars($product['nama']); ?>" class="product-img"></td>
                        <td><?php echo htmlspecialchars($product['nama']); ?></td>
                        <td><?php echo number_format($product['harga']); ?></td>
                        
                        <td>
                            <form method="POST" style="display: flex; align-items: center;">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="stok" value="<?php echo $product['stok']; ?>" required min="0">
                                <button type="submit" name="update_stok" class="btn btn-warning" style="margin-left: 5px; padding: 5px 8px; font-size: 0.8em;">Update</button>
                            </form>
                        </td>

                        <td><?php echo htmlspecialchars($product['deskripsi'] ?: '-'); ?></td>
                        
                        <td>
                            <?php 
                                echo $product['tanggal_update'] ? date('d/m/Y H:i', strtotime($product['tanggal_update'])) : '-'; 
                            ?>
                        </td>

                        <td>
                            <button class="btn btn-info openEditModal" data-id="<?php echo $product['id']; ?>">Edit Detail</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus produk <?php echo addslashes(htmlspecialchars($product['nama'])); ?>?');">
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="hapus_produk" class="btn btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeAddModal">&times;</span>
        <h3>Tambah Produk Baru</h3>
        <form id="addProductForm" action="add_product.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nama">Nama Produk</label>
                <input type="text" id="nama" name="nama" required>
            </div>
            <div class="form-group">
                <label for="harga">Harga (Rp)</label>
                <input type="number" id="harga" name="harga" required min="0">
            </div>
            <div class="form-group">
                <label for="stok">Stok Awal</label>
                <input type="number" id="stok" name="stok" required min="0">
            </div>
            <div class="form-group">
                <label for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi"></textarea>
            </div>
            <div class="form-group">
                <label for="foto">Foto Produk</label>
                <input type="file" id="foto" name="foto" accept="image/*">
            </div>
            <button type="submit" class="btn btn-success">Simpan Produk</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEditModal">&times;</span>
        <h3>Edit Detail Produk</h3>
        <form id="editForm" action="edit_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="hidden" name="edit_old_foto_path" id="edit_old_foto_path">
            
            <div class="form-group">
                <label for="edit_nama">Nama Produk</label>
                <input type="text" id="edit_nama" name="edit_nama" required>
            </div>
            <div class="form-group">
                <label for="edit_harga">Harga (Rp)</label>
                <input type="number" id="edit_harga" name="edit_harga" required min="0">
            </div>
            <div class="form-group">
                <label for="edit_stok">Stok</label>
                <input type="number" id="edit_stok" name="edit_stok" required min="0">
            </div>
            <div class="form-group">
                <label for="edit_deskripsi">Deskripsi</label>
                <textarea id="edit_deskripsi" name="edit_deskripsi"></textarea>
            </div>
            
            <div class="form-group">
                <label>Foto Saat Ini:</label>
                <img id="current_foto" src="" style="max-width: 100px; height: auto; display: block; margin-bottom: 10px;">
                <label for="edit_foto">Ganti Foto Baru</label>
                <input type="file" id="edit_foto" name="edit_foto" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Update Detail</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var addModal = $('#addModal')[0];
    var editModal = $('#editModal')[0];

    // Helper function to show/hide modals
    function openModal(modal) {
        modal.style.display = 'block';
    }
    function closeModal(modal) {
        modal.style.display = 'none';
    }

    // Logika buka/tutup Modal Tambah
    $('#openAddModal').on('click', function() {
        openModal(addModal);
    });

    $('#closeAddModal').on('click', function() {
        closeModal(addModal);
        $('#addProductForm')[0].reset(); 
    });

    // Logika buka/tutup Modal Edit
    $('#closeEditModal').on('click', function() {
        closeModal(editModal);
    });

    // Logika Ambil Data dan Buka Modal Edit
    $('.openEditModal').on('click', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: 'get_product_data.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#edit_id').val(response.id);
                    $('#edit_nama').val(response.nama);
                    $('#edit_harga').val(response.harga);
                    $('#edit_stok').val(response.stok);
                    $('#edit_deskripsi').val(response.deskripsi);
                    $('#edit_old_foto_path').val(response.foto);
                    $('#current_foto').attr('src', response.foto);
                    
                    // Reset input file
                    $('#edit_foto').val('');

                    openModal(editModal);
                } else {
                    alert('Gagal memuat data produk: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error (Get Data):", status, error);
                alert('Terjadi kesalahan koneksi saat memuat data produk.');
            }
        });
    });

    // Logika Submit Form Tambah Produk (AJAX)
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this); 

        $.ajax({
            url: 'add_product.php',
            type: 'POST',
            data: formData,
            contentType: false, 
            processData: false, 
            dataType: 'json', 
            success: function(result) {
                alert(result.message);
                if(result.status === 'success') {
                    closeModal(addModal);
                    window.location.reload(); 
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error (Submit Add):", status, error);
                console.error("Response Text:", xhr.responseText);
                alert('Terjadi kesalahan koneksi atau server internal error saat tambah produk. Cek konsol browser untuk detail.');
            }
        });
    });

    // Logika Submit Form Edit Produk (AJAX)
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

    // Tutup modal jika klik di luar area modal
    window.onclick = function(event) {
        if (event.target == addModal) {
            closeModal(addModal);
            $('#addProductForm')[0].reset();
        }
        if (event.target == editModal) {
            closeModal(editModal);
        }
    }
});
</script>
</body>
</html>