<?php
// verify_qr.php - Untuk verifikasi tiket saat check-in
header('Content-Type: application/json');
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Method tidak diizinkan"]);
    exit;
}

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

$kodePendaftaran = $ticketData['kode_pendaftaran'] ?? '';
$eventId = $ticketData['event_id'] ?? '';

if (empty($kodePendaftaran)) {
    echo json_encode(["status" => "error", "message" => "Kode tiket tidak ditemukan"]);
    exit;
}

// Cek pendaftaran di database
$stmt = $pdo->prepare("
    SELECT p.*, e.nama_event, e.tanggal, e.waktu, e.lokasi 
    FROM pendaftaran_event p 
    JOIN events e ON p.event_id = e.id 
    WHERE p.kode_pendaftaran = ?
");
$stmt->execute([$kodePendaftaran]);
$pendaftaran = $stmt->fetch(PDO::FETCH_ASSOC);

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

// Cek apakah sudah check-in (tambahkan kolom is_checked_in jika diperlukan)
// Untuk saat ini, return data tiket valid
echo json_encode([
    "status" => "success",
    "message" => "Tiket valid",
    "data" => [
        "kode_pendaftaran" => $pendaftaran['kode_pendaftaran'],
        "nama_peserta" => $pendaftaran['nama_peserta'],
        "nama_event" => $pendaftaran['nama_event'],
        "tanggal" => $pendaftaran['tanggal'],
        "lokasi" => $pendaftaran['lokasi']
    ]
]);
?>