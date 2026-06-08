<?php
// hapus_event.php
include 'koneksi.php'; // Mengambil koneksi database

// Menerima input data berupa raw JSON dari body request
$input_data = json_decode(file_get_contents('php://input'), true);
$event_id = $input_data['event_id'] ?? '';

if (empty($event_id)) {
    echo json_encode(["status" => "error", "message" => "ID Event tidak terdeteksi."]);
    exit;
}

// 1. Cari nama file gambar terlebih dahulu untuk dihapus fisik dari folder
$query_gambar = "SELECT banner_img FROM events WHERE id = ?";
$stmt_gbr = mysqli_prepare($koneksi, $query_gambar);
mysqli_stmt_bind_param($stmt_gbr, "i", $event_id);
mysqli_stmt_execute($stmt_gbr);
$result_gbr = mysqli_stmt_get_result($stmt_gbr);

if (mysqli_num_rows($result_gbr) > 0) {
    $data = mysqli_fetch_assoc($result_gbr);
    // Jika kolom gambar tidak kosong dan filenya ada di folder 'uploads', hapus file tersebut
    if (!empty($data['banner_img']) && file_exists("uploads/" . $data['banner_img'])) {
        unlink("uploads/" . $data['banner_img']); 
    }
}
mysqli_stmt_close($stmt_gbr);

// 2. Hapus data event dari database
$query_delete = "DELETE FROM events WHERE id = ?";
$stmt_del = mysqli_prepare($koneksi, $query_delete);
mysqli_stmt_bind_param($stmt_del, "i", $event_id);

if (mysqli_stmt_execute($stmt_del)) {
    echo json_encode(["status" => "success", "message" => "Event berhasil dihapus dari sistem!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal menghapus event: " . mysqli_error($koneksi)]);
}

mysqli_stmt_close($stmt_del);
mysqli_close($koneksi);
?>