<?php
include 'koneksi.php';

$query = "ALTER TABLE registrasi_event ADD COLUMN kode_pendaftaran VARCHAR(50) UNIQUE NULL AFTER event_id";
if (mysqli_query($koneksi, $query)) {
    echo "Berhasil menambahkan kolom kode_pendaftaran\n";
} else {
    echo "Gagal: " . mysqli_error($koneksi) . "\n";
}

// Since it's existing data, some might be NULL, let's generate random codes for existing ones
$query2 = "SELECT id FROM registrasi_event WHERE kode_pendaftaran IS NULL";
$res = mysqli_query($koneksi, $query2);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $id = $row['id'];
        $kode = 'EVT' . date('ymd') . strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));
        mysqli_query($koneksi, "UPDATE registrasi_event SET kode_pendaftaran = '$kode' WHERE id = $id");
    }
    echo "Berhasil mengupdate kode_pendaftaran untuk data lama\n";
}
?>
