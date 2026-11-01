(() => {
  const form = document.querySelector('.form-box');
  const members = document.getElementById('members');
  if (!form || !members) return;
  const radios = [...form.querySelectorAll('[name="tim"]')];
  const update = () => {
    members.classList.toggle('on', radios.find(radio => radio.checked)?.value === 'kelompok');
  };
  radios.forEach(radio => radio.addEventListener('change', update));
  update();
})();
