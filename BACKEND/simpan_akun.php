<?php
header('Content-Type: application/json');

// --- KONFIGURASI DATABASE XAMPP ---
$host       = "localhost";
$username_db= "root"; 
$password_db= "";     
$dbname     = "EVENTRA";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Koneksi database gagal: ' . $e->getMessage()]);
    exit;
}

// Menangkap kiriman data JSON dari browser (Fetch API)
$input_mentah = file_get_contents('php://input');
$data = json_decode($input_mentah, true);

if (!$data) {
    echo json_encode(['status' => 'gagal', 'pesan' => 'Data tidak dikirim secara valid.']);
    exit;
}

// --- PROSES REGISTRASI / DAFTAR AKUN BARU ---
if ($data['aksi'] === 'daftar') {
    
    // 1. Validasi Duplikasi Username Terlebih Dahulu
    $stmt_cek = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    $stmt_cek->execute([$data['username']]);
    if ($stmt_cek->rowCount() > 0) {
        echo json_encode(['status' => 'gagal', 'pesan' => 'Pendaftaran Gagal! Username sudah digunakan akun lain.']);
        exit;
    }

    // 2. Generate Auto Login ID Berdasarkan Role
    $role = $data['role'];
    $prefix = '';
    
    if ($role === 'mahasiswa') {
        $prefix = 'USR-';
    } elseif ($role === 'non_mahasiswa') {
        $prefix = 'UNM-';
    } elseif ($role === 'panitia') {
        $prefix = 'PNT-';
    } else {
        echo json_encode(['status' => 'gagal', 'pesan' => 'Role tidak valid.']);
        exit;
    }

    // Menghitung user dengan role yang sama untuk menentukan nomor urut berikutnya
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = ?");
    $stmt_count->execute([$role]);
    $count = $stmt_count->fetchColumn();
    $next_number = $count + 1;
    
    // Format menjadi 3 digit (misal: USR-001)
    $loginId_otomatis = $prefix . sprintf('%03d', $next_number);

    // Amankan password
    $password_aman = password_hash($data['password'], PASSWORD_BCRYPT);

    try {
        // Mulai transaksi database agar penyimpanan sinkron ke 2 tabel
        $pdo->beginTransaction();

        // 3. Insert ke Tabel Utama (users) menggunakan loginId otomatis
        $sql_user = "INSERT INTO users (role, loginId, username, password) VALUES (:role, :loginId, :username, :password)";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([
            ':role'     => $role,
            ':loginId'  => $loginId_otomatis,
            ':username' => $data['username'],
            ':password' => $password_aman
        ]);

        // Mengambil ID terakhir yang baru saja terbuat di tabel users
        $new_user_id = $pdo->lastInsertId();

        // 4. Insert ke Tabel Spesifik berdasarkan Role masing-masing
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
                ':fakultas' => $data['fakultas'] ?? null,
                ':prodi'    => $data['prodi'] ?? null
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

        // Jika semua query sukses, terapkan perubahan ke database resmi
        $pdo->commit();
        
        // Berikan feedback info ID Login otomatisnya kepada user agar mereka tahu info kartu loginnya
        echo json_encode([
            'status' => 'sukses', 
            'pesan' => "Pendaftaran Berhasil! ID Login Anda adalah: $loginId_otomatis. Silakan gunakan ID ini untuk Masuk."
        ]);

    } catch (PDOException $e) {
        // Jika ada yang error di tengah jalan, batalkan semua agar tidak berantakan
        $pdo->rollBack();
        echo json_encode(['status' => 'gagal', 'pesan' => 'Gagal mendaftar: ' . $e->getMessage()]);
    }
    exit;
}

// --- PROSES AUTENTIKASI / VERIFIKASI LOGIN ---
if ($data['aksi'] === 'login') {
    
    // Cari akun di tabel utama terlebih dahulu
    $sql_login = "SELECT * FROM users WHERE role = :role AND loginId = :loginId AND username = :username";
    $stmt_login = $pdo->prepare($sql_login);
    $stmt_login->execute([
        ':role'     => $data['role'],
        ':loginId'  => $data['loginId'],
        ':username' => $data['username']
    ]);
    
    $user = $stmt_login->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verifikasi kesesuaian password
        if (password_verify($data['password'], $user['password'])) {
            
            $profil = [];

            // Menggabungkan data profil asli dari sub-tabel berdasarkan rolenya
            if ($user['role'] === 'mahasiswa') {
                $stmt_profil = $pdo->prepare("SELECT * FROM mahasiswa WHERE user_id = ?");
                $stmt_profil->execute([$user['id']]);
                $profil = $stmt_profil->fetch(PDO::FETCH_ASSOC);
                
                if (!$profil || $profil['nim'] !== $data['nim']) {
                    echo json_encode(['status' => 'gagal', 'pesan' => 'Login Gagal! NIM Anda salah.']);
                    exit;
                }
            } elseif ($user['role'] === 'non_mahasiswa') {
                $stmt_profil = $pdo->prepare("SELECT * FROM non_mahasiswa WHERE user_id = ?");
                $stmt_profil->execute([$user['id']]);
                $profil = $stmt_profil->fetch(PDO::FETCH_ASSOC);
            } elseif ($user['role'] === 'panitia') {
                // DI SINI PERBAIKANNYA: Menghapus kata 'set' yang typo/salah syntax SQL
                $stmt_profil = $pdo->prepare("SELECT * FROM panitia WHERE user_id = ?");
                $stmt_profil->execute([$user['id']]);
                $profil = $stmt_profil->fetch(PDO::FETCH_ASSOC);
                
                if (!$profil || $profil['nim'] !== $data['nim']) {
                    echo json_encode(['status' => 'gagal', 'pesan' => 'Login Gagal! NIM Panitia Anda salah.']);
                    exit;
                }
            }

            // Jika profil tidak ditemukan sama sekali
            if (!$profil) {
                echo json_encode(['status' => 'gagal', 'pesan' => 'Login Gagal! Data profil tidak ditemukan.']);
                exit;
            }

            // Gabungkan data dasar dan data profil lengkap untuk dikirim balik ke Frontend
            unset($user['password']); // Keamanan
            $user_lengkap = array_merge($user, $profil);

            echo json_encode([
                'status' => 'sukses', 
                'pesan' => 'Login Sukses! Selamat datang kembali di platform EVENTRA.',
                'user'   => $user_lengkap
            ]);
            exit;
        }
    }

    echo json_encode(['status' => 'gagal', 'pesan' => 'Login Gagal! Kredensial tidak cocok atau akun tidak terdaftar.']);
    exit;
}
?>