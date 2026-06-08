import os
import re

# 1. Update CSS
with open('styles.css', 'r', encoding='utf-8') as f:
    css = f.read()

# Header to Floating Pill
css = css.replace("""header {
  position: sticky;
  top: 0;
  width: 100%;
  z-index: 100;
  padding: 16px 0;
}""", """header {
  position: sticky;
  top: 24px;
  width: max-content;
  max-width: 90vw;
  margin: 0 auto;
  z-index: 100;
  padding: 12px 24px;
  border-radius: 9999px;
  transition: all 0.7s cubic-bezier(0.32, 0.72, 0, 1);
}""")

css = css.replace("""
.glass-panel {
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(12px) saturate(150%);
  -webkit-backdrop-filter: blur(12px) saturate(150%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.4);
}""", """
.glass-panel {
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(24px) saturate(200%);
  -webkit-backdrop-filter: blur(24px) saturate(200%);
  border: 1px solid rgba(255, 255, 255, 0.5);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
}""")

# Event Card Hover Physics
css = css.replace("""
.event-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(255, 141, 40, 0.15);
}""", """
.event-card:hover {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 20px 40px rgba(255, 141, 40, 0.15), 0 1px 3px rgba(0,0,0,0.02);
}""")

# Doppelrand Classes
if ".glass-card-premium" not in css:
    css += """
/* --- AWWWARDS UI UPGRADES --- */
.glass-card-premium {
  background: rgba(255, 255, 255, 0.4);
  backdrop-filter: blur(24px) saturate(180%);
  -webkit-backdrop-filter: blur(24px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.6);
  border-radius: 2rem;
  padding: 6px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0,0,0,0.02);
  transition: all 0.7s cubic-bezier(0.32, 0.72, 0, 1);
  display: flex;
  flex-direction: column;
}

.glass-card-premium:hover {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 24px 48px rgba(255, 141, 40, 0.15), 0 1px 3px rgba(0,0,0,0.02);
}

.card-inner {
  background: rgba(255, 255, 255, 0.85);
  border-radius: calc(2rem - 6px);
  height: 100%;
  box-shadow: inset 0 1px 1px rgba(255,255,255,0.8), 0 2px 8px rgba(255, 141, 40, 0.05);
  overflow: hidden;
  display: inherit;
  flex-direction: inherit;
}

.horizontal-card.glass-card-premium {
  flex-direction: row;
}

/* Button-in-Button Trailing Icon */
.btn-icon-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  margin-left: 8px;
  margin-right: -8px;
  transition: all 0.7s cubic-bezier(0.32,0.72,0,1);
}

.btn-primary:hover .btn-icon-wrapper {
  transform: translate(3px, -1px) scale(1.05);
  background: rgba(255, 255, 255, 0.3);
}

/* Cinematic Reveal */
.reveal-item {
  opacity: 0;
  transform: translateY(40px);
  filter: blur(8px);
  transition: all 0.9s cubic-bezier(0.32, 0.72, 0, 1);
  will-change: transform, opacity, filter;
}

.reveal-visible {
  opacity: 1;
  transform: translateY(0);
  filter: blur(0);
}
"""

# Update primary button physics
css = css.replace("""
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(255, 141, 40, 0.45);
  color: var(--white);
}""", """
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 24px rgba(255, 141, 40, 0.45);
  color: var(--white);
}
.btn-primary:active {
  transform: scale(0.98);
}""")
css = css.replace("""transition: transform 0.15s, box-shadow 0.15s;""", """transition: all 0.7s cubic-bezier(0.32, 0.72, 0, 1);""")

# Change spacing in search and activity
css = css.replace("margin: 48px 0 24px;", "margin: 80px 0 32px;") # more whitespace

with open('styles.css', 'w', encoding='utf-8') as f:
    f.write(css)

# 2. Update HTML files
html_files = ["index.html", "search.html", "activity.html", "profile.html"]

for file in html_files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            html = f.read()

        # Wrap event cards in card-inner
        # This requires some regex or smart replace
        # We find `<article class="event-card glass-card">` and `<article class="horizontal-card glass-card">`
        # We change them to `<article class="event-card glass-card-premium reveal-item">`
        # and insert `<div class="card-inner">` right after.
        # Then we must find the matching `</article>` and insert `</div>` before it.
        
        # A simpler way: we know the structure.
        
        html = html.replace('<article class="event-card glass-card">', '<article class="event-card glass-card-premium reveal-item">\n<div class="card-inner">')
        html = html.replace('<article class="event-card glass-card skeleton-card" aria-hidden="true">', '<article class="event-card glass-card-premium skeleton-card reveal-item" aria-hidden="true">\n<div class="card-inner">')
        html = html.replace('<article class="horizontal-card glass-card">', '<article class="horizontal-card glass-card-premium reveal-item">\n<div class="card-inner">')
        html = html.replace('<article class="horizontal-card glass-card" style="border: 2px solid #DC2626;">', '<article class="horizontal-card glass-card-premium reveal-item" style="border: 2px solid #DC2626;">\n<div class="card-inner">')
        
        # Closing div before </article>
        html = html.replace('</article>', '</div>\n</article>')
        
        # Wrap the "→" in buttons with btn-icon-wrapper
        html = html.replace('Daftar Sekarang →</button>', 'Daftar Sekarang <span class="btn-icon-wrapper">→</span></button>')
        html = html.replace('Jelajahi Event Sekarang</button>', 'Jelajahi Event Sekarang <span class="btn-icon-wrapper">→</span></button>')
        html = html.replace('Daftar Sekarang →', 'Daftar Sekarang <span class="btn-icon-wrapper">→</span>')

        # Add reveal-item to other major sections
        html = html.replace('<section class="hero"', '<section class="hero reveal-item"')
        html = html.replace('<aside class="filter-sidebar glass-card">', '<aside class="filter-sidebar glass-card reveal-item">')
        html = html.replace('<div class="stat-pill glass-card">', '<div class="stat-pill glass-card reveal-item">')
        
        # If activity.html or profile.html, ensure app.js is included
        if file in ['activity.html', 'profile.html']:
            if '<script src="app.js"></script>' not in html:
                html = html.replace('</body>', '  <script src="app.js"></script>\n</body>')
        
        with open(file, 'w', encoding='utf-8') as f:
            f.write(html)

print("UI/UX Upgrade Applied")
