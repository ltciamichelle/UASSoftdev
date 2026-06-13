<?php
error_reporting(0);
try {
    include 'koneksi.php';
    $aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';

    if ($aksi === 'upload') {
        header('Content-Type: application/json');
        
        $id_pendaftaran = isset($_POST['Id_Pendaftaran']) ? (int)$_POST['Id_Pendaftaran'] : 0;
        
        if (empty($id_pendaftaran)) {
            echo json_encode(["status" => "error", "message" => "ID Pendaftaran tidak valid."]);
            exit;
        }

        if (isset($_FILES['Sertifikat_File']) && $_FILES['Sertifikat_File']['error'] === 0) {
            $target_dir = "uploads/sertifikat/";
            if (!is_dir($target_dir)) {
                if (!mkdir($target_dir, 0755, true)) {
                    echo json_encode(["status" => "error", "message" => "Gagal membuat folder uploads/sertifikat."]);
                    exit;
                }
            }
            
            $ekstensi_file = strtolower(pathinfo($_FILES["Sertifikat_File"]["name"], PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($ekstensi_file, $allowed_ext)) {
                 echo json_encode(["status" => "error", "message" => "Ekstensi file tidak diizinkan. Harap upload PDF, JPG, atau PNG."]);
                 exit;
            }

            $nama_file_sertifikat = 'Sertifikat_' . $id_pendaftaran . '_' . time() . '.' . $ekstensi_file;
            $target_file = $target_dir . $nama_file_sertifikat;
            
            if (!move_uploaded_file($_FILES["Sertifikat_File"]["tmp_name"], $target_file)) {
                echo json_encode(["status" => "error", "message" => "Gagal mengunggah file sertifikat."]);
                exit;
            }

            // Cek apakah sudah ada sertifikat sebelumnya
            $cek = mysqli_query($koneksi, "SELECT Id_Sertifikat FROM Sertifikat WHERE Id_Pendaftaran = $id_pendaftaran");
            if (mysqli_num_rows($cek) > 0) {
                $query = "UPDATE Sertifikat SET File_Sertifikat = '$nama_file_sertifikat', Tanggal_Upload = CURRENT_TIMESTAMP WHERE Id_Pendaftaran = $id_pendaftaran";
            } else {
                $query = "INSERT INTO Sertifikat (Id_Pendaftaran, File_Sertifikat) VALUES ($id_pendaftaran, '$nama_file_sertifikat')";
            }

            if (mysqli_query($koneksi, $query)) {
                echo json_encode(["status" => "success", "message" => "Sertifikat berhasil diunggah!"]);
            } else {
                echo json_encode(["status" => "error", "message" => "Gagal menyimpan data ke database: " . mysqli_error($koneksi)]);
            }
        } else {
            $error_code = isset($_FILES['Sertifikat_File']) ? $_FILES['Sertifikat_File']['error'] : 'Tidak ada file';
            echo json_encode(["status" => "error", "message" => "Tidak ada file yang diunggah atau terjadi error (Kode: $error_code)."]);
        }
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Terjadi kesalahan sistem: " . $e->getMessage()]);
}
?>
