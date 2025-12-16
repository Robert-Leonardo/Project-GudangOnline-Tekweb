<?php
session_start();
include "config.php";

error_reporting(0); 
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Cek apakah user login, memiliki ID produk, DAN gudang aktif diset
if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !isset($_SESSION['active_gudang_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak atau Gudang tidak aktif.']);
    exit();
}

$product_id = $_GET['id'];
$active_gudang_id = $_SESSION['active_gudang_id']; // Ambil gudang ID aktif

// Query: Ambil data produk spesifik yang dimiliki oleh gudang ini
$stmt = $connect->prepare("SELECT id, nama, harga, stok, deskripsi, foto FROM produk WHERE id = ? AND gudang_id = ?");
$stmt->bind_param("ii", $product_id, $active_gudang_id);
$stmt->execute();
$result = $stmt->get_result();

if ($product = $result->fetch_assoc()) {
    $product['status'] = 'success';
    $product['foto'] = $product['foto'] ?: 'uploads/no-image.png'; 
    echo json_encode($product);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan atau bukan milik gudang aktif Anda.']);
}

$stmt->close();
?>