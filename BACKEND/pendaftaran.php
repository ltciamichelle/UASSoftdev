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

// Fallback ke GET parameter jika bukan POST JSON (untuk endpoint GET)
$aksi = isset($data['aksi']) ? $data['aksi'] : (isset($_GET['aksi']) ? $_GET['aksi'] : '');

// ==========================================
// 1. DAFTAR EVENT
// ==========================================
if ($aksi === 'daftar') {
    $user_id = mysqli_real_escape_string($koneksi, $data['user_id']);
    $event_id = mysqli_real_escape_string($koneksi, $data['event_id']);
    $nama_pendaftar = mysqli_real_escape_string($koneksi, $data['nama_pendaftar']);
    $email = mysqli_real_escape_string($koneksi, $data['email']);
    $no_wa = mysqli_real_escape_string($koneksi, $data['no_wa']);
    $instansi = mysqli_real_escape_string($koneksi, $data['instansi']);

    if (empty($user_id) || empty($event_id) || empty($nama_pendaftar) || empty($email) || empty($no_wa)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
        exit;
    }

    // Cek apakah sudah pernah mendaftar
    $cek_query = "SELECT id FROM pendaftaran WHERE user_id = '$user_id' AND event_id = '$event_id'";
    $cek_result = mysqli_query($koneksi, $cek_query);
    if (mysqli_num_rows($cek_result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah terdaftar pada event ini.']);
        exit;
    }

    $query_insert = "INSERT INTO pendaftaran (user_id, event_id, nama_pendaftar, email, no_wa, instansi) 
                     VALUES ('$user_id', '$event_id', '$nama_pendaftar', '$email', '$no_wa', '$instansi')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar: ' . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 2. AMBIL EVENT YANG DIDAFTAR OLEH USER
// ==========================================
if ($aksi === 'ambil_event_user') {
    $user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($koneksi, $_GET['user_id']) : '';

    if (empty($user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID User tidak valid.']);
        exit;
    }

    // Melakukan JOIN antara tabel pendaftaran dan events
    $query = "SELECT e.*, p.tanggal_daftar 
              FROM pendaftaran p 
              JOIN events e ON p.event_id = e.id 
              WHERE p.user_id = '$user_id' 
              ORDER BY p.tanggal_daftar DESC";
              
    $result = mysqli_query($koneksi, $query);
    $daftar_event = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $daftar_event[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $daftar_event]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memuat histori event: ' . mysqli_error($koneksi)]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
exit;
?>
