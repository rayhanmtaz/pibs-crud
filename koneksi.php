<?php
// Pengaturan Koneksi Database
$host = "localhost"; // Ganti jika host Anda berbeda
$user = "root";      // Ganti dengan username MySQL Anda
$pass = "";          // Ganti dengan password MySQL Anda
$db = "db_profil";   // Ganti dengan nama database yang Anda buat

// Buat koneksi dengan MySQLi
$koneksi = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($koneksi->connect_error) {
    die("Koneksi ke database gagal: " . $koneksi->connect_error);
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_roti_nusantara";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Gagal Terkoneksi: " . mysqli_connect_error());
}
}
?>