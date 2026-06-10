<?php
// Mencegah error CORS dari Vercel
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Mengambil file konfigurasi global agar kredensial sama persis
include 'koneksi.php';

// Koneksi PDO (Menggunakan variabel dari koneksi.php)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit;
}

// Menangkap kiriman data JSON
$input_mentah = file_get_contents('php://input');
$data = json_decode($input_mentah, true);

if (!$data) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Data tidak dikirim secara valid.']);
    exit;
}

// ==========================================
// 1. CREATE / DAFTAR AKUN
// ==========================================
if ($data['aksi'] === 'daftar') {
    
    // Validasi username
    $stmt_cek = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    $stmt_cek->execute([$data['username']]);
    if ($stmt_cek->rowCount() > 0) {
        echo json_encode(['status' => 'gagal', 'pesan' => 'Pendaftaran Gagal! Username sudah digunakan.']);
        exit;
    }

    $role = $data['role'];
    $prefix = '';
    
    if ($role === 'mahasiswa') $prefix = 'USR-';
    elseif ($role === 'non_mahasiswa') $prefix = 'UNM-';
    elseif ($role === 'panitia') $prefix = 'PNT-';
    else {
        echo json_encode(['status' => 'gagal', 'pesan' => 'Role tidak valid.']);
        exit;
    }

    $stmt_max = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(loginId, 5) AS UNSIGNED)) FROM users WHERE role = ?");
    $stmt_max->execute([$role]);
    $max_number = $stmt_max->fetchColumn();
    $next_number = ($max_number ? $max_number : 0) + 1;
    
    $loginId_otomatis = $prefix . str_pad($next_number, 3, '0', STR_PAD_LEFT);

    $password_aman = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
        $pdo->beginTransaction();

        $sql_user = "INSERT INTO users (role, loginId, username, password) VALUES (:role, :loginId, :username, :password)";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([
            ':role'     => $role,
            ':loginId'  => $loginId_otomatis,
            ':username' => $data['username'],
            ':password' => $password_aman
        ]);

        $new_user_id = $pdo->lastInsertId();

        if ($role === 'mahasiswa') {
            $sql_spesifik = "INSERT INTO mahasiswa (user_id, nama, email, phone, nim, fakultas, prodi) 
                             VALUES (:user_id, :nama, :email, :phone, :nim, :fakultas, :prodi)";
            $stmt_spesifik = $pdo->prepare($sql_spesifik);
            $stmt_spesifik->execute([
                ':user_id'  => $new_user_id,
                ':nama'     => $data['nama'],
                ':email'    => $data['email'],
                ':phone'    => $data['phone'],
                ':nim'      => $data['nim'],
                ':fakultas' => isset($data['fakultas']) ? $data['fakultas'] : null,
                ':prodi'    => isset($data['prodi']) ? $data['prodi'] : null
            ]);
        } elseif ($role === 'non_mahasiswa') {
            $sql_spesifik = "INSERT INTO non_mahasiswa (user_id, nama, email, phone, pekerjaan) 
                             VALUES (:user_id, :nama, :email, :phone, :pekerjaan)";
            $stmt_spesifik = $pdo->prepare($sql_spesifik);
            $stmt_spesifik->execute([
                ':user_id'   => $new_user_id,
                ':nama'      => $data['nama'],
                ':email'     => $data['email'],
                ':phone'     => $data['phone'],
                ':pekerjaan' => $data['pekerjaan']
            ]);
        } elseif ($role === 'panitia') {
            $sql_spesifik = "INSERT INTO panitia (user_id, nama, email, phone, nim) 
                             VALUES (:user_id, :nama, :email, :phone, :nim)";
            $stmt_spesifik = $pdo->prepare($sql_spesifik);
            $stmt_spesifik->execute([
                ':user_id'  => $new_user_id,
                ':nama'     => $data['nama'],
                ':email'    => $data['email'],
                ':phone'    => $data['phone'],
                ':nim'      => $data['nim']
            ]);
        }

        $pdo->commit();
        echo json_encode([
            'status' => 'sukses', 
            'pesan' => "Pendaftaran Berhasil!"
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'gagal', 'pesan' => 'Gagal mendaftar: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 2. READ / LOGIN
// ==========================================
if ($data['aksi'] === 'login') {
    $sql_login = "SELECT * FROM users WHERE username = :username";
    $stmt_login = $pdo->prepare($sql_login);
    $stmt_login->execute([
        ':username' => $data['username']
    ]);
    
    $user = $stmt_login->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($data['password'], $user['password'])) {
        $profil = [];

        if ($user['role'] === 'mahasiswa') {
            $stmt_profil = $pdo->prepare("SELECT * FROM mahasiswa WHERE user_id = ?");
            $stmt_profil->execute([$user['id']]);
            $profil = $stmt_profil->fetch(PDO::FETCH_ASSOC);
        } elseif ($user['role'] === 'non_mahasiswa') {
            $stmt_profil = $pdo->prepare("SELECT * FROM non_mahasiswa WHERE user_id = ?");
            $stmt_profil->execute([$user['id']]);
            $profil = $stmt_profil->fetch(PDO::FETCH_ASSOC);
        } elseif ($user['role'] === 'panitia') {
            $stmt_profil = $pdo->prepare("SELECT * FROM panitia WHERE user_id = ?");
            $stmt_profil->execute([$user['id']]);
            $profil = $stmt_profil->fetch(PDO::FETCH_ASSOC);
        }

        if (!$profil) {
            echo json_encode(['status' => 'gagal', 'pesan' => 'Profil tidak ditemukan.']); exit;
        }

        unset($user['password']);
        echo json_encode([
            'status' => 'sukses', 
            'pesan' => 'Login Sukses!',
            'user'   => array_merge($user, $profil)
        ]);
        exit;
    }

    echo json_encode(['status' => 'gagal', 'pesan' => 'Kredensial tidak cocok.']);
    exit;
}

// ==========================================
// 3. UPDATE / EDIT PROFIL
// ==========================================
if ($data['aksi'] === 'update_profil') {
    $user_id = $data['user_id'];
    $role = $data['role'];

    try {
        if ($role === 'mahasiswa') {
            $sql = "UPDATE mahasiswa SET nama = ?, email = ?, phone = ?, nim = ?, fakultas = ?, prodi = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nama'], $data['email'], $data['phone'], $data['nim'], 
                isset($data['fakultas']) ? $data['fakultas'] : null, 
                isset($data['prodi']) ? $data['prodi'] : null, 
                $user_id
            ]);
        } elseif ($role === 'non_mahasiswa') {
            $sql = "UPDATE non_mahasiswa SET nama = ?, email = ?, phone = ?, pekerjaan = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nama'], $data['email'], $data['phone'], $data['pekerjaan'], $user_id
            ]);
        } elseif ($role === 'panitia') {
            $sql = "UPDATE panitia SET nama = ?, email = ?, phone = ?, nim = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['nama'], $data['email'], $data['phone'], $data['nim'], $user_id
            ]);
        }
        echo json_encode(['status' => 'sukses', 'pesan' => 'Profil berhasil diperbarui! Silakan login ulang untuk melihat perubahan.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'gagal', 'pesan' => 'Gagal update: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 4. DELETE / HAPUS AKUN
// ==========================================
if ($data['aksi'] === 'hapus_akun') {
    $user_id = $data['user_id'];
    $role = $data['role'];

    try {
        $pdo->beginTransaction();

        // Hapus dari tabel spesifik dulu
        if ($role === 'mahasiswa') $pdo->prepare("DELETE FROM mahasiswa WHERE user_id = ?")->execute([$user_id]);
        elseif ($role === 'non_mahasiswa') $pdo->prepare("DELETE FROM non_mahasiswa WHERE user_id = ?")->execute([$user_id]);
        elseif ($role === 'panitia') $pdo->prepare("DELETE FROM panitia WHERE user_id = ?")->execute([$user_id]);

        // Hapus dari tabel utama
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

        $pdo->commit();
        echo json_encode(['status' => 'sukses', 'pesan' => 'Akun berhasil dihapus permanen.']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'gagal', 'pesan' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// 5. DAFTAR PANITIA (UPGRADE ROLE)
// ==========================================
if ($data['aksi'] === 'daftar_panitia') {
    $user_id = isset($data['user_id']) ? $data['user_id'] : null;

    if (!$user_id) {
        echo json_encode(['status' => 'gagal', 'pesan' => 'ID User tidak valid. Pastikan Anda telah me-refresh halaman atau coba logout lalu login kembali.']);
        exit;
    }

    // Cek apakah user mahasiswa
    $stmt_cek = $pdo->prepare("SELECT * FROM mahasiswa WHERE user_id = ?");
    $stmt_cek->execute([$user_id]);
    $mahasiswa = $stmt_cek->fetch(PDO::FETCH_ASSOC);

    if (!$mahasiswa) {
        echo json_encode(['status' => 'gagal', 'pesan' => 'Hanya mahasiswa yang dapat mendaftar sebagai panitia. Jika Anda yakin mahasiswa, silakan logout dan login kembali.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Pindahkan data ke tabel panitia
        $sql_panitia = "INSERT INTO panitia (user_id, nama, email, phone, nim) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql_panitia)->execute([
            $user_id, $mahasiswa['nama'], $mahasiswa['email'], $mahasiswa['phone'], $mahasiswa['nim']
        ]);

        // Hapus dari mahasiswa karena role berubah
        $pdo->prepare("DELETE FROM mahasiswa WHERE user_id = ?")->execute([$user_id]);

        // Update role di tabel users
        $pdo->prepare("UPDATE users SET role = 'panitia' WHERE id = ?")->execute([$user_id]);

        $pdo->commit();
        
        // Ambil data user terbaru untuk di-return
        $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user_updated = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        $stmt_profil = $pdo->prepare("SELECT * FROM panitia WHERE user_id = ?");
        $stmt_profil->execute([$user_id]);
        $profil = $stmt_profil->fetch(PDO::FETCH_ASSOC);
        
        if ($user_updated) unset($user_updated['password']);
        
        echo json_encode([
            'status' => 'sukses', 
            'pesan' => 'Pendaftaran Panitia Berhasil! Halaman akan dimuat ulang.',
            'user' => array_merge($user_updated ?: [], $profil ?: [])
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'gagal', 'pesan' => 'Gagal mendaftar panitia: ' . $e->getMessage()]);
    }
    exit;
}
?>