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

// Support FormData ($_POST) if data is not JSON
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
} else if (empty($data)) {
    $data = [];
}

// Fallback ke GET parameter jika bukan POST JSON
$aksi = isset($data['aksi']) ? $data['aksi'] : (isset($_GET['aksi']) ? $_GET['aksi'] : '');

// ==========================================
// 1. DAFTAR EVENT
// ==========================================
if ($aksi === 'daftar') {
    $user_id = mysqli_real_escape_string($koneksi, $data['user_id'] ?? '');
    $event_id = mysqli_real_escape_string($koneksi, $data['event_id'] ?? '');
    $nama_lengkap = mysqli_real_escape_string($koneksi, $data['nama_lengkap'] ?? '');
    $email = mysqli_real_escape_string($koneksi, $data['email'] ?? '');
    $no_wa = mysqli_real_escape_string($koneksi, $data['no_wa'] ?? '');
    $instansi = mysqli_real_escape_string($koneksi, $data['instansi'] ?? '');

    if (empty($user_id) || empty($event_id) || empty($nama_lengkap) || empty($email) || empty($no_wa)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
        exit;
    }

    // Cek apakah sudah pernah mendaftar
    $cek_query = "SELECT id FROM registrasi_event WHERE user_id = '$user_id' AND event_id = '$event_id'";
    $cek_result = mysqli_query($koneksi, $cek_query);
    if (mysqli_num_rows($cek_result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah terdaftar pada event ini.']);
        exit;
    }

    // Ambil info event (apakah berbayar?)
    $event_query = "SELECT tipe_tiket FROM events WHERE id = '$event_id'";
    $event_res = mysqli_query($koneksi, $event_query);
    $event_data = mysqli_fetch_assoc($event_res);
    $tipe_tiket = $event_data['tipe_tiket'] ?? 'Gratis';

    $nama_file_bukti = NULL;
    $status_pendaftaran = 'Sukses'; // Default sukses untuk Gratis

    if ($tipe_tiket === 'Berbayar') {
        $status_pendaftaran = 'Menunggu Verifikasi';
        
        if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== 0) {
            echo json_encode(['status' => 'error', 'message' => 'Bukti pembayaran wajib diunggah untuk event berbayar.']);
            exit;
        }

        $target_dir = "uploads/bukti_bayar/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $ekstensi_file = strtolower(pathinfo($_FILES["bukti_bayar"]["name"], PATHINFO_EXTENSION));
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["bukti_bayar"]["tmp_name"]);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($_FILES["bukti_bayar"]["tmp_name"]);
        } else {
            $mime = $_FILES["bukti_bayar"]["type"];
        }

        if (!in_array($mime, $allowed_mime)) {
            echo json_encode(["status" => "error", "message" => "Format file bukti pembayaran tidak valid."]);
            exit;
        }

        $nama_file_bukti = time() . '_bukti_' . uniqid() . '.webp';
        $target_file = $target_dir . $nama_file_bukti;

        // Compress to WebP
        $image = null;
        if ($mime == 'image/jpeg') $image = imagecreatefromjpeg($_FILES["bukti_bayar"]["tmp_name"]);
        elseif ($mime == 'image/png') $image = imagecreatefrompng($_FILES["bukti_bayar"]["tmp_name"]);
        elseif ($mime == 'image/webp') $image = imagecreatefromwebp($_FILES["bukti_bayar"]["tmp_name"]);
        elseif ($mime == 'image/gif') $image = imagecreatefromgif($_FILES["bukti_bayar"]["tmp_name"]);

        if ($image !== false) {
            if (function_exists('imagewebp')) {
                imagewebp($image, $target_file, 80);
            } else {
                $target_file = str_replace('.webp', '.jpg', $target_file);
                $nama_file_bukti = str_replace('.webp', '.jpg', $nama_file_bukti);
                imagejpeg($image, $target_file, 80);
            }
            imagedestroy($image);
        } else {
            move_uploaded_file($_FILES["bukti_bayar"]["tmp_name"], $target_file);
        }
    }

    // $nama_file_bukti = ($nama_file_bukti) ? "'$nama_file_bukti'" : "NULL";

    $query_insert = "INSERT INTO registrasi_event (user_id, event_id, nama_lengkap, email, no_wa, instansi, status_pendaftaran, bukti_bayar) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($koneksi, $query_insert);
    mysqli_stmt_bind_param($stmt, "iissssss", $user_id, $event_id, $nama_lengkap, $email, $no_wa, $instansi, $status_pendaftaran, $nama_file_bukti);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar: ' . mysqli_error($koneksi)]);
    }
    mysqli_stmt_close($stmt);
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

    // Melakukan JOIN antara tabel registrasi_event dan events
    $query = "SELECT e.*, r.tanggal_daftar, r.nama_lengkap 
              FROM registrasi_event r 
              JOIN events e ON r.event_id = e.id 
              WHERE r.user_id = '$user_id' 
              ORDER BY r.tanggal_daftar DESC";
              
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

// ==========================================
// 3. HITUNG PENDAFTAR EVENT
// ==========================================
if ($aksi === 'hitung_pendaftar') {
    $event_id = isset($_GET['event_id']) ? mysqli_real_escape_string($koneksi, $_GET['event_id']) : '';

    if (empty($event_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID Event tidak valid.']);
        exit;
    }

    $query = "SELECT COUNT(id) as total FROM registrasi_event WHERE event_id = '$event_id'";
    $result = mysqli_query($koneksi, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode(['status' => 'success', 'total' => $row['total']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghitung pendaftar.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
exit;
?>
