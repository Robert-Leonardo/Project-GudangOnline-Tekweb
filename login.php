<?php
// File: login.php (MODIFIED - Menyimpan user_id & UI Fix)
session_start(); 
include 'config.php';

// --- LOGIKA LOGOUT "KERAS" ---
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_unset();
    session_destroy();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    header("Location: login.php");
    exit();
}

// ---------------- LOGIKA LOGIN ----------------
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ambil ID dan password hash dari DB
    $stmt = $connect->prepare("SELECT id, password FROM usernames WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    // Bind hasil ke $id dan $hashed_password
    $stmt->bind_result($id, $hashed_password); 

    if ($stmt->num_rows == 1) {
        $stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            // Login Berhasil
            session_regenerate_id(true);
            
            // --- BARIS KRUSIAL: SIMPAN ID USER ---
            $_SESSION['user_id'] = $id; 
            // ------------------------------------
            $_SESSION['username'] = $username;

            header("Location: home.php");
            exit();
        } else {
            echo "<script>alert('Username atau Password salah!'); window.location.href='login.php';</script>";
        }
    } else {
        echo "<script>alert('Username atau Password salah!'); window.location.href='login.php';</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>

    <style>
        /* CSS Fix untuk memastikan form terlihat */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to right, #ffffff, #63a4ff);
            color: #333;
            margin: 0;
        }

        .wrapper {
            perspective: 1000px;
            width: 100%;
            max-width: 400px; 
            height: 480px; 
            position: relative;
        }

        .form-box {
            position: absolute;
            width: 100%;
            height: 100%;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            box-sizing: border-box;
            backface-visibility: hidden; 
            transition: transform 0.8s;
        }

        .form-box h3 {
            font-size: 32px;
            text-align: center;
            margin-bottom: 25px;
            color: #0d6efd; 
        }

        .form-box input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background: #f4f4f4;
            border-radius: 8px;
            border: 1px solid #ddd;
            outline: none;
            font-size: 16px;
            color: black;
            box-sizing: border-box;
        }

        .form-box button {
            width: 100%;
            padding: 12px;
            background: #0d6efd; 
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: bold;
            transition: background 0.3s;
        }

        .form-box button:hover {
            background: #0b5ed7;
        }
        
        .form-box p {
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
        }
        
        .form-box a {
            color: #0d6efd;
            text-decoration: none;
            font-weight: bold;
        }
        
        /* Flip Logic CSS */
        #login-form { transform: rotateY(0deg); }
        #register-form { transform: rotateY(180deg); }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="form-box" id="login-form">
            <form action="login.php" method="post">
                <h3>Login</h3>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p>Belum punya akun? <a href="#" onclick="showForm('register-form')">Daftar di sini</a></p>
            </form>
        </div>

        <div class="form-box" id="register-form">
            <form action="login_register.php" method="post">
                <h3>Register</h3>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required>
                <button type="submit" name="register">Register</button>
                <p>Sudah punya akun? <a href="#" onclick="showForm('login-form')">Kembali ke Login</a></p>
            </form>
        </div>
    </div>
    
    <script>
    function showForm(formId) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        
        if (formId === 'register-form') {
            loginForm.style.transform = 'rotateY(-180deg)';
            registerForm.style.transform = 'rotateY(0deg)';
        } else {
            registerForm.style.transform = 'rotateY(180deg)';
            loginForm.style.transform = 'rotateY(0deg)';
        }
    }
    </script>
</body>
</html>