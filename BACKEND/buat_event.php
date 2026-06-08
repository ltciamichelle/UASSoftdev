<?php
// ==========================================
// 1. KONEKSI DATABASE & PENGATURAN AWAL
// ==========================================
include 'koneksi.php';

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

// ==========================================
// 2. PROSES TAMBAH EVENT
// ==========================================
if ($aksi === 'tambah_event') {
    header('Content-Type: application/json');

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
    $slot_kursi       = isset($_POST['slot_kursi']) ? mysqli_real_escape_string($koneksi, $_POST['slot_kursi']) : 0;
    $deskripsi        = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    if (empty($nama_event) || empty($kategori) || empty($tanggal) || empty($waktu) || empty($tanggal_selesai) || empty($waktu_selesai) || empty($lokasi) || empty($tipe_tiket) || empty($id_panitia)) {
        echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi!"]);
        exit;
    }

    $nama_file_gambar = "";
    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] === 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $ekstensi_file = pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION);
        $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
        $target_file = $target_dir . $nama_file_gambar;
        
        move_uploaded_file($_FILES["banner_img"]["tmp_name"], $target_file);
    }

    $query_insert = "INSERT INTO events (id_event, nama_event, id_panitia, kategori, tanggal, waktu, tanggal_selesai, waktu_selesai, lokasi, tipe_tiket, slot_kursi, deskripsi, banner_img) 
                     VALUES ('$id_event', '$nama_event', '$id_panitia', '$kategori', '$tanggal', '$waktu', '$tanggal_selesai', '$waktu_selesai', '$lokasi', '$tipe_tiket', '$slot_kursi', '$deskripsi', '$nama_file_gambar')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo json_encode(["status" => "success", "message" => "Event berhasil dibuat dan dipublikasikan!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database: " . mysqli_error($koneksi)]);
    }
    exit;
}

// ==========================================
// 3. PROSES AMBIL DATA EVENT (UMUM)
// ==========================================
if ($aksi === 'ambil_event') {
    header('Content-Type: application/json');

    $query_select = "SELECT * FROM events ORDER BY id DESC";
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
// NEW FITUR: AMBIL EVENT KHUSUS PEMBUAT / PANITIA AKTIF
// ==========================================
if ($aksi === 'ambil_event_panitia') {
    header('Content-Type: application/json');
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
// 4. PROSES AMBIL DATA EVENT DETAIL BY ID
// ==========================================
if ($aksi === 'cari_event_id') {
    header('Content-Type: application/json');
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
// 5. PROSES UPDATE EVENT
// ==========================================
if ($aksi === 'update_event') {
    header('Content-Type: application/json');

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
    $slot_kursi       = isset($_POST['slot_kursi']) ? mysqli_real_escape_string($koneksi, $_POST['slot_kursi']) : 0;
    $deskripsi        = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    if (empty($event_id) || empty($nama_event) || empty($kategori) || empty($tanggal) || empty($waktu) || empty($tanggal_selesai) || empty($waktu_selesai) || empty($lokasi) || empty($tipe_tiket)) {
        echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi untuk memperbarui data!"]);
        exit;
    }

    $query_lama = mysqli_query($koneksi, "SELECT banner_img FROM events WHERE id = '$event_id'");
    $data_lama = mysqli_fetch_assoc($query_lama);
    $nama_file_gambar = $data_lama['banner_img'];

    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] === 0) {
        $target_dir = "uploads/";
        if (!empty($nama_file_gambar) && file_exists($target_dir . $nama_file_gambar)) {
            unlink($target_dir . $nama_file_gambar);
        }

        $ekstensi_file = pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION);
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
// 6. PROSES HAPUS EVENT
// ==========================================
if ($aksi === 'hapus_event') {
    header('Content-Type: application/json');
    
    $input_data = json_decode(file_get_contents('php://input'), true);
    $event_id = isset($input_data['event_id']) ? mysqli_real_escape_string($koneksi, $input_data['event_id']) : '';

    if (empty($event_id)) {
        echo json_encode(["status" => "error", "message" => "ID Event tidak terdeteksi."]);
        exit;
    }

    $query_gambar = mysqli_query($koneksi, "SELECT banner_img FROM events WHERE id = '$event_id'");
    if (mysqli_num_rows($query_gambar) > 0) {
        $data = mysqli_fetch_assoc($query_gambar);
        if (!empty($data['banner_img']) && file_exists("uploads/" . $data['banner_img'])) {
            unlink("uploads/" . $data['banner_img']); 
        }
    }

    $query_delete = "DELETE FROM events WHERE id = '$event_id'";
    if (mysqli_query($koneksi, $query_delete)) {
        echo json_encode(["status" => "success", "message" => "Event berhasil dihapus dari sistem!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menghapus event: " . mysqli_error($koneksi)]);
    }
    exit;
}
?>