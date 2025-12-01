<?php
// File: edit_product.php (HARD FIX - Error Reporting Off)

// --- BARIS PENTING: Matikan semua output error saat memproses AJAX ---
error_reporting(0); 
ini_set('display_errors', 0);
// ---------------------------------------------------------------------

session_start();
include "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_POST['edit_id'];
$nama = $_POST['edit_nama'];
$harga = $_POST['edit_harga'] ?? 0;
$stok = $_POST['edit_stok'] ?? 0;
$deskripsi = $_POST['edit_deskripsi'] ?? NULL;
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
    $file_extension = pathinfo($_FILES['edit_foto']['name'], PATHINFO_EXTENSION);
    $new_foto_name = uniqid('img_', true) . '.' . $file_extension;
    $new_foto_path = $folder . $new_foto_name;

    if (!move_uploaded_file($fotoTmp, $new_foto_path)) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal upload foto baru!']);
        exit();
    }
}

// 2. Siapkan Query UPDATE
$query = "UPDATE produk SET nama = ?, harga = ?, stok = ?, deskripsi = ?, foto = ? WHERE id = ? AND user_id = ?";
$stmt = $connect->prepare($query);

// Parameter: string, double, integer, string, string, integer, integer
$stmt->bind_param("sdisii", $nama, $harga, $stok, $deskripsi, $new_foto_path, $id, $user_id);

if ($stmt->execute()) {
    // 3. Hapus foto lama jika foto baru berhasil diupload DAN path foto lama valid/berbeda
    if ($file_uploaded && $old_foto_path && $old_foto_path !== 'uploads/no-image.png' && file_exists($old_foto_path)) {
        unlink($old_foto_path);
    }
    
    $stmt->close();
    echo json_encode(['status' => 'success', 'message' => 'Produk berhasil diperbarui!']);
} else {
    // Jika update gagal, hapus foto baru yang terlanjur diupload
    if ($file_uploaded && file_exists($new_foto_path)) {
        unlink($new_foto_path);
    }
    $stmt->close();
    // Tampilkan error database hanya jika update gagal
    echo json_encode(['status' => 'error', 'message' => 'Gagal update database: ' . $connect->error]);
}