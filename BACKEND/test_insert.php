<?php
include 'koneksi.php';

echo "Testing Database Insert into Event...\n";

// Get user to use valid ID
$user = mysqli_query($koneksi, "SELECT Id_User FROM User LIMIT 1");
$u = mysqli_fetch_assoc($user);
$id_user = $u ? $u['Id_User'] : 1;

$kategori = mysqli_query($koneksi, "SELECT Id_Kategori FROM Kategori LIMIT 1");
$k = mysqli_fetch_assoc($kategori);
$id_kategori = $k ? $k['Id_Kategori'] : 1;

$query_insert = "INSERT INTO Event (Id_User, Id_Kategori, Nama_Event, Deskripsi, Lokasi, Tanggal_Event, Poster_Event) 
                 VALUES ($id_user, $id_kategori, 'TEST_EVENT', 'TEST', 'TEST', '2026-01-01', '')";

if (mysqli_query($koneksi, $query_insert)) {
    echo "SUCCESS\n";
    $id = mysqli_insert_id($koneksi);
    mysqli_query($koneksi, "DELETE FROM Event WHERE Id_Event = $id");
} else {
    echo "ERROR: " . mysqli_error($koneksi) . "\n";
}
?>
