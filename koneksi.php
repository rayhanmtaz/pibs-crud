<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_roti_nusantara"; // Gunakan database utama proyekmu

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Gagal Terkoneksi: " . mysqli_connect_error());
}
?>