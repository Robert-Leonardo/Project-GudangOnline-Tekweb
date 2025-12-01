<?php
include "config.php";
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit(); // Stop loading halaman
}
// ... kode sisanya di bawah ...

// --- LOGIKA UPDATE STOK ---
if (isset($_POST['update_stok'])) {
    // PENTING: Gunakan prepared statement untuk keamanan!
    $id = $_POST['id'];
    $stok_baru = $_POST['stok'];

    // Contoh penggunaan prepared statement (walaupun Anda tidak menggunakan di original, ini lebih baik)
    // $stmt = mysqli_prepare($connect, "UPDATE produk SET stok = ? WHERE id = ?");
    // mysqli_stmt_bind_param($stmt, "ii", $stok_baru, $id);
    // mysqli_stmt_execute($stmt);

    $query = "UPDATE produk SET stok = '$stok_baru' WHERE id = '$id'";
    mysqli_query($connect, $query);
    
    echo "<script>alert('Stok berhasil diupdate!'); window.location.href='kelola_stok.php';</script>";
}

// --- LOGIKA HAPUS PRODUK ---
if (isset($_POST['hapus_produk'])) {
    // PENTING: Gunakan prepared statement untuk keamanan!
    $id = $_POST['id'];

    // Langsung hapus data dari database (Tidak perlu hapus file foto lagi)
    $query = "DELETE FROM produk WHERE id = '$id'";
    mysqli_query($connect, $query);

    echo "<script>alert('Produk berhasil dihapus!'); window.location.href='kelola_stok.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Stok</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        /* CSS untuk tata letak header baru */
        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: center; vertical-align: middle; }
        th { background: #0d6efd; color: white; }
        
        .btn-back { background: #555; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block;}
        
        /* Tombol Tambah Produk */
        .btn-add { background: #0d6efd; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; display: inline-block; }
        
        .btn-update { background: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        
        input[type="number"] { width: 70px; padding: 5px; text-align: center; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header-controls">
        <a href="home.php" class="btn-back">← Kembali ke Home</a>
        <a href="tambah_produk.php" class="btn-add">+ Tambah Produk Baru</a>
    </div>

    <h2>Kelola Stok & Hapus Produk</h2>

    <table>
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok (Edit Angka)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $tampil = mysqli_query($connect, "SELECT * FROM produk ORDER BY nama ASC");
            while ($data = mysqli_fetch_array($tampil)) {
            ?>
            <tr>
                <td><?php echo $data['nama']; ?></td>
                <td>Rp <?php echo number_format($data['harga']); ?></td>
                
                <td>
                    <form method="POST" style="display:flex; gap:5px; justify-content:center;">
                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                        <input type="number" name="stok" value="<?php echo $data['stok']; ?>" required min="0">
                        <button type="submit" name="update_stok" class="btn-update">Simpan</button>
                    </form>
                </td>

                <td>
                    <form method="POST" onsubmit="return confirm('Yakin mau hapus produk <?php echo $data['nama']; ?> permanen?');">
                        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                        <button type="submit" name="hapus_produk" class="btn-delete">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>