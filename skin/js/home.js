let slide = 0, timer;
/* 顯示短暫的操作結果提示。 */
function showToast(message) { const $toast = $('#toast'); $toast.text(message).addClass('show'); clearTimeout(showToast.timer); showToast.timer = setTimeout(() => $toast.removeClass('show'), 2600); }
/* 切換輪播項目並同步無障礙狀態。 */
function setSlide(index, userAction) { const $slides = $('.hero-slide'), $dots = $('[data-go-slide]'); slide = (index + $slides.length) % $slides.length; $slides.each(function (i) { $(this).toggleClass('active', i === slide).attr('aria-hidden', i !== slide); }); $dots.each(function (i) { $(this).toggleClass('active', i === slide).attr('aria-selected', i === slide); }); $('#currentSlide').text(String(slide + 1).padStart(2, '0')); if (userAction) restartCarousel(); }
/* 重設自動輪播計時器。 */
function restartCarousel() { clearInterval(timer); timer = setInterval(() => setSlide(slide + 1), 6500); }
/* 將文字複製到剪貼簿。 */
async function copyText(text) { try { await navigator.clipboard.writeText(text); showToast('已複製，可貼到 Codex 或 Claude 使用。'); } catch { showToast('目前無法存取剪貼簿，請手動複製。'); } }
$(function () { $('#prevSlide').on('click', () => setSlide(slide - 1, true)); $('#nextSlide').on('click', () => setSlide(slide + 1, true)); $(document).on('click', '[data-go-slide]', function () { setSlide(Number($(this).data('go-slide')), true); }).on('click', '.hero-copy', function () { copyText($(this).data('prompt')); }).on('click', '#copyPrompt', () => copyText($('#skillPrompt').text())); restartCarousel(); });