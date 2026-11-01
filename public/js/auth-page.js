(() => {
  const hero = document.getElementById('hero');
  if (!hero) return;

  const track = document.getElementById('track');
  const dots = document.getElementById('dots');
  const slides = [...track.children];
  let index = 0;
  let timer;

  slides.forEach((_, position) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.setAttribute('aria-label', `Gambar ${position + 1}`);
    dot.addEventListener('click', () => show(position, true));
    dots.appendChild(dot);
  });

  function show(next, restart = false) {
    index = (next + slides.length) % slides.length;
    track.style.transform = `translateX(${-index * 100}%)`;
    [...dots.children].forEach((dot, position) => dot.classList.toggle('on', position === index));
    if (restart) start();
  }
  function start() {
    clearInterval(timer);
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      timer = setInterval(() => show(index + 1), 5000);
    }
  }

  document.getElementById('prev').addEventListener('click', () => show(index - 1, true));
  document.getElementById('next').addEventListener('click', () => show(index + 1, true));
  hero.addEventListener('mouseenter', () => clearInterval(timer));
  hero.addEventListener('mouseleave', start);
  show(0);
  start();

  document.getElementById('eye').addEventListener('click', () => {
    const password = document.getElementById('pw');
    password.type = password.type === 'password' ? 'text' : 'password';
  });
})();
