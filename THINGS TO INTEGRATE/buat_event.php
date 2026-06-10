<?php
// ==========================================
// BUAT_EVENT.PHP - VERSI LENGKAP & TERREVISI
// ==========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'koneksi.php';

// ==========================================
// AUTO CREATE TABLES YANG BELUM ADA
// ==========================================

// 1. CEK & BUAT TABEL EVENTS
$cek_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'events'");
if (mysqli_num_rows($cek_table) == 0) {
    $create_events = "CREATE TABLE IF NOT EXISTS `events` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_event` varchar(50) NOT NULL,
        `nama_event` varchar(200) NOT NULL,
        `id_panitia` varchar(50) NOT NULL,
        `kategori` varchar(50) DEFAULT NULL,
        `tanggal` date NOT NULL,
        `waktu` time NOT NULL,
        `tanggal_selesai` date NOT NULL,
        `waktu_selesai` time NOT NULL,
        `lokasi` varchar(255) NOT NULL,
        `tipe_tiket` enum('Gratis','Berbayar') DEFAULT 'Gratis',
        `harga_event` decimal(15,0) DEFAULT NULL,
        `slot_kursi` int(11) DEFAULT 0,
        `deskripsi` text DEFAULT NULL,
        `banner_img` varchar(255) DEFAULT NULL,
        `status_event` enum('aktif','selesai') DEFAULT 'aktif',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `id_event` (`id_event`),
        KEY `idx_id_panitia` (`id_panitia`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($koneksi, $create_events);
}

// 2. CEK & BUAT TABEL EVENT_KEUANGAN
$cek_keuangan = mysqli_query($koneksi, "SHOW TABLES LIKE 'event_keuangan'");
if (mysqli_num_rows($cek_keuangan) == 0) {
    $create_keuangan = "CREATE TABLE IF NOT EXISTS `event_keuangan` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_id` int(11) NOT NULL,
        `divisi` varchar(50) NOT NULL,
        `pengeluaran` decimal(15,0) DEFAULT 0,
        `keterangan` text DEFAULT NULL,
        `tanggal_catat` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unik_event_divisi` (`event_id`,`divisi`),
        KEY `event_keuangan_ibfk_1` (`event_id`),
        CONSTRAINT `event_keuangan_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($koneksi, $create_keuangan);
}

// 3. CEK & BUAT TABEL FEEDBACK_EVENT
$cek_feedback = mysqli_query($koneksi, "SHOW TABLES LIKE 'feedback_event'");
if (mysqli_num_rows($cek_feedback) == 0) {
    $create_feedback = "CREATE TABLE IF NOT EXISTS `feedback_event` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `pendaftaran_id` int(11) DEFAULT NULL,
        `rating` int(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
        `komentar` text DEFAULT NULL,
        `saran` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `unik_feedback` (`event_id`,`user_id`),
        KEY `feedback_event_ibfk_1` (`event_id`),
        KEY `feedback_event_ibfk_2` (`user_id`),
        KEY `feedback_event_ibfk_3` (`pendaftaran_id`),
        CONSTRAINT `feedback_event_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
        CONSTRAINT `feedback_event_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `feedback_event_ibfk_3` FOREIGN KEY (`pendaftaran_id`) REFERENCES `pendaftaran_event` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($koneksi, $create_feedback);
}

// 4. CEK & BUAT TABEL SERTIFIKAT_EVENT
$cek_sertifikat = mysqli_query($koneksi, "SHOW TABLES LIKE 'sertifikat_event'");
if (mysqli_num_rows($cek_sertifikat) == 0) {
    $create_sertifikat = "CREATE TABLE IF NOT EXISTS `sertifikat_event` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_id` int(11) NOT NULL,
        `pendaftaran_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `file_sertifikat` varchar(255) NOT NULL,
        `nomor_sertifikat` varchar(100) NOT NULL,
        `tanggal_terbit` timestamp NOT NULL DEFAULT current_timestamp(),
        `status_kirim` tinyint(1) DEFAULT 0,
        `tanggal_kirim` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `pendaftaran_id` (`pendaftaran_id`),
        UNIQUE KEY `nomor_sertifikat` (`nomor_sertifikat`),
        KEY `sertifikat_event_ibfk_1` (`event_id`),
        KEY `sertifikat_event_ibfk_2` (`user_id`),
        CONSTRAINT `sertifikat_event_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
        CONSTRAINT `sertifikat_event_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `sertifikat_event_ibfk_3` FOREIGN KEY (`pendaftaran_id`) REFERENCES `pendaftaran_event` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    mysqli_query($koneksi, $create_sertifikat);
}

// 5. CEK & TAMBAHKAN KOLOM DI TABEL PENDAFTARAN_EVENT (JIKA BELUM ADA)
$cek_kolom_kelulusan = mysqli_query($koneksi, "SHOW COLUMNS FROM pendaftaran_event LIKE 'status_kelulusan'");
if (mysqli_num_rows($cek_kolom_kelulusan) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pendaftaran_event ADD COLUMN status_kelulusan enum('pending','lulus','tidak_lulus') DEFAULT 'pending'");
}

$cek_kolom_nilai = mysqli_query($koneksi, "SHOW COLUMNS FROM pendaftaran_event LIKE 'nilai_kehadiran'");
if (mysqli_num_rows($cek_kolom_nilai) == 0) {
    mysqli_query($koneksi, "ALTER TABLE pendaftaran_event ADD COLUMN nilai_kehadiran int(3) DEFAULT NULL");
}

// Buat folder uploads jika belum ada
$folders = ['uploads', 'uploads/bukti_bayar', 'uploads/sertifikat'];
foreach ($folders as $folder) {
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
}

// ==========================================
// FUNGSI-FUNGSI API
// ==========================================

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

// ==========================================
// 1. TAMBAH EVENT
// ==========================================
if ($aksi === 'tambah_event') {
    $id_event         = mysqli_real_escape_string($koneksi, $_POST['id_event']);
    $nama_event       = mysqli_real_escape_string($koneksi, $_POST['nama_event']);
    $id_panitia       = mysqli_real_escape_string($koneksi, $_POST['id_panitia']);
    $kategori         = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $tanggal          = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $waktu            = mysqli_real_escape_string($koneksi, $_POST['waktu']);
    $tanggal_selesai  = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $waktu_selesai    = mysqli_real_escape_string($koneksi, $_POST['waktu_selesai']);
    $lokasi           = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $tipe_tiket       = mysqli_real_escape_string($koneksi, $_POST['tipe_tiket']);
    $harga_event      = isset($_POST['harga_event']) && $_POST['harga_event'] != '' ? mysqli_real_escape_string($koneksi, $_POST['harga_event']) : NULL;
    $slot_kursi       = isset($_POST['slot_kursi']) ? mysqli_real_escape_string($koneksi, $_POST['slot_kursi']) : 0;
    $deskripsi        = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Validasi
    if (empty($nama_event) || empty($kategori) || empty($tanggal) || empty($waktu) || empty($tanggal_selesai) || empty($waktu_selesai) || empty($lokasi) || empty($tipe_tiket) || empty($id_panitia)) {
        echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi!"]);
        exit;
    }

    if ($tipe_tiket === 'Berbayar' && (empty($harga_event) || $harga_event <= 0)) {
        echo json_encode(["status" => "error", "message" => "Event berbayar harus mengisi nominal harga!"]);
        exit;
    }

    // Upload gambar
    $nama_file_gambar = "";
    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] === 0) {
        $target_dir = "uploads/";
        $ekstensi_file = strtolower(pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ekstensi_file, $allowed)) {
            echo json_encode(["status" => "error", "message" => "Format file tidak diizinkan!"]);
            exit;
        }
        
        $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
        $target_file = $target_dir . $nama_file_gambar;
        
        if (!move_uploaded_file($_FILES["banner_img"]["tmp_name"], $target_file)) {
            echo json_encode(["status" => "error", "message" => "Gagal mengupload gambar!"]);
            exit;
        }
    }

    $query_insert = "INSERT INTO events (id_event, nama_event, id_panitia, kategori, tanggal, waktu, tanggal_selesai, waktu_selesai, lokasi, tipe_tiket, harga_event, slot_kursi, deskripsi, banner_img, status_event) 
                     VALUES ('$id_event', '$nama_event', '$id_panitia', '$kategori', '$tanggal', '$waktu', '$tanggal_selesai', '$waktu_selesai', '$lokasi', '$tipe_tiket', " . ($harga_event ? "'$harga_event'" : "NULL") . ", '$slot_kursi', '$deskripsi', '$nama_file_gambar', 'aktif')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo json_encode(["status" => "success", "message" => "Event berhasil dibuat dan dipublikasikan!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 2. AMBIL SEMUA EVENT (AKTIF)
// ==========================================
if ($aksi === 'ambil_event') {
    $query_select = "SELECT * FROM events WHERE status_event = 'aktif' ORDER BY id DESC";
    $result = mysqli_query($koneksi, $query_select);
    $daftar_event = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $daftar_event[] = $row;
        }
        echo json_encode($daftar_event);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memuat database."]);
    }
    exit;
}

// ==========================================
// 3. AMBIL EVENT KHUSUS PANITIA
// ==========================================
if ($aksi === 'ambil_event_panitia') {
    $id_panitia = isset($_GET['id_panitia']) ? mysqli_real_escape_string($koneksi, $_GET['id_panitia']) : '';

    if (empty($id_panitia)) {
        echo json_encode(["status" => "error", "message" => "Parameter Panitia tidak valid."]);
        exit;
    }

    $query_select = "SELECT * FROM events WHERE id_panitia = '$id_panitia' ORDER BY id DESC";
    $result = mysqli_query($koneksi, $query_select);
    $daftar_event = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $daftar_event[] = $row;
        }
        echo json_encode($daftar_event);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memuat data event."]);
    }
    exit;
}

// ==========================================
// 4. CARI EVENT BY ID
// ==========================================
if ($aksi === 'cari_event_id') {
    $event_id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

    if (empty($event_id)) {
        echo json_encode(["status" => "error", "message" => "ID Event tidak boleh kosong."]);
        exit;
    }

    $query = "SELECT * FROM events WHERE id = '$event_id'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode(["status" => "success", "data" => $data]);
    } else {
        echo json_encode(["status" => "error", "message" => "Event tidak ditemukan."]);
    }
    exit;
}

// ==========================================
// 5. UPDATE EVENT
// ==========================================
if ($aksi === 'update_event') {
    $event_id         = mysqli_real_escape_string($koneksi, $_POST['event_id_primary']); 
    $id_event         = mysqli_real_escape_string($koneksi, $_POST['id_event']);
    $nama_event       = mysqli_real_escape_string($koneksi, $_POST['nama_event']);
    $kategori         = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $tanggal          = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $waktu            = mysqli_real_escape_string($koneksi, $_POST['waktu']);
    $tanggal_selesai  = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $waktu_selesai    = mysqli_real_escape_string($koneksi, $_POST['waktu_selesai']);
    $lokasi           = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $tipe_tiket       = mysqli_real_escape_string($koneksi, $_POST['tipe_tiket']);
    $harga_event      = isset($_POST['harga_event']) && $_POST['harga_event'] != '' ? mysqli_real_escape_string($koneksi, $_POST['harga_event']) : NULL;
    $slot_kursi       = isset($_POST['slot_kursi']) ? mysqli_real_escape_string($koneksi, $_POST['slot_kursi']) : 0;
    $deskripsi        = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    if (empty($event_id) || empty($nama_event) || empty($kategori) || empty($tanggal) || empty($waktu) || empty($tanggal_selesai) || empty($waktu_selesai) || empty($lokasi) || empty($tipe_tiket)) {
        echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi untuk memperbarui data!"]);
        exit;
    }

    if ($tipe_tiket === 'Berbayar' && (empty($harga_event) || $harga_event <= 0)) {
        echo json_encode(["status" => "error", "message" => "Event berbayar harus mengisi nominal harga!"]);
        exit;
    }

    // Ambil gambar lama
    $query_lama = mysqli_query($koneksi, "SELECT banner_img FROM events WHERE id = '$event_id'");
    $data_lama = mysqli_fetch_assoc($query_lama);
    $nama_file_gambar = $data_lama['banner_img'];

    // Upload gambar baru jika ada
    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] === 0) {
        $target_dir = "uploads/";
        if (!empty($nama_file_gambar) && file_exists($target_dir . $nama_file_gambar)) {
            unlink($target_dir . $nama_file_gambar);
        }

        $ekstensi_file = strtolower(pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ekstensi_file, $allowed)) {
            echo json_encode(["status" => "error", "message" => "Format file tidak diizinkan!"]);
            exit;
        }
        
        $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
        move_uploaded_file($_FILES["banner_img"]["tmp_name"], $target_dir . $nama_file_gambar);
    }

    $query_update = "UPDATE events SET 
                        id_event = '$id_event',
                        nama_event = '$nama_event', 
                        kategori = '$kategori', 
                        tanggal = '$tanggal', 
                        waktu = '$waktu', 
                        tanggal_selesai = '$tanggal_selesai', 
                        waktu_selesai = '$waktu_selesai', 
                        lokasi = '$lokasi', 
                        tipe_tiket = '$tipe_tiket', 
                        harga_event = " . ($harga_event ? "'$harga_event'" : "NULL") . ",
                        slot_kursi = '$slot_kursi', 
                        deskripsi = '$deskripsi',
                        banner_img = '$nama_file_gambar' 
                     WHERE id = '$event_id'";

    if (mysqli_query($koneksi, $query_update)) {
        echo json_encode(["status" => "success", "message" => "Event berhasil diperbarui!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memperbarui database: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 6. HAPUS EVENT
// ==========================================
if ($aksi === 'hapus_event') {
    $input_data = json_decode(file_get_contents('php://input'), true);
    $event_id = isset($input_data['event_id']) ? mysqli_real_escape_string($koneksi, $input_data['event_id']) : '';

    if (empty($event_id)) {
        echo json_encode(["status" => "error", "message" => "ID Event tidak terdeteksi."]);
        exit;
    }

    mysqli_begin_transaction($koneksi);
    
    try {
        // Hapus file banner
        $query_gambar = mysqli_query($koneksi, "SELECT banner_img FROM events WHERE id = '$event_id'");
        if (mysqli_num_rows($query_gambar) > 0) {
            $data = mysqli_fetch_assoc($query_gambar);
            if (!empty($data['banner_img']) && file_exists("uploads/" . $data['banner_img'])) {
                unlink("uploads/" . $data['banner_img']); 
            }
        }
        
        // Hapus data terkait
        mysqli_query($koneksi, "DELETE FROM pendaftaran_event WHERE event_id = '$event_id'");
        mysqli_query($koneksi, "DELETE FROM event_keuangan WHERE event_id = '$event_id'");
        mysqli_query($koneksi, "DELETE FROM feedback_event WHERE event_id = '$event_id'");
        mysqli_query($koneksi, "DELETE FROM sertifikat_event WHERE event_id = '$event_id'");
        
        // Hapus event
        $query_delete = "DELETE FROM events WHERE id = '$event_id'";
        
        if (!mysqli_query($koneksi, $query_delete)) {
            throw new Exception(mysqli_error($koneksi));
        }
        
        mysqli_commit($koneksi);
        echo json_encode(["status" => "success", "message" => "Event berhasil dihapus dari sistem!"]);
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo json_encode(["status" => "error", "message" => "Gagal menghapus event: " . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 7. CEK KETERSEDIAAN SLOT EVENT
// ==========================================
if ($aksi === 'cek_slot_event') {
    $event_id = isset($_GET['event_id']) ? mysqli_real_escape_string($koneksi, $_GET['event_id']) : '';
    
    if (empty($event_id)) {
        echo json_encode(["status" => "error", "message" => "ID Event tidak valid."]);
        exit;
    }
    
    $query = "SELECT id, nama_event, slot_kursi, tipe_tiket, harga_event FROM events WHERE id = '$event_id'";
    $result = mysqli_query($koneksi, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $slot_tersedia = $data['slot_kursi'];
        $is_full = ($slot_tersedia <= 0);
        
        echo json_encode([
            "status" => "success",
            "slot_tersedia" => $slot_tersedia,
            "is_full" => $is_full,
            "nama_event" => $data['nama_event'],
            "tipe_tiket" => $data['tipe_tiket'],
            "harga_event" => $data['harga_event']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Event tidak ditemukan."]);
    }
    exit;
}

// ==========================================
// 8. PENDAFTARAN EVENT (DENGAN BUKTI BAYAR)
// ==========================================
if ($aksi === 'daftar_event') {
    $event_id = mysqli_real_escape_string($koneksi, $_POST['event_id']);
    $user_id = mysqli_real_escape_string($koneksi, $_POST['user_id']);
    $nama_peserta = mysqli_real_escape_string($koneksi, $_POST['nama_peserta']);
    $email_peserta = mysqli_real_escape_string($koneksi, $_POST['email_peserta']);
    $no_telepon = mysqli_real_escape_string($koneksi, $_POST['no_telepon']);
    
    if (empty($event_id) || empty($user_id) || empty($nama_peserta) || empty($email_peserta) || empty($no_telepon)) {
        echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi!"]);
        exit;
    }
    
    mysqli_begin_transaction($koneksi);
    
    try {
        $query_check = "SELECT id, nama_event, slot_kursi, tipe_tiket, harga_event FROM events WHERE id = '$event_id' FOR UPDATE";
        $result_check = mysqli_query($koneksi, $query_check);
        $event = mysqli_fetch_assoc($result_check);
        
        if (!$event) {
            throw new Exception("Event tidak ditemukan.");
        }
        
        if ($event['slot_kursi'] <= 0) {
            throw new Exception("Maaf, kuota peserta event ini sudah penuh!");
        }
        
        $query_cek_peserta = "SELECT id FROM pendaftaran_event WHERE user_id = '$user_id' AND event_id = '$event_id'";
        $result_cek = mysqli_query($koneksi, $query_cek_peserta);
        
        if (mysqli_num_rows($result_cek) > 0) {
            throw new Exception("Anda sudah terdaftar di event ini!");
        }
        
        // Proses upload bukti bayar
        $nama_file_bukti = NULL;
        if ($event['tipe_tiket'] === 'Berbayar') {
            if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== 0) {
                throw new Exception("Event berbayar wajib mengupload bukti pembayaran!");
            }
            
            $target_dir = "uploads/bukti_bayar/";
            $ekstensi_file = strtolower(pathinfo($_FILES["bukti_bayar"]["name"], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (!in_array($ekstensi_file, $allowed)) {
                throw new Exception("Format file bukti bayar tidak diizinkan!");
            }
            
            $nama_file_bukti = time() . '_' . uniqid() . '.' . $ekstensi_file;
            $target_file = $target_dir . $nama_file_bukti;
            
            if (!move_uploaded_file($_FILES["bukti_bayar"]["tmp_name"], $target_file)) {
                throw new Exception("Gagal mengupload bukti pembayaran!");
            }
        }
        
        $kode_pendaftaran = "REG-" . date('Ymd') . '-' . strtoupper(uniqid());
        
        $query_insert = "INSERT INTO pendaftaran_event (user_id, event_id, kode_pendaftaran, nama_peserta, email_peserta, no_telepon, status_pendaftaran, bukti_bayar) 
                         VALUES ('$user_id', '$event_id', '$kode_pendaftaran', '$nama_peserta', '$email_peserta', '$no_telepon', 'Menunggu Verifikasi', " . ($nama_file_bukti ? "'$nama_file_bukti'" : "NULL") . ")";
        
        if (!mysqli_query($koneksi, $query_insert)) {
            throw new Exception("Gagal menyimpan data pendaftaran: " . mysqli_error($koneksi));
        }
        
        $new_slot = $event['slot_kursi'] - 1;
        $query_update = "UPDATE events SET slot_kursi = '$new_slot' WHERE id = '$event_id'";
        
        if (!mysqli_query($koneksi, $query_update)) {
            throw new Exception("Gagal mengupdate kuota event: " . mysqli_error($koneksi));
        }
        
        mysqli_commit($koneksi);
        
        $message = $event['tipe_tiket'] === 'Berbayar' 
            ? "Pendaftaran berhasil! Kode pendaftaran: " . $kode_pendaftaran . ". Bukti bayar telah diupload, menunggu verifikasi panitia."
            : "Pendaftaran berhasil! Kode pendaftaran Anda: " . $kode_pendaftaran;
        
        echo json_encode([
            "status" => "success",
            "message" => $message,
            "kode_pendaftaran" => $kode_pendaftaran,
            "sisa_slot" => $new_slot
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 9. AMBIL EVENT YANG DIDAFTAR USER
// ==========================================
if ($aksi === 'ambil_event_saya') {
    $user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($koneksi, $_GET['user_id']) : '';
    
    if (empty($user_id)) {
        echo json_encode(["status" => "error", "message" => "User ID tidak valid."]);
        exit;
    }
    
    $query = "SELECT 
                p.id as pendaftaran_id,
                p.kode_pendaftaran,
                p.tanggal_daftar,
                p.status_pendaftaran,
                p.bukti_bayar,
                p.nama_peserta,
                p.email_peserta,
                p.no_telepon,
                p.status_kelulusan,
                p.nilai_kehadiran,
                p.is_checked_in,
                p.check_in_time,
                e.id as event_id,
                e.id_event,
                e.nama_event,
                e.kategori,
                e.tanggal,
                e.waktu,
                e.tanggal_selesai,
                e.waktu_selesai,
                e.lokasi,
                e.tipe_tiket,
                e.harga_event,
                e.slot_kursi,
                e.deskripsi,
                e.banner_img,
                e.status_event
              FROM pendaftaran_event p
              JOIN events e ON p.event_id = e.id
              WHERE p.user_id = '$user_id'
              ORDER BY p.tanggal_daftar DESC";
    
    $result = mysqli_query($koneksi, $query);
    $daftar_event = array();
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $daftar_event[] = $row;
        }
        echo json_encode($daftar_event);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memuat data event yang didaftar."]);
    }
    exit;
}

// ==========================================
// 10. AMBIL PESERTA PER EVENT (UNTUK PANITIA)
// ==========================================
if ($aksi === 'ambil_peserta_event') {
    $event_id = isset($_GET['event_id']) ? mysqli_real_escape_string($koneksi, $_GET['event_id']) : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
    
    if (empty($event_id)) {
        echo json_encode(["status" => "error", "message" => "Event ID tidak valid"]);
        exit;
    }
    
    $query = "SELECT 
                p.id as pendaftaran_id,
                p.user_id,
                p.event_id,
                p.kode_pendaftaran,
                p.nama_peserta,
                p.email_peserta,
                p.no_telepon,
                p.tanggal_daftar,
                p.status_pendaftaran,
                p.bukti_bayar,
                p.is_checked_in,
                p.check_in_time,
                p.status_kelulusan,
                p.nilai_kehadiran,
                e.nama_event,
                e.tanggal,
                e.waktu,
                e.lokasi,
                e.tipe_tiket,
                e.harga_event
              FROM pendaftaran_event p 
              JOIN events e ON p.event_id = e.id 
              WHERE p.event_id = '$event_id'";
    
    if (!empty($search)) {
        $query .= " AND (p.kode_pendaftaran LIKE '%$search%' 
                    OR p.nama_peserta LIKE '%$search%' 
                    OR p.email_peserta LIKE '%$search%'
                    OR p.no_telepon LIKE '%$search%')";
    }
    
    if (!empty($status)) {
        $query .= " AND p.status_pendaftaran = '$status'";
    }
    
    $query .= " ORDER BY p.tanggal_daftar DESC";
    
    $result = mysqli_query($koneksi, $query);
    $peserta_list = array();
    $stats = [
        'total' => 0,
        'waiting' => 0,
        'verified' => 0,
        'checked_in' => 0,
        'graduated' => 0
    ];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['is_checked_in'] == 1 && $row['status_pendaftaran'] !== 'Sudah Check-in') {
                $row['status_pendaftaran'] = 'Sudah Check-in';
            }
            $peserta_list[] = $row;
            
            $stats['total']++;
            if ($row['status_pendaftaran'] === 'Menunggu Verifikasi') $stats['waiting']++;
            elseif ($row['status_pendaftaran'] === 'Terdaftar') $stats['verified']++;
            elseif ($row['status_pendaftaran'] === 'Sudah Check-in') $stats['checked_in']++;
            
            if ($row['status_kelulusan'] === 'lulus') $stats['graduated']++;
        }
        echo json_encode([
            "status" => "success", 
            "peserta" => $peserta_list,
            "stats" => $stats
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memuat data peserta"]);
    }
    exit;
}

// ==========================================
// 11. VERIFIKASI PESERTA (PANITIA)
// ==========================================
if ($aksi === 'verifikasi_peserta') {
    $pendaftaran_id = isset($_POST['pendaftaran_id']) ? mysqli_real_escape_string($koneksi, $_POST['pendaftaran_id']) : '';
    $action = isset($_POST['action']) ? mysqli_real_escape_string($koneksi, $_POST['action']) : '';
    
    if (empty($pendaftaran_id) || empty($action)) {
        echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
        exit;
    }
    
    if ($action === 'verify') {
        $status = 'Terdaftar';
        $message = 'Peserta berhasil diverifikasi!';
    } elseif ($action === 'reject') {
        $status = 'Ditolak';
        $message = 'Pendaftaran peserta ditolak!';
    } else {
        echo json_encode(["status" => "error", "message" => "Aksi tidak dikenal"]);
        exit;
    }
    
    $query_update = "UPDATE pendaftaran_event SET status_pendaftaran = '$status' WHERE id = '$pendaftaran_id'";
    
    if (mysqli_query($koneksi, $query_update)) {
        echo json_encode(["status" => "success", "message" => $message]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal verifikasi: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 12. CHECK-IN PESERTA (PANITIA)
// ==========================================
if ($aksi === 'checkin_peserta') {
    $pendaftaran_id = isset($_POST['pendaftaran_id']) ? mysqli_real_escape_string($koneksi, $_POST['pendaftaran_id']) : '';
    
    if (empty($pendaftaran_id)) {
        echo json_encode(["status" => "error", "message" => "ID pendaftaran tidak valid"]);
        exit;
    }
    
    $checkin_time = date('Y-m-d H:i:s');
    $query_update = "UPDATE pendaftaran_event SET is_checked_in = 1, check_in_time = '$checkin_time' WHERE id = '$pendaftaran_id'";
    
    if (mysqli_query($koneksi, $query_update)) {
        echo json_encode(["status" => "success", "message" => "Check-in berhasil! Selamat datang di event."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal check-in: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 13. SIMPAN PENGELUARAN DIVISI
// ==========================================
if ($aksi === 'simpan_pengeluaran') {
    $event_id = mysqli_real_escape_string($koneksi, $_POST['event_id']);
    $divisi = mysqli_real_escape_string($koneksi, $_POST['divisi']);
    $pengeluaran = mysqli_real_escape_string($koneksi, $_POST['pengeluaran']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    if (empty($event_id) || empty($divisi) || empty($pengeluaran)) {
        echo json_encode(["status" => "error", "message" => "Event, divisi, dan nominal wajib diisi"]);
        exit;
    }
    
    if (!is_numeric($pengeluaran) || $pengeluaran < 0) {
        echo json_encode(["status" => "error", "message" => "Nominal pengeluaran harus berupa angka positif"]);
        exit;
    }
    
    $cek = mysqli_query($koneksi, "SELECT id FROM event_keuangan WHERE event_id = '$event_id' AND divisi = '$divisi'");
    if (mysqli_num_rows($cek) > 0) {
        $query = "UPDATE event_keuangan SET pengeluaran = '$pengeluaran', keterangan = '$keterangan', tanggal_catat = NOW() WHERE event_id = '$event_id' AND divisi = '$divisi'";
    } else {
        $query = "INSERT INTO event_keuangan (event_id, divisi, pengeluaran, keterangan) VALUES ('$event_id', '$divisi', '$pengeluaran', '$keterangan')";
    }
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => "Data pengeluaran $divisi berhasil disimpan"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 14. AMBIL DATA PENGELUARAN PER EVENT
// ==========================================
if ($aksi === 'ambil_pengeluaran') {
    $event_id = mysqli_real_escape_string($koneksi, $_GET['event_id']);
    
    if (empty($event_id)) {
        echo json_encode([]);
        exit;
    }
    
    $query = "SELECT divisi, pengeluaran, keterangan FROM event_keuangan WHERE event_id = '$event_id'";
    $result = mysqli_query($koneksi, $query);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['divisi']] = $row;
    }
    echo json_encode($data);
    exit;
}

// ==========================================
// 15. LAPORAN KEUANGAN LENGKAP
// ==========================================
if ($aksi === 'laporan_keuangan') {
    $event_id = mysqli_real_escape_string($koneksi, $_GET['event_id']);
    
    if (empty($event_id)) {
        echo json_encode(["status" => "error", "message" => "Event ID diperlukan"]);
        exit;
    }
    
    $event = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_event, tipe_tiket, harga_event FROM events WHERE id = '$event_id'"));
    if (!$event) {
        echo json_encode(["status" => "error", "message" => "Event tidak ditemukan"]);
        exit;
    }
    
    $query_pemasukan = "SELECT COUNT(*) as jumlah_peserta, SUM(CASE WHEN e.tipe_tiket = 'Berbayar' THEN e.harga_event ELSE 0 END) as total_pemasukan 
                        FROM pendaftaran_event p 
                        JOIN events e ON p.event_id = e.id 
                        WHERE p.event_id = '$event_id' AND p.status_pendaftaran IN ('Terdaftar', 'Sudah Check-in')";
    $pemasukan = mysqli_fetch_assoc(mysqli_query($koneksi, $query_pemasukan));
    
    $query_pengeluaran = "SELECT divisi, pengeluaran, keterangan FROM event_keuangan WHERE event_id = '$event_id'";
    $result_pengeluaran = mysqli_query($koneksi, $query_pengeluaran);
    $pengeluaran_list = [];
    $total_pengeluaran = 0;
    while ($row = mysqli_fetch_assoc($result_pengeluaran)) {
        $pengeluaran_list[] = $row;
        $total_pengeluaran += (int)$row['pengeluaran'];
    }
    
    $total_pemasukan = ($event['tipe_tiket'] == 'Berbayar') ? ((int)$pemasukan['total_pemasukan']) : 0;
    $jumlah_peserta = (int)$pemasukan['jumlah_peserta'];
    $saldo = $total_pemasukan - $total_pengeluaran;
    
    echo json_encode([
        "status" => "success",
        "nama_event" => $event['nama_event'],
        "tipe_tiket" => $event['tipe_tiket'],
        "harga_tiket" => (int)$event['harga_event'],
        "jumlah_peserta_terverifikasi" => $jumlah_peserta,
        "total_pemasukan" => $total_pemasukan,
        "pengeluaran" => $pengeluaran_list,
        "total_pengeluaran" => $total_pengeluaran,
        "saldo" => $saldo
    ]);
    exit;
}

// ==========================================
// 16. AMBIL EVENT SELESAI UNTUK FEEDBACK
// ==========================================
if ($aksi === 'ambil_event_selesai') {
    $user_id = isset($_GET['user_id']) ? mysqli_real_escape_string($koneksi, $_GET['user_id']) : '';
    
    if (empty($user_id)) {
        echo json_encode(["status" => "error", "message" => "User ID tidak valid"]);
        exit;
    }
    
    $query = "SELECT DISTINCT 
                e.id,
                e.id_event,
                e.nama_event,
                e.tanggal,
                e.waktu,
                e.tanggal_selesai,
                e.waktu_selesai,
                e.lokasi,
                e.banner_img,
                p.status_pendaftaran,
                (SELECT COUNT(*) FROM feedback_event WHERE event_id = e.id AND user_id = '$user_id') as sudah_feedback
              FROM events e
              JOIN pendaftaran_event p ON e.id = p.event_id
              WHERE p.user_id = '$user_id' 
                AND e.status_event = 'selesai'
                AND p.status_pendaftaran IN ('Terdaftar', 'Sudah Check-in')
              ORDER BY e.tanggal_selesai DESC";
    
    $result = mysqli_query($koneksi, $query);
    $events = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    
    echo json_encode($events);
    exit;
}

// ==========================================
// 17. UPDATE STATUS KELULUSAN PESERTA
// ==========================================
if ($aksi === 'update_status_kelulusan') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pendaftaran_id = isset($input['pendaftaran_id']) ? mysqli_real_escape_string($koneksi, $input['pendaftaran_id']) : '';
    $status_kelulusan = isset($input['status_kelulusan']) ? mysqli_real_escape_string($koneksi, $input['status_kelulusan']) : '';
    
    if (empty($pendaftaran_id) || empty($status_kelulusan)) {
        echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
        exit;
    }
    
    $allowed_status = ['lulus', 'tidak_lulus', 'pending'];
    if (!in_array($status_kelulusan, $allowed_status)) {
        echo json_encode(["status" => "error", "message" => "Status kelulusan tidak valid"]);
        exit;
    }
    
    $query = "UPDATE pendaftaran_event SET status_kelulusan = '$status_kelulusan' WHERE id = '$pendaftaran_id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => "Status kelulusan berhasil diupdate"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 18. UPDATE NILAI KEHADIRAN PESERTA
// ==========================================
if ($aksi === 'update_nilai_kehadiran') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pendaftaran_id = isset($input['pendaftaran_id']) ? mysqli_real_escape_string($koneksi, $input['pendaftaran_id']) : '';
    $nilai_kehadiran = isset($input['nilai_kehadiran']) ? (int)$input['nilai_kehadiran'] : NULL;
    
    if (empty($pendaftaran_id)) {
        echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
        exit;
    }
    
    if ($nilai_kehadiran !== NULL && ($nilai_kehadiran < 0 || $nilai_kehadiran > 100)) {
        echo json_encode(["status" => "error", "message" => "Nilai kehadiran harus antara 0-100"]);
        exit;
    }
    
    $query = "UPDATE pendaftaran_event SET nilai_kehadiran = " . ($nilai_kehadiran !== NULL ? "'$nilai_kehadiran'" : "NULL") . " WHERE id = '$pendaftaran_id'";
    
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["status" => "success", "message" => "Nilai kehadiran berhasil diupdate"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// DEFAULT RESPONSE
// ==========================================
echo json_encode([
    "status" => "error", 
    "message" => "Aksi tidak dikenal: " . $aksi
]);
exit;
?>