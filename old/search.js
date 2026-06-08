document.addEventListener('DOMContentLoaded', () => {

  // Debounce helper
  function debounce(func, delay) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => {
        func.apply(this, args);
      }, delay);
    };
  }

  // Instant Results Dropdown Logic
  const searchInput = document.getElementById('searchInput');
  const searchDropdown = document.getElementById('searchDropdown');
  const resultsContainer = document.getElementById('resultsContainer');
  const zeroState = document.getElementById('zeroState');

  const handleSearch = debounce((e) => {
    const val = e.target.value.trim().toLowerCase();
    
    // Simulate Instant Dropdown
    if (val.length > 0) {
      searchDropdown.classList.add('active');
    } else {
      searchDropdown.classList.remove('active');
    }

    // Simulate Zero State
    if (val === 'kosong' || val === 'zero' || val === 'nothing') {
      resultsContainer.style.display = 'none';
      zeroState.classList.add('active');
      searchDropdown.classList.remove('active'); // Hide dropdown when searching for zero state
    } else {
      resultsContainer.style.display = 'block';
      zeroState.classList.remove('active');
    }
  }, 300);

  if (searchInput) {
    searchInput.addEventListener('input', handleSearch);

    // Hide dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
        searchDropdown.classList.remove('active');
      }
    });
  }

  // Sidebar Toggles Logic
  const toggleGroups = document.querySelectorAll('.toggle-group');
  toggleGroups.forEach(group => {
    const btns = group.querySelectorAll('.toggle-btn');
    btns.forEach(btn => {
      btn.addEventListener('click', () => {
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
      });
    });
  });

  // Save button interaction
  const saveBtns = document.querySelectorAll('.btn-save');
  saveBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
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
