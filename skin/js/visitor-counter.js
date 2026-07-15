/* 讀取並呈現目前網站的訪客統計。 */
jQuery(function () {
  const endpoint = 'ip_visitor_counter.php?api=1';
  const updateNumber = (selector, value) => jQuery(selector).text(Number(value || 0).toLocaleString('zh-TW'));
  const animateTotal = (value) => { const $target = jQuery('#total-visits'); const total = Number(value || 0); if (!$target.length) return; jQuery({ count: 0 }).animate({ count: total }, { duration: 900, step() { $target.text(Math.ceil(this.count).toLocaleString('zh-TW')); }, complete() { updateNumber('#total-visits', total); } }); };
  jQuery.post(endpoint, { url: window.location.href, referrer: document.referrer }).done((response) => { if (!response || !response.success) return; const data = response.data || {}; animateTotal(data.total_visits); updateNumber('#today-visits', data.today_visits); updateNumber('#month-visits', data.month_visits); updateNumber('#page-today', data.page_today); updateNumber('#page-total', data.page_total); }).fail(() => jQuery('#visitorStats').addClass('is-unavailable'));
});
