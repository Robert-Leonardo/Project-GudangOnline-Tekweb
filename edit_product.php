<?php
// File: edit_product.php (MODIFIED - Multi-Gudang Logic)
error_reporting(0); 
ini_set('display_errors', 0);

session_start();
include "config.php";

header('Content-Type: application/json');

// Pastikan user login dan gudang aktif diset
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION['active_gudang_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah / Gudang tidak aktif.']);
    exit();
}

$user_id = $_SESSION['user_id']; // Digunakan hanya untuk verifikasi kepemilikan
$active_gudang_id = $_SESSION['active_gudang_id']; // ID Gudang Aktif
$id = $_POST['edit_id'];
$nama = $_POST['edit_nama'];
$harga = isset($_POST['edit_harga']) ? floatval($_POST['edit_harga']) : 0;
$stok = isset($_POST['edit_stok']) ? intval($_POST['edit_stok']) : 0;
$deskripsi = isset($_POST['edit_deskripsi']) ? trim($_POST['edit_deskripsi']) : NULL;
$old_foto_path = $_POST['edit_old_foto_path'] ?? NULL;

$new_foto_path = $old_foto_path;
$file_uploaded = false;

// 1. Cek apakah ada file baru yang diupload
if (isset($_FILES['edit_foto']) && $_FILES['edit_foto']['error'] === UPLOAD_ERR_OK) {
    $file_uploaded = true;
    $fotoTmp = $_FILES['edit_foto']['tmp_name'];
    $folder = "uploads/";

    // Pastikan folder ada
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
    
    // Generate nama file unik
    $file_extension = strtolower(pathinfo($_FILES['edit_foto']['name'], PATHINFO_EXTENSION));
    $new_foto_name = uniqid('img_', true) . '.' . $file_extension;
    $new_foto_path = $folder . $new_foto_name;

    if (!move_uploaded_file($fotoTmp, $new_foto_path)) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal upload foto baru!']);
        exit();
    }
}

// 2. Siapkan Query UPDATE (Penting: filter gudang_id)
$query = "UPDATE produk SET nama = ?, harga = ?, stok = ?, deskripsi = ?, foto = ? WHERE id = ? AND gudang_id = ?";
// Prepare statement and validate
$stmt = $connect->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query: ' . $connect->error]);
    // Jika gagal, dan ada file baru yang terlanjur diupload, hapus file tersebut
    if ($file_uploaded && $new_foto_path && file_exists($new_foto_path)) {
        unlink($new_foto_path);
    }
    exit();
}

// Parameter types: nama (s), harga (d), stok (i), deskripsi (s), foto (s), id (i), gudang_id (i)
$stmt->bind_param("sdisii", $nama, $harga, $stok, $deskripsi, $new_foto_path, $id, $active_gudang_id);

if ($stmt->execute()) {
    // 3. Hapus foto lama jika foto baru berhasil diupload DAN path foto lama valid/berbeda
    if ($file_uploaded && $old_foto_path && $old_foto_path !== 'uploads/no-image.png' && file_exists($old_foto_path)) {
        unlink($old_foto_path);
    }
    
    $stmt->close();
    echo json_encode(['status' => 'success', 'message' => 'Produk berhasil diperbarui!']);
} else {
    // Jika gagal update, dan ada file baru yang terlanjur diupload, hapus file tersebut
    if ($file_uploaded && $new_foto_path && file_exists($new_foto_path)) {
        unlink($new_foto_path);
    }
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui produk: ' . $connect->error]);
}
?>