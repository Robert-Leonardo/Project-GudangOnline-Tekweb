<?php
// File: get_product_data.php
session_start();
include "config.php";

header('Content-Type: application/json');

// Pastikan user login dan memiliki ID produk
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$product_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Ambil data produk spesifik yang dimiliki oleh user ini
$stmt = $connect->prepare("SELECT id, nama, harga, stok, deskripsi, foto FROM produk WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($product = $result->fetch_assoc()) {
    $product['status'] = 'success';
    // Mengganti path NULL atau kosong menjadi string kosong untuk form HTML
    $product['foto'] = $product['foto'] ?? ''; 
    echo json_encode($product);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan atau bukan milik Anda.']);
}

$stmt->close();
?>