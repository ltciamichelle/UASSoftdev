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

// 1. Ambil list event milik panitia
if ($aksi === 'ambil_event_panitia') {
    $user_id = mysqli_real_escape_string($koneksi, $_GET['user_id'] ?? '');

    if (empty($user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'User ID tidak valid']);
        exit;
    }

    $query = "SELECT * FROM events WHERE user_id = '$user_id' ORDER BY id DESC";
    $result = mysqli_query($koneksi, $query);
    $events = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $events]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data event']);
    }
    exit;
}

// 2. Ambil list pendaftar untuk event tertentu
if ($aksi === 'ambil_pendaftar') {
    $event_id = mysqli_real_escape_string($koneksi, $_GET['event_id'] ?? '');

    if (empty($event_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Event ID tidak valid']);
        exit;
    }

    $query = "SELECT r.*, e.tipe_tiket, e.harga_event 
              FROM registrasi_event r 
              JOIN events e ON r.event_id = e.id 
              WHERE r.event_id = '$event_id' ORDER BY r.tanggal_daftar DESC";
    
    $result = mysqli_query($koneksi, $query);
    $pendaftar = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $pendaftar[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $pendaftar]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data pendaftar: ' . mysqli_error($koneksi)]);
    }
    exit;
}

// 3. Verifikasi Pendaftaran (Terima/Tolak)
if ($aksi === 'verifikasi_pembayaran') {
    $registrasi_id = mysqli_real_escape_string($koneksi, $data['registrasi_id'] ?? '');
    $status_baru = mysqli_real_escape_string($koneksi, $data['status'] ?? '');

    if (empty($registrasi_id) || empty($status_baru)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit;
    }

    // Validasi status
    $allowed_status = ['Sukses', 'Ditolak'];
    if (!in_array($status_baru, $allowed_status)) {
        echo json_encode(['status' => 'error', 'message' => 'Status tidak valid']);
        exit;
    }

    $query = "UPDATE registrasi_event SET status_pendaftaran = ? WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "si", $status_baru, $registrasi_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => "Pendaftaran berhasil di-$status_baru"]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status: ' . mysqli_error($koneksi)]);
    }
    
    mysqli_stmt_close($stmt);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali']);
?>
