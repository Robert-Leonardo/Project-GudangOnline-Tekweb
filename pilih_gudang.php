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
            // Insert Gudang Baru
            $stmt_insert = $connect->prepare("INSERT INTO gudang (user_id, nama_gudang) VALUES (?, ?)");
            $stmt_insert->bind_param("is", $current_user_id, $nama_gudang);
            
            if ($stmt_insert->execute()) {
                $new_gudang_id = $stmt_insert->insert_id;
                // Langsung set gudang baru sebagai aktif
                $_SESSION['active_gudang_id'] = $new_gudang_id;
                $message = "<div class='alert success'>Gudang '$nama_gudang' berhasil ditambahkan dan diaktifkan!</div>";
            } else {
                $message = "<div class='alert error'>Gagal menambahkan gudang: " . $stmt_insert->error . "</div>";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}

// --- LOGIKA PILIH GUDANG (SET ACTIVE) ---
if (isset($_POST['select_gudang'])) {
    $gudang_id = $_POST['gudang_id'];
    
    // VALIDASI: Pastikan gudang_id adalah milik user
    $stmt = $connect->prepare("SELECT nama_gudang FROM gudang WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $gudang_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $data = $result->fetch_assoc();
        $_SESSION['active_gudang_id'] = $gudang_id;
        $message = "<div class='alert success'>Gudang '{$data['nama_gudang']}' berhasil diaktifkan. Anda akan diarahkan...</div>";
        echo "<script>
            setTimeout(function() {
                window.location.href = 'home.php';
            }, 1500);
        </script>";
    } else {
        $message = "<div class='alert error'>Akses ditolak: Gudang tidak valid!</div>";
    }
    $stmt->close();
}

// --- LOGIKA HAPUS GUDANG ---
if (isset($_POST['hapus_gudang'])) {
    $gudang_id = $_POST['gudang_id_hapus'];
    
    // VALIDASI & HAPUS
    $stmt = $connect->prepare("DELETE FROM gudang WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $gudang_id, $current_user_id);

    if ($stmt->execute()) {
        $message = "<div class='alert success'>Gudang berhasil dihapus (beserta semua produk di dalamnya).</div>";
        // Jika gudang aktif yang dihapus, hapus dari session
        if (isset($_SESSION['active_gudang_id']) && $_SESSION['active_gudang_id'] == $gudang_id) {
            unset($_SESSION['active_gudang_id']);
        }
    } else {
        $message = "<div class='alert error'>Gagal menghapus gudang: " . $stmt->error . "</div>";
    }
    $stmt->close();
}


// --- AMBIL DAFTAR GUDANG USER ---
$list_gudang = [];
$stmt_list = $connect->prepare("SELECT id, nama_gudang FROM gudang WHERE user_id = ? ORDER BY nama_gudang ASC");
$stmt_list->bind_param("i", $current_user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
while ($row = $result_list->fetch_assoc()) {
    $list_gudang[] = $row;
}
$stmt_list->close();

$active_gudang_id = $_SESSION['active_gudang_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Gudang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to left, white, rgb(120, 120, 236));
            margin: 0;
            padding: 30px 15px;
            display: flex;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #333; margin-bottom: 25px; }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-back { margin-bottom: 20px; color: #555; display: block; }
        .form-group { margin-bottom: 15px; }
        input[type="text"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 5px; }

        .gudang-list { margin-top: 20px; }
        .gudang-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .gudang-card.active {
            border: 2px solid #007bff;
            background: #e9f5ff;
        }
        .gudang-info { font-weight: bold; }
        .gudang-actions button { margin-left: 10px; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
        .alert.success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        @media (max-width: 600px) {
            .gudang-card { flex-direction: column; align-items: flex-start; }
            .gudang-actions { margin-top: 10px; }
            .gudang-actions button { margin-left: 0; margin-right: 10px; margin-bottom: 5px; width: 100%; display: block; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="home.php" class="btn-back">← Kembali ke Beranda</a>
    <h2>Kelola Gudang Anda</h2>

    <?php echo $message; ?>

    <div style="border: 1px solid #ccc; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Tambah Gudang Baru</h3>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="nama_gudang" placeholder="Nama Gudang Baru" required>
            </div>
            <button type="submit" name="tambah_gudang" class="btn btn-success" style="width: 100%;">Tambahkan & Aktifkan</button>
        </form>
    </div>

    <h3>Daftar Gudang (Pilih yang Aktif)</h3>
    <div class="gudang-list">
        <?php if (empty($list_gudang)): ?>
            <div class="alert error">Anda belum memiliki gudang. Silakan tambahkan satu di atas.</div>
        <?php else: ?>
            <?php foreach ($list_gudang as $gudang): ?>
                <div class="gudang-card <?php echo ($gudang['id'] == $active_gudang_id) ? 'active' : ''; ?>">
                    <div class="gudang-info">
                        <?php echo htmlspecialchars($gudang['nama_gudang']); ?>
                        <?php if ($gudang['id'] == $active_gudang_id): ?>
                            <span style="color: #007bff; font-size: 12px;">(AKTIF)</span>
                        <?php endif; ?>
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

</div>

</body>
</html>