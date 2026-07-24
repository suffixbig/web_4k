function showToast(message) {
  const $toast = $('#toast');
  $toast.text(message).addClass('show');
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => $toast.removeClass('show'), 2600);
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text);
    showToast('已複製，可直接貼到 Codex 或 Claude 使用。');
  } catch {
    showToast('目前無法存取剪貼簿，請手動複製。');
  }
}

$(function () {
  const carousel = document.querySelector('[data-home-carousel]');
  if (carousel) {
    const slides = [...carousel.querySelectorAll('[data-home-feature-slide]')];
    const dots = [...carousel.querySelectorAll('[data-home-go-slide]')];
    const previousButton = carousel.querySelector('#homePrevSlide');
    const nextButton = carousel.querySelector('#homeNextSlide');
    const status = carousel.querySelector('#homeSlideStatus');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeIndex = 0;
    let pointerStart = null;

    function showSlide(requestedIndex, announce = true) {
      if (!slides.length) return;
      activeIndex = (requestedIndex + slides.length) % slides.length;
      slides.forEach((slide, index) => {
        const isActive = index === activeIndex;
        slide.classList.toggle('is-active', isActive);
        slide.setAttribute('aria-hidden', String(!isActive));
        slide.toggleAttribute('inert', !isActive);
      });
      dots.forEach((dot, index) => {
        const isActive = index === activeIndex;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-current', String(isActive));
      });
      if (announce && status) status.textContent = `已顯示第 ${activeIndex + 1} 張，共 ${slides.length} 張`;
    }

    previousButton?.addEventListener('click', () => showSlide(activeIndex - 1));
    nextButton?.addEventListener('click', () => showSlide(activeIndex + 1));
    dots.forEach(dot => dot.addEventListener('click', () => showSlide(Number(dot.dataset.homeGoSlide))));
    carousel.addEventListener('keydown', event => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      showSlide(activeIndex + (event.key === 'ArrowRight' ? 1 : -1));
    });
    carousel.addEventListener('pointerdown', event => {
      if (event.pointerType === 'mouse' || event.target.closest('a,button,input,textarea,select')) return;
      pointerStart = { id: event.pointerId, x: event.clientX, y: event.clientY };
    });
    carousel.addEventListener('pointerup', event => {
      if (!pointerStart || pointerStart.id !== event.pointerId) return;
      const deltaX = event.clientX - pointerStart.x;
      const deltaY = event.clientY - pointerStart.y;
      pointerStart = null;
      if (Math.abs(deltaX) < 48 || Math.abs(deltaX) <= Math.abs(deltaY)) return;
      showSlide(activeIndex + (deltaX < 0 ? 1 : -1));
    });
    carousel.addEventListener('pointercancel', () => { pointerStart = null; });
    carousel.addEventListener('pointerleave', event => { if (event.pointerType !== 'mouse') pointerStart = null; });
    if (reducedMotion) carousel.classList.add('reduce-motion');
    showSlide(0, false);
  }

  $(document).on('click', '.hero-copy', function () { copyText($(this).data('prompt')); });
  $('#copyPrompt').on('click', () => copyText($('#skillPrompt').text()));
  $('.home-search-panel').on('submit', function (event) {
    const $input = $(this).find('input[name="q"]');
    if ($input.val().trim()) return;
    event.preventDefault();
    $input.trigger('focus');
    showToast('請先輸入想找的桌布內容。');
  });
});
