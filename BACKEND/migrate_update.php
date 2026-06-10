<?php
header('Content-Type: application/json');
include 'koneksi.php';

try {
    // 1. Rename table if pendaftaran_event exists and registrasi_event does not
    $check_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'registrasi_event'");
    if (mysqli_num_rows($check_table) == 0) {
        $check_old = mysqli_query($koneksi, "SHOW TABLES LIKE 'pendaftaran_event'");
        if (mysqli_num_rows($check_old) > 0) {
            mysqli_query($koneksi, "RENAME TABLE pendaftaran_event TO registrasi_event");
        } else {
            // Create table if it doesn't exist
            mysqli_query($koneksi, "CREATE TABLE registrasi_event (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                event_id INT(11) NOT NULL,
                nama_lengkap VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                no_wa VARCHAR(50) NOT NULL,
                instansi VARCHAR(255) NOT NULL,
                tanggal_daftar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                status_pendaftaran VARCHAR(50) DEFAULT 'Terdaftar',
                bukti_bayar VARCHAR(255) DEFAULT NULL
            )");
        }
    }

    // 2. Add columns to registrasi_event if missing
    $check_bukti = mysqli_query($koneksi, "SHOW COLUMNS FROM registrasi_event LIKE 'bukti_bayar'");
    if (mysqli_num_rows($check_bukti) == 0) {
        mysqli_query($koneksi, "ALTER TABLE registrasi_event ADD COLUMN bukti_bayar VARCHAR(255) DEFAULT NULL");
    }

    $check_status = mysqli_query($koneksi, "SHOW COLUMNS FROM registrasi_event LIKE 'status_pendaftaran'");
    if (mysqli_num_rows($check_status) == 0) {
        mysqli_query($koneksi, "ALTER TABLE registrasi_event ADD COLUMN status_pendaftaran VARCHAR(50) DEFAULT 'Terdaftar'");
    }

    $check_kehadiran = mysqli_query($koneksi, "SHOW COLUMNS FROM registrasi_event LIKE 'kehadiran'");
    if (mysqli_num_rows($check_kehadiran) == 0) {
        mysqli_query($koneksi, "ALTER TABLE registrasi_event ADD COLUMN kehadiran ENUM('Tidak Hadir', 'Hadir') DEFAULT 'Tidak Hadir'");
    }

    // 3. Add harga_event to events table if missing
    $check_harga = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'harga_event'");
    if (mysqli_num_rows($check_harga) == 0) {
        mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN harga_event INT(11) DEFAULT 0");
    }

    // 4. Add user_id to events table if missing
    $check_user_id = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'user_id'");
    if (mysqli_num_rows($check_user_id) == 0) {
        mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN user_id INT(11) DEFAULT NULL AFTER id");
    }


    // 5. Add Rekening info for paid events
    $check_bank = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'bank_name'");
    if (mysqli_num_rows($check_bank) == 0) {
        mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN bank_name VARCHAR(100) DEFAULT NULL");
        mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN bank_rekening VARCHAR(100) DEFAULT NULL");
        mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN bank_atas_nama VARCHAR(255) DEFAULT NULL");
    }
    // 6. Create feedbacks table
    $check_feedbacks = mysqli_query($koneksi, "SHOW TABLES LIKE 'feedbacks'");
    if (mysqli_num_rows($check_feedbacks) == 0) {
        mysqli_query($koneksi, "CREATE TABLE feedbacks (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            event_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            rating INT(11) NOT NULL DEFAULT 5,
            ulasan TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // 7. Add template_sertifikat to events table if missing
    $check_template = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'template_sertifikat'");
    if (mysqli_num_rows($check_template) == 0) {
        mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN template_sertifikat VARCHAR(255) DEFAULT NULL");
    }

    echo json_encode(['status' => 'success', 'message' => 'Database migration completed successfully!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
