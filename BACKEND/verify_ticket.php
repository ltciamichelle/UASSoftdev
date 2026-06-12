<?php
require_once 'koneksi.php';

$kode = isset($_GET['kode']) ? (int)$_GET['kode'] : 0; // Using Id_Pendaftaran as kode

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Tiket - EVENTRA</title>
    <style>body { font-family: sans-serif; padding: 20px; }</style>
</head>
<body>
    <h2>Verifikasi Pendaftaran</h2>
    <?php if ($kode > 0): 
        $query = "SELECT P.*, U.Nama, U.Email, E.Nama_Event, E.Tanggal_Event 
                  FROM Pendaftaran_Event P
                  JOIN User U ON P.Id_User = U.Id_User
                  JOIN Event E ON P.Id_Event = E.Id_Event
                  WHERE P.Id_Pendaftaran = $kode";
        $result = mysqli_query($koneksi, $query);
        $ticket = mysqli_fetch_assoc($result);
        
        if ($ticket):
    ?>
        <div style="border:1px solid #ccc; padding: 15px; border-radius: 8px;">
            <h3>Event: <?= htmlspecialchars($ticket['Nama_Event']) ?></h3>
            <p><strong>Nama Pendaftar:</strong> <?= htmlspecialchars($ticket['Nama']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($ticket['Email']) ?></p>
            <p><strong>Tanggal Event:</strong> <?= htmlspecialchars($ticket['Tanggal_Event']) ?></p>
            <p><strong>Status Pendaftaran:</strong> <?= htmlspecialchars($ticket['Status_Pendaftaran']) ?></p>
        </div>
    <?php else: ?>
        <p>Data pendaftaran tidak ditemukan.</p>
    <?php endif; else: ?>
        <p>Kode Pendaftaran tidak valid.</p>
    <?php endif; ?>
</body>
</html>