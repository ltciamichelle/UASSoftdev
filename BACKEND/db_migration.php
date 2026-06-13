<?php
include 'koneksi.php';

$queries = [
    "ALTER TABLE Event ADD COLUMN IF NOT EXISTS Tipe_Tiket ENUM('Gratis', 'Berbayar') DEFAULT 'Gratis'",
    "ALTER TABLE Event ADD COLUMN IF NOT EXISTS Harga INT DEFAULT 0",
    "ALTER TABLE Event ADD COLUMN IF NOT EXISTS Bank_Name VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE Event ADD COLUMN IF NOT EXISTS Bank_Rekening VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE Event ADD COLUMN IF NOT EXISTS Bank_Atas_Nama VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE Pendaftaran_Event ADD COLUMN IF NOT EXISTS Bukti_Bayar VARCHAR(255) DEFAULT NULL"
];

foreach ($queries as $query) {
    if (mysqli_query($koneksi, $query)) {
        echo "Berhasil: $query <br>";
    } else {
        echo "Gagal: $query - " . mysqli_error($koneksi) . "<br>";
    }
}
echo "Migrasi Selesai!";
?>
