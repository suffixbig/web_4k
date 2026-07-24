const faIcons = { 'arrow-left': 'fa-arrow-left', 'arrow-right': 'fa-arrow-right', 'arrow-up-right': 'fa-arrow-up-right-from-square', 'bot': 'fa-robot', 'calendar-check': 'fa-calendar-check', 'calendar-days': 'fa-calendar-days', 'chevron-down': 'fa-chevron-down', 'chevron-left': 'fa-chevron-left', 'chevron-right': 'fa-chevron-right', 'circle-check-big': 'fa-circle-check', 'copy': 'fa-copy', 'download': 'fa-download', 'heart': 'fa-heart', 'info': 'fa-circle-info', 'menu': 'fa-bars', 'message-square': 'fa-message', 'monitor-down': 'fa-desktop', 'play': 'fa-play', 'search': 'fa-magnifying-glass', 'settings': 'fa-gear', 'shield-check': 'fa-shield-halved', 'sparkles': 'fa-wand-magic-sparkles', 'terminal': 'fa-terminal', 'thumbs-down': 'fa-thumbs-down', 'thumbs-up': 'fa-thumbs-up', 'trophy': 'fa-trophy', 'wifi-off': 'fa-wifi', 'x': 'fa-xmark' };
/* 將既有 Lucide 標記轉為本機 Font Awesome 圖示。 */
function createIcons() { jQuery('[data-lucide]').each(function () { const name = jQuery(this).data('lucide'); jQuery(this).removeAttr('data-lucide').addClass('fa-solid ' + (faIcons[name] || 'fa-circle')); }); }
window.lucide = { createIcons };
jQuery(createIcons);
/* 設定翻譯狀態後切換語系；繁中會清除翻譯狀態。 */
function setTranslationCookie(value, expires) { const domains = [location.hostname, '.' + location.hostname]; domains.forEach(function (domain) { document.cookie = 'googtrans=' + value + ';path=/;SameSite=Lax;max-age=' + expires + ';domain=' + domain; }); }
/* 依捲動位置顯示或隱藏回到頂端按鈕。 */
function updateBackToTop() { jQuery('#backToTop').toggleClass('is-visible', window.scrollY > 360); }
/* 初始化頁尾回到頂端功能。 */
function initBackToTop() { const $button = jQuery('#backToTop'); if (!$button.length) return; jQuery(window).on('scroll', updateBackToTop); $button.on('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); }); updateBackToTop(); }
/* 手機版保留完整品牌並以可展開選單呈現所有主要導覽項目。 */
function initMobileNavigation() {
  const $toggle = jQuery('#mobileMenuToggle');
  const $links = jQuery('#primaryNavLinks');
  if (!$toggle.length || !$links.length) return;
  const setOpen = open => {
    $toggle.attr('aria-expanded', String(open));
    $links.toggleClass('is-open', open);
    $toggle.find('i').toggleClass('fa-bars', !open).toggleClass('fa-xmark', open);
  };
  $toggle.on('click', event => { event.stopPropagation(); setOpen($toggle.attr('aria-expanded') !== 'true'); });
  $links.on('click', 'a', () => setOpen(false));
  jQuery(document).on('click', event => { if (!jQuery(event.target).closest('.nav-shell').length) setOpen(false); });
  jQuery(document).on('keydown', event => { if (event.key === 'Escape' && $toggle.attr('aria-expanded') === 'true') { setOpen(false); $toggle.trigger('focus'); } });
  jQuery(window).on('resize', () => { if (window.innerWidth > 1120) setOpen(false); });
}
/* 使用者切換頁尾語言。 */
jQuery(function () { const $language = jQuery('#languageSelect'); $language.on('change', function () { const code = this.value; const url = jQuery(this).find(':selected').data('url'); if (!url) return; if (code === 'zh_TW') { setTranslationCookie('', 0); localStorage.removeItem('wallpaperTranslateLang'); } else { setTranslationCookie('/zh-TW/' + code.replace('_', '-'), 31536000); localStorage.setItem('wallpaperTranslateLang', code); } window.location.assign(url); }); initBackToTop(); initMobileNavigation(); });
