<?php
error_reporting(0);
try {
    include 'koneksi.php';
    $data = json_decode(file_get_contents("php://input"), true);
    $aksi = isset($data['aksi']) ? $data['aksi'] : '';

    // ==========================================
    // 1. LOGIN
    // ==========================================
    if ($aksi === 'login') {
        header('Content-Type: application/json');
        $email = mysqli_real_escape_string($koneksi, $data['email']);
        $password = $data['password'];

        $query = "SELECT * FROM User WHERE Email = '$email'";
        $result = mysqli_query($koneksi, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['Password'])) {
                // Jangan kirim password ke frontend
                unset($user['Password']);
                echo json_encode(["status" => "success", "message" => "Login berhasil!", "data" => $user]);
            } else {
                echo json_encode(["status" => "error", "message" => "Password salah!"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Email tidak ditemukan!"]);
        }
        exit;
    }

    // ==========================================
    // 2. REGISTER
    // ==========================================
    if ($aksi === 'daftar') {
        header('Content-Type: application/json');
        
        $nama = mysqli_real_escape_string($koneksi, $data['nama']);
        $email = mysqli_real_escape_string($koneksi, $data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $role = isset($data['role']) ? mysqli_real_escape_string($koneksi, $data['role']) : 'user';
        $no_hp = isset($data['no_hp']) ? mysqli_real_escape_string($koneksi, $data['no_hp']) : '';

        // Cek email ganda
        $cek = mysqli_query($koneksi, "SELECT Id_User FROM User WHERE Email = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            echo json_encode(["status" => "error", "message" => "Email sudah terdaftar!"]);
            exit;
        }

        $query = "INSERT INTO User (Nama, Email, Password, Role, No_HP) VALUES ('$nama', '$email', '$password', '$role', '$no_hp')";
        if (mysqli_query($koneksi, $query)) {
            echo json_encode(["status" => "success", "message" => "Registrasi berhasil! Silakan login."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal registrasi: " . mysqli_error($koneksi)]);
        }
        exit;
    }

    // ==========================================
    // 3. UPDATE PROFIL
    // ==========================================
    if ($aksi === 'update_profil') {
        header('Content-Type: application/json');
        $id_user = (int)$data['Id_User'];
        $nama = mysqli_real_escape_string($koneksi, $data['Nama']);
        $no_hp = mysqli_real_escape_string($koneksi, $data['No_HP']);

        $query = "UPDATE User SET Nama = '$nama', No_HP = '$no_hp' WHERE Id_User = $id_user";
        if (mysqli_query($koneksi, $query)) {
            // Ambil data terbaru
            $res = mysqli_query($koneksi, "SELECT * FROM User WHERE Id_User = $id_user");
            $user = mysqli_fetch_assoc($res);
            unset($user['Password']);
            echo json_encode(["status" => "success", "message" => "Profil berhasil diperbarui!", "data" => $user]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal update profil"]);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>