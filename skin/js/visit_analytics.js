document.querySelectorAll('.analytics-filter').forEach((input) => {
  input.addEventListener('input', () => {
    const panel = input.closest('.analytics-panel');
    const keyword = input.value.trim().toLowerCase();
    if (!panel) return;

    panel.querySelectorAll('tbody tr').forEach((row) => {
      row.hidden = keyword !== '' && !row.textContent.toLowerCase().includes(keyword);
    });
  });
});
