<?php
// File: login.php
// WAJIB: Start session di baris paling atas
session_start(); 
include 'config.php';

// --- LOGIKA LOGOUT ---
// Kalau ada link login.php?logout=true diklik, hapus session
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['username']);
    header("Location: login.php");
    exit();
}

// ---------------- LOGIN ----------------
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ambil password hash
    $stmt = $connect->prepare("SELECT password FROM usernames WHERE name = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        // Verifikasi password
        if (password_verify($password, $hashed_password)) {
            // --- BAGIAN PENTING: SET SESSION ---
            // Ini tiket masuknya. Kalau ini ga ada, kamu bakal ditendang terus dari home.php
            $_SESSION['username'] = $username; 
            
            // Redirect pakai PHP Header (lebih cepat & aman buat session)
            header("Location: home.php");
            exit;
        } else {
            $stmt->close();
            echo "<script>alert('Username atau password salah!');</script>";
        }
    } else {
        $stmt->close();
        echo "<script>alert('Username atau password salah!');</script>";
    }
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