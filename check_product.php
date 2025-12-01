<?php
// File: check_product.php
session_start();
include "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['exists' => false, 'error' => 'Not logged in.']);
    exit();
}

if (!isset($_GET['nama'])) {
    echo json_encode(['exists' => false, 'error' => 'Nama parameter missing.']);
    exit();
}

$nama = trim($_GET['nama']);
$user_id = $_SESSION['user_id'];

if (empty($nama)) {
    echo json_encode(['exists' => false]);
    exit();
}

// Menggunakan Prepared Statement untuk keamanan
$stmt = $connect->prepare("SELECT id FROM produk WHERE nama = ? AND user_id = ?");
$stmt->bind_param("si", $nama, $user_id);
$stmt->execute();
$stmt->store_result();

$exists = $stmt->num_rows > 0;

$stmt->close();
echo json_encode(['exists' => $exists]);
?>