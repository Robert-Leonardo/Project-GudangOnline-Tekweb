<?php
// File: edit_product.php (UPDATED - Multi-Gudang Logic)
error_reporting(0); 
ini_set('display_errors', 0);

session_start();
include "config.php";

header('Content-Type: application/json');

// Cek apakah user login DAN memiliki gudang aktif yang diset
if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION['active_gudang_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah atau Gudang Aktif belum diset.']);
    exit();
}

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

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['edit_foto']['name'], PATHINFO_EXTENSION));
    $new_foto_name = uniqid('img_', true) . '.' . $file_extension;
    $new_foto_path = $folder . $new_foto_name;

    if (!move_uploaded_file($fotoTmp, $new_foto_path)) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal upload foto baru!']);
        exit();
    }
}

// 2. Siapkan Query UPDATE (PENTING: Memfilter berdasarkan gudang_id)
$query = "UPDATE produk SET nama = ?, harga = ?, stok = ?, deskripsi = ?, foto = ? WHERE id = ? AND gudang_id = ?";
$stmt = $connect->prepare($query);

if (!$stmt) {
    // Jika gagal menyiapkan query, hapus foto baru jika terlanjur diupload
    if ($file_uploaded && $new_foto_path && file_exists($new_foto_path)) {
        unlink($new_foto_path);
    }
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyiapkan query: ' . $connect->error]);
    exit();
}

// Parameter types: nama (s), harga (d), stok (i), deskripsi (s), foto (s), id (i), gudang_id (i)
$stmt->bind_param("sdissii", $nama, $harga, $stok, $deskripsi, $new_foto_path, $id, $active_gudang_id);

if ($stmt->execute()) {
    // 3. Hapus foto lama jika diganti
    if ($file_uploaded && $old_foto_path && $old_foto_path !== 'uploads/no-image.png' && file_exists($old_foto_path)) {
        unlink($old_foto_path);
    }
    
    $stmt->close();
    echo json_encode(['status' => 'success', 'message' => 'Produk berhasil diperbarui!']);
} else {
    // Jika gagal update, hapus foto baru yang terlanjur diupload
    if ($file_uploaded && $new_foto_path && file_exists($new_foto_path)) {
        unlink($new_foto_path);
    }
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'Gagal update database: ' . $connect->error]);
}
// Tidak ada tag penutup PHP