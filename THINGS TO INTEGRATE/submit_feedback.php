<?php
// submit_feedback.php - Submit feedback untuk event yang sudah selesai
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'koneksi.php';

session_start();
$userData = null;
if (isset($_COOKIE['user_eventra'])) {
    $userData = json_decode($_COOKIE['user_eventra'], true);
} elseif (isset($_SESSION['user_eventra'])) {
    $userData = $_SESSION['user_eventra'];
}

// Cek juga dari localStorage via POST (untuk request dari frontend)
if (!$userData && isset($_POST['user_id'])) {
    $userData = ['id' => $_POST['user_id']];
}

$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $event_id = isset($input['event_id']) ? mysqli_real_escape_string($koneksi, $input['event_id']) : '';
    $rating = isset($input['rating']) ? (int)$input['rating'] : 0;
    $komentar = isset($input['komentar']) ? mysqli_real_escape_string($koneksi, $input['komentar']) : '';
    $saran = isset($input['saran']) ? mysqli_real_escape_string($koneksi, $input['saran']) : '';
    $user_id_from_input = isset($input['user_id']) ? mysqli_real_escape_string($koneksi, $input['user_id']) : '';
} else {
    $event_id = isset($_POST['event_id']) ? mysqli_real_escape_string($koneksi, $_POST['event_id']) : '';
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $komentar = isset($_POST['komentar']) ? mysqli_real_escape_string($koneksi, $_POST['komentar']) : '';
    $saran = isset($_POST['saran']) ? mysqli_real_escape_string($koneksi, $_POST['saran']) : '';
    $user_id_from_input = isset($_POST['user_id']) ? mysqli_real_escape_string($koneksi, $_POST['user_id']) : '';
}

if (empty($event_id) || $rating < 1 || $rating > 5) {
    echo json_encode(["status" => "error", "message" => "Data tidak valid"]);
    exit;
}

// Ambil user_id dari berbagai sumber
$user_id = null;
if ($userData && isset($userData['id'])) {
    $user_id = $userData['id'];
} elseif ($user_id_from_input) {
    $user_id = $user_id_from_input;
} else {
    echo json_encode(["status" => "error", "message" => "User tidak teridentifikasi"]);
    exit;
}

// Cek apakah user sudah memberikan feedback untuk event ini
$cek_query = "SELECT id FROM feedback_event WHERE event_id = '$event_id' AND user_id = '$user_id'";
$cek_result = mysqli_query($koneksi, $cek_query);

if (mysqli_num_rows($cek_result) > 0) {
    echo json_encode(["status" => "error", "message" => "Anda sudah memberikan feedback untuk event ini"]);
    exit;
}

// Ambil pendaftaran_id
$pendaftaran_query = "SELECT id FROM pendaftaran_event WHERE event_id = '$event_id' AND user_id = '$user_id' LIMIT 1";
$pendaftaran_result = mysqli_query($koneksi, $pendaftaran_query);
$pendaftaran = mysqli_fetch_assoc($pendaftaran_result);

$pendaftaran_id = $pendaftaran ? $pendaftaran['id'] : 'NULL';

$insert_query = "INSERT INTO feedback_event (event_id, user_id, pendaftaran_id, rating, komentar, saran) 
                 VALUES ('$event_id', '$user_id', " . ($pendaftaran_id !== 'NULL' ? "'$pendaftaran_id'" : "NULL") . ", '$rating', '$komentar', '$saran')";

if (mysqli_query($koneksi, $insert_query)) {
    echo json_encode(["status" => "success", "message" => "Terima kasih atas feedback Anda!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal menyimpan feedback: " . mysqli_error($koneksi)]);
}
?>