<?php
// api_verifikasi.php - API untuk verifikasi peserta
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'koneksi.php';

// Cek koneksi
if (!$koneksi) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : (isset($_POST['aksi']) ? $_POST['aksi'] : '');

// Jika data dari POST JSON
if (empty($aksi)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $aksi = isset($input['aksi']) ? $input['aksi'] : '';
    
    if ($aksi === 'verify' || $aksi === 'reject' || $aksi === 'checkin') {
        handleVerificationAction($input, $koneksi);
        exit;
    }
}

// GET Request - Ambil peserta berdasarkan event
if ($aksi === 'get_participants') {
    getParticipants($koneksi);
    exit;
}

echo json_encode(["status" => "error", "message" => "Aksi tidak dikenal"]);
exit;

function getParticipants($koneksi) {
    $event_id = isset($_GET['event_id']) ? mysqli_real_escape_string($koneksi, $_GET['event_id']) : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
    
    if (empty($event_id)) {
        echo json_encode(["status" => "error", "message" => "Event ID tidak ditemukan"]);
        exit;
    }
    
    // Query dasar
    $query = "SELECT 
                p.id as pendaftaran_id,
                p.user_id,
                p.event_id,
                p.kode_pendaftaran,
                p.nama_lengkap as nama_peserta,
                p.email as email_peserta,
                p.no_wa as no_telepon,
                p.tanggal_daftar,
                p.status_pendaftaran,
                p.bukti_bayar,
                p.kehadiran,
                e.id as event_id_db,
                e.nama_event,
                e.tanggal,
                e.waktu,
                e.lokasi,
                e.tipe_tiket,
                e.harga_event
              FROM registrasi_event p 
              JOIN events e ON p.event_id = e.id 
              WHERE p.event_id = '$event_id'";
    
    if (!empty($search)) {
        $query .= " AND (p.kode_pendaftaran LIKE '%$search%' 
                    OR p.nama_lengkap LIKE '%$search%' 
                    OR p.email LIKE '%$search%'
                    OR p.no_wa LIKE '%$search%')";
    }
    
    if (!empty($status)) {
        $query .= " AND p.status_pendaftaran = '$status'";
    }
    
    $query .= " ORDER BY p.tanggal_daftar DESC";
    
    $result = mysqli_query($koneksi, $query);
    
    if (!$result) {
        echo json_encode(["status" => "error", "message" => "Query error: " . mysqli_error($koneksi)]);
        exit;
    }
    
    $participants = [];
    $stats = [
        'total' => 0,
        'waiting' => 0,
        'verified' => 0,
        'checked_in' => 0,
        'rejected' => 0
    ];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Format status untuk tampilan
        if ($row['kehadiran'] == 'Hadir') {
            $row['status_pendaftaran'] = 'Sudah Check-in';
            $row['check_in_time'] = 'Hadir';
        }
        
        $participants[] = $row;
        
        $stats['total']++;
        if ($row['status_pendaftaran'] === 'Menunggu Verifikasi') $stats['waiting']++;
        elseif ($row['status_pendaftaran'] === 'Terdaftar') $stats['verified']++;
        elseif ($row['status_pendaftaran'] === 'Sudah Check-in') $stats['checked_in']++;
        elseif ($row['status_pendaftaran'] === 'Ditolak') $stats['rejected']++;
    }
    
    echo json_encode([
        "status" => "success",
        "participants" => $participants,
        "stats" => $stats
    ]);
}

function handleVerificationAction($input, $koneksi) {
    $aksi = $input['aksi'];
    $pendaftaran_id = isset($input['pendaftaran_id']) ? mysqli_real_escape_string($koneksi, $input['pendaftaran_id']) : '';
    
    if (empty($pendaftaran_id)) {
        echo json_encode(["status" => "error", "message" => "Pendaftaran ID tidak ditemukan"]);
        exit;
    }
    
    if ($aksi === 'verify') {
        $status = 'Terdaftar';
        $message = 'Pendaftaran berhasil diverifikasi!';
    } elseif ($aksi === 'reject') {
        $status = 'Ditolak';
        $message = 'Pendaftaran ditolak!';
    } elseif ($aksi === 'checkin') {
        // Update kehadiran
        $query = "UPDATE registrasi_event SET kehadiran = 'Hadir' WHERE id = '$pendaftaran_id'";
        
        if (mysqli_query($koneksi, $query)) {
            echo json_encode(["status" => "success", "message" => "Check-in berhasil! Peserta sudah masuk area event."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal check-in: " . mysqli_error($koneksi)]);
        }
        exit;
    } else {
        echo json_encode(["status" => "error", "message" => "Aksi tidak dikenal"]);
        exit;
    }
    
    // Untuk verify dan reject
    $query = "UPDATE registrasi_event SET status_pendaftaran = '$status' WHERE id = '$pendaftaran_id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => $message]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update: " . mysqli_error($koneksi)]);
    }
}
?>