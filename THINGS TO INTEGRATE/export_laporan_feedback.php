<?php
// export_laporan_feedback.php - Export laporan peserta dan feedback ke Excel
require_once 'koneksi.php';

session_start();
$userData = null;
if (isset($_COOKIE['user_eventra'])) {
    $userData = json_decode($_COOKIE['user_eventra'], true);
} elseif (isset($_SESSION['user_eventra'])) {
    $userData = $_SESSION['user_eventra'];
}

if (!$userData || $userData['role'] !== 'panitia') {
    header('Location: Login.html');
    exit;
}

$event_id = isset($_GET['event_id']) ? mysqli_real_escape_string($koneksi, $_GET['event_id']) : '';

if (empty($event_id)) {
    die("Event ID tidak ditemukan");
}

// Ambil data event
$event_query = "SELECT * FROM events WHERE id = '$event_id'";
$event_result = mysqli_query($koneksi, $event_query);
$event = mysqli_fetch_assoc($event_result);

if (!$event) {
    die("Event tidak ditemukan");
}

// Ambil data peserta lengkap dengan feedback
$query = "SELECT 
            p.id as pendaftaran_id,
            p.kode_pendaftaran,
            p.nama_peserta,
            p.email_peserta,
            p.no_telepon,
            p.tanggal_daftar,
            p.status_pendaftaran,
            p.is_checked_in,
            p.check_in_time,
            p.status_kelulusan,
            p.nilai_kehadiran,
            s.nomor_sertifikat as nomor_sertifikat,
            s.status_kirim as sertifikat_terkirim,
            f.rating,
            f.komentar,
            f.saran,
            f.created_at as feedback_date
          FROM pendaftaran_event p
          LEFT JOIN sertifikat_event s ON p.id = s.pendaftaran_id
          LEFT JOIN feedback_event f ON p.id = f.pendaftaran_id
          WHERE p.event_id = '$event_id'
          ORDER BY p.tanggal_daftar DESC";

$result = mysqli_query($koneksi, $query);

// Set headers untuk download Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Laporan_Event_' . $event['nama_event'] . '_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html>';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>Laporan Event ' . $event['nama_event'] . '</title>';
echo '<style>';
echo 'th { background-color: #ff7a00; color: white; padding: 8px; }';
echo 'td { padding: 6px; border: 1px solid #ddd; }';
echo '.header-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }';
echo '.subtitle { font-size: 14px; margin-bottom: 20px; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Header laporan
echo '<div class="header-title">LAPORAN LENGKAP EVENT</div>';
echo '<div class="subtitle">';
echo 'Nama Event: ' . $event['nama_event'] . '<br>';
echo 'ID Event: ' . $event['id_event'] . '<br>';
echo 'Tanggal Event: ' . date('d F Y', strtotime($event['tanggal'])) . ' - ' . date('d F Y', strtotime($event['tanggal_selesai'])) . '<br>';
echo 'Lokasi: ' . $event['lokasi'] . '<br>';
echo 'Status Event: ' . ($event['status_event'] == 'aktif' ? 'Aktif' : 'Selesai') . '<br>';
echo 'Tanggal Export: ' . date('d F Y H:i:s') . '<br>';
echo '</div>';

// Statistik
$total_peserta = mysqli_num_rows($result);
$hadir_count = 0;
$lulus_count = 0;
$feedback_count = 0;
$total_rating = 0;

$data_peserta = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data_peserta[] = $row;
    if ($row['is_checked_in'] == 1) $hadir_count++;
    if ($row['status_kelulusan'] == 'lulus') $lulus_count++;
    if ($row['rating']) {
        $feedback_count++;
        $total_rating += $row['rating'];
    }
}

$rata_rating = $feedback_count > 0 ? round($total_rating / $feedback_count, 1) : 0;

echo '<div style="margin: 20px 0;">';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th colspan="2">STATISTIK EVENT</th></tr>';
echo '<tr><td>Total Peserta Terdaftar</td><td>' . $total_peserta . '</td></tr>';
echo '<tr><td>Jumlah Hadir Check-in</td><td>' . $hadir_count . '</td></tr>';
echo '<tr><td>Presentase Kehadiran</td><td>' . ($total_peserta > 0 ? round(($hadir_count / $total_peserta) * 100, 1) : 0) . '%</td></tr>';
echo '<tr><td>Peserta Lulus</td><td>' . $lulus_count . '</td></tr>';
echo '<tr><td>Jumlah Feedback</td><td>' . $feedback_count . '</td></tr>';
echo '<tr><td>Rata-rata Rating</td><td>' . $rata_rating . ' / 5 ★</td></tr>';
echo '</table>';
echo '</div>';

