document.addEventListener('DOMContentLoaded', () => {

  // --- Profile Edit Mode Toggle ---
  const btnToggleEdit = document.getElementById('btnToggleEdit');
  const profileInfoContainer = document.getElementById('profileInfoContainer');
  const avatarPreview = document.getElementById('avatarPreview');
  const avatarInput = document.getElementById('avatarInput');

  // DOM Elements for values (View Mode)
  const valNama = document.getElementById('valNama');
  const valKampus = document.getElementById('valKampus');
  const valJurusan = document.getElementById('valJurusan');
  const valAngkatan = document.getElementById('valAngkatan');
  const valBio = document.getElementById('valBio');

  // DOM Elements for inputs (Edit Mode)
  const inputNama = document.getElementById('inputNama');
  const inputKampus = document.getElementById('inputKampus');
  const inputJurusan = document.getElementById('inputJurusan');
  const inputAngkatan = document.getElementById('inputAngkatan');
  const inputBio = document.getElementById('inputBio');

  let isEditing = false;

  if (btnToggleEdit) {
    btnToggleEdit.addEventListener('click', () => {
      isEditing = !isEditing;

      if (isEditing) {
        // Switch to Edit Mode
        profileInfoContainer.classList.add('is-editing');
        btnToggleEdit.innerHTML = `<i data-lucide="save" class="icon-sm"></i> Simpan Profil`;
        lucide.createIcons();
        btnToggleEdit.classList.remove('btn-ghost');
        btnToggleEdit.classList.add('btn-primary');
      } else {
        // Save and Switch to View Mode
        valNama.textContent = inputNama.value;
        valKampus.textContent = inputKampus.value;
        valJurusan.textContent = inputJurusan.value;
        valAngkatan.textContent = inputAngkatan.value;
        valBio.textContent = inputBio.value;

        // Also update the header name to match
        document.querySelector('.profile-name').textContent = inputNama.value;

        profileInfoContainer.classList.remove('is-editing');
        btnToggleEdit.innerHTML = `<i data-lucide="pencil" class="icon-sm"></i> Edit Profil`;
        lucide.createIcons();
        btnToggleEdit.classList.remove('btn-primary');
        btnToggleEdit.classList.add('btn-ghost');
      }
    });
  }

  // Allow clicking avatar to upload new image when in edit mode
  if (avatarPreview && avatarInput) {
    avatarPreview.addEventListener('click', () => {
      if (isEditing) {
        avatarInput.click();
      }
    });

    avatarInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          avatarPreview.src = e.target.result;
          // Also update nav avatar if it exists
          const navAvatar = document.querySelector('.nav-avatar');
          if (navAvatar) navAvatar.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // --- Delete Account Modal ---
  const btnDeleteAccount = document.getElementById('btnDeleteAccount');
  const deleteModal = document.getElementById('deleteModal');
  const btnCancelDelete = document.getElementById('btnCancelDelete');
  const btnConfirmDelete = document.getElementById('btnConfirmDelete');

  if (btnDeleteAccount && deleteModal) {
    btnDeleteAccount.addEventListener('click', () => {
      deleteModal.classList.add('active');
    });

    btnCancelDelete.addEventListener('click', () => {
      deleteModal.classList.remove('active');
    });

    // Close on overlay click
    deleteModal.addEventListener('click', (e) => {
      if (e.target === deleteModal) {
        deleteModal.classList.remove('active');
      }
    });

    // Mock confirm action
    btnConfirmDelete.addEventListener('click', () => {
      alert("Account deleted simulation. Redirecting to home...");
      window.location.href = "index.html";
    });
  }

});
