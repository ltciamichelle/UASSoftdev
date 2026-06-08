document.addEventListener('DOMContentLoaded', () => {

  // --- Tab Navigation ---
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      // Remove active from all
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      
      // Add active to clicked
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab).classList.add('active');

      // Re-trigger progress bar animation if tab-org is opened
      if (btn.dataset.tab === 'tab-org') {
        animateProgressBars();
      }
    });
  });

  // --- Live Countdown Timers ---
  const timers = document.querySelectorAll('.countdown-box');
  
  // Set mock target dates based on the data attributes
  const now = new Date().getTime();
  const targetDates = {
    'green': now + (10 * 24 * 60 * 60 * 1000) + (5 * 60 * 60 * 1000), // 10 days, 5 hours
    'red': now + (12 * 60 * 60 * 1000) + (30 * 60 * 1000) // 12 hours, 30 mins
  };

  setInterval(() => {
    const currentNow = new Date().getTime();

    timers.forEach(timer => {
      const targetType = timer.dataset.target;
      if (!targetType) return;

      const targetTime = targetDates[targetType];
      const distance = targetTime - currentNow;

      if (distance < 0) {
        timer.querySelector('.time-display').innerHTML = "EXPIRED";
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      timer.querySelector('.time-display').innerHTML = 
        `${days} hari ${hours} jam ${minutes} menit ${seconds} detik`;
    });
  }, 1000);

  // --- Feedback Form Interaction ---
  const btnFeedback = document.querySelector('.btn-feedback');
  const feedbackPanel = document.querySelector('.feedback-panel');
  const btnSubmit = document.querySelector('.btn-submit-feedback');
  const thankYouState = document.querySelector('.feedback-thank-you');
  const stars = document.querySelectorAll('.star-rating svg');
  const feedbackChips = document.querySelectorAll('.feedback-chips .chip');
  const textarea = document.querySelector('.feedback-textarea');
  const charCounter = document.querySelector('.char-counter');

  // Toggle Panel
  if (btnFeedback && feedbackPanel) {
    btnFeedback.addEventListener('click', () => {
      feedbackPanel.classList.toggle('active');
    });
  }

  // Star Rating
  stars.forEach((star, index) => {
    star.addEventListener('click', () => {
      stars.forEach((s, i) => {
        if (i <= index) {
          s.classList.add('active');
        } else {
          s.classList.remove('active');
        }
      });
    });
  });

  // Chips
  feedbackChips.forEach(chip => {
    chip.addEventListener('click', () => {
      chip.classList.toggle('active');
    });
  });

  // Textarea char counter
  if (textarea && charCounter) {
    textarea.addEventListener('input', () => {
      charCounter.textContent = `${textarea.value.length} / 300`;
    });
  }

  // Submit Feedback
  if (btnSubmit) {
    btnSubmit.addEventListener('click', () => {
      feedbackPanel.style.display = 'none';
      thankYouState.classList.add('active');
      btnFeedback.style.display = 'none'; // hide the trigger button
    });
  }

  // --- Organizer Dashboard Progress Bars ---
  function animateProgressBars() {
    const fills = document.querySelectorAll('.progress-fill');
    fills.forEach(fill => {
      const target = fill.dataset.progress;
      fill.style.width = '0%';
      setTimeout(() => {
        fill.style.width = target + '%';
      }, 100); // slight delay for animation
    });
  }
  // Initialize once
  animateProgressBars();

  // --- Modals and Drawers ---
  const certModal = document.getElementById('certModal');
  const btnCert = document.querySelector('.btn-cert');
  const closeBtns = document.querySelectorAll('.modal-close');
  
  const drawer = document.getElementById('participantDrawer');
  const drawerOverlay = document.getElementById('drawerOverlay');
  const btnParticipants = document.querySelector('.btn-participants');

  if (btnCert) {
    btnCert.addEventListener('click', () => {
      certModal.classList.add('active');
    });
  }

  if (btnParticipants) {
    btnParticipants.addEventListener('click', () => {
      drawer.classList.add('active');
      drawerOverlay.classList.add('active');
    });
  }

  closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      certModal.classList.remove('active');
      drawer.classList.remove('active');
      drawerOverlay.classList.remove('active');
    });
  });

  if (drawerOverlay) {
    drawerOverlay.addEventListener('click', () => {
      drawer.classList.remove('active');
      drawerOverlay.classList.remove('active');
    });
  }

  // --- Developer Toggle for Tab B State ---
  let isOrganizer = false;
  const devToggle = document.getElementById('devToggle');
  const upgradePrompt = document.getElementById('org-upgrade');
  const dashboard = document.getElementById('org-dashboard');

  if (devToggle) {
    devToggle.addEventListener('click', () => {
      isOrganizer = !isOrganizer;
      if (isOrganizer) {
        upgradePrompt.style.display = 'none';
        dashboard.style.display = 'block';
        animateProgressBars();
      } else {
        upgradePrompt.style.display = 'block';
        dashboard.style.display = 'none';
      }
    });
  }

});
