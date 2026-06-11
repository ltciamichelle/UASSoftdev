<?php
// verifikasi_peserta.php - Halaman verifikasi peserta untuk panitia
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
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

$username_panitia = $userData['username'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Peserta - EVENTRA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <style>
        :root {
            --primary: #ff7a00;
            --primary-light: #ff9533;
            --dark: #0f172a;
            --light: #f8fafc;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR */
        aside {
            width: 280px;
            background: var(--dark);
            color: white;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        aside .logo {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 45px;
            background: linear-gradient(to right, #ff7a00, #ffba73);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }

        .nav-menu { list-style: none; flex-grow: 1; }
        .nav-item { margin-bottom: 8px; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            transition: var(--transition);
            gap: 15px;
            font-weight: 500;
            cursor: pointer;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 122, 0, 0.1);
            color: var(--primary);
        }

        .btn-logout {
            width: 100%; padding: 12px; background: #f43f5e; border: none;
            border-radius: 12px; color: white; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: var(--transition);
        }
        .btn-logout:hover { background: #e11d48; }

        /* MAIN CONTENT */
        main {
            flex-grow: 1;
            margin-left: 280px;
            padding: 40px;
            width: calc(100% - 280px);
        }

        .header-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }
        .header-title h2 { font-size: 1.8rem; font-weight: 800; }
        .header-title p { color: #64748b; font-size: 0.95rem; }

        .badge-role {
            background: rgba(255, 122, 0, 0.08);
            color: var(--primary);
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        /* EVENT SELECTOR */
        .event-selector {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .event-selector select {
            width: 100%;
            padding: 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            outline: none;
            transition: var(--transition);
        }

        .event-selector select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
        }

        /* SEARCH BAR */
        .search-bar {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-bar input {
            flex: 1;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            outline: none;
            transition: var(--transition);
        }

        .search-bar input:focus {
            border-color: var(--primary);
        }

        .search-bar select {
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            outline: none;
        }

        .search-bar button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-bar button:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        /* PARTICIPANT TABLE */
        .participant-table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .participant-table {
            width: 100%;
            border-collapse: collapse;
        }

        .participant-table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
        }

        .participant-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .participant-table tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-verified {
            background: #dcfce7;
            color: #166534;
        }

        .status-waiting {
            background: #fed7aa;
            color: #9a3412;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-checked-in {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-verify {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-reject {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-left: 8px;
        }

        .btn-checkin {
            background: var(--info);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-verify:hover, .btn-reject:hover, .btn-checkin:hover {
            transform: translateY(-2px);
        }

        .btn-qr {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-qr:hover {
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #64748b;
        }

        .stats-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 15px 25px;
            flex: 1;
            min-width: 150px;
            box-shadow: var(--shadow);
        }

        .stat-card h4 {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .modal-container {
            background: white;
            width: 90%;
            max-width: 500px;
            border-radius: 24px;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #f1f5f9;
        }

        .modal-header h3 { font-size: 1.2rem; font-weight: 700; }

        .close-modal {
            cursor: pointer;
            font-size: 1.3rem;
            color: #64748b;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .modal-body {
            padding: 25px;
            text-align: center;
        }

        .qr-display {
            background: white;
            padding: 20px;
            border-radius: 16px;
            margin: 15px 0;
        }

        canvas.qr-canvas {
            width: 200px;
            height: 200px;
        }

        @media (max-width: 768px) {
            aside { display: none; }
            main { margin-left: 0; width: 100%; padding: 20px; }
            .participant-table { display: block; overflow-x: auto; }
            .stats-summary { flex-direction: column; }
        }
    </style>
</head>
<body>

<aside>
    <div class="logo">EVENTRA</div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a href="CREATEEVENT.html" class="nav-link"><i class="fa-solid fa-calendar-plus"></i><span>Buat Event</span></a>
        </li>
        <li class="nav-item">
            <a href="verifikasi_peserta.php" class="nav-link active"><i class="fa-solid fa-user-check"></i><span>Verifikasi Peserta</span></a>
        </li>
    </ul>
    <button class="btn-logout" onclick="handleLogout()"><i class="fa-solid fa-power-off"></i><span>Keluar</span></button>
</aside>

<main>
    <div class="header-panel">
        <div class="header-title">
            <h2><i class="fa-solid fa-user-check"></i> Verifikasi Peserta Event</h2>
            <p>Kelola verifikasi pendaftaran dan check-in peserta per event</p>
        </div>
        <div class="badge-role">
            <i class="fa-solid fa-user-shield"></i>
            <span>Panitia: <?php echo htmlspecialchars($username_panitia); ?></span>
        </div>
    </div>

    <div class="event-selector">
        <select id="eventSelect" onchange="loadParticipants()">
            <option value="">-- Pilih Event --</option>
<?php
                    // Ambil daftar event milik panitia
                    $query_events = "SELECT id, nama_event FROM events WHERE user_id = '{$userData['user_id']}' OR user_id = '{$userData['id']}' ORDER BY tanggal DESC";
                    $result_events = mysqli_query($koneksi, $query_events);
                    
                    if (mysqli_num_rows($result_events) > 0) {
                        while ($row = mysqli_fetch_assoc($result_events)) {
                            echo "<option value='{$row['id']}'>{$row['nama_event']}</option>";
                        }
                    } else {
                        echo "<option value=''>Belum ada event</option>";
                    }
                    ?>
        </select>
    </div>

    <div class="stats-summary" id="statsSummary" style="display: none;">
        <div class="stat-card">
            <h4><i class="fa-solid fa-users"></i> Total Peserta</h4>
            <div class="number" id="totalCount">0</div>
        </div>
        <div class="stat-card">
            <h4><i class="fa-solid fa-clock"></i> Menunggu Verifikasi</h4>
            <div class="number" id="waitingCount">0</div>
        </div>
        <div class="stat-card">
            <h4><i class="fa-solid fa-check-circle"></i> Terverifikasi</h4>
            <div class="number" id="verifiedCount">0</div>
        </div>
        <div class="stat-card">
            <h4><i class="fa-solid fa-sign-in-alt"></i> Sudah Check-in</h4>
            <div class="number" id="checkedInCount">0</div>
        </div>
    </div>

    <div class="search-bar" id="searchBar" style="display: none;">
        <input type="text" id="searchInput" placeholder="Cari berdasarkan nama, email, atau kode tiket...">
        <select id="statusFilter">
            <option value="">Semua Status</option>
            <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
            <option value="Terdaftar">Terverifikasi</option>
            <option value="Ditolak">Ditolak</option>
            <option value="Sudah Check-in">Sudah Check-in</option>
        </select>
        <button onclick="loadParticipants()">
            <i class="fa-solid fa-search"></i> Cari
        </button>
        <button onclick="resetFilters()" style="background: #e2e8f0; color: #1e293b;">
            <i class="fa-solid fa-eraser"></i> Reset
        </button>
    </div>

    <div class="participant-table-container" id="tableContainer">
        <div class="empty-state">
            <i class="fa-solid fa-calendar-alt" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
            <p>Silakan pilih event terlebih dahulu</p>
        </div>
    </div>
</main>

<!-- MODAL QR CODE -->
<div id="qrModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fa-solid fa-qrcode"></i> QR Code Tiket</h3>
            <div class="close-modal" onclick="closeQrModal()">
                <i class="fa-solid fa-xmark"></i>
            </div>
        </div>
        <div class="modal-body">
            <div class="qr-display">
                <canvas id="qrCanvas" class="qr-canvas" width="200" height="200"></canvas>
            </div>
            <div id="qrInfo" style="margin-top: 15px; text-align: left;">
                <p><strong>Kode Tiket:</strong> <span id="qrKode"></span></p>
                <p><strong>Nama Peserta:</strong> <span id="qrNama"></span></p>
                <p><strong>Event:</strong> <span id="qrEvent"></span></p>
            </div>
            <button class="btn-verify" onclick="downloadQRCode()" style="margin-top: 15px; width: 100%;">
                <i class="fa-solid fa-download"></i> Download QR Code
            </button>
        </div>
    </div>
</div>

<script>
    let currentParticipantData = null;
    let currentEventId = null;

    // Load daftar event untuk panitia ini
    function loadEvents() {
        const username = '<?php echo $username_panitia; ?>';
        fetch(`buat_event.php?aksi=ambil_event_panitia&id_panitia=${username}`)
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('eventSelect');
                select.innerHTML = '<option value="">-- Pilih Event --</option>';
                
                if (data && Array.isArray(data) && data.length > 0) {
                    data.forEach(event => {
                        const option = document.createElement('option');
                        option.value = event.id;
                        option.textContent = `${event.nama_event} (${event.id_event}) - ${event.tanggal}`;
                        select.appendChild(option);
                    });
                } else {
                    select.innerHTML = '<option value="">-- Belum ada event --</option>';
                }
            })
            .catch(err => console.error('Error loading events:', err));
    }

    // Load peserta berdasarkan event yang dipilih
    function loadParticipants() {
        const eventId = document.getElementById('eventSelect').value;
        if (!eventId) {
            document.getElementById('statsSummary').style.display = 'none';
            document.getElementById('searchBar').style.display = 'none';
            document.getElementById('tableContainer').innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-alt" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <p>Silakan pilih event terlebih dahulu</p>
                </div>`;
            return;
        }

        currentEventId = eventId;

        const searchTerm = document.getElementById('searchInput').value;
        const statusFilter = document.getElementById('statusFilter').value;

        let url = `api_verifikasi.php?aksi=get_participants&event_id=${eventId}`;
        if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
        if (statusFilter) url += `&status=${encodeURIComponent(statusFilter)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    displayParticipants(data.participants);
                    updateStats(data.stats);
                    document.getElementById('statsSummary').style.display = 'flex';
                    document.getElementById('searchBar').style.display = 'flex';
                } else {
                    document.getElementById('tableContainer').innerHTML = `
                        <div class="empty-state">
                            <i class="fa-solid fa-face-frown" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                            <p>${data.message}</p>
                        </div>`;
                    document.getElementById('statsSummary').style.display = 'none';
                    document.getElementById('searchBar').style.display = 'none';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                document.getElementById('tableContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fa-solid fa-circle-exclamation" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                        <p>Gagal memuat data peserta</p>
                    </div>`;
            });
    }

    function displayParticipants(participants) {
        const container = document.getElementById('tableContainer');
        
        if (!participants || participants.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-users-slash" style="font-size: 3rem; margin-bottom: 15px; display: block;"></i>
                    <p>Belum ada peserta yang mendaftar di event ini</p>
                </div>`;
            return;
        }

        let html = `
            <table class="participant-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Tiket</th>
                        <th>Nama Peserta</th>
                        <th>Email</th>
                        <th>No Telepon</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
        `;

        participants.forEach((p, index) => {
            let statusClass = '';
            let statusText = p.status_pendaftaran;
            
            if (p.status_pendaftaran === 'Terdaftar') {
                statusClass = 'status-verified';
                statusText = '✓ Terverifikasi';
            } else if (p.status_pendaftaran === 'Menunggu Verifikasi') {
                statusClass = 'status-waiting';
                statusText = '⏳ Menunggu Verifikasi';
            } else if (p.status_pendaftaran === 'Ditolak') {
                statusClass = 'status-rejected';
                statusText = '✗ Ditolak';
            } else if (p.status_pendaftaran === 'Sudah Check-in') {
                statusClass = 'status-checked-in';
                statusText = '✓ Sudah Check-in';
            }

            let actionButtons = '';
            
            if (p.status_pendaftaran === 'Menunggu Verifikasi') {
                actionButtons = `
                    <button class="btn-verify" onclick="verifyParticipant(${p.pendaftaran_id}, 'verify')">
                        <i class="fa-solid fa-check"></i> Verifikasi
                    </button>
                    <button class="btn-reject" onclick="verifyParticipant(${p.pendaftaran_id}, 'reject')">
                        <i class="fa-solid fa-times"></i> Tolak
                    </button>
                `;
            } else if (p.status_pendaftaran === 'Terdaftar') {
                actionButtons = `
                    <button class="btn-checkin" onclick="checkInParticipant(${p.pendaftaran_id})">
                        <i class="fa-solid fa-sign-in-alt"></i> Check-in
                    </button>
                `;
            } else if (p.status_pendaftaran === 'Sudah Check-in') {
                actionButtons = `
                    <span style="color: #10b981;">
                        <i class="fa-solid fa-check-circle"></i> Check-in: ${p.check_in_time ? formatDateTime(p.check_in_time) : '-'}
                    </span>
                `;
            }

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        ${p.kode_pendaftaran}
                        <button class="btn-qr" onclick="showQRCode('${p.kode_pendaftaran}', '${escapeJs(p.nama_peserta)}', '${escapeJs(p.nama_event)}')">
                            <i class="fa-solid fa-qrcode"></i>
                        </button>
                    </td>
                    <td>${escapeHtml(p.nama_peserta)}</td>
                    <td>${escapeHtml(p.email_peserta)}</td>
                    <td>${escapeHtml(p.no_telepon)}</td>
                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    <td>${actionButtons}</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    }

    function updateStats(stats) {
        document.getElementById('totalCount').innerText = stats.total || 0;
        document.getElementById('waitingCount').innerText = stats.waiting || 0;
        document.getElementById('verifiedCount').innerText = stats.verified || 0;
        document.getElementById('checkedInCount').innerText = stats.checked_in || 0;
    }

    function verifyParticipant(pendaftaranId, action) {
        if (!confirm(action === 'verify' ? 'Verifikasi pendaftaran peserta ini?' : 'Tolak pendaftaran peserta ini?')) return;

        fetch('api_verifikasi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                aksi: action === 'verify' ? 'verify' : 'reject',
                pendaftaran_id: pendaftaranId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                loadParticipants(); // Refresh list
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Terjadi kesalahan koneksi');
        });
    }

    function checkInParticipant(pendaftaranId) {
        if (!confirm('Konfirmasi check-in untuk peserta ini?')) return;

        fetch('api_verifikasi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                aksi: 'checkin',
                pendaftaran_id: pendaftaranId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                loadParticipants();
            } else {
                alert('Gagal: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Terjadi kesalahan koneksi');
        });
    }

    function generateQRCodeWithUrl(url) {
        return new Promise((resolve, reject) => {
            try {
                const qr = qrcode(0, 'H');
                qr.addData(url);
                qr.make();
                
                const moduleCount = qr.getModuleCount();
                const size = 200;
                const cellSize = size / moduleCount;
                
                const canvas = document.createElement('canvas');
                canvas.width = size;
                canvas.height = size;
                const ctx = canvas.getContext('2d');
                
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, size, size);
                
                for (let row = 0; row < moduleCount; row++) {
                    for (let col = 0; col < moduleCount; col++) {
                        if (qr.isDark(row, col)) {
                            ctx.fillStyle = '#000000';
                            ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                        }
                    }
                }
                
                resolve(canvas);
            } catch (error) {
                reject(error);
            }
        });
    }

    async function showQRCode(kode, nama, eventName) {
        const baseUrl = window.location.protocol + '//' + window.location.host;
        const verifyUrl = `${baseUrl}${window.location.pathname.replace('verifikasi_peserta.php', '')}verify_ticket.php?kode=${encodeURIComponent(kode)}`;
        
        try {
            const canvas = await generateQRCodeWithUrl(verifyUrl);
            const modalCanvas = document.getElementById('qrCanvas');
            const ctx = modalCanvas.getContext('2d');
            modalCanvas.width = canvas.width;
            modalCanvas.height = canvas.height;
            ctx.drawImage(canvas, 0, 0);
            
            document.getElementById('qrKode').innerText = kode;
            document.getElementById('qrNama').innerText = nama;
            document.getElementById('qrEvent').innerText = eventName;
            currentParticipantData = { kode, nama, eventName };
            
            document.getElementById('qrModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } catch (error) {
            console.error('Error generating QR:', error);
            alert('Gagal membuat QR Code');
        }
    }

    function downloadQRCode() {
        const canvas = document.getElementById('qrCanvas');
        if (canvas && currentParticipantData) {
            const link = document.createElement('a');
            link.download = `ticket_${currentParticipantData.kode}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    }

    function closeQrModal() {
        document.getElementById('qrModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        currentParticipantData = null;
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        loadParticipants();
    }

    function formatDateTime(dateTime) {
        if (!dateTime) return '-';
        return new Date(dateTime).toLocaleString('id-ID');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function escapeJs(text) {
        if (!text) return '';
        return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    function handleLogout() {
        if (confirm('Yakin ingin keluar?')) {
            document.cookie = 'user_eventra=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            localStorage.removeItem('user_eventra');
            window.location.href = 'Login.html';
        }
    }

    // Load events on page load
    loadEvents();
</script>
</body>
</html>