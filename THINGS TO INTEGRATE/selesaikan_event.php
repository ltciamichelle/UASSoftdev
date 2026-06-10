<?php
// selesaikan_event.php - Mengubah status event menjadi selesai
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'koneksi.php';

session_start();
$userData = null;
if (isset($_COOKIE['user_eventra'])) {
    $userData = json_decode($_COOKIE['user_eventra'], true);
} elseif (isset($_SESSION['user_eventra'])) {
    $userData = $_SESSION['user_eventra'];
}

if (!$userData || $userData['role'] !== 'panitia') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$event_id = isset($input['event_id']) ? mysqli_real_escape_string($koneksi, $input['event_id']) : '';

if (empty($event_id)) {
    echo json_encode(["status" => "error", "message" => "Event ID tidak ditemukan"]);
    exit;
}

$update_query = "UPDATE events SET status_event = 'selesai', tanggal_selesai_event = NOW() WHERE id = '$event_id'";

if (mysqli_query($koneksi, $update_query)) {
    echo json_encode(["status" => "success", "message" => "Event berhasil diselesaikan"]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal update status: " . mysqli_error($koneksi)]);
}
?>