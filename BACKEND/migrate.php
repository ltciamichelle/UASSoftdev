<?php
// migrate.php
// Script untuk mengupdate struktur tabel events (menambahkan user_id dan views)

include 'koneksi.php';

header('Content-Type: text/html');

echo "<h1>Migrasi Database Eventra</h1>";

$queries = [
    "ALTER TABLE events ADD COLUMN user_id INT NULL;",
    "ALTER TABLE events ADD COLUMN views INT DEFAULT 0;"
];

foreach ($queries as $query) {
    echo "<p>Menjalankan: <code>$query</code></p>";
    if (mysqli_query($koneksi, $query)) {
        echo "<p style='color: green;'>Sukses!</p>";
    } else {
        echo "<p style='color: red;'>Error/Sudah ada: " . mysqli_error($koneksi) . "</p>";
    }
}

echo "<h3>Selesai. Anda dapat menghapus file migrate.php ini demi keamanan.</h3>";
?>
