(function () {
  const modal = document.getElementById('donate-modal');
  if (!modal) return;

  const overlay = modal;
  const closeBtn = modal.querySelector('.modal-close');

  document.querySelectorAll('[data-donate-placeholder]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      overlay.classList.add('active');
    });
  });

  function close() {
    overlay.classList.remove('active');
  }

  if (closeBtn) closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) close();
  });
})();