// Tabel data peserta lengkap
echo '<h3>DATA PESERTA LENGKAP</h3>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<thead>';
echo '<tr>';
echo '<th>No</th>';
echo '<th>Kode Tiket</th>';
echo '<th>Nama Peserta</th>';
echo '<th>Email</th>';
echo '<th>No Telepon</th>';
echo '<th>Tanggal Daftar</th>';
echo '<th>Status Verifikasi</th>';
echo '<th>Status Check-in</th>';
echo '<th>Waktu Check-in</th>';
echo '<th>Status Kelulusan</th>';
echo '<th>Nilai Kehadiran</th>';
echo '<th>Nomor Sertifikat</th>';
echo '<th>Sertifikat Terkirim</th>';
echo '<th>Rating</th>';
echo '<th>Komentar</th>';
echo '<th>Saran</th>';
echo '<th>Tanggal Feedback</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

$no = 1;
foreach ($data_peserta as $peserta) {
    echo '<tr>';
    echo '<td>' . $no++ . '</td>';
    echo '<td>' . $peserta['kode_pendaftaran'] . '</td>';
    echo '<td>' . htmlspecialchars($peserta['nama_peserta']) . '</td>';
    echo '<td>' . htmlspecialchars($peserta['email_peserta']) . '</td>';
    echo '<td>' . htmlspecialchars($peserta['no_telepon']) . '</td>';
    echo '<td>' . date('d/m/Y H:i', strtotime($peserta['tanggal_daftar'])) . '</td>';
    
    // Status Verifikasi
    $status_class = '';
    if ($peserta['status_pendaftaran'] == 'Terdaftar') $status_class = 'Terverifikasi';
    elseif ($peserta['status_pendaftaran'] == 'Menunggu Verifikasi') $status_class = 'Menunggu Verifikasi';
    elseif ($peserta['status_pendaftaran'] == 'Ditolak') $status_class = 'Ditolak';
    echo '<td>' . $status_class . '</td>';
    
    // Status Check-in
    echo '<td>' . ($peserta['is_checked_in'] == 1 ? 'Sudah Check-in' : 'Belum Check-in') . '</td>';
    echo '<td>' . ($peserta['check_in_time'] ? date('d/m/Y H:i', strtotime($peserta['check_in_time'])) : '-') . '</td>';
    
    // Status Kelulusan
    $kelulusan = '';
    if ($peserta['status_kelulusan'] == 'lulus') $kelulusan = 'Lulus';
    elseif ($peserta['status_kelulusan'] == 'tidak_lulus') $kelulusan = 'Tidak Lulus';
    else $kelulusan = 'Pending';
    echo '<td>' . $kelulusan . '</td>';
    
    echo '<td>' . ($peserta['nilai_kehadiran'] ? $peserta['nilai_kehadiran'] . '%' : '-') . '</td>';
    echo '<td>' . ($peserta['nomor_sertifikat'] ? $peserta['nomor_sertifikat'] : '-') . '</td>';
    echo '<td>' . ($peserta['sertifikat_terkirim'] == 1 ? 'Sudah Terkirim' : 'Belum Terkirim') . '</td>';
    
    // Rating
    $rating_star = '';
    if ($peserta['rating']) {
        for ($i = 1; $i <= 5; $i++) {
            $rating_star .= $i <= $peserta['rating'] ? '★' : '☆';
        }
    } else {
        $rating_star = '-';
    }
    echo '<td>' . $rating_star . ' (' . ($peserta['rating'] ? $peserta['rating'] . '/5' : '-') . ')</td>';
    
    echo '<td>' . htmlspecialchars($peserta['komentar'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($peserta['saran'] ?? '-') . '</td>';
    echo '<td>' . ($peserta['feedback_date'] ? date('d/m/Y H:i', strtotime($peserta['feedback_date'])) : '-') . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

// Ringkasan Feedback
echo '<div style="margin-top: 30px;">';
echo '<h3>RINGKASAN FEEDBACK PESERTA</h3>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Rating</th><th>Jumlah</th><th>Persentase</th></tr>';

$rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($data_peserta as $peserta) {
    if ($peserta['rating']) {
        $rating_counts[$peserta['rating']]++;
    }
}

for ($i = 5; $i >= 1; $i--) {
    $persen = $feedback_count > 0 ? round(($rating_counts[$i] / $feedback_count) * 100, 1) : 0;
    $stars = '';
    for ($j = 1; $j <= 5; $j++) {
        $stars .= $j <= $i ? '★' : '☆';
    }
    echo '<tr>';
    echo '<td>' . $stars . ' (' . $i . '/5)</td>';
    echo '<td>' . $rating_counts[$i] . '</td>';
    echo '<td>' . $persen . '%</td>';
    echo '</tr>';
}

echo '</table>';
echo '</div>';

echo '</body>';
echo '</html>';
?>