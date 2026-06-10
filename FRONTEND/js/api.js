// js/api.js
// Konfigurasi URL Backend API
// Menggunakan jalur relatif karena Frontend dan Backend digabungkan di server yang sama (InfinityFree htdocs)
const BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
    ? 'http://localhost/UASSoftdev/BACKEND' 
    : '.';

/**
 * Fetch semua event dari database
 * @returns {Promise<Array>} Array of event objects
 */
async function fetchEvents() {
    try {
        const response = await fetch(`${BASE_URL}/buat_event.php?aksi=ambil_event`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        // Cek jika error dari PHP
        if (data.status === 'error') {
            console.error("Backend Error:", data.message);
            return [];
        }
        
        return data;
    } catch (error) {
        console.error("Gagal mengambil data event:", error);
        return [];
    }
}

/**
 * Format string tanggal (YYYY-MM-DD) ke format cantik (DD MMM YYYY)
 */
function formatTanggal(tanggalStr) {
    if (!tanggalStr) return '';
    const date = new Date(tanggalStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

/**
 * Authentication & Account CRUD
 */
async function loginUser(data) {
    data.aksi = 'login';
    const res = await fetch(`${BASE_URL}/simpan_akun.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

async function registerUser(data) {
    data.aksi = 'daftar';
    const res = await fetch(`${BASE_URL}/simpan_akun.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

async function updateUser(data) {
    data.aksi = 'update_profil';
    const res = await fetch(`${BASE_URL}/simpan_akun.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

async function submitPendaftaran(data) {
    if (data instanceof FormData) {
        data.append('aksi', 'daftar');
        const res = await fetch(`${BASE_URL}/registrasi_event.php`, {
            method: 'POST',
            body: data
        });
        return await res.json();
    } else {
        data.aksi = 'daftar';
        const res = await fetch(`${BASE_URL}/registrasi_event.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await res.json();
    }
}

async function fetchEventUser(userId) {
    try {
        const response = await fetch(`${BASE_URL}/registrasi_event.php?aksi=ambil_event_user&user_id=${userId}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        const data = await response.json();
        return data.status === 'success' ? data.data : [];
    } catch (error) {
        console.error("Gagal mengambil histori pendaftaran:", error);
        return [];
    }
}

async function deleteUser(userId, role) {
    const res = await fetch(`${BASE_URL}/simpan_akun.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' },
        body: JSON.stringify({ aksi: 'hapus_akun', user_id: userId, role: role })
    });
    return await res.json();
}

function logoutUser() {
    localStorage.removeItem('eventra_user');
    window.location.href = 'index.html';
}

/**
 * Update UI Navbar based on Auth State
 */
function updateNavbarAuth() {
    const userStr = localStorage.getItem('eventra_user');
    const avatarLink = document.querySelector('.nav-profile-container > a');
    
    // Jika tidak ada avatarLink (misal di halaman login), return.
    if (!avatarLink) return;

    if (userStr) {
        // Sudah Login
        const user = JSON.parse(userStr);
        avatarLink.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.15); padding: 6px 16px 6px 6px; border-radius: 40px; cursor: pointer; border: 1px solid rgba(255,255,255,0.2);">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--white); display: flex; align-items: center; justify-content: center; color: var(--primary-dark); font-weight: bold; font-size: 1rem;">
                    ${user.nama.charAt(0).toUpperCase()}
                </div>
                <span style="font-weight: 700; font-size: 0.95rem; color: white;">${user.nama.split(' ')[0]}</span>
            </div>
        `;
    } else {
        // Belum Login
        avatarLink.href = 'login.html';
        avatarLink.innerHTML = `
            <button class="btn btn-primary" style="padding: 10px 24px; font-size: 0.9rem;">
                Sign In
            </button>
        `;
    }
}

async function registerPanitia(userId) {
    const res = await fetch(`${BASE_URL}/simpan_akun.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain' },
        body: JSON.stringify({ aksi: 'daftar_panitia', user_id: userId })
    });
    return await res.json();
}

async function getEventPanitia(userId) {
    try {
        const response = await fetch(`${BASE_URL}/buat_event.php?aksi=ambil_event_panitia&id_panitia=${userId}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        const data = await response.json();
        return Array.isArray(data) ? data : [];
    } catch (error) {
        console.error("Gagal mengambil event panitia:", error);
        return [];
    }
}

async function hitungPendaftar(eventId) {
    try {
        const response = await fetch(`${BASE_URL}/registrasi_event.php?aksi=hitung_pendaftar&event_id=${eventId}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        const data = await response.json();
        return data.status === 'success' ? data.total : 0;
    } catch (error) {
        console.error("Gagal menghitung pendaftar:", error);
        return 0;
    }
}

async function incrementEventView(eventId) {
    try {
        await fetch(`${BASE_URL}/buat_event.php?aksi=tambah_view&id=${eventId}`);
    } catch (error) {
        console.error("Gagal menambah view:", error);
    }
}

/**
 * Toast Notification System
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; top: 32px; right: 32px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; pointer-events: none;';
        document.body.appendChild(container);
        
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
            .eventra-toast { padding: 16px 24px; border-radius: 12px; font-weight: 600; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.1); font-family: inherit; font-size: 0.95rem; animation: slideInRight 0.4s cubic-bezier(0.32, 0.72, 0, 1) forwards; max-width: 350px; display: flex; align-items: center; gap: 12px; pointer-events: auto; }
            .toast-success { background: #10b981; }
            .toast-error { background: #ef4444; }
            .toast-info { background: #3b82f6; }
        `;
        document.head.appendChild(style);
    }

    const toast = document.createElement('div');
    toast.className = `eventra-toast toast-${type}`;
    
    let iconSvg = '';
    if (type === 'success') {
        iconSvg = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
    } else if (type === 'error') {
        iconSvg = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
    } else {
        iconSvg = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
    }

    toast.innerHTML = `${iconSvg} <span style="line-height:1.4;">${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.4s forwards';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

async function getPendaftarEvent(eventId) {
    try {
        const res = await fetch(`${BASE_URL}/dashboard_panitia.php?aksi=ambil_pendaftar&event_id=${eventId}`);
        if (!res.ok) throw new Error("Network error");
        const data = await res.json();
        return data.status === 'success' ? data.data : [];
    } catch (err) {
        console.error("Gagal get pendaftar:", err);
        return [];
    }
}

async function deleteEvent(eventId, userId) {
    try {
        const res = await fetch(`${BASE_URL}/buat_event.php?aksi=hapus_event`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId, user_id: userId })
        });
        const data = await res.json();
        return data;
    } catch (err) {
        console.error("Gagal menghapus event:", err);
        return { status: 'error', message: err.message };
    }
}

async function getEventById(eventId) {
    try {
        const response = await fetch(`${BASE_URL}/buat_event.php?aksi=cari_event_id&id=${eventId}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Gagal mengambil event:", error);
        return { status: 'error', message: error.message };
    }
}

async function verifikasiPembayaran(registrasiId, statusBaru) {
    try {
        const res = await fetch(`${BASE_URL}/dashboard_panitia.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ aksi: 'verifikasi_pembayaran', registrasi_id: registrasiId, status: statusBaru })
        });
        return await res.json();
    } catch (err) {
        return { status: 'error', message: 'Gagal jaringan' };
    }
}

// Export fungsionalitas agar bisa digunakan di file lain jika module, 
// atau bisa dipanggil langsung dari script HTML.
window.api = {
    fetchEvents,
    formatTanggal,
    loginUser,
    registerUser,
    updateUser,
    deleteUser,
    logoutUser,
    updateNavbarAuth,
    submitPendaftaran,
    fetchEventUser,
    registerPanitia,
    getEventPanitia,
    hitungPendaftar,
    incrementEventView,
    getPendaftarEvent,
    verifikasiPembayaran,
    BASE_URL,
    showToast,
    deleteEvent,
    getEventById
};
