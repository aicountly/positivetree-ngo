(function () {
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.hero-nav button');
  const prevBtn = document.querySelector('.hero-arrows .prev');
  const nextBtn = document.querySelector('.hero-arrows .next');
  if (!slides.length) return;

  let current = 0;
  let timer;

  function show(index) {
    slides.forEach((s, i) => s.classList.toggle('active', i === index));
    dots.forEach((d, i) => d.classList.toggle('active', i === index));
    current = index;
  }

  function next() {
    show((current + 1) % slides.length);
  }

  function prev() {
    show((current - 1 + slides.length) % slides.length);
  }

  function startAuto() {
    timer = setInterval(next, 6000);
  }

  function resetAuto() {
    clearInterval(timer);
    startAuto();
  }

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      show(i);
      resetAuto();
    });
  });

  if (nextBtn) nextBtn.addEventListener('click', () => { next(); resetAuto(); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prev(); resetAuto(); });

  show(0);
  startAuto();
})();
