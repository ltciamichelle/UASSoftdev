<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'koneksi.php';

$input_mentah = file_get_contents('php://input');
$data = json_decode($input_mentah, true);

if (empty($data) && !empty($_POST)) {
    $data = $_POST;
} else if (empty($data)) {
    $data = [];
}

$aksi = isset($data['aksi']) ? $data['aksi'] : (isset($_GET['aksi']) ? $_GET['aksi'] : '');

// VERIFIKASI QR CODE / CHECK-IN
if ($aksi === 'verifikasi_qr') {
    $registrasi_id = mysqli_real_escape_string($koneksi, $data['registrasi_id'] ?? '');
    $event_id = mysqli_real_escape_string($koneksi, $data['event_id'] ?? '');
    $panitia_id = mysqli_real_escape_string($koneksi, $data['panitia_id'] ?? ''); // user id panitia

    if (empty($registrasi_id) || empty($event_id) || empty($panitia_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Data QR tidak lengkap.']);
        exit;
    }

    // 1. Cek apakah panitia ini benar pemilik event
    $cek_event = "SELECT id FROM events WHERE id = '$event_id' AND user_id = '$panitia_id'";
    $res_event = mysqli_query($koneksi, $cek_event);
    if (mysqli_num_rows($res_event) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki akses ke event ini.']);
        exit;
    }

    // 2. Ambil data pendaftaran
    $cek_reg = "SELECT * FROM registrasi_event WHERE id = '$registrasi_id' AND event_id = '$event_id'";
    $res_reg = mysqli_query($koneksi, $cek_reg);
    
    if (mysqli_num_rows($res_reg) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tiket tidak ditemukan atau tidak valid untuk event ini.']);
        exit;
    }

    $row = mysqli_fetch_assoc($res_reg);

    // 3. Cek Status Pembayaran
    if ($row['status_pendaftaran'] !== 'Sukses') {
        echo json_encode(['status' => 'error', 'message' => 'Tiket ditolak! Status pendaftaran: ' . $row['status_pendaftaran']]);
        exit;
    }

    // 4. Cek Kehadiran
    if ($row['kehadiran'] === 'Hadir') {
        echo json_encode(['status' => 'warning', 'message' => 'Tiket ini sudah digunakan (Sudah Check-In sebelumnya).', 'data' => $row]);
        exit;
    }

    // 5. Update kehadiran
    $update = "UPDATE registrasi_event SET kehadiran = 'Hadir' WHERE id = '$registrasi_id'";
    if (mysqli_query($koneksi, $update)) {
        echo json_encode(['status' => 'success', 'message' => 'Check-In Berhasil! Selamat datang, ' . $row['nama_lengkap'], 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate kehadiran: ' . mysqli_error($koneksi)]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
?>
