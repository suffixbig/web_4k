const adsGrid = document.querySelector('#adsAdminGrid');
const adsGlobalToggle = document.querySelector('#adsGlobalToggle');
const adsMessage = document.querySelector('#adsAdminMessage');
const adsSaveButton = document.querySelector('#adsSaveButton');
const adsDashboard = document.querySelector('#adsDashboard');
const adsPreviewDialog = document.querySelector('#adsPreviewDialog');
const adsPreviewImage = document.querySelector('#adsPreviewImage');
const adsPreviewTitle = document.querySelector('#adsPreviewTitle');
const adsPreviewMeta = document.querySelector('#adsPreviewMeta');
let adsConfig = null;
const weekdays = [{ value: 1, label: '一' }, { value: 2, label: '二' }, { value: 3, label: '三' }, { value: 4, label: '四' }, { value: 5, label: '五' }, { value: 6, label: '六' }, { value: 7, label: '日' }];
const safe = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
const showAdsMessage = (message, type = '') => { adsMessage.textContent = message; adsMessage.dataset.state = type; };
const number = value => new Intl.NumberFormat('zh-TW').format(Number(value) || 0);
const today = () => new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Taipei' });

function normalizedSchedule(ad) {
  const schedule = ad.schedule || {};
  return { start_date: schedule.start_date || '', end_date: schedule.end_date || '', weekdays: (schedule.weekdays || []).map(Number).filter(day => day >= 1 && day <= 7) };
}
function normalizedLimits(ad) { return { daily_impressions: Number(ad.limits?.daily_impressions) || 0, total_impressions: Number(ad.limits?.total_impressions) || 0 }; }
function normalizedStats(ad) { return { total_impressions: Number(ad.stats?.total_impressions) || 0, by_day: ad.stats?.by_day || {} }; }
function normalizedCreatives(ad) {
  const creatives = Array.isArray(ad.creatives) ? ad.creatives.filter(creative => creative && creative.enabled !== false && creative.image) : [];
  return creatives.length ? creatives : [{ id: 'default', brand: ad.brand, headline: ad.headline, image: ad.image }];
}
function last30(ad) {
  const start = new Date(`${today()}T00:00:00`); start.setDate(start.getDate() - 29);
  return Object.entries(normalizedStats(ad).by_day).reduce((sum, [date, count]) => new Date(`${date}T00:00:00`) >= start ? sum + (Number(count) || 0) : sum, 0);
}
function scheduleText(ad) {
  const schedule = normalizedSchedule(ad);
  const days = schedule.weekdays.length ? `每週${schedule.weekdays.map(day => weekdays.find(item => item.value === day)?.label).join('、')}` : '每天';
  const dates = [schedule.start_date, schedule.end_date].filter(Boolean).join(' 至 ');
  return dates ? `${days} · ${dates}` : `${days} · 不限期間`;
}
function placementStatus(ad) {
  if (!adsConfig.global_enabled) return ['全站已暫停', 'paused'];
  if (!ad.enabled) return ['此版位已暫停', 'paused'];
  const schedule = normalizedSchedule(ad), now = new Date(`${today()}T00:00:00`), weekday = ((now.getDay() + 6) % 7) + 1;
  if ((schedule.start_date && today() < schedule.start_date) || (schedule.end_date && today() > schedule.end_date) || (schedule.weekdays.length && !schedule.weekdays.includes(weekday))) return ['等待排程', 'scheduled'];
  const limits = normalizedLimits(ad), stats = normalizedStats(ad);
  if ((limits.total_impressions && stats.total_impressions >= limits.total_impressions) || (limits.daily_impressions && (Number(stats.by_day[today()]) || 0) >= limits.daily_impressions)) return ['曝光額度已滿', 'paused'];
  return ['投放中', 'live'];
}
function renderDashboard() {
  if (!adsDashboard || !adsConfig) return;
  const placements = Object.values(adsConfig.placements || {});
  const live = placements.filter(ad => placementStatus(ad)[1] === 'live').length;
  adsDashboard.innerHTML = [
    ['全部版位', number(placements.length), '可獨立設定素材與排程'],
    ['投放中', number(live), adsConfig.global_enabled ? '目前符合投放條件' : '全站廣告已暫停'],
    ['近 30 天曝光', number(placements.reduce((sum, ad) => sum + last30(ad), 0)), '畫面進入可視區域時計入'],
    ['累計曝光', number(placements.reduce((sum, ad) => sum + normalizedStats(ad).total_impressions, 0)), '所有廣告版位合計'],
  ].map(([label, value, detail]) => `<article class="ads-kpi"><span>${label}</span><strong>${value}</strong><small>${detail}</small></article>`).join('');
}
function renderAdsAdmin() {
  if (!adsConfig) return;
  adsGlobalToggle.checked = Boolean(adsConfig.global_enabled);
  adsGrid.setAttribute('aria-busy', 'false');
  renderDashboard();
  adsGrid.innerHTML = Object.entries(adsConfig.placements || {}).map(([slot, ad]) => {
    const schedule = normalizedSchedule(ad), limits = normalizedLimits(ad), stats = normalizedStats(ad), status = placementStatus(ad), creatives = normalizedCreatives(ad);
    const primary = creatives[0];
    const creativeStrip = creatives.length > 1 ? `<div class="ads-creative-strip" aria-label="${safe(ad.label)}隨機素材">${creatives.map((creative, index) => `<button class="ads-creative-thumb" type="button"
      data-preview-src="${safe(creative.image)}"
      data-preview-title="${safe(`${ad.label}｜${creative.brand}`)}"
      data-preview-meta="${safe(`第 ${index + 1} 張／共 ${creatives.length} 張 · ${creative.headline || ''}`)}"
      aria-label="查看 ${safe(creative.brand)} 1920×600 原始廣告圖"><img src="${safe(creative.image)}" alt="${safe(creative.brand)}廣告縮圖" width="1920" height="600" loading="lazy"><span>${safe(creative.brand)}</span></button>`).join('')}</div>` : '';
    return `<article class="ads-admin-card ads-admin-card--rich">
      <div class="ads-card-main">
        <button class="ads-admin-preview" type="button"
          data-preview-src="${safe(primary.image)}"
          data-preview-title="${safe(`${ad.label}｜${primary.brand || ad.brand}`)}"
          data-preview-meta="${safe(`${ad.format} · ${creatives.length > 1 ? `${creatives.length} 張，每次載入隨機固定 1 張` : (ad.campaign_name || '未命名活動')}`)}"
          aria-label="查看 ${safe(ad.label)} 原始廣告圖"><img src="${safe(primary.image)}" alt="${safe(primary.brand || ad.brand)} 廣告素材" width="1920" height="600"><span><i class="fa-solid fa-expand" aria-hidden="true"></i>查看原圖</span></button>
        <div class="ads-admin-card__copy"><div class="ads-card-eyebrow"><span>${safe(ad.format)}</span><b class="ads-status ads-status--${status[1]}">${status[0]}</b></div><h3>${safe(ad.label)}</h3><strong>${safe(ad.brand)}</strong><p>${safe(ad.headline)}${creatives.length > 1 ? ` · ${creatives.length} 張素材` : ''}</p></div>
        <div class="ads-admin-stats"><div><span>近 30 天</span><strong>${number(last30(ad))}</strong></div><div><span>累計曝光</span><strong>${number(stats.total_impressions)}</strong></div><div><span>今日曝光</span><strong>${number(stats.by_day[today()])}</strong></div></div>
        <label class="ads-switch ads-switch--placement"><input type="checkbox" data-ad-slot="${safe(slot)}" ${ad.enabled ? 'checked' : ''}><span aria-hidden="true"></span><b>${ad.enabled ? '版位啟用' : '版位暫停'}</b></label>
      </div>
      ${creativeStrip}
      <details class="ads-card-settings">
        <summary><i class="fa-solid fa-sliders" aria-hidden="true"></i>編輯活動名稱、投放日期、每週出現日與曝光上限</summary>
        <div class="ads-card-settings__grid">
          <fieldset class="ads-schedule"><legend>投放排程</legend><label class="ads-field ads-field--campaign">活動名稱<input type="text" maxlength="60" data-campaign-slot="${safe(slot)}" value="${safe(ad.campaign_name || '')}" placeholder="例如：夏季新品檔期"></label><div class="ads-schedule__dates"><label class="ads-field">開始日期<input type="date" data-start-slot="${safe(slot)}" value="${safe(schedule.start_date)}"></label><label class="ads-field">結束日期<input type="date" data-end-slot="${safe(slot)}" value="${safe(schedule.end_date)}"></label></div><div class="ads-weekdays"><span>每週投放日 <small>未勾選代表每天</small></span><div>${weekdays.map(day => `<label><input type="checkbox" data-weekday-slot="${safe(slot)}" value="${day.value}" ${schedule.weekdays.includes(day.value) ? 'checked' : ''}><b>${day.label}</b></label>`).join('')}</div></div><p>${scheduleText(ad)}</p></fieldset>
          <fieldset class="ads-limits"><legend>曝光額度 <small>填 0 代表不設上限</small></legend><label class="ads-field">每日最多曝光<input type="number" min="0" step="1" inputmode="numeric" data-daily-limit-slot="${safe(slot)}" value="${limits.daily_impressions}"></label><label class="ads-field">累計最多曝光<input type="number" min="0" step="1" inputmode="numeric" data-total-limit-slot="${safe(slot)}" value="${limits.total_impressions}"></label></fieldset>
        </div>
      </details>
    </article>`;
  }).join('');
}
function updateSlot(slot, callback) { if (!adsConfig?.placements?.[slot]) return; callback(adsConfig.placements[slot]); renderAdsAdmin(); }
async function loadAdsAdmin() {
  try {
    const response = await fetch('api/ads/read', { cache: 'no-store' }); const payload = await response.json();
    if (!response.ok || !payload.success) throw new Error(payload.message || '無法讀取廣告設定');
    adsConfig = payload.data.ads; showAdsMessage('修改後請按「儲存全部設定」才會更新前台投放。', 'success'); renderAdsAdmin();
  } catch (error) { adsGrid.setAttribute('aria-busy', 'false'); showAdsMessage(error.message, 'error'); }
}
adsGrid.addEventListener('change', event => {
  const input = event.target, slot = input.dataset.adSlot || input.dataset.startSlot || input.dataset.endSlot || input.dataset.weekdaySlot || input.dataset.dailyLimitSlot || input.dataset.totalLimitSlot;
  if (!slot) return;
  updateSlot(slot, ad => {
    if (input.dataset.adSlot) ad.enabled = input.checked;
    if (input.dataset.startSlot) { ad.schedule = normalizedSchedule(ad); ad.schedule.start_date = input.value; }
    if (input.dataset.endSlot) { ad.schedule = normalizedSchedule(ad); ad.schedule.end_date = input.value; }
    if (input.dataset.weekdaySlot) { ad.schedule = normalizedSchedule(ad); const day = Number(input.value); ad.schedule.weekdays = input.checked ? [...new Set([...ad.schedule.weekdays, day])].sort() : ad.schedule.weekdays.filter(value => value !== day); }
    if (input.dataset.dailyLimitSlot) { ad.limits = normalizedLimits(ad); ad.limits.daily_impressions = Math.max(0, Number(input.value) || 0); }
    if (input.dataset.totalLimitSlot) { ad.limits = normalizedLimits(ad); ad.limits.total_impressions = Math.max(0, Number(input.value) || 0); }
  });
});
adsGrid.addEventListener('input', event => { const slot = event.target.dataset.campaignSlot; if (slot && adsConfig?.placements?.[slot]) adsConfig.placements[slot].campaign_name = event.target.value; });
adsGrid.addEventListener('click', event => {
  const button = event.target.closest('[data-preview-src]'); if (!button || !adsPreviewDialog) return;
  adsPreviewImage.src = button.dataset.previewSrc;
  adsPreviewImage.alt = `${button.dataset.previewTitle || '廣告素材'}原始圖片`;
  adsPreviewTitle.textContent = button.dataset.previewTitle || '廣告素材';
  adsPreviewMeta.textContent = button.dataset.previewMeta || '';
  adsPreviewDialog.showModal();
});
document.querySelector('#adsPreviewClose')?.addEventListener('click', () => adsPreviewDialog.close());
adsPreviewDialog?.addEventListener('click', event => { if (event.target === adsPreviewDialog) adsPreviewDialog.close(); });
adsGlobalToggle.addEventListener('change', () => { if (adsConfig) { adsConfig.global_enabled = adsGlobalToggle.checked; renderAdsAdmin(); } });
adsSaveButton.addEventListener('click', async () => {
  if (!adsConfig) return; adsSaveButton.disabled = true; showAdsMessage('正在儲存投放設定…');
  try { const response = await fetch('api/ads/write', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(adsConfig) }); const payload = await response.json(); if (!response.ok || !payload.success) throw new Error(payload.message || '儲存失敗'); adsConfig = payload.data.ads; showAdsMessage('已儲存，前台會立即依新版排程與曝光額度投放。', 'success'); renderAdsAdmin(); } catch (error) { showAdsMessage(error.message, 'error'); } finally { adsSaveButton.disabled = false; }
});
loadAdsAdmin();
