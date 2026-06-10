const fs = require('fs');
fetch('https://eventra-api.infinityfree.me/BACKEND/buat_event.php?aksi=tambah_event', {
    method: 'POST',
    body: new URLSearchParams({
        'nama_event': 'Test',
        'kategori': 'Akademik',
        'tanggal': '2024-01-01',
        'waktu': '10:00',
        'tanggal_selesai': '2024-01-01',
        'waktu_selesai': '12:00',
        'lokasi': 'Test',
        'tipe_tiket': 'Gratis',
        'user_id': '1'
    })
}).then(r => r.text()).then(t => {
    fs.writeFileSync('test_output.txt', t);
    console.log('Done');
}).catch(e => console.log('Fetch error:', e.message));
