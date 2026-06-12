import os
import re

dir_path = 'FRONTEND'
for root, dirs, files in os.walk(dir_path):
    for file in files:
        if file.endswith('.html') or file.endswith('.js'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()

            # JS event object properties
            content = re.sub(r'event\.id\b', 'event.Id_Event', content)
            content = re.sub(r'event\.nama_event\b', 'event.Nama_Event', content)
            content = re.sub(r'event\.banner_img\b', 'event.Poster_Event', content)
            content = re.sub(r'event\.tanggal\b', 'event.Tanggal_Event', content)
            content = re.sub(r'event\.lokasi\b', 'event.Lokasi', content)
            content = re.sub(r'event\.deskripsi\b', 'event.Deskripsi', content)
            content = re.sub(r'event\.kategori\b', 'event.Nama_Kategori', content)
            content = re.sub(r'e\.id\b', 'e.Id_Event', content)
            content = re.sub(r'e\.nama_event\b', 'e.Nama_Event', content)
            content = re.sub(r'e\.banner_img\b', 'e.Poster_Event', content)
            content = re.sub(r'e\.tanggal\b', 'e.Tanggal_Event', content)
            content = re.sub(r'e\.lokasi\b', 'e.Lokasi', content)
            
            # JS user object properties
            content = re.sub(r'user\.user_id\b', 'user.Id_User', content)
            content = re.sub(r'userObj\.user_id\b', 'userObj.Id_User', content)
            content = re.sub(r'userObj\.id\b', 'userObj.Id_User', content)
            content = re.sub(r'user\.nama\b', 'user.Nama', content)
            content = re.sub(r'user\.email\b', 'user.Email', content)

            # Form data and inputs
            content = content.replace('name="event_id"', 'name="Id_Event"')
            content = content.replace("formData.append('user_id', user.user_id)", "formData.append('Id_User', user.Id_User)")
            content = content.replace("formData.append('user_id', user.Id_User)", "formData.append('Id_User', user.Id_User)")

            # In activity.html it maps `activity`
            content = re.sub(r'activity\.nama_event\b', 'activity.Nama_Event', content)
            content = re.sub(r'activity\.tanggal\b', 'activity.Tanggal_Event', content)
            content = re.sub(r'activity\.status_pendaftaran\b', 'activity.Status_Pendaftaran', content)
            content = re.sub(r'activity\.tanggal_daftar\b', 'activity.Tanggal_Daftar', content)
            content = re.sub(r'activity\.id\b', 'activity.Id_Pendaftaran', content)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
print("Frontend Refactoring Complete")
