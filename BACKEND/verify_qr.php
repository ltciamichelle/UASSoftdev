<?php
// verify_qr.php - Untuk verifikasi tiket saat check-in
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'koneksi.php';

$input = json_decode(file_get_contents('php://input'), true);
$qrData = $input['qr_data'] ?? '';

if (empty($qrData)) {
    echo json_encode(["status" => "error", "message" => "Data QR tidak valid"]);
    exit;
}

// Decode data QR
$ticketData = json_decode($qrData, true);

if (!$ticketData) {
    echo json_encode(["status" => "error", "message" => "Format QR tidak valid"]);
    exit;
}

$kodePendaftaran = mysqli_real_escape_string($koneksi, $ticketData['kode_pendaftaran'] ?? '');
$eventId = mysqli_real_escape_string($koneksi, $ticketData['event_id'] ?? '');

if (empty($kodePendaftaran)) {
    echo json_encode(["status" => "error", "message" => "Kode tiket tidak ditemukan"]);
    exit;
}

// Cek pendaftaran di database
$query = "SELECT p.*, e.nama_event, e.tanggal, e.waktu, e.lokasi 
          FROM registrasi_event p 
          JOIN events e ON p.event_id = e.id 
          WHERE p.kode_pendaftaran = '$kodePendaftaran'";

$result = mysqli_query($koneksi, $query);
$pendaftaran = mysqli_fetch_assoc($result);

if (!$pendaftaran) {
    echo json_encode(["status" => "error", "message" => "Tiket tidak ditemukan"]);
    exit;
}

// Cek status
if ($pendaftaran['status_pendaftaran'] === 'Ditolak') {
    echo json_encode(["status" => "error", "message" => "Tiket ini ditolak"]);
    exit;
}

if ($pendaftaran['status_pendaftaran'] === 'Menunggu Verifikasi') {
    echo json_encode(["status" => "warning", "message" => "Tiket masih menunggu verifikasi pembayaran"]);
    exit;
}

if ($pendaftaran['kehadiran'] === 'Hadir') {
    echo json_encode(["status" => "warning", "message" => "Tiket sudah pernah digunakan untuk check-in"]);
    exit;
}

// Cek apakah event ID cocok
if (!empty($eventId) && $pendaftaran['event_id'] != $eventId) {
    echo json_encode(["status" => "error", "message" => "Tiket ini bukan untuk event yang dipilih"]);
    exit;
}

// Berhasil diverifikasi
echo json_encode([
    "status" => "success",
    "message" => "Tiket valid",
    "data" => [
        "kode_pendaftaran" => $pendaftaran['kode_pendaftaran'],
        "nama_peserta" => $pendaftaran['nama_lengkap'],
        "nama_event" => $pendaftaran['nama_event'],
        "tanggal" => $pendaftaran['tanggal'],
        "lokasi" => $pendaftaran['lokasi']
    ]
]);
?>
