import os
import re

html_files = ["index.html", "search.html", "activity.html", "profile.html"]
js_files = ["profile.js"]
css_file = "styles.css"

emoji_map = {
    "🎉": '<i data-lucide="party-popper" class="icon-sm"></i>',
    "🎪": '<i data-lucide="tent" class="icon-sm"></i>',
    "🏫": '<i data-lucide="building-2" class="icon-sm"></i>',
    "👥": '<i data-lucide="users" class="icon-sm"></i>',
    "📅": '<i data-lucide="calendar" class="icon-sm"></i>',
    "📍": '<i data-lucide="map-pin" class="icon-sm"></i>',
    "⏱": '<i data-lucide="timer" class="icon-sm"></i>',
    "🔴": '<i data-lucide="radio" class="icon-sm"></i>',
    "✅": '<i data-lucide="check-circle" class="icon-sm"></i>',
    "📜": '<i data-lucide="scroll" class="icon-sm"></i>',
    "⭐": '<i data-lucide="star" class="icon-sm"></i>',
    "✏️": '<i data-lucide="pencil" class="icon-sm"></i>',
    "⚙️": '<i data-lucide="settings" class="icon-sm"></i>',
    "❤️": '<i data-lucide="heart" class="icon-sm"></i>',
    "🎓": '<i data-lucide="graduation-cap" class="icon-sm"></i>',
    "🛠": '<i data-lucide="wrench" class="icon-sm"></i>',
    "🏆": '<i data-lucide="trophy" class="icon-sm"></i>',
    "🎵": '<i data-lucide="music" class="icon-sm"></i>',
    "🗣": '<i data-lucide="message-circle" class="icon-sm"></i>',
    "⚽": '<i data-lucide="dribbble" class="icon-sm"></i>',
    "💾": '<i data-lucide="bookmark" class="icon-sm"></i>',
    "👤": '<i data-lucide="user" class="icon-sm"></i>',
    "📋": '<i data-lucide="clipboard-list" class="icon-sm"></i>',
    "🗑": '<i data-lucide="trash-2" class="icon-sm"></i>',
    "👁️": '<i data-lucide="eye" class="icon-sm"></i>',
    "🎨": '<i data-lucide="palette" class="icon-sm"></i>',
    "🟢": '<i data-lucide="circle-dot" class="icon-sm"></i>',
}

# 1. Update HTML files
for file in html_files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Replace emojis
        for emoji, lucide_html in emoji_map.items():
            content = content.replace(emoji, lucide_html)
        
        # Add Lucide script in head if not present
        if '<script src="https://unpkg.com/lucide@latest"></script>' not in content:
            content = content.replace('</head>', '  <script src="https://unpkg.com/lucide@latest"></script>\n</head>')

        # Add lucide.createIcons() before </body> if not present
        if 'lucide.createIcons();' not in content:
            content = content.replace('</body>', '  <script>lucide.createIcons();</script>\n</body>')

        with open(file, 'w', encoding='utf-8') as f:
            f.write(content)

# 2. Update profile.js
for file in js_files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        content = content.replace("'💾 Simpan Profil'", "`<i data-lucide=\"save\" class=\"icon-sm\"></i> Simpan Profil`")
        content = content.replace("'✏️ Edit Profil'", "`<i data-lucide=\"pencil\" class=\"icon-sm\"></i> Edit Profil`")
        
        # We need to call lucide.createIcons() after replacing innerHTML
        # Instead of parsing heavily, we just replace the btnToggleEdit.innerHTML lines
        content = content.replace(
            "btnToggleEdit.innerHTML = `<i data-lucide=\"save\" class=\"icon-sm\"></i> Simpan Profil`;", 
            "btnToggleEdit.innerHTML = `<i data-lucide=\"save\" class=\"icon-sm\"></i> Simpan Profil`;\n        lucide.createIcons();"
        )
        content = content.replace(
            "btnToggleEdit.innerHTML = `<i data-lucide=\"pencil\" class=\"icon-sm\"></i> Edit Profil`;", 
            "btnToggleEdit.innerHTML = `<i data-lucide=\"pencil\" class=\"icon-sm\"></i> Edit Profil`;\n        lucide.createIcons();"
        )

        with open(file, 'w', encoding='utf-8') as f:
            f.write(content)

# 3. Add CSS for icon-sm
with open(css_file, 'a', encoding='utf-8') as f:
    f.write("\n\n/* Lucide Icon Base Styles */\n.icon-sm { width: 18px; height: 18px; display: inline-block; vertical-align: middle; margin-right: 4px; margin-top: -2px; }\n")

print("Done replacing emojis with Lucide icons.")
