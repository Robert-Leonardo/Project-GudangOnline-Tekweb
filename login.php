<?php
// File: login.php
// WAJIB: Start session di baris paling atas
session_start(); 
include 'config.php';

// --- LOGIKA LOGOUT "KERAS" ---
// Menangkap link dari home.php?logout=true
if (isset($_GET['logout'])) {
    // 1. Kosongkan semua data session
    $_SESSION = [];
    session_unset();
    
    // 2. Hancurkan session di server
    session_destroy();

    // 3. Paksa hapus cookie session di browser (Biar bersih total)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // 4. Refresh halaman ke login murni
    header("Location: login.php");
    exit();
}

// ---------------- LOGIKA LOGIN ----------------
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ambil password hash dari DB
    $stmt = $connect->prepare("SELECT password FROM usernames WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        // Verifikasi password
        if (password_verify($password, $hashed_password)) {
            // Login Berhasil -> Buat Session Baru (Tiket Masuk)
            session_regenerate_id(true); // Ganti ID session biar aman
            $_SESSION['username'] = $username; 
            
            // Redirect ke home
            header("Location: home.php");
            exit();
        } else {
            echo "<script>alert('Username atau password salah!');</script>";
        }
    } else {
        echo "<script>alert('Username atau password salah!');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>

    <style>
        body{
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to right,white,rgb(120, 120, 236));
            color: #333;
        }

        .container{
            margin:0 15px;
        }

        .form-box{
            width: 100%;
            max-width: 450px;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow:0 0 15px rgba(0,0,0,0.3);
            box-sizing: border-box;
            display: none;
        }

        .form-box.active{
            display: block;
        }

        form{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        h3{
            font-size: 40px;
            text-align: center;
            margin-bottom: 20px;
        }

        input{
          width: 100%;
          padding: 12px;
          background: #eee;
          border-radius: 6px;
          border: none;
          outline: none;
          font-size: 15px;
          color: black;
          margin-bottom: 20px;
          box-sizing: border-box;
        }

        button{
            width: 100%;
            padding: 12px;
            background: rgb(120,120,236);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        button:hover{
            background: rgb(90,90,210);
        }
        
        p{
            font-size: 20px;
            margin-bottom: 10px;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="form-box active" id="login-form">
            <form action="login.php" method="post">
                <h3>Login</h3>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p>Create account? <a href="#" onclick="showForm('register-form')">Here</a></p>
            </form>
        </div>

        <div class="form-box" id="register-form">
            <form action="login_register.php" method="post">
                <h3>Register</h3>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="register">Register</button>
                <p>Already have account? <a href="#" onclick="showForm('login-form')">back</a></p>
            </form>
        </div>
    </div>
    
    <script>
    function showForm(formId) {
        const forms = document.querySelectorAll('.form-box');
        forms.forEach(form => {
        form.classList.remove('active');
        });

    document.getElementById(formId).classList.add('active');
    }
    </script>
</body>
</html>