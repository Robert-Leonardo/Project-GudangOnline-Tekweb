<?php
// File: login_register.php (Original)
session_start();

// 1. Cek apakah user sudah login. Jika iya, redirect ke home.
if (isset($_SESSION['username'])) {
    header("Location: home.php");
    exit();
}

include "config.php";

// ---------------- LOGIKA REGISTER ----------------
if (isset($_POST['register'])) {
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    // Menangkap input confirm_password
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if ($username === '' || $password === '' || $confirm_password === '') {
        echo "<script>alert('Semua field wajib diisi!'); window.location.href='login.php';</script>";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Password dan Konfirmasi Password tidak sama!'); window.location.href='login.php';</script>";
        exit;
    }

    // Cek apakah username sudah ada (Menggunakan Prepared Statement)
    $stmt = $connect->prepare("SELECT id FROM usernames WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo "<script>alert('Username sudah digunakan! Silakan coba nama lain.'); window.location.href='login.php';</script>";
        exit;
    }
    $stmt->close();

    // Hash password sebelum disimpan
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Masukkan user baru (Menggunakan Prepared Statement)
    // Catatan: Kolom name di tabel usernames harus UNIQUE
    $ins = $connect->prepare("INSERT INTO usernames (name, password) VALUES (?, ?)");
    $ins->bind_param("ss", $username, $hashed);

    if ($ins->execute()) {
        echo "<script>alert('Registrasi berhasil! Silakan Login.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Registrasi gagal: " . $connect->error . "'); window.location.href='login.php';</script>";
    }
    $ins->close();
    
} else {
    // Jika diakses tanpa POST, redirect ke login
    header("Location: login.php");
    exit();
}
?>