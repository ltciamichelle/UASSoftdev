<?php
error_reporting(0);
try {
    include 'koneksi.php';

    function compressAndConvertToWebP($source, $destination, $quality = 80, $max_width = 1200) {
        $info = getimagesize($source);
        if (!$info) return false;

        $mime = $info['mime'];
        if ($mime == 'image/jpeg') $image = imagecreatefromjpeg($source);
        elseif ($mime == 'image/gif') $image = imagecreatefromgif($source);
        elseif ($mime == 'image/png') $image = imagecreatefrompng($source);
        elseif ($mime == 'image/webp') $image = imagecreatefromwebp($source);
        else return false;

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
            $destination = str_replace('.webp', '.jpg', $destination);
            $result = imagejpeg($new_image, $destination, $quality);
        }

        imagedestroy($image);
        imagedestroy($new_image);
        return $result;
    }

    $aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

    // ==========================================
    // 1. GET ALL CATEGORIES
    // ==========================================
    if ($aksi === 'get_kategori') {
        header('Content-Type: application/json');
        $res = mysqli_query($koneksi, "SELECT * FROM Kategori");
        $data = [];
        while($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        exit;
    }

    // ==========================================
    // 2. CREATE EVENT
    // ==========================================
    if ($aksi === 'tambah_event') {
        header('Content-Type: application/json');

        $nama_event       = mysqli_real_escape_string($koneksi, $_POST['Nama_Event']);
        $id_kategori      = (int)$_POST['Id_Kategori'];
        $tanggal_event    = mysqli_real_escape_string($koneksi, $_POST['Tanggal_Event']);
        $lokasi           = mysqli_real_escape_string($koneksi, $_POST['Lokasi']);
        $deskripsi        = isset($_POST['Deskripsi']) ? mysqli_real_escape_string($koneksi, $_POST['Deskripsi']) : '';
        $id_user          = isset($_POST['Id_User']) ? (int)$_POST['Id_User'] : 0;

        if (empty($nama_event) || empty($id_kategori) || empty($tanggal_event) || empty($lokasi)) {
            echo json_encode(["status" => "error", "message" => "Semua bidang wajib diisi!"]);
            exit;
        }

        $nama_file_gambar = "";
        if (isset($_FILES['Poster_Event']) && $_FILES['Poster_Event']['error'] === 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
            
            $ekstensi_file = strtolower(pathinfo($_FILES["Poster_Event"]["name"], PATHINFO_EXTENSION));
            $nama_file_gambar = time() . '_' . uniqid() . '.webp';
            $target_file = $target_dir . $nama_file_gambar;
            
            if (!compressAndConvertToWebP($_FILES["Poster_Event"]["tmp_name"], $target_file)) {
                $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
                $target_file = $target_dir . $nama_file_gambar;
                move_uploaded_file($_FILES["Poster_Event"]["tmp_name"], $target_file);
            }
        }

        $query_insert = "INSERT INTO Event (Id_User, Id_Kategori, Nama_Event, Deskripsi, Lokasi, Tanggal_Event, Poster_Event) 
                         VALUES ($id_user, $id_kategori, '$nama_event', '$deskripsi', '$lokasi', '$tanggal_event', '$nama_file_gambar')";

        if (mysqli_query($koneksi, $query_insert)) {
            echo json_encode(["status" => "success", "message" => "Event berhasil dibuat!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database: " . mysqli_error($koneksi)]);
        }
        exit;
    }

    // ==========================================
    // 3. GET ALL EVENTS
    // ==========================================
    if ($aksi === 'ambil_event') {
        header('Content-Type: application/json');

        $query_select = "SELECT E.*, K.Nama_Kategori FROM Event E LEFT JOIN Kategori K ON E.Id_Kategori = K.Id_Kategori ORDER BY E.Id_Event DESC";
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
    // 4. GET EVENT BY USER ID
    // ==========================================
    if ($aksi === 'ambil_event_panitia') {
        header('Content-Type: application/json');
        $id_user = isset($_GET['Id_User']) ? (int)$_GET['Id_User'] : 0;

        $query = "SELECT E.*, K.Nama_Kategori FROM Event E LEFT JOIN Kategori K ON E.Id_Kategori = K.Id_Kategori WHERE E.Id_User = $id_user ORDER BY E.Id_Event DESC";
        $result = mysqli_query($koneksi, $query);
        $events = [];
        if ($result) {
            while($row = mysqli_fetch_assoc($result)){
                $events[] = $row;
            }
        }
        echo json_encode($events);
        exit;
    }

    // ==========================================
    // 5. UPDATE EVENT
    // ==========================================
    if ($aksi === 'update_event') {
        header('Content-Type: application/json');

        $id_event         = (int)$_POST['Id_Event'];
        $id_user          = (int)$_POST['Id_User'];
        $nama_event       = mysqli_real_escape_string($koneksi, $_POST['Nama_Event']);
        $id_kategori      = (int)$_POST['Id_Kategori'];
        $tanggal_event    = mysqli_real_escape_string($koneksi, $_POST['Tanggal_Event']);
        $lokasi           = mysqli_real_escape_string($koneksi, $_POST['Lokasi']);
        $deskripsi        = isset($_POST['Deskripsi']) ? mysqli_real_escape_string($koneksi, $_POST['Deskripsi']) : '';

        // Check ownership
        $cek = mysqli_query($koneksi, "SELECT Id_User, Poster_Event FROM Event WHERE Id_Event = $id_event");
        $data_lama = mysqli_fetch_assoc($cek);
        if ($data_lama['Id_User'] != $id_user) {
            echo json_encode(["status" => "error", "message" => "Akses ditolak."]);
            exit;
        }

        $nama_file_gambar = $data_lama['Poster_Event'];
        if (isset($_FILES['Poster_Event']) && $_FILES['Poster_Event']['error'] === 0) {
            $target_dir = "uploads/";
            $ekstensi_file = strtolower(pathinfo($_FILES["Poster_Event"]["name"], PATHINFO_EXTENSION));
            $nama_file_gambar = time() . '_' . uniqid() . '.webp';
            $target_file = $target_dir . $nama_file_gambar;
            
            if (!compressAndConvertToWebP($_FILES["Poster_Event"]["tmp_name"], $target_file)) {
                $nama_file_gambar = time() . '_' . uniqid() . '.' . $ekstensi_file;
                $target_file = $target_dir . $nama_file_gambar;
                move_uploaded_file($_FILES["Poster_Event"]["tmp_name"], $target_file);
            }
            if (!empty($data_lama['Poster_Event']) && file_exists("uploads/" . $data_lama['Poster_Event'])) {
                unlink("uploads/" . $data_lama['Poster_Event']);
            }
        }

        $query = "UPDATE Event SET 
                  Id_Kategori = $id_kategori,
                  Nama_Event = '$nama_event',
                  Deskripsi = '$deskripsi',
                  Lokasi = '$lokasi',
                  Tanggal_Event = '$tanggal_event',
                  Poster_Event = '$nama_file_gambar'
                  WHERE Id_Event = $id_event AND Id_User = $id_user";

        if (mysqli_query($koneksi, $query)) {
            echo json_encode(["status" => "success", "message" => "Event berhasil diperbarui!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal update: " . mysqli_error($koneksi)]);
        }
        exit;
    }

    // ==========================================
    // 6. DELETE EVENT
    // ==========================================
    if ($aksi === 'hapus_event') {
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents("php://input"), true);
        $id_event = (int)$data['Id_Event'];
        $id_user = (int)$data['Id_User'];

        $cek = mysqli_query($koneksi, "SELECT Poster_Event FROM Event WHERE Id_Event = $id_event AND Id_User = $id_user");
        if(mysqli_num_rows($cek) == 0) {
             echo json_encode(["status" => "error", "message" => "Event tidak ditemukan atau bukan milik Anda"]);
             exit;
        }

        $row = mysqli_fetch_assoc($cek);
        if (!empty($row['Poster_Event']) && file_exists("uploads/" . $row['Poster_Event'])) {
            unlink("uploads/" . $row['Poster_Event']); 
        }

        $query_hapus = "DELETE FROM Event WHERE Id_Event = $id_event AND Id_User = $id_user";
        if (mysqli_query($koneksi, $query_hapus)) {
            echo json_encode(["status" => "success", "message" => "Event berhasil dihapus"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menghapus event"]);
        }
        exit;
    }
    
    // ==========================================
    // 7. GET SINGLE EVENT
    // ==========================================
    if ($aksi === 'get_event_by_id') {
        header('Content-Type: application/json');
        $id_event = isset($_GET['Id_Event']) ? (int)$_GET['Id_Event'] : 0;
        $query = "SELECT * FROM Event WHERE Id_Event = $id_event";
        $res = mysqli_query($koneksi, $query);
        if($row = mysqli_fetch_assoc($res)){
             echo json_encode(["status" => "success", "data" => $row]);
        } else {
             echo json_encode(["status" => "error", "message" => "Event tidak ditemukan"]);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>