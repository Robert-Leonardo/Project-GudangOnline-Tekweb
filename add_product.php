<?php
// File: add_product.php (MODIFIED - Multi-Gudang Logic & Tanggal Update)
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
    $nama = trim($_POST['nama']);
    $harga = isset($_POST['harga']) && is_numeric($_POST['harga']) ? (float)$_POST['harga'] : 0;
    $stok = isset($_POST['stok']) && is_numeric($_POST['stok']) ? (int)$_POST['stok'] : 0; 
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : NULL; 
    
    header('Content-Type: application/json');

    // 1. Validasi Input Dasar
    if (empty($nama) || $harga < 0 || $stok < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Nama, Harga, dan Stok wajib diisi/valid.']);
        exit();
    }
    
    // 2. Cek Duplikat Nama Produk di Gudang Aktif
    $stmt_check = $connect->prepare("SELECT id FROM produk WHERE nama = ? AND gudang_id = ?");
    $stmt_check->bind_param("si", $nama, $active_gudang_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        echo json_encode(['status' => 'error', 'message' => 'Produk dengan nama tersebut sudah ada di gudang ini!']);
        exit();
    }
    $stmt_check->close();

    // 3. Upload Foto
    $path = 'uploads/no-image.png'; // Default path jika tidak ada foto
    $foto = $_FILES['foto'] ?? null;

    if ($foto && $foto['error'] === UPLOAD_ERR_OK) {
        $fotoName = $foto['name'];
        $fotoTmp = $foto['tmp_name'];
        $folder = "uploads/";

        // Buat folder jika belum ada
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

    // [MODIFIKASI DI SINI] Insert ke database dengan tanggal_update = NOW()
    $query = "INSERT INTO produk (gudang_id, nama, harga, stok, deskripsi, foto, tanggal_update) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $connect->prepare($query); 
    // Types: i (gudang_id), s (nama), d (harga), i (stok), s (deskripsi), s (path/foto)
    $stmt->bind_param("isdiss", $active_gudang_id, $nama, $harga, $stok, $deskripsi, $path);
    
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Produk berhasil ditambahkan!']);
    } else {
        $stmt->close();
        // Jika gagal insert, dan ada file yang terlanjur diupload, hapus file tersebut
        if ($path && $path !== 'uploads/no-image.png' && file_exists($path)) {
            unlink($path);
        }
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data produk ke database: ' . $connect->error]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
}
?>