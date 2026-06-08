<?php
// edit_event.php
include 'koneksi.php'; // Mengambil koneksi database

// Memastikan request menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metode request harus POST."]);
    exit;
}

// Menangkap data dari form-data
$event_id         = $_POST['event_id_primary'] ?? ''; 
$id_event         = $_POST['id_event'] ?? '';
$nama_event       = $_POST['nama_event'] ?? '';
$kategori         = $_POST['kategori'] ?? '';
$tanggal          = $_POST['tanggal'] ?? '';
$waktu            = $_POST['waktu'] ?? '';
$tanggal_selesai  = $_POST['tanggal_selesai'] ?? '';
$waktu_selesai    = $_POST['waktu_selesai'] ?? '';
$lokasi           = $_POST['lokasi'] ?? '';
$tipe_tiket       = $_POST['tipe_tiket'] ?? '';
$slot_kursi       = $_POST['slot_kursi'] ?? 0;
$deskripsi        = $_POST['deskripsi'] ?? '';

// Validasi field yang wajib diisi
if (empty($event_id) || empty($nama_event) || empty($kategori) || empty($tanggal) || empty($waktu) || empty($tanggal_selesai) || empty($waktu_selesai) || empty($lokasi) || empty($tipe_tiket)) {
    echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi untuk memperbarui data!"]);
    exit;
}

// 1. Ambil nama file gambar lama dari database
$query_lama = "SELECT banner_img FROM events WHERE id = ?";
$stmt_lama = mysqli_prepare($koneksi, $query_lama);
mysqli_stmt_bind_param($stmt_lama, "i", $event_id);
mysqli_stmt_execute($stmt_lama);
$result_lama = mysqli_stmt_get_result($stmt_lama);
$data_lama = mysqli_fetch_assoc($result_lama);
$nama_file_gambar = $data_lama['banner_img'] ?? '';
mysqli_stmt_close($stmt_lama);

// 2. Proses jika user mengunggah gambar/banner baru
if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] === 0) {
    $target_dir = "uploads/";
    
    // Hapus gambar lama dari folder uploads jika filenya ada
    if (!empty($nama_file_gambar) && file_exists($target_dir . $nama_file_gambar)) {
        unlink($target_dir . $nama_file_gambar);
    }

    // Generate nama unik untuk gambar baru
    $ekstensi_file = pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION);
    $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
    move_uploaded_file($_FILES["banner_img"]["tmp_name"], $target_dir . $nama_file_gambar);
}

// 3. Jalankan query update menggunakan Prepared Statements (Aman dari SQL Injection)
$query_update = "UPDATE events SET 
                    id_event = ?, nama_event = ?, kategori = ?, tanggal = ?, waktu = ?, 
                    tanggal_selesai = ?, waktu_selesai = ?, lokasi = ?, tipe_tiket = ?, 
                    slot_kursi = ?, deskripsi = ?, banner_img = ? 
                 WHERE id = ?";

$stmt_update = mysqli_prepare($koneksi, $query_update);
mysqli_stmt_bind_param($stmt_update, "ssssssssssssi", $id_event, $nama_event, $kategori, $tanggal, $waktu, $tanggal_selesai, $waktu_selesai, $lokasi, $tipe_tiket, $slot_kursi, $deskripsi, $nama_file_gambar, $event_id);

if (mysqli_stmt_execute($stmt_update)) {
    echo json_encode(["status" => "success", "message" => "Event berhasil diperbarui!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal memperbarui database: " . mysqli_error($koneksi)]);
}

mysqli_stmt_close($stmt_update);
mysqli_close($koneksi);
?>