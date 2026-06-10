<?php
header('Content-Type: application/json');
include 'koneksi.php'; // Sesuaikan lokasi file koneksi Anda

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method tidak diizinkan."]);
    exit;
}

// Menangkap input
$event_id         = $_POST['event_id_primary'] ?? '';
$id_event         = $_POST['id_event'] ?? '';
$nama_event       = $_POST['nama_event'] ?? '';
// ... (tambahkan variabel lain sesuai form Anda)

// 1. Ambil nama file lama
$stmt_check = mysqli_prepare($koneksi, "SELECT banner_img FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt_check, "i", $event_id);
mysqli_stmt_execute($stmt_check);
$result = mysqli_stmt_get_result($stmt_check);
$data_lama = mysqli_fetch_assoc($result);
$nama_file_baru = $data_lama['banner_img'] ?? '';

// 2. Jika ada upload gambar baru
if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] === 0) {
    // Hapus file lama jika ada
    if (!empty($nama_file_baru) && file_exists("uploads/" . $nama_file_baru)) {
        unlink("uploads/" . $nama_file_baru);
    }
    $ekstensi = pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION);
    $nama_file_baru = time() . '_' . uniqid() . '.' . $ekstensi;
    move_uploaded_file($_FILES["banner_img"]["tmp_name"], "uploads/" . $nama_file_baru);
}

// 3. Update database
$sql = "UPDATE events SET id_event=?, nama_event=?, kategori=?, tanggal=?, waktu=?, 
        tanggal_selesai=?, waktu_selesai=?, lokasi=?, tipe_tiket=?, slot_kursi=?, 
        deskripsi=?, banner_img=? WHERE id=?";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "sssssssssissi", $id_event, $nama_event, $kategori, $tanggal, $waktu, $tanggal_selesai, $waktu_selesai, $lokasi, $tipe_tiket, $slot_kursi, $deskripsi, $nama_file_baru, $event_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(["status" => "success", "message" => "Event berhasil diperbarui!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal: " . mysqli_error($koneksi)]);
}
?>