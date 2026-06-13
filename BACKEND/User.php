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

        $query = "
            SELECT u.id as Id_User, u.role as Role, u.password as Password,
                   COALESCE(m.email, nm.email, p.email) as Email,
                   COALESCE(m.nama, nm.nama, p.nama) as Nama,
                   COALESCE(m.phone, nm.phone, p.phone) as No_HP,
                   m.nim as NIM, m.fakultas as Fakultas, m.prodi as Prodi, nm.pekerjaan as Pekerjaan
            FROM users u
            LEFT JOIN mahasiswa m ON u.id = m.user_id
            LEFT JOIN non_mahasiswa nm ON u.id = nm.user_id
            LEFT JOIN panitia p ON u.id = p.user_id
            WHERE m.email = '$email' OR nm.email = '$email' OR p.email = '$email'
        ";
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
        $role = mysqli_real_escape_string($koneksi, isset($data['role']) ? $data['role'] : 'mahasiswa');
        $no_hp = isset($data['no_hp']) ? mysqli_real_escape_string($koneksi, $data['no_hp']) : '';
        
        $nim = isset($data['nim']) ? mysqli_real_escape_string($koneksi, $data['nim']) : '';
        $fakultas = isset($data['fakultas']) ? mysqli_real_escape_string($koneksi, $data['fakultas']) : '';
        $prodi = isset($data['prodi']) ? mysqli_real_escape_string($koneksi, $data['prodi']) : '';
        $pekerjaan = isset($data['pekerjaan']) ? mysqli_real_escape_string($koneksi, $data['pekerjaan']) : '';

        // Cek email ganda
        $cek = mysqli_query($koneksi, "
            SELECT email FROM mahasiswa WHERE email = '$email'
            UNION
            SELECT email FROM non_mahasiswa WHERE email = '$email'
            UNION
            SELECT email FROM panitia WHERE email = '$email'
        ");
        if (mysqli_num_rows($cek) > 0) {
            echo json_encode(["status" => "error", "message" => "Email sudah terdaftar!"]);
            exit;
        }

        // Generate loginId and username
        $prefix = ($role == 'panitia') ? 'PNT-' : 'USR-';
        $loginId = $prefix . rand(1000, 9999);
        $username = explode('@', $email)[0] . rand(10, 99);

        mysqli_begin_transaction($koneksi);
        try {
            $query_user = "INSERT INTO users (role, loginId, username, password) VALUES ('$role', '$loginId', '$username', '$password')";
            mysqli_query($koneksi, $query_user);
            $user_id = mysqli_insert_id($koneksi);

            if ($role === 'mahasiswa') {
                $query_detail = "INSERT INTO mahasiswa (user_id, nama, email, phone, nim, fakultas, prodi) VALUES ($user_id, '$nama', '$email', '$no_hp', '$nim', '$fakultas', '$prodi')";
            } else if ($role === 'panitia') {
                $query_detail = "INSERT INTO panitia (user_id, nama, email, phone, nim) VALUES ($user_id, '$nama', '$email', '$no_hp', '$nim')";
            } else {
                $query_detail = "INSERT INTO non_mahasiswa (user_id, nama, email, phone, pekerjaan) VALUES ($user_id, '$nama', '$email', '$no_hp', '$pekerjaan')";
            }
            mysqli_query($koneksi, $query_detail);
            mysqli_commit($koneksi);
            
            echo json_encode(["status" => "success", "message" => "Registrasi berhasil! Silakan login."]);
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo json_encode(["status" => "error", "message" => "Gagal registrasi: " . $e->getMessage()]);
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

        $role_query = mysqli_query($koneksi, "SELECT role FROM users WHERE id = $id_user");
        $role_row = mysqli_fetch_assoc($role_query);
        if ($role_row) {
            $role = $role_row['role'];
            if ($role === 'mahasiswa') {
                $query = "UPDATE mahasiswa SET nama = '$nama', phone = '$no_hp' WHERE user_id = $id_user";
            } else if ($role === 'panitia') {
                $query = "UPDATE panitia SET nama = '$nama', phone = '$no_hp' WHERE user_id = $id_user";
            } else {
                $query = "UPDATE non_mahasiswa SET nama = '$nama', phone = '$no_hp' WHERE user_id = $id_user";
            }
            
            if (mysqli_query($koneksi, $query)) {
                $res = mysqli_query($koneksi, "
                    SELECT u.id as Id_User, u.role as Role,
                           COALESCE(m.email, nm.email, p.email) as Email,
                           COALESCE(m.nama, nm.nama, p.nama) as Nama,
                           COALESCE(m.phone, nm.phone, p.phone) as No_HP,
                           m.nim as NIM, m.fakultas as Fakultas, m.prodi as Prodi, nm.pekerjaan as Pekerjaan
                    FROM users u
                    LEFT JOIN mahasiswa m ON u.id = m.user_id
                    LEFT JOIN non_mahasiswa nm ON u.id = nm.user_id
                    LEFT JOIN panitia p ON u.id = p.user_id
                    WHERE u.id = $id_user
                ");
                $user = mysqli_fetch_assoc($res);
                echo json_encode(["status" => "success", "message" => "Profil berhasil diperbarui!", "data" => $user]);
            } else {
                echo json_encode(["status" => "error", "message" => "Gagal update profil"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "User tidak ditemukan"]);
        }
        exit;
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>