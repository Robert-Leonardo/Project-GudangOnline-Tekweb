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


// Ambil dan bersihkan input
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    echo "<script>alert('Isi semua field!'); window.location.href='login.php';</script>";
    exit;
}

// Cek apakah username sudah ada (Menggunakan tabel 'usernames' dan kolom 'name')
$stmt = $connect->prepare("SELECT id FROM usernames WHERE name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    echo "<script>alert('Username sudah digunakan!'); window.location.href='login.php';</script>";
    exit;
}
$stmt->close();

$hashed = password_hash($password, PASSWORD_DEFAULT);

// Masukkan user baru (Menggunakan tabel 'usernames' dan kolom 'name')
$ins = $connect->prepare("INSERT INTO usernames (name, password) VALUES (?, ?)");
$ins->bind_param("ss", $username, $hashed);

if ($ins->execute()) {
    $ins->close();
    // --- REDIRECT KE LOGIN.PHP ---
    echo "<script>alert('Akun berhasil dibuat! Silakan login.'); window.location.href='login.php';</script>";
    exit;
} else {
    $ins->close();
    echo "<script>alert('Gagal membuat akun!'); window.location.href='login.php';</script>";
    exit;
}
?>