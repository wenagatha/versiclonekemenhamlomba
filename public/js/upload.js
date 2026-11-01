(() => {
  const limits = { pdf: 100, word: 100, pernyataan: 10 };
  Object.entries(limits).forEach(([name, maxMb]) => {
    const input = document.querySelector(`[name="${name}"]`);
    if (!input) return;
    input.addEventListener('change', () => {
      const file = input.files?.[0];
      if (file && file.size > maxMb * 1024 * 1024) {
        input.setCustomValidity(`Ukuran berkas maksimal ${maxMb} MB.`);
        input.reportValidity();
      } else {
        input.setCustomValidity('');
      }
    });
  });
})();
