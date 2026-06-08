document.addEventListener('DOMContentLoaded', () => {
  // Cinematic Staggered Entrances (Scroll Reveal)
  const revealItems = document.querySelectorAll('.reveal-item');
  
  const observerOptions = {
    threshold: 0.05,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    // Group entries that are intersecting simultaneously for staggering
    let delay = 0;
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('reveal-visible');
        }, delay);
        delay += 100; // 100ms stagger
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  revealItems.forEach(item => {
    observer.observe(item);
  });

  // Filter Chips Logic
  const chips = document.querySelectorAll('.chip');
  const resetBtn = document.getElementById('resetFilters');
  const dropdowns = document.querySelectorAll('.dropdown');

  function updateResetButtonVisibility() {
    // Check if any chip other than 'Semua' is active, or if dropdowns are changed
    let isFilterActive = false;
    chips.forEach(chip => {
      if (chip.classList.contains('active') && chip.textContent !== 'Semua') {
        isFilterActive = true;
      }
    });

    dropdowns.forEach(dropdown => {
      if (dropdown.selectedIndex > 0) { // If not the first placeholder option
        isFilterActive = true;
      }
    });

    if (isFilterActive) {
      resetBtn.style.display = 'inline-block';
    } else {
      resetBtn.style.display = 'none';
    }
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      if (chip.textContent === 'Semua') {
        // If "Semua" is clicked, deselect others and activate "Semua"
        chips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
      } else {
        // If a specific category is clicked, toggle it
        chip.classList.toggle('active');
        // Deselect "Semua" if it was active
        const semuaChip = Array.from(chips).find(c => c.textContent === 'Semua');
        if (semuaChip) semuaChip.classList.remove('active');
        
        // If no chips are active, re-activate "Semua"
        const anyActive = Array.from(chips).some(c => c.classList.contains('active'));
        if (!anyActive && semuaChip) {
          semuaChip.classList.add('active');
        }
      }
      updateResetButtonVisibility();
    });
  });

  dropdowns.forEach(dropdown => {
    dropdown.addEventListener('change', updateResetButtonVisibility);
  });

  resetBtn.addEventListener('click', () => {
    // Reset chips
    chips.forEach(c => c.classList.remove('active'));
    const semuaChip = Array.from(chips).find(c => c.textContent === 'Semua');
    if (semuaChip) semuaChip.classList.add('active');

    // Reset dropdowns
    dropdowns.forEach(dropdown => {
      dropdown.selectedIndex = 0;
    });

    updateResetButtonVisibility();
  });
  
  // Save button interaction
  const saveBtns = document.querySelectorAll('.btn-save');
  saveBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      // Toggle saved state
      const svg = btn.querySelector('svg');
      if (svg.getAttribute('fill') === 'none') {
        svg.setAttribute('fill', '#ff4757');
        svg.setAttribute('stroke', '#ff4757');
      } else {
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
      }
    });
  });
});
