(() => {
  const slots = [...document.querySelectorAll('[data-ad-slot]')];
  if (!slots.length || !('IntersectionObserver' in window)) return;
  const record = slot => {
    const key = `ads-impression:${location.pathname}:${slot}`;
    if (sessionStorage.getItem(key)) return;
    sessionStorage.setItem(key, '1');
    const body = JSON.stringify({ slot });
    if (navigator.sendBeacon) navigator.sendBeacon('api/ads/impression', new Blob([body], { type: 'application/json' }));
    else fetch('api/ads/impression', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body, keepalive: true }).catch(() => {});
  };
  const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { record(entry.target.dataset.adSlot); observer.unobserve(entry.target); } }), { threshold: 0.5 });
  slots.forEach(slot => observer.observe(slot));
})();
