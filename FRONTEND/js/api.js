// js/api.js
// Konfigurasi URL Backend.
// Jika diunggah ke Vercel dan backend ke InfinityFree, ubah BASE_URL ini ke domain InfinityFree Anda.
const BASE_URL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' 
    ? 'http://localhost/UASSoftdev/BACKEND' 
    : 'https://backend-eventra-anda.com/BACKEND'; // Ganti dengan domain cloud Anda nantinya

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

// Export fungsionalitas agar bisa digunakan di file lain jika module, 
// atau bisa dipanggil langsung dari script HTML.
window.api = {
    fetchEvents,
    formatTanggal,
    BASE_URL
};
