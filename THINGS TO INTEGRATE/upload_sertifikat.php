<?php
// upload_sertifikat.php - Upload sertifikat untuk peserta event
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'koneksi.php';

// Cek session/login
session_start();
$userData = null;
if (isset($_COOKIE['user_eventra'])) {
    $userData = json_decode($_COOKIE['user_eventra'], true);
} elseif (isset($_SESSION['user_eventra'])) {
    $userData = $_SESSION['user_eventra'];
}

if (!$userData || $userData['role'] !== 'panitia') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : (isset($_GET['aksi']) ? $_GET['aksi'] : '');

if ($aksi === 'upload_sertifikat') {
    $event_id = mysqli_real_escape_string($koneksi, $_POST['event_id']);
    $pendaftaran_id = mysqli_real_escape_string($koneksi, $_POST['pendaftaran_id']);
    $user_id = mysqli_real_escape_string($koneksi, $_POST['user_id']);
    $status_kelulusan = mysqli_real_escape_string($koneksi, $_POST['status_kelulusan']);
    $nilai_kehadiran = isset($_POST['nilai_kehadiran']) ? mysqli_real_escape_string($koneksi, $_POST['nilai_kehadiran']) : NULL;
    
    if (empty($event_id) || empty($pendaftaran_id) || empty($user_id)) {
        echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
        exit;
    }
    
    // Proses upload file sertifikat
    if (!isset($_FILES['file_sertifikat']) || $_FILES['file_sertifikat']['error'] !== 0) {
        echo json_encode(["status" => "error", "message" => "File sertifikat wajib diupload"]);
        exit;
    }
    
    $target_dir = "uploads/sertifikat/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $ekstensi_file = pathinfo($_FILES["file_sertifikat"]["name"], PATHINFO_EXTENSION);
    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array(strtolower($ekstensi_file), $allowed_ext)) {
        echo json_encode(["status" => "error", "message" => "Format file tidak diizinkan. Gunakan PDF, JPG, JPEG, atau PNG"]);
        exit;
    }
    
    $nomor_sertifikat = "SRT-" . strtoupper(uniqid()) . "-" . $event_id;
    $nama_file = $nomor_sertifikat . '.' . $ekstensi_file;
    $target_file = $target_dir . $nama_file;
    
    if (move_uploaded_file($_FILES["file_sertifikat"]["tmp_name"], $target_file)) {
        // Cek apakah sudah ada sertifikat untuk pendaftaran ini
        $cek_query = "SELECT id FROM sertifikat_event WHERE pendaftaran_id = '$pendaftaran_id'";
        $cek_result = mysqli_query($koneksi, $cek_query);
        
        if (mysqli_num_rows($cek_result) > 0) {
            // Update existing
            $query = "UPDATE sertifikat_event SET 
                        file_sertifikat = '$nama_file',
                        nomor_sertifikat = '$nomor_sertifikat',
                        tanggal_terbit = NOW(),
                        status_kirim = 0
                      WHERE pendaftaran_id = '$pendaftaran_id'";
        } else {
            // Insert new
            $query = "INSERT INTO sertifikat_event (event_id, pendaftaran_id, user_id, file_sertifikat, nomor_sertifikat) 
                      VALUES ('$event_id', '$pendaftaran_id', '$user_id', '$nama_file', '$nomor_sertifikat')";
        }
        
        if (mysqli_query($koneksi, $query)) {
            // Update status kelulusan di pendaftaran_event
            $update_pendaftaran = "UPDATE pendaftaran_event SET 
                                    status_kelulusan = '$status_kelulusan',
                                    nilai_kehadiran = " . ($nilai_kehadiran ? "'$nilai_kehadiran'" : "NULL") . "
                                  WHERE id = '$pendaftaran_id'";
            mysqli_query($koneksi, $update_pendaftaran);
            
            echo json_encode(["status" => "success", "message" => "Sertifikat berhasil diupload", "nomor_sertifikat" => $nomor_sertifikat]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database: " . mysqli_error($koneksi)]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal mengupload file"]);
    }
    exit;
}

if ($aksi === 'kirim_sertifikat_email') {
    $pendaftaran_id = mysqli_real_escape_string($koneksi, $_POST['pendaftaran_id']);
    
    if (empty($pendaftaran_id)) {
        echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
        exit;
    }
    
    // Ambil data sertifikat dan peserta
    $query = "SELECT se.*, pe.nama_peserta, pe.email_peserta, e.nama_event, se.file_sertifikat, se.nomor_sertifikat
              FROM sertifikat_event se
              JOIN pendaftaran_event pe ON se.pendaftaran_id = pe.id
              JOIN events e ON se.event_id = e.id
              WHERE se.pendaftaran_id = '$pendaftaran_id'";
    $result = mysqli_query($koneksi, $query);
    $data = mysqli_fetch_assoc($result);
    
    if (!$data) {
        echo json_encode(["status" => "error", "message" => "Data sertifikat tidak ditemukan"]);
        exit;
    }
    
    // Kirim email dengan sertifikat
    $to = $data['email_peserta'];
    $subject = "Sertifikat Event - " . $data['nama_event'];
    $message = "Yth. " . $data['nama_peserta'] . ",\n\n";
    $message .= "Selamat! Anda telah menyelesaikan event " . $data['nama_event'] . ".\n";
    $message .= "Berikut adalah sertifikat Anda:\n";
    $message .= "Nomor Sertifikat: " . $data['nomor_sertifikat'] . "\n\n";
    $message .= "Sertifikat terlampir dalam email ini.\n\n";
    $message .= "Terima kasih telah berpartisipasi!\n\n";
    $message .= "Salam,\nTim EVENTRA";
    
    $file_path = "uploads/sertifikat/" . $data['file_sertifikat'];
    
    if (file_exists($file_path)) {
        // Gunakan PHPMailer atau mail() dengan attachment
        // Untuk contoh ini, kita akan menggunakan mail() sederhana
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@eventra.com" . "\r\n";
        
        if (mail($to, $subject, $message, $headers)) {
            // Update status kirim
            $update = "UPDATE sertifikat_event SET status_kirim = 1, tanggal_kirim = NOW() WHERE pendaftaran_id = '$pendaftaran_id'";
            mysqli_query($koneksi, $update);
            echo json_encode(["status" => "success", "message" => "Sertifikat berhasil dikirim ke email peserta"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal mengirim email"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "File sertifikat tidak ditemukan"]);
    }
    exit;
}

echo json_encode(["status" => "error", "message" => "Aksi tidak dikenal"]);
?>