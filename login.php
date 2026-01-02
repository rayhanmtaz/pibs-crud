<?php 
session_start();
include "koneksi.php";

// LOGOUT
if(isset($_GET['action']) && $_GET['action'] == 'logout'){
    session_destroy();
    header("location:login.php");
    exit;
}

// LOGIN
if(isset($_POST['login'])){
    $nip = $_POST['nip'];
    $pass = $_POST['password'];

    $q = mysqli_query($koneksi, "SELECT * FROM karyawan WHERE nip='$nip'");
    
    if(mysqli_num_rows($q) > 0){
        $data = mysqli_fetch_array($q);
        
        if($pass == $data['password']){
            $_SESSION['nip'] = $data['nip'];
            $_SESSION['nama'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];

            // REVISI FINAL: SEMUA ROLE KE INDEX.PHP
            echo "<script>
                alert('Login Berhasil! Selamat Datang, ".$data['nama_lengkap']."'); 
                window.location='index.php';
            </script>";
        } else {
            echo "<script>alert('Password Salah!');</script>";
        }
    } else {
        echo "<script>alert('NIP Tidak Ditemukan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Roti Nusantara</title>
    <style>
        body { margin:0; padding:0; display:flex; justify-content:center; align-items:center; min-height:100vh; background: #FFF3E0; font-family:'Segoe UI', sans-serif; }
        .login-box { background:white; padding:40px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.1); width:100%; max-width:350px; text-align:center; border-top: 5px solid #D84315; }
        .input-field { width:100%; padding:12px; margin:10px 0; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; outline:none; }
        .btn-login { width:100%; padding:12px; background:#D84315; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:1rem; transition:0.3s; }
        .btn-login:hover { background:#bf360c; }
        .back-link { display:block; margin-top:20px; text-decoration:none; color:#666; font-size:0.9rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2 style="color:#D84315; margin:0 0 10px;">Roti Nusantara</h2>
        <p style="color:#777; margin-bottom:25px;">Sistem Informasi & Payroll</p>
        <form method="POST">
            <input type="text" name="nip" class="input-field" placeholder="NIP Pegawai" required>
            <input type="password" name="password" class="input-field" placeholder="Kata Sandi" required>
            <button type="submit" name="login" class="btn-login">MASUK</button>
        </form>
        <a href="index.php" class="back-link">← Kembali ke Website</a>
    </div>
</body>
</html>