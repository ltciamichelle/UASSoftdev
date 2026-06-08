<?php
// koneksi.php
$host = "localhost";
$user = "root";     
$pass = "";         
$db   = "eventra";  

$koneksi = mysqli_connect($host, $user, $pass, $db);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!$koneksi) {
    echo json_encode(["status" => "error", "message" => "Gagal terhubung ke database: " . mysqli_connect_error()]);
    exit;
}
?>