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
    data.aksi = 'daftar';
    const res = await fetch(`${BASE_URL}/pendaftaran.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

async function fetchEventUser(userId) {
    try {
        const response = await fetch(`${BASE_URL}/pendaftaran.php?aksi=ambil_event_user&user_id=${userId}`);
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
    const avatarLink = document.querySelector('.fluid-nav > a[href="profile.html"]');
    
    // Jika tidak ada avatarLink (misal di halaman login), return.
    if (!avatarLink) return;

    if (userStr) {
        // Sudah Login
        const user = JSON.parse(userStr);
        avatarLink.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; background: rgba(0,0,0,0.05); padding: 6px 16px 6px 6px; border-radius: 40px; cursor: pointer;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1rem;">
                    ${user.nama.charAt(0).toUpperCase()}
                </div>
                <span style="font-weight: 700; font-size: 0.95rem;">${user.nama.split(' ')[0]}</span>
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
    BASE_URL
};
