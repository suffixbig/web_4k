/* 顯示短暫且不搶走焦點的操作結果提示。 */
function showToast(message) {
  const $toast = $('#toast');
  $toast.text(message).addClass('show');
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => $toast.removeClass('show'), 2600);
}

/* 將 AI 指令複製到剪貼簿。 */
async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text);
    showToast('已複製，可貼到 Codex 或 Claude 使用。');
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
    const toggleButton = carousel.querySelector('#homeCarouselToggle');
    const status = carousel.querySelector('#homeSlideStatus');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeIndex = 0;
    let autoplayTimer = null;
    let isPaused = reducedMotion;
    let isHovered = false;
    let hasFocus = false;

    function updateToggle() {
      if (!toggleButton) return;
      const icon = toggleButton.querySelector('i');
      const label = toggleButton.querySelector('span');
      toggleButton.setAttribute('aria-pressed', String(isPaused));
      if (reducedMotion) {
        toggleButton.disabled = true;
        toggleButton.setAttribute('aria-label', '已依系統設定停用自動輪播');
        if (icon) icon.className = 'fa-solid fa-ban';
        if (label) label.textContent = '已停用';
        return;
      }
      toggleButton.setAttribute('aria-label', isPaused ? '播放首頁特色輪播' : '暫停首頁特色輪播');
      if (icon) icon.className = `fa-solid fa-${isPaused ? 'play' : 'pause'}`;
      if (label) label.textContent = isPaused ? '播放' : '暫停';
    }

    function stopTimer() {
      window.clearTimeout(autoplayTimer);
      autoplayTimer = null;
    }

    function scheduleAutoplay() {
      stopTimer();
      if (isPaused || isHovered || hasFocus || document.hidden || slides.length < 2) return;
      autoplayTimer = window.setTimeout(() => {
        showSlide(activeIndex + 1, false);
        scheduleAutoplay();
      }, 7000);
    }

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
      if (announce && status) {
        const label = dots[activeIndex]?.getAttribute('aria-label') || `第 ${activeIndex + 1} 張`;
        status.textContent = `已${label}`;
      }
    }

    previousButton?.addEventListener('click', () => {
      showSlide(activeIndex - 1);
      scheduleAutoplay();
    });
    nextButton?.addEventListener('click', () => {
      showSlide(activeIndex + 1);
      scheduleAutoplay();
    });
    dots.forEach((dot) => dot.addEventListener('click', () => {
      showSlide(Number(dot.dataset.homeGoSlide));
      scheduleAutoplay();
    }));
    toggleButton?.addEventListener('click', () => {
      isPaused = !isPaused;
      updateToggle();
      scheduleAutoplay();
    });
    carousel.addEventListener('mouseenter', () => {
      isHovered = true;
      stopTimer();
    });
    carousel.addEventListener('mouseleave', () => {
      isHovered = false;
      scheduleAutoplay();
    });
    carousel.addEventListener('focusin', () => {
      hasFocus = true;
      stopTimer();
    });
    carousel.addEventListener('focusout', (event) => {
      if (carousel.contains(event.relatedTarget)) return;
      hasFocus = false;
      scheduleAutoplay();
    });
    carousel.addEventListener('keydown', (event) => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      showSlide(activeIndex + (event.key === 'ArrowRight' ? 1 : -1));
    });
    document.addEventListener('visibilitychange', scheduleAutoplay);

    showSlide(0, false);
    updateToggle();
    scheduleAutoplay();
  }

  $(document).on('click', '.hero-copy', function () { copyText($(this).data('prompt')); });
  $('#copyPrompt').on('click', () => copyText($('#skillPrompt').text()));
  $('.home-search-panel').on('submit', function (event) {
    const $input = $(this).find('input[name="q"]');
    if ($input.val().trim()) return;
    event.preventDefault();
    $input.trigger('focus');
    showToast('請先輸入想找的桌布條件。');
  });
});
