// js/api.js
const isFrontendFolder = window.location.pathname.includes('/FRONTEND/');
const BASE_URL = isFrontendFolder ? '../BACKEND' : '.';

async function fetchEvents() {
    try {
        const response = await fetch(`${BASE_URL}/Event.php?aksi=ambil_event&t=${Date.now()}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        if (data.status === 'error') return [];
        return data;
    } catch (error) {
        return [];
    }
}

function formatTanggal(tanggalStr) {
    if (!tanggalStr) return '';
    const date = new Date(tanggalStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

async function loginUser(data) {
    data.aksi = 'login';
    const res = await fetch(`${BASE_URL}/User.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

async function registerUser(data) {
    data.aksi = 'daftar';
    try {
        const res = await fetch(`${BASE_URL}/User.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error("Server response:", text);
            throw new Error("Invalid JSON response from server. Check console.");
        }
    } catch (error) {
        throw error;
    }
}

async function updateUser(data) {
    data.aksi = 'update_profil';
    const res = await fetch(`${BASE_URL}/User.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return await res.json();
}

async function submitPendaftaran(data) {
    const res = await fetch(`${BASE_URL}/Pendaftaran_Event.php?aksi=daftar_event`, {
        method: 'POST',
        body: data // FormData for uploads if any, but ERD doesn't have it. We'll pass FormData.
    });
    return await res.json();
}

async function fetchEventUser(userId) {
    try {
        const response = await fetch(`${BASE_URL}/Pendaftaran_Event.php?aksi=event_diikuti&Id_User=${userId}&t=${Date.now()}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        return await response.json();
    } catch (error) {
        return [];
    }
}

function logoutUser() {
    localStorage.removeItem('eventra_user');
    window.location.href = 'index.html';
}

function updateNavbarAuth() {
    const userStr = localStorage.getItem('eventra_user');
    const avatarLink = document.querySelector('.nav-profile-container > a');
    if (!avatarLink) return;

    if (userStr) {
        const user = JSON.parse(userStr);
        avatarLink.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.15); padding: 6px 16px 6px 6px; border-radius: 40px; cursor: pointer; border: 1px solid rgba(255,255,255,0.2);">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--white); display: flex; align-items: center; justify-content: center; color: var(--primary-dark); font-weight: bold; font-size: 1rem;">
                    ${user.Nama ? user.Nama.charAt(0).toUpperCase() : 'U'}
                </div>
                <span style="font-weight: 700; font-size: 0.95rem; color: white;">${user.Nama ? user.Nama.split(' ')[0] : 'User'}</span>
            </div>
        `;
    } else {
        avatarLink.href = 'login.html';
        avatarLink.innerHTML = `
            <button class="btn btn-primary" style="padding: 10px 24px; font-size: 0.9rem;">
                Sign In
            </button>
        `;
    }
}

async function getEventPanitia(userId) {
    try {
        const response = await fetch(`${BASE_URL}/Event.php?aksi=ambil_event_panitia&Id_User=${userId}&t=${Date.now()}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        return await response.json();
    } catch (error) {
        return [];
    }
}

async function deleteEvent(eventId, userId) {
    try {
        const res = await fetch(`${BASE_URL}/Event.php?aksi=hapus_event`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ Id_Event: eventId, Id_User: userId })
        });
        return await res.json();
    } catch (err) {
        return { status: 'error', message: err.message };
    }
}

async function getEventById(eventId) {
    try {
        const response = await fetch(`${BASE_URL}/Event.php?aksi=get_event_by_id&Id_Event=${eventId}&t=${Date.now()}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        return await response.json();
    } catch (error) {
        return { status: 'error', message: error.message };
    }
}

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
    toast.innerHTML = `<span style="line-height:1.4;">${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.4s forwards';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

async function hitungPendaftar(eventId) {
    try {
        const response = await fetch(`${BASE_URL}/Pendaftaran_Event.php?aksi=hitung_pendaftar&Id_Event=${eventId}&t=${Date.now()}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        const data = await response.json();
        return data.total || 0;
    } catch (error) {
        return 0;
    }
}

async function getPendaftarEvent(eventId) {
    try {
        const response = await fetch(`${BASE_URL}/Pendaftaran_Event.php?aksi=ambil_pendaftar_event&Id_Event=${eventId}&t=${Date.now()}`);
        if (!response.ok) throw new Error(`HTTP error!`);
        return await response.json();
    } catch (error) {
        return [];
    }
}

async function verifikasiPembayaran(registrasiId, status) {
    try {
        const res = await fetch(`${BASE_URL}/Pendaftaran_Event.php?aksi=verifikasi_pembayaran`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ registrasi_id: registrasiId, status: status })
        });
        return await res.json();
    } catch (err) {
        return { status: 'error', message: err.message };
    }
}

async function uploadSertifikat(formData) {
    try {
        const res = await fetch(`${BASE_URL}/Sertifikat.php?aksi=upload`, {
            method: 'POST',
            body: formData
        });
        return await res.json();
    } catch (err) {
        return { status: 'error', message: err.message };
    }
}

window.api = {
    fetchEvents, formatTanggal, loginUser, registerUser, updateUser, logoutUser,
    updateNavbarAuth, submitPendaftaran, fetchEventUser, getEventPanitia, deleteEvent, getEventById,
    hitungPendaftar, getPendaftarEvent, verifikasiPembayaran, uploadSertifikat,
    BASE_URL, showToast
};
