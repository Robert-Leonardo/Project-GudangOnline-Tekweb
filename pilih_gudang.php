<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$current_user_id = $_SESSION['user_id'];
$message = "";

// --- LOGIKA TAMBAH GUDANG BARU ---
if (isset($_POST['tambah_gudang'])) {
    $nama_gudang = trim($_POST['nama_gudang']);

    if (empty($nama_gudang)) {
        $message = "<div class='alert error'>Nama Gudang tidak boleh kosong.</div>";
    } else {
        // Cek duplikat nama gudang untuk user ini
        $stmt_check = $connect->prepare("SELECT id FROM gudang WHERE user_id = ? AND nama_gudang = ?");
        $stmt_check->bind_param("is", $current_user_id, $nama_gudang);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "<div class='alert error'>Anda sudah memiliki gudang dengan nama '$nama_gudang'.</div>";
        } else {
            // [MODIFIKASI DI SINI] Insert Gudang Baru dengan tanggal_buat
            $stmt_insert = $connect->prepare("INSERT INTO gudang (user_id, nama_gudang, tanggal_buat) VALUES (?, ?, NOW())");
            $stmt_insert->bind_param("is", $current_user_id, $nama_gudang);
            
            if ($stmt_insert->execute()) {
                $new_gudang_id = $stmt_insert->insert_id;
                $_SESSION['active_gudang_id'] = $new_gudang_id; // Set sebagai gudang aktif
                $message = "<div class='alert success'>Gudang '$nama_gudang' berhasil dibuat dan diaktifkan!</div>";
            } else {
                $message = "<div class='alert error'>Gagal membuat gudang: " . $stmt_insert->error . "</div>";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

// --- LOGIKA PILIH GUDANG ---
if (isset($_POST['select_gudang'])) {
    $gudang_id = (int)$_POST['gudang_id'];
    
    // Verifikasi kepemilikan
    $stmt_verify = $connect->prepare("SELECT id FROM gudang WHERE id = ? AND user_id = ?");
    $stmt_verify->bind_param("ii", $gudang_id, $current_user_id);
    $stmt_verify->execute();
    $stmt_verify->store_result();

    if ($stmt_verify->num_rows > 0) {
        $_SESSION['active_gudang_id'] = $gudang_id;
        header("Location: home.php");
        exit();
    } else {
        $message = "<div class='alert error'>Akses ke gudang ini ditolak.</div>";
    }
    $stmt_verify->close();
}

// --- LOGIKA HAPUS GUDANG ---
if (isset($_POST['hapus_gudang'])) {
    $gudang_id_hapus = (int)$_POST['gudang_id_hapus'];
    
    // Hapus Produk yang terkait dengan gudang ini (CASCADE DELETE atau manual)
    $stmt_del_prod = $connect->prepare("DELETE FROM produk WHERE gudang_id = ?");
    $stmt_del_prod->bind_param("i", $gudang_id_hapus);
    $stmt_del_prod->execute();
    $stmt_del_prod->close();

    // Hapus Gudang
    $stmt_del_gudang = $connect->prepare("DELETE FROM gudang WHERE id = ? AND user_id = ?");
    $stmt_del_gudang->bind_param("ii", $gudang_id_hapus, $current_user_id);
    
    if ($stmt_del_gudang->execute() && $stmt_del_gudang->affected_rows > 0) {
        $message = "<div class='alert success'>Gudang berhasil dihapus.</div>";
        // Jika gudang aktif yang dihapus, unset session active_gudang_id
        if (isset($_SESSION['active_gudang_id']) && $_SESSION['active_gudang_id'] == $gudang_id_hapus) {
            unset($_SESSION['active_gudang_id']);
            // Redirect agar session bersih dan user kembali ke home
            header("Location: pilih_gudang.php");
            exit();
        }
    } else {
        $message = "<div class='alert error'>Gagal menghapus gudang atau gudang tidak ditemukan.</div>";
    }
    $stmt_del_gudang->close();
}

// --- AMBIL DAFTAR GUDANG USER ---
$gudang_list = [];
$active_gudang_id = $_SESSION['active_gudang_id'] ?? null;

// Ambil gudang list milik user saat ini
$stmt_list = $connect->prepare("SELECT id, nama_gudang, tanggal_buat FROM gudang WHERE user_id = ? ORDER BY id DESC");
$stmt_list->bind_param("i", $current_user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();

while ($row = $result_list->fetch_assoc()) {
    $gudang_list[] = $row;
}
$stmt_list->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pilih Gudang</title>
    <style>
        /* Gaya CSS disesuaikan agar mudah dibaca */
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; margin-bottom: 25px; }
        input[type="text"] { width: 70%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; margin-right: 5px; }
        button { padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: bold; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-danger { background: #dc3545; }
        .form-tambah { display: flex; margin-bottom: 20px; }
        .gudang-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; margin-bottom: 10px; border: 1px solid #eee; border-radius: 8px; background: #f9f9f9; }
        .gudang-item.active { border-left: 5px solid #007bff; background: #e9f5ff; }
        .gudang-name { font-weight: bold; font-size: 1.1em; }
        .gudang-actions button { margin-left: 5px; padding: 8px 12px; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 15px; font-weight: bold; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn-back { display: block; margin-bottom: 20px; text-decoration: none; color: #555; }
        /* Tambahan gaya untuk tanggal */
        .gudang-date { font-size: 0.8em; color: #6c757d; margin-top: 2px; }
    </style>
</head>
<body>

<div class="container">
    <a href="home.php" class="btn-back">← Kembali ke Beranda</a>
    <h2>Kelola dan Pilih Gudang</h2>

    <?php echo $message; ?>

    <h3>Tambah Gudang Baru</h3>
    <form method="POST" class="form-tambah">
        <input type="text" name="nama_gudang" placeholder="Nama Gudang Baru" required>
        <button type="submit" name="tambah_gudang" class="btn-success">Tambah</button>
    </form>

    <hr>

    <h3>Daftar Gudang Anda (<?php echo count($gudang_list); ?>)</h3>
    <?php if (empty($gudang_list)): ?>
        <div class="alert error">Anda belum memiliki gudang. Silakan buat satu!</div>
    <?php else: ?>
        <?php foreach ($gudang_list as $gudang): ?>
            <div class="gudang-item <?php echo ($gudang['id'] == $active_gudang_id) ? 'active' : ''; ?>">
                <div>
                    <div class="gudang-name">
                        <?php echo htmlspecialchars($gudang['nama_gudang']); ?>
                        <?php if ($gudang['id'] == $active_gudang_id): ?>
                            <span style="color: #007bff; font-size: 12px; font-weight: normal;">(AKTIF)</span>
                        <?php endif; ?>
                    </div>
                    <div class="gudang-date">
                        Dibuat: <?php echo $gudang['tanggal_buat'] ? date('d/m/Y H:i', strtotime($gudang['tanggal_buat'])) : '-'; ?>
                    </div>
                </div>
                <div class="gudang-actions">
                    <?php if ($gudang['id'] != $active_gudang_id): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="gudang_id" value="<?php echo $gudang['id']; ?>">
                            <button type="submit" name="select_gudang" class="btn btn-primary" style="background: #007bff; color: white;">Aktifkan</button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: inline;" onsubmit="return confirm('PERINGATAN! Semua produk dalam gudang ini akan dihapus. Lanjutkan menghapus Gudang <?php echo addslashes(htmlspecialchars($gudang['nama_gudang'])); ?>?');">
                        <input type="hidden" name="gudang_id_hapus" value="<?php echo $gudang['id']; ?>">
                        <button type="submit" name="hapus_gudang" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
    
</body>
</html>