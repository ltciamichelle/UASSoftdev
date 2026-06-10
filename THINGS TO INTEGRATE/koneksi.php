<?php
// koneksi.php
$host = "localhost";
$user = "root";     
$pass = "";         
$db   = "eventra";  

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die(json_encode(["status" => "error", "message" => "Gagal terhubung ke database: " . mysqli_connect_error()]));
}

// Set charset ke UTF-8
mysqli_set_charset($koneksi, "utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>