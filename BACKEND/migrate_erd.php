<?php
header('Content-Type: application/json');
include 'koneksi.php';

try {
    mysqli_begin_transaction($koneksi);

    // Disable foreign key checks for dropping tables
    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 0");

    // Drop all old tables to ensure a clean slate that 100% matches ERD
    $tables = ['events', 'event_keuangan', 'feedback_event', 'mahasiswa', 'non_mahasiswa', 'panitia', 'pendaftaran_event', 'sertifikat_event', 'users', 'registrasi_event', 'feedbacks'];
    foreach ($tables as $table) {
        mysqli_query($koneksi, "DROP TABLE IF EXISTS `$table`");
    }

    // 1. Create User table
    mysqli_query($koneksi, "CREATE TABLE `User` (
        `Id_User` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Nama` VARCHAR(100) NOT NULL,
        `Email` VARCHAR(100) NOT NULL UNIQUE,
        `Password` VARCHAR(255) NOT NULL,
        `Role` VARCHAR(50) NOT NULL,
        `No_HP` VARCHAR(20) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Create Kategori table
    mysqli_query($koneksi, "CREATE TABLE `Kategori` (
        `Id_Kategori` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Nama_Kategori` VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert default categories
    mysqli_query($koneksi, "INSERT INTO `Kategori` (`Nama_Kategori`) VALUES ('Akademik'), ('Olahraga'), ('Seni & Budaya'), ('Seminar'), ('Hiburan')");

    // 3. Create Event table
    mysqli_query($koneksi, "CREATE TABLE `Event` (
        `Id_Event` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Id_User` INT(11) NOT NULL,
        `Id_Kategori` INT(11) NOT NULL,
        `Nama_Event` VARCHAR(200) NOT NULL,
        `Deskripsi` TEXT NULL,
        `Lokasi` VARCHAR(255) NOT NULL,
        `Tanggal_Event` DATE NOT NULL,
        `Poster_Event` VARCHAR(255) NULL,
        `Status_Event` VARCHAR(50) DEFAULT 'aktif',
        FOREIGN KEY (`Id_User`) REFERENCES `User`(`Id_User`) ON DELETE CASCADE,
        FOREIGN KEY (`Id_Kategori`) REFERENCES `Kategori`(`Id_Kategori`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Create Pendaftaran_Event table
    mysqli_query($koneksi, "CREATE TABLE `Pendaftaran_Event` (
        `Id_Pendaftaran` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Id_User` INT(11) NOT NULL,
        `Id_Event` INT(11) NOT NULL,
        `Tanggal_Daftar` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `Status_Pendaftaran` VARCHAR(50) DEFAULT 'Terdaftar',
        FOREIGN KEY (`Id_User`) REFERENCES `User`(`Id_User`) ON DELETE CASCADE,
        FOREIGN KEY (`Id_Event`) REFERENCES `Event`(`Id_Event`) ON DELETE CASCADE,
        UNIQUE KEY `unik_pendaftaran` (`Id_User`, `Id_Event`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Create Feedback table
    mysqli_query($koneksi, "CREATE TABLE `Feedback` (
        `Id_Feedback` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Id_User` INT(11) NOT NULL,
        `Id_Event` INT(11) NOT NULL,
        `Isi_Feedback` TEXT NOT NULL,
        `Rating` INT(1) NOT NULL DEFAULT 5,
        `Tanggal_Feedback` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`Id_User`) REFERENCES `User`(`Id_User`) ON DELETE CASCADE,
        FOREIGN KEY (`Id_Event`) REFERENCES `Event`(`Id_Event`) ON DELETE CASCADE,
        UNIQUE KEY `unik_feedback` (`Id_User`, `Id_Event`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. Create Sertifikat table
    mysqli_query($koneksi, "CREATE TABLE `Sertifikat` (
        `Id_Sertifikat` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Id_Pendaftaran` INT(11) NOT NULL,
        `File_Sertifikat` VARCHAR(255) NOT NULL,
        `Tanggal_Upload` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`Id_Pendaftaran`) REFERENCES `Pendaftaran_Event`(`Id_Pendaftaran`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    mysqli_query($koneksi, "SET FOREIGN_KEY_CHECKS = 1");
    mysqli_commit($koneksi);

    echo json_encode(["status" => "success", "message" => "Berhasil membuat tabel-tabel baru sesuai dengan ERD yang baru! Tabel lama beserta fitur tambahannya telah dihapus."]);
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo json_encode(["status" => "error", "message" => "Gagal memigrasi: " . $e->getMessage()]);
}
?>
