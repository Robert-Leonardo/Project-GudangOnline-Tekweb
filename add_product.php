<?php
// File: add_product.php (MODIFIED - Minimal Field & Validasi)
session_start();
include "config.php";

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login kembali.']);
    exit();
}
$user_id = $_SESSION['user_id'];

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = $_POST['nama'];
    $harga = isset($_POST['harga']) && is_numeric($_POST['harga']) ? $_POST['harga'] : 0; // TIDAK WAJIB, default 0
    $stok = isset($_POST['stok']) && is_numeric($_POST['stok']) ? $_POST['stok'] : 0; // WAJIB
    $deskripsi = isset($_POST['deskripsi']) ? $_POST['deskripsi'] : NULL; // TIDAK WAJIB
    
    header('Content-Type: application/json');
    
    if (empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama Produk wajib diisi!']);
        exit();
    }
    
    // --- VALIDASI DUPLIKAT DI SISI SERVER ---
    $check_stmt = $connect->prepare("SELECT id FROM produk WHERE nama = ? AND user_id = ?");
    $check_stmt->bind_param("si", $nama, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        echo json_encode(['status' => 'warning', 'message' => 'Produk dengan nama tersebut sudah ada di gudang Anda!']);
        exit();
    }
    $check_stmt->close();
    // --- END VALIDASI ---

    $path = NULL; // Default tanpa foto
    
    // Proses Upload Foto (Jika ada file di-upload)
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fotoName = $_FILES['foto']['name'];
        $fotoTmp = $_FILES['foto']['tmp_name'];
        $folder = "uploads/"; 

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $path = $folder . basename($fotoName);
        
        if (!move_uploaded_file($fotoTmp, $path)) {
             echo json_encode(['status' => 'error', 'message' => 'Gagal upload foto!']);
             exit();
        }
    }

    // Insert ke database
    $query = "INSERT INTO produk (user_id, nama, harga, stok, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($query); 
    // Types: i (user_id), s (nama), d (harga), i (stok), s (deskripsi), s (path/foto)
    $stmt->bind_param("isdiss", $user_id, $nama, $harga, $stok, $deskripsi, $path);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Produk berhasil ditambahkan!']);
    } else {
        $stmt->close();
        if ($path) unlink($path); // Hapus foto jika insert gagal
        echo json_encode(['status' => 'error', 'message' => 'Error database: ' . $connect->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak sah.']);
}
?>