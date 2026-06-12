import os
import re

dir_path = 'BACKEND'
for root, dirs, files in os.walk(dir_path):
    for file in files:
        if file.endswith('.html') or file.endswith('.php'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()

            if file == 'CREATEEVENT.html':
                content = content.replace('name="nama_event"', 'name="Nama_Event"')
                content = content.replace('name="tanggal"', 'name="Tanggal_Event"')
                content = content.replace('name="banner_img"', 'name="Poster_Event"')
                content = content.replace('name="kategori"', 'name="Id_Kategori"')
                content = content.replace('id="nama_event"', 'id="Nama_Event"')
                content = content.replace('id="tanggal"', 'id="Tanggal_Event"')
                content = content.replace('id="kategori"', 'id="Id_Kategori"')

            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
print("Backend HTML Refactoring Complete")
