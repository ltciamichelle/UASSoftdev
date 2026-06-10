<?php
// ==========================================
// 1. KONEKSI DATABASE & PENGATURAN AWAL
// ==========================================
include 'koneksi.php';

// Fungsi kompresi gambar dan konversi ke WebP
function compressAndConvertToWebP($source, $destination, $quality = 80, $max_width = 1200) {
    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];
    if ($mime == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($mime == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($mime == 'image/png') {
        $image = imagecreatefrompng($source);
    } elseif ($mime == 'image/webp') {
        $image = imagecreatefromwebp($source);
    } else {
        return false;
    }

    if (!$image) return false;

    $width = $info[0];
    $height = $info[1];
    
    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = floor($height * ($max_width / $width));
    } else {
        $new_width = $width;
        $new_height = $height;
    }

    $new_image = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency
    if ($mime == 'image/png' || $mime == 'image/gif' || $mime == 'image/webp') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }

    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    if (function_exists('imagewebp')) {
        $result = imagewebp($new_image, $destination, $quality);
    } else {
        // Fallback to jpeg if webp not supported by GD
        $destination = str_replace('.webp', '.jpg', $destination);
        $result = imagejpeg($new_image, $destination, $quality);
    }

    imagedestroy($image);
    imagedestroy($new_image);

    return $result;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

// ==========================================
// 2. PROSES TAMBAH EVENT
// ==========================================
if ($aksi === 'tambah_event') {
    header('Content-Type: application/json');

    $nama_event       = mysqli_real_escape_string($koneksi, $_POST['nama_event']);
    $kategori         = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $tanggal          = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $waktu            = mysqli_real_escape_string($koneksi, $_POST['waktu']);
    $tanggal_selesai  = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $waktu_selesai    = mysqli_real_escape_string($koneksi, $_POST['waktu_selesai']);
    $lokasi           = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $tipe_tiket       = mysqli_real_escape_string($koneksi, $_POST['tipe_tiket']);
    $harga_event      = isset($_POST['harga_event']) ? (int)$_POST['harga_event'] : 0;
    $slot_kursi       = isset($_POST['slot_kursi']) ? (int)$_POST['slot_kursi'] : 0;
    $user_id          = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if (empty($nama_event) || empty($kategori) || empty($tanggal) || empty($waktu) || empty($lokasi)) {
        echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi!"]);
        exit;
    }

    $nama_file_gambar = "";
    if (isset($_FILES['banner_img']) && $_FILES['banner_img']['error'] === 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $ekstensi_file = strtolower(pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES["banner_img"]["tmp_name"]);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($_FILES["banner_img"]["tmp_name"]);
        } else {
            $mime = $_FILES["banner_img"]["type"];
        }

        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($ekstensi_file, $allowed_ext) || !in_array($mime, $allowed_mime)) {
            echo json_encode(["status" => "error", "message" => "Format file tidak didukung! Hanya gambar yang diizinkan."]);
            exit;
        }

        $nama_file_gambar = time() . '_' . uniqid() . '.webp';
        $target_file = $target_dir . $nama_file_gambar;
        
        // Kompres dan ubah ke webp
        if (!compressAndConvertToWebP($_FILES["banner_img"]["tmp_name"], $target_file)) {
            // Fallback jika GD error
            $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
            $target_file = $target_dir . $nama_file_gambar;
            move_uploaded_file($_FILES["banner_img"]["tmp_name"], $target_file);
        }
    }

    $query_insert = "INSERT INTO events (user_id, nama_event, kategori, tanggal, waktu, tanggal_selesai, waktu_selesai, lokasi, tipe_tiket, harga_event, slot_kursi, banner_img) 
                     VALUES ($user_id, '$nama_event', '$kategori', '$tanggal', '$waktu', '$tanggal_selesai', '$waktu_selesai', '$lokasi', '$tipe_tiket', $harga_event, '$slot_kursi', '$nama_file_gambar')";

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

    $query_select = "SELECT * FROM events WHERE user_id = '$id_panitia' ORDER BY id DESC";
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
    $nama_event       = mysqli_real_escape_string($koneksi, $_POST['nama_event']);
    $kategori         = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $tanggal          = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $waktu            = mysqli_real_escape_string($koneksi, $_POST['waktu']);
    $tanggal_selesai  = mysqli_real_escape_string($koneksi, $_POST['tanggal_selesai']);
    $waktu_selesai    = mysqli_real_escape_string($koneksi, $_POST['waktu_selesai']);
    $lokasi           = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $tipe_tiket       = mysqli_real_escape_string($koneksi, $_POST['tipe_tiket']);
    $slot_kursi       = isset($_POST['slot_kursi']) ? mysqli_real_escape_string($koneksi, $_POST['slot_kursi']) : 0;

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

        $ekstensi_file = strtolower(pathinfo($_FILES["banner_img"]["name"], PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES["banner_img"]["tmp_name"]);
        finfo_close($finfo);
        $allowed_mime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($ekstensi_file, $allowed_ext) || !in_array($mime, $allowed_mime)) {
            echo json_encode(["status" => "error", "message" => "Format file tidak didukung! Hanya gambar yang diizinkan."]);
            exit;
        }

        $nama_file_gambar = time() . '_' . uniqid() . '.webp';
        $target_file = $target_dir . $nama_file_gambar;
        
        if (!compressAndConvertToWebP($_FILES["banner_img"]["tmp_name"], $target_file)) {
            $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
            move_uploaded_file($_FILES["banner_img"]["tmp_name"], $target_dir . $nama_file_gambar);
        }
    }

    $query_update = "UPDATE events SET 
                        nama_event = '$nama_event', 
                        kategori = '$kategori', 
                        tanggal = '$tanggal', 
                        waktu = '$waktu', 
                        tanggal_selesai = '$tanggal_selesai', 
                        waktu_selesai = '$waktu_selesai', 
                        lokasi = '$lokasi', 
                        tipe_tiket = '$tipe_tiket', 
                        slot_kursi = '$slot_kursi', 
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

// ==========================================
// 7. PROSES TAMBAH VIEW EVENT
// ==========================================
if ($aksi === 'tambah_view') {
    header('Content-Type: application/json');
    $event_id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

    if (!empty($event_id)) {
        $query = "UPDATE events SET views = views + 1 WHERE id = '$event_id'";
        mysqli_query($koneksi, $query);
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ID tidak valid."]);
    }
    exit;
}
?>