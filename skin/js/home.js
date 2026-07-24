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
  const homeSearchToggle = document.querySelector('#homeSearchToggle');
  const homeTopSearch = document.querySelector('#homeTopSearch');
  if (homeSearchToggle && homeTopSearch) {
    const setSearchVisibility = (isVisible, moveFocus = false) => {
      homeTopSearch.classList.toggle('is-hidden', !isVisible);
      homeSearchToggle.setAttribute('aria-expanded', String(isVisible));
      homeSearchToggle.setAttribute('aria-label', isVisible ? '隱藏首頁搜尋列' : '顯示首頁搜尋列');
      document.body.classList.toggle('home-search-collapsed', !isVisible);
      if (isVisible && moveFocus) {
        window.setTimeout(() => document.querySelector('#homeSearch')?.focus(), 220);
      }
    };

    homeSearchToggle.addEventListener('click', () => {
      const isVisible = homeSearchToggle.getAttribute('aria-expanded') === 'true';
      setSearchVisibility(!isVisible, true);
    });
  }

  const carousel = document.querySelector('[data-home-carousel]');
  if (carousel && $.fn.slick) {
    const track = carousel.querySelector('.home-feature-track');
    const $track = $(track);
    const slides = [...carousel.querySelectorAll('[data-home-feature-slide]')];
    const dots = [...carousel.querySelectorAll('[data-home-go-slide]')];
    const previousButton = carousel.querySelector('#homePrevSlide');
    const nextButton = carousel.querySelector('#homeNextSlide');
    const status = carousel.querySelector('#homeSlideStatus');
    const counter = carousel.querySelector('#homeFeatureCounter');
    const counterCurrent = carousel.querySelector('#homeFeatureCurrent');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function syncSlide(requestedIndex, announce = true) {
      if (!slides.length) return;
      const activeIndex = Math.max(0, Math.min(requestedIndex, slides.length - 1));
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
      if (counterCurrent) counterCurrent.textContent = String(activeIndex + 1).padStart(2, '0');
      if (counter) counter.setAttribute('aria-label', `目前為第 ${activeIndex + 1} 張，共 ${slides.length} 張`);
      if (announce && status) status.textContent = `已顯示第 ${activeIndex + 1} 張，共 ${slides.length} 張`;
    }

    $track.on('init', (_event, slick) => syncSlide(slick.currentSlide, false));
    $track.on('afterChange', (_event, _slick, currentSlide) => syncSlide(currentSlide));
    $track.slick({
      accessibility: true,
      adaptiveHeight: false,
      arrows: false,
      autoplay: false,
      dots: false,
      draggable: true,
      infinite: false,
      mobileFirst: false,
      pauseOnFocus: true,
      pauseOnHover: true,
      slidesToScroll: 1,
      slidesToShow: 1,
      speed: reducedMotion ? 0 : 420,
      swipe: true,
      touchMove: true,
      touchThreshold: 7,
      waitForAnimate: false,
    });

    previousButton?.addEventListener('click', () => {
      const current = $track.slick('slickCurrentSlide');
      $track.slick('slickGoTo', current <= 0 ? slides.length - 1 : current - 1);
    });
    nextButton?.addEventListener('click', () => {
      const current = $track.slick('slickCurrentSlide');
      $track.slick('slickGoTo', current >= slides.length - 1 ? 0 : current + 1);
    });
    dots.forEach(dot => dot.addEventListener('click', () => $track.slick('slickGoTo', Number(dot.dataset.homeGoSlide))));
    carousel.addEventListener('keydown', event => {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      (event.key === 'ArrowRight' ? nextButton : previousButton)?.click();
    });
    if (reducedMotion) carousel.classList.add('reduce-motion');
  }

  const latestCarousel = document.querySelector('[data-latest-carousel]');
  if (latestCarousel && $.fn.slick) {
    const $latestCarousel = $(latestCarousel);
    const latestStatus = document.querySelector('#latestCarouselStatus');
    const latestDots = document.querySelector('.home-latest-dots');

    $latestCarousel.on('init reInit afterChange', (_event, slick, currentSlide = 0) => {
      if (!latestStatus) return;
      const visible = Number(slick.options.slidesToShow) || 1;
      const start = currentSlide + 1;
      const end = Math.min(currentSlide + visible, slick.slideCount);
      latestStatus.textContent = `顯示第 ${start} 至 ${end} 張最新桌布，共 ${slick.slideCount} 張`;
    });
    $latestCarousel.slick({
      accessibility: true,
      arrows: true,
      dots: true,
      draggable: true,
      infinite: false,
      prevArrow: document.querySelector('#latestPrevSlide'),
      nextArrow: document.querySelector('#latestNextSlide'),
      appendDots: latestDots,
      slidesToShow: 4,
      slidesToScroll: 4,
      speed: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 360,
      swipe: true,
      touchMove: true,
      touchThreshold: 7,
      waitForAnimate: false,
      responsive: [
        { breakpoint: 1100, settings: { slidesToShow: 3, slidesToScroll: 3 } },
        { breakpoint: 700, settings: { slidesToShow: 2, slidesToScroll: 2 } },
        { breakpoint: 390, settings: { slidesToShow: 1, slidesToScroll: 1 } },
      ],
    });
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
