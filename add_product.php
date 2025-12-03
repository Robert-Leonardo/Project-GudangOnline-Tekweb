<?php
// File: add_product.php (MODIFIED - Multi-Gudang Logic)
session_start();
include "config.php";

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['active_gudang_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir atau Gudang Aktif belum diset. Silakan pilih gudang.']);
    exit();
}
$user_id = $_SESSION['user_id'];
$active_gudang_id = $_SESSION['active_gudang_id']; // ID Gudang Aktif

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = $_POST['nama'];
    $harga = isset($_POST['harga']) && is_numeric($_POST['harga']) ? (float)$_POST['harga'] : 0;
    $stok = isset($_POST['stok']) && is_numeric($_POST['stok']) ? (int)$_POST['stok'] : 0; 
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : NULL; 
    
    header('Content-Type: application/json');
    
    if (empty($nama)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama Produk wajib diisi!']);
        exit();
    }
    
    // --- VALIDASI DUPLIKAT NAMA PRODUK (DI DALAM GUDANG AKTIF INI) ---
    $check_stmt = $connect->prepare("SELECT id FROM produk WHERE nama = ? AND gudang_id = ?");
    $check_stmt->bind_param("si", $nama, $active_gudang_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Produk dengan nama ini sudah ada di gudang aktif Anda!']);
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

        // Generate nama file unik
        $file_extension = strtolower(pathinfo($fotoName, PATHINFO_EXTENSION));
        $unique_name = uniqid('img_', true) . '.' . $file_extension;
        $path = $folder . $unique_name;
        
        if (!move_uploaded_file($fotoTmp, $path)) {
             echo json_encode(['status' => 'error', 'message' => 'Gagal upload foto!']);
             exit();
        }
    }

    // Insert ke database (Penting: menggunakan gudang_id)
    $query = "INSERT INTO produk (gudang_id, nama, harga, stok, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $connect->prepare($query); 
    // Types: i (gudang_id), s (nama), d (harga), i (stok), s (deskripsi), s (path/foto)
    $stmt->bind_param("isdiss", $active_gudang_id, $nama, $harga, $stok, $deskripsi, $path);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Produk berhasil ditambahkan!']);
    } else {
        $stmt->close();
        // Jika gagal insert, dan ada file yang terlanjur diupload, hapus file tersebut
        if ($path && file_exists($path)) {
            unlink($path);
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan produk: ' . $connect->error]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Metode permintaan tidak valid.']);
}