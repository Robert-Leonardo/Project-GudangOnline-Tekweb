<?php
session_start();

// 1. Cek apakah user login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. Mencegah Browser Cache (Supaya tombol Back gak bisa dipake pas logout)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $deskripsi = $_POST['deskripsi'];

    // Proses Upload Foto
    $fotoName = $_FILES['foto']['name'];
    $fotoTmp = $_FILES['foto']['tmp_name'];
    $folder = "uploads/"; // Pastikan folder ini ada

    // Kalau folder uploads belum ada, buat otomatis
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $path = $folder . basename($fotoName);
    
    // Pindahkan foto ke folder uploads
    if (move_uploaded_file($fotoTmp, $path)) {
        // Simpan ke database
        $query = "INSERT INTO produk (nama, harga, stok, deskripsi, foto) VALUES ('$nama', '$harga', '$stok', '$deskripsi', '$path')";
        
        if (mysqli_query($connect, $query)) {
            echo "<script>alert('Produk berhasil ditambahkan!'); window.location.href='home.php';</script>";
        } else {
            echo "Error: " . mysqli_error($connect);
        }
    } else {
        echo "<script>alert('Gagal upload foto!'); window.location.href='tambah.html';</script>";
    }
}
?>