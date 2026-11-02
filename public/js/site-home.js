(() => {
  const notificationDialog = document.getElementById('notifikasi');
  const notificationTrigger = document.getElementById('notifications-trigger');
  const notificationClose = notificationDialog?.querySelector('.notification-close');

  if (notificationDialog && notificationTrigger) {
    const openNotifications = () => {
      if (!notificationDialog.open) notificationDialog.showModal();
      notificationTrigger.setAttribute('aria-expanded', 'true');
    };
    const closeNotifications = () => {
      notificationDialog.close();
      notificationTrigger.setAttribute('aria-expanded', 'false');
      notificationTrigger.focus();
    };

    notificationTrigger.addEventListener('click', openNotifications);
    notificationClose?.addEventListener('click', closeNotifications);
    notificationDialog.addEventListener('close', () => notificationTrigger.setAttribute('aria-expanded', 'false'));
    notificationDialog.addEventListener('click', (event) => {
      if (event.target === notificationDialog) closeNotifications();
    });

    if (window.location.hash === '#notifikasi') openNotifications();
  }

  const car = document.getElementById('car');
  if (!car) return;
  const track = document.getElementById('track');
  const dots = document.getElementById('dots');
  const caption = document.getElementById('slide-caption');
  const slides = [
    { src: car.dataset.bookImage, alt: 'Ilustrasi ajakan mengikuti lomba buku cerita HAM', title: 'LOMBA BUKU CERITA HAM' },
    { src: car.dataset.comicImage, alt: 'Ilustrasi ajakan mengikuti lomba serial komik HAM', title: 'LOMBA SERIAL KOMIK HAM' }
  ];
  let index = 0;
  let timer;

  slides.forEach((item, position) => {
    const slide = document.createElement('div');
    slide.className = 'slide';
    const image = document.createElement('img');
    image.src = item.src;
    image.alt = item.alt;
    slide.appendChild(image);
    track.appendChild(slide);

    const dot = document.createElement('button');
    dot.type = 'button';
    dot.setAttribute('aria-label', `Foto ${position + 1}`);
    dot.addEventListener('click', () => show(position, true));
    dots.appendChild(dot);
  });

  function show(next, restart = false) {
    index = (next + slides.length) % slides.length;
    track.style.transform = `translateX(${-index * 100}%)`;
    caption.textContent = slides[index].title;
    [...dots.children].forEach((dot, position) => dot.classList.toggle('on', position === index));
    if (restart) start();
  }
  function start() {
    clearInterval(timer);
    if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
      timer = setInterval(() => show(index + 1), 3500);
    }
  }

  document.getElementById('prev').addEventListener('click', () => show(index - 1, true));
  document.getElementById('next').addEventListener('click', () => show(index + 1, true));
  car.addEventListener('mouseenter', () => clearInterval(timer));
  car.addEventListener('mouseleave', start);
  show(0);
  start();

  const phones = document.getElementById('phones');
  const cell = phones?.parentElement;
  if (cell && 'ResizeObserver' in window) {
    const fit = () => {
      phones.style.zoom = matchMedia('(max-width: 760px)').matches ? 1 : Math.min(1, cell.clientWidth / 568);
    };
    new ResizeObserver(fit).observe(cell);
    fit();
  }
})();
