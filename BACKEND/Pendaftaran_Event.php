<?php
error_reporting(0);
try {
    include 'koneksi.php';
    $aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

    // ==========================================
    // 1. DAFTAR EVENT
    // ==========================================
    if ($aksi === 'daftar_event') {
        header('Content-Type: application/json');
        
        $id_user = (int)$_POST['Id_User'];
        $id_event = (int)$_POST['Id_Event'];

        if (empty($id_user) || empty($id_event)) {
            echo json_encode(["status" => "error", "message" => "Data pendaftaran tidak lengkap!"]);
            exit;
        }

        // Cek apakah sudah terdaftar
        $cek_daftar = mysqli_query($koneksi, "SELECT Id_Pendaftaran FROM Pendaftaran_Event WHERE Id_User = $id_user AND Id_Event = $id_event");
        if (mysqli_num_rows($cek_daftar) > 0) {
            echo json_encode(["status" => "error", "message" => "Anda sudah terdaftar di event ini."]);
            exit;
        }

        $query_insert = "INSERT INTO Pendaftaran_Event (Id_User, Id_Event, Status_Pendaftaran) 
                         VALUES ($id_user, $id_event, 'Terdaftar')";

        if (mysqli_query($koneksi, $query_insert)) {
            echo json_encode(["status" => "success", "message" => "Pendaftaran berhasil!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan pendaftaran: " . mysqli_error($koneksi)]);
        }
        exit;
    }

    // ==========================================
    // 2. AMBIL EVENT YANG DIIKUTI USER
    // ==========================================
    if ($aksi === 'event_diikuti') {
        header('Content-Type: application/json');
        $id_user = isset($_GET['Id_User']) ? (int)$_GET['Id_User'] : 0;
        
        $query = "SELECT E.*, K.Nama_Kategori, P.Id_Pendaftaran, P.Status_Pendaftaran, P.Tanggal_Daftar, S.File_Sertifikat 
                  FROM Pendaftaran_Event P 
                  JOIN Event E ON P.Id_Event = E.Id_Event 
                  LEFT JOIN Kategori K ON E.Id_Kategori = K.Id_Kategori
                  LEFT JOIN Sertifikat S ON P.Id_Pendaftaran = S.Id_Pendaftaran
                  WHERE P.Id_User = $id_user 
                  ORDER BY P.Tanggal_Daftar DESC";
                  
        $result = mysqli_query($koneksi, $query);
        $data = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        exit;
    }

    // ==========================================
    // 3. SUBMIT FEEDBACK
    // ==========================================
    if ($aksi === 'submit_feedback') {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) $data = $_POST; // fallback

        $id_event = (int)$data['Id_Event'];
        $id_user = (int)$data['Id_User'];
        $rating = (int)$data['Rating'];
        $isi_feedback = mysqli_real_escape_string($koneksi, $data['Isi_Feedback']);

        if (empty($id_event) || empty($id_user) || empty($rating) || empty($isi_feedback)) {
            echo json_encode(['status' => 'error', 'message' => 'Semua bidang wajib diisi']);
            exit;
        }

        // Cek apakah sudah pernah submit feedback
        $check_fb = mysqli_query($koneksi, "SELECT Id_Feedback FROM Feedback WHERE Id_Event = $id_event AND Id_User = $id_user");
        
        if (mysqli_num_rows($check_fb) > 0) {
            $update = "UPDATE Feedback SET Rating = $rating, Isi_Feedback = '$isi_feedback', Tanggal_Feedback = CURRENT_TIMESTAMP WHERE Id_Event = $id_event AND Id_User = $id_user";
            if (mysqli_query($koneksi, $update)) {
                echo json_encode(['status' => 'success', 'message' => 'Feedback berhasil diperbarui!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate feedback']);
            }
        } else {
            $insert = "INSERT INTO Feedback (Id_Event, Id_User, Rating, Isi_Feedback) VALUES ($id_event, $id_user, $rating, '$isi_feedback')";
            if (mysqli_query($koneksi, $insert)) {
                echo json_encode(['status' => 'success', 'message' => 'Terima kasih atas feedback Anda!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan feedback']);
            }
        }
        exit;
    }

    // ==========================================
    // 4. AMBIL PENDAFTAR EVENT (PANITIA)
    // ==========================================
    if ($aksi === 'ambil_pendaftar_event') {
        header('Content-Type: application/json');
        $id_event = isset($_GET['Id_Event']) ? (int)$_GET['Id_Event'] : 0;
        
        $query = "SELECT P.*, U.Nama AS nama_lengkap, U.Email AS email, U.No_HP AS no_wa, S.File_Sertifikat 
                  FROM Pendaftaran_Event P 
                  JOIN User U ON P.Id_User = U.Id_User 
                  LEFT JOIN Sertifikat S ON P.Id_Pendaftaran = S.Id_Pendaftaran
                  WHERE P.Id_Event = $id_event 
                  ORDER BY P.Tanggal_Daftar DESC";
                  
        $result = mysqli_query($koneksi, $query);
        $data = array();
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        exit;
    }

    // ==========================================
    // 5. HITUNG PENDAFTAR (PANITIA)
    // ==========================================
    if ($aksi === 'hitung_pendaftar') {
        header('Content-Type: application/json');
        $id_event = isset($_GET['Id_Event']) ? (int)$_GET['Id_Event'] : 0;
        
        $query = "SELECT COUNT(Id_Pendaftaran) AS total FROM Pendaftaran_Event WHERE Id_Event = $id_event";
        $result = mysqli_query($koneksi, $query);
        $data = array("total" => 0);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $data["total"] = (int)$row["total"];
        }
        echo json_encode($data);
        exit;
    }

    // ==========================================
    // 6. VERIFIKASI PEMBAYARAN (PANITIA)
    // ==========================================
    if ($aksi === 'verifikasi_pembayaran') {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);
        
        $id_pendaftaran = (int)$data['registrasi_id'];
        $status = mysqli_real_escape_string($koneksi, $data['status']);
        
        $update = "UPDATE Pendaftaran_Event SET Status_Pendaftaran = '$status' WHERE Id_Pendaftaran = $id_pendaftaran";
        if (mysqli_query($koneksi, $update)) {
            echo json_encode(['status' => 'success', 'message' => 'Status pendaftaran berhasil diperbarui!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status pendaftaran']);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
