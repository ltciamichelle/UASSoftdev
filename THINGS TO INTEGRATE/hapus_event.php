<?php
header('Content-Type: application/json');
include 'koneksi.php';

$input = json_decode(file_get_contents('php://input'), true);
$event_id = $input['event_id'] ?? '';

if (empty($event_id)) {
    echo json_encode(["status" => "error", "message" => "ID tidak valid."]);
    exit;
}

// 1. Ambil info gambar untuk dihapus dari server
$stmt_get = mysqli_prepare($koneksi, "SELECT banner_img FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt_get, "i", $event_id);
mysqli_stmt_execute($stmt_get);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_get));

if ($data && !empty($data['banner_img'])) {
    if (file_exists("uploads/" . $data['banner_img'])) {
        unlink("uploads/" . $data['banner_img']);
    }
}

// 2. Hapus dari database
$stmt_del = mysqli_prepare($koneksi, "DELETE FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt_del, "i", $event_id);

if (mysqli_stmt_execute($stmt_del)) {
    echo json_encode(["status" => "success", "message" => "Event berhasil dihapus."]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal menghapus data."]);
}
?>