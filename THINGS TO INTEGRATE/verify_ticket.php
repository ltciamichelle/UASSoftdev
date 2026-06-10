<?php
// verify_ticket.php - Halaman verifikasi tiket untuk panitia dan peserta
require_once 'koneksi.php';

$kode = isset($_GET['kode']) ? trim($_GET['kode']) : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Verifikasi Tiket - EVENTRA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 550px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            background: white;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .card-header {
            background: linear-gradient(135deg, #ff7a00 0%, #ffc107 100%);
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        .card-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .card-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
        
        .status-valid {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-warning {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .status-invalid {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .info-section {
            margin: 20px 0;
        }
        
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-label {
            width: 120px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
        }
        
        .info-value {
            flex: 1;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
            word-break: break-word;
        }
        
        .info-value small {
            font-weight: normal;
            color: #64748b;
        }
        
        .qr-placeholder {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: #ff7a00;
        }
        
        .btn-back {
            background: #f1f5f9;
            color: #1e293b;
            border: none;
            padding: 14px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff7a00, #ffc107);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 122, 0, 0.3);
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .warning-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .success-box {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        @media (max-width: 480px) {
            .card-body {
                padding: 20px;
            }
            .info-label {
                width: 100px;
                font-size: 0.7rem;
            }
            .info-value {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fa-solid fa-ticket"></i> EVENTRA</h1>
                <p>Verifikasi Tiket Digital</p>
            </div>
            <div class="card-body">
                <?php if (empty($kode)): ?>
                    <!-- Tampilkan form jika tidak ada kode -->
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <h3 style="margin-bottom: 10px;">Scan QR Code Tiket</h3>
                        <p style="color: #64748b; margin-bottom: 25px;">Tidak ada kode tiket yang discan</p>
                        <div class="qr-placeholder">
                            <i class="fa-solid fa-camera" style="font-size: 2rem; color: #ff7a00; margin-bottom: 10px; display: block;"></i>
                            <p style="font-size: 0.85rem;">Silakan scan QR Code tiket menggunakan kamera HP Anda</p>
                            <small style="color: #94a3b8;">Atau masukkan kode tiket secara manual</small>
                        </div>
                        <div style="margin-top: 15px;">
                            <input type="text" id="manualKode" placeholder="Masukkan kode tiket" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px;">
                            <button class="btn-primary" onclick="verifyManual()">
                                <i class="fa-solid fa-magnifying-glass"></i> Verifikasi Manual
                            </button>
                        </div>
                        <a href="verify.html" class="btn-back" style="margin-top: 15px;">
                            <i class="fa-solid fa-arrow-left"></i> Buka Scanner
                        </a>
                    </div>
                <?php else:
                    // Cari data tiket di database menggunakan MySQLi
                    $query = "SELECT 
                                p.id as pendaftaran_id,
                                p.kode_pendaftaran,
                                p.nama_peserta,
                                p.email_peserta,
                                p.no_telepon,
                                p.tanggal_daftar,
                                p.status_pendaftaran,
                                p.bukti_bayar,
                                p.is_checked_in,
                                p.check_in_time,
                                e.id as event_id,
                                e.nama_event,
                                e.tanggal,
                                e.waktu,
                                e.tanggal_selesai,
                                e.waktu_selesai,
                                e.lokasi,
                                e.tipe_tiket,
                                e.harga_event,
                                e.deskripsi
                              FROM pendaftaran_event p 
                              JOIN events e ON p.event_id = e.id 
                              WHERE p.kode_pendaftaran = ?";
                    
                    $stmt = mysqli_prepare($koneksi, $query);
                    mysqli_stmt_bind_param($stmt, "s", $kode);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $ticket = mysqli_fetch_assoc($result);
                    
                    if ($ticket):
                        $status = $ticket['status_pendaftaran'];
                        $isValid = ($status === 'Terdaftar' || $status === 'Terverifikasi' || $status === 'Sudah Check-in');
                        $isWaiting = ($status === 'Menunggu Verifikasi');
                        $isRejected = ($status === 'Ditolak');
                        
                        if ($isValid) {
                            $statusClass = 'status-valid';
                            $statusIcon = '✅';
                            $statusMessage = 'TIKET VALID';
                        } elseif ($isWaiting) {
                            $statusClass = 'status-warning';
                            $statusIcon = '⏳';
                            $statusMessage = 'MENUNGGU VERIFIKASI';
                        } else {
                            $statusClass = 'status-invalid';
                            $statusIcon = '❌';
                            $statusMessage = 'TIKET TIDAK VALID';
                        }
                        
                        $hargaFormatted = ($ticket['tipe_tiket'] === 'Berbayar' && $ticket['harga_event']) ? 
                            'Rp ' . number_format($ticket['harga_event'], 0, ',', '.') : 'Gratis';
                    ?>
                        <div style="text-align: center;">
                            <div class="status-badge <?php echo $statusClass; ?>">
                                <i class="fa-solid <?php echo $isValid ? 'fa-circle-check' : ($isWaiting ? 'fa-clock' : 'fa-circle-xmark'); ?>"></i>
                                <?php echo $statusIcon; ?> <?php echo $statusMessage; ?>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-row">
                                <div class="info-label">🎫 Kode Tiket</div>
                                <div class="info-value"><strong><?php echo htmlspecialchars($ticket['kode_pendaftaran']); ?></strong></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">👤 Nama Peserta</div>
                                <div class="info-value"><?php echo htmlspecialchars($ticket['nama_peserta']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">📧 Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($ticket['email_peserta']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">📱 No Telepon</div>
                                <div class="info-value"><?php echo htmlspecialchars($ticket['no_telepon']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">🎪 Nama Event</div>
                                <div class="info-value"><strong><?php echo htmlspecialchars($ticket['nama_event']); ?></strong></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">📅 Tanggal Event</div>
                                <div class="info-value">
                                    <?php echo date('d F Y', strtotime($ticket['tanggal'])); ?>
                                    <br><small><?php echo date('H:i', strtotime($ticket['waktu'])); ?> - <?php echo date('H:i', strtotime($ticket['waktu_selesai'])); ?> WIB</small>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">📍 Lokasi</div>
                                <div class="info-value"><?php echo htmlspecialchars($ticket['lokasi']); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">🎟️ Tiket</div>
                                <div class="info-value"><?php echo htmlspecialchars($ticket['tipe_tiket']); ?> (<?php echo $hargaFormatted; ?>)</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">📆 Tanggal Daftar</div>
                                <div class="info-value"><?php echo date('d F Y H:i', strtotime($ticket['tanggal_daftar'])); ?></div>
                            </div>
                        </div>
                        
                        <?php if ($isValid && $ticket['is_checked_in'] == 1): ?>
                            <div class="success-box">
                                <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                                <strong>✓ Sudah Check-in</strong><br>
                                <small>Waktu check-in: <?php echo date('d F Y H:i', strtotime($ticket['check_in_time'])); ?></small>
                            </div>
                        <?php elseif ($isValid): ?>
                            <div class="success-box">
                                <i class="fa-solid fa-check-circle" style="color: #10b981;"></i>
                                <strong>✓ Tiket Valid</strong><br>
                                <small>Peserta dapat memasuki acara. Tiket belum digunakan untuk check-in.</small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($isWaiting): ?>
                            <div class="warning-box">
                                <i class="fa-solid fa-clock" style="color: #f59e0b;"></i>
                                <strong>⏳ Menunggu Verifikasi Pembayaran</strong><br>
                                <small>Tiket ini masih dalam proses verifikasi oleh panitia. Silakan hubungi panitia event untuk info lebih lanjut.</small>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($isRejected): ?>
                            <div class="warning-box">
                                <i class="fa-solid fa-ban" style="color: #dc2626;"></i>
                                <strong>❌ Tiket Ditolak</strong><br>
                                <small>Pendaftaran ditolak. Silakan hubungi panitia event untuk informasi lebih lanjut.</small>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <a href="verify.html" class="btn-back" style="flex: 1;">
                                <i class="fa-solid fa-qrcode"></i> Scan Lagi
                            </a>
                            <a href="EVENT.html" class="btn-primary" style="flex: 1;">
                                <i class="fa-solid fa-home"></i> Ke Beranda
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fa-solid fa-circle-exclamation"></i>
                            </div>
                            <h3 style="margin-bottom: 10px; color: #dc2626;">Tiket Tidak Ditemukan</h3>
                            <p style="color: #64748b; margin-bottom: 25px;">
                                Kode tiket <strong><?php echo htmlspecialchars($kode); ?></strong> tidak terdaftar dalam sistem EVENTRA.
                            </p>
                            <div class="qr-placeholder">
                                <i class="fa-solid fa-question-circle" style="font-size: 2rem; color: #ff7a00; margin-bottom: 10px; display: block;"></i>
                                <p style="font-size: 0.85rem;">Pastikan QR Code yang Anda scan valid dan berasal dari sistem EVENTRA.</p>
                            </div>
                            <a href="verify.html" class="btn-primary">
                                <i class="fa-solid fa-arrow-left"></i> Kembali ke Scanner
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="footer">
                <i class="fa-regular fa-copyright"></i> 2026 EVENTRA - Sistem Manajemen Event Kampus
            </div>
        </div>
    </div>
    
    <script>
        function verifyManual() {
            const kode = document.getElementById('manualKode').value.trim();
            if (kode) {
                window.location.href = `verify_ticket.php?kode=${encodeURIComponent(kode)}`;
            } else {
                alert('Masukkan kode tiket terlebih dahulu!');
            }
        }
        
        // Auto submit jika ada parameter kode di URL
        const urlParams = new URLSearchParams(window.location.search);
        const kodeParam = urlParams.get('kode');
        if (kodeParam && document.getElementById('manualKode')) {
            document.getElementById('manualKode').value = kodeParam;
        }
    </script>
</body>
</html>