const $ = selector => document.querySelector(selector);
const clientId = localStorage.getItem('luma-client-id') || (crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`);
localStorage.setItem('luma-client-id', clientId);

const names = { game: '遊戲世界', dragon: '帥龍奇幻', anime: '萌姬動漫', black: '暗色護眼', xuanling: '玄靈', wellbeing: '幸福仙境' };
const colorNames = { red: '偏紅', orange: '偏橘', yellow: '偏黃', green: '偏綠', blue: '偏藍', purple: '偏紫', black: '深色', white: '偏白', brown: '偏棕' };
const PAGE_SIZE = 24;
const RECENT_SEARCH_KEY = 'wallpaper-recent-searches';
const VIEW_STATE_KEY = 'wallpaper-catalog-view-state';
const dialog = $('#previewDialog');
const toast = $('#toast');
let wallpapers = [];
let profile = { favorites: [], playlist: [] };
let userVotes = {};
let topic = 'all';
let query = '';
let sort = 'popular';
let active = null;
let device = 'all';
let orientation = 'all';
let contentType = 'all';
let activeColor = 'all';
let visibleLimit = PAGE_SIZE;
let searchTimer = null;
let restoreScrollY = 0;

const icon = name => `<i data-lucide="${name}" aria-hidden="true"></i>`;
const fmt = number => new Intl.NumberFormat('zh-TW', {
  notation: number > 9999 ? 'compact' : 'standard',
  maximumFractionDigits: 1,
}).format(number || 0);
const refreshIcons = () => window.lucide && lucide.createIcons();
const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
const formatSize = bytes => bytes ? `${(bytes / 1024 / 1024).toFixed(2)} MB` : '未知';
const qualityLabel = wallpaper => wallpaper.width >= 3840 && wallpaper.height >= 2160 ? '4K' : wallpaper.width >= 1920 && wallpaper.height >= 1080 ? 'FHD' : 'HD';

/* 產生桌布基本資訊欄位 */
function detailRows(wallpaper) {
  const labels = (wallpaper.labels || []).join('｜') || '未分類';
  const colors = (wallpaper.colors || []).map(color => colorNames[color] || color).join('、') || '未標示';
  const tags = (wallpaper.tags || wallpaper.topics || []).join(', ') || '未標示';
  const date = wallpaper.created_at ? new Date(wallpaper.created_at).toLocaleDateString('zh-TW').replaceAll('/', '-') : '未標示';
  const rows = [['分類', labels], ['色系', colors], ['大小', formatSize(wallpaper.size_bytes)], ['瀏覽量', fmt(wallpaper.views)], ['下載量', fmt(wallpaper.downloads)], ['收藏量', fmt(wallpaper.favorites)], ['網友評分', `${wallpaper.rating_average || 0} 分（${wallpaper.rating_count || 0} 人）`], ['發布時間', date], ['AI 或真人創作', wallpaper.content_type === 'real' ? '真人' : 'AI'], ['作者', wallpaper.creator || '未署名'], ['作品名', wallpaper.title || '未命名'], ['標籤', tags]];
  return rows.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`).join('');
}

function showToast(message) {
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.classList.remove('show'), 2800);
}

function renderSkeleton() {
  $('#catalogGrid').innerHTML = Array.from({ length: 8 }, () => `
    <article class="wallpaper-card skeleton-card" aria-hidden="true">
      <div class="image-wrap"></div>
      <div class="skeleton-line"></div>
    </article>`).join('');
}

function activeFilterEntries() {
  const entries = [];
  if (query) entries.push(['query', `關鍵字：${query}`]);
  if (topic !== 'all') entries.push(['topic', names[topic] || topic]);
  if (orientation !== 'all') entries.push(['orientation', orientation === 'portrait' ? '直式' : '橫式']);
  if (activeColor !== 'all') entries.push(['color', colorNames[activeColor] || activeColor]);
  if (contentType !== 'all') entries.push(['contentType', contentType === 'real' ? '真人' : 'AI 創作']);
  return entries;
}

function updateUrl() {
  const params = new URLSearchParams();
  if (query) params.set('q', query);
  if (topic !== 'all') params.set('topic', topic);
  if (orientation !== 'all') params.set('orientation', orientation);
  if (activeColor !== 'all') params.set('color', activeColor);
  if (contentType !== 'all') params.set('type', contentType);
  if (sort !== 'popular') params.set('sort', sort);
  const search = params.toString();
  history.replaceState({ catalog: true }, '', `${location.pathname}${search ? `?${search}` : ''}`);
}

function updateFilterSummary() {
  const entries = activeFilterEntries();
  const summary = $('#activeFilterSummary');
  const chips = $('#activeFilterChips');
  summary.hidden = entries.length === 0;
  chips.innerHTML = entries.map(([key, label]) => `<button type="button" data-clear-filter="${key}" aria-label="移除篩選：${escapeHtml(label)}">${escapeHtml(label)} ${icon('x')}</button>`).join('');
  const badge = $('#mobileFilterCount');
  badge.textContent = String(entries.length);
  badge.hidden = entries.length === 0;
}

function rememberSearch(value) {
  const normalized = value.trim();
  if (!normalized) return;
  let recent = [];
  try { recent = JSON.parse(localStorage.getItem(RECENT_SEARCH_KEY) || '[]'); } catch { recent = []; }
  recent = [normalized, ...recent.filter(item => item !== normalized)].slice(0, 5);
  localStorage.setItem(RECENT_SEARCH_KEY, JSON.stringify(recent));
  renderRecentSuggestions();
}

function renderRecentSuggestions() {
  const datalist = $('#catalogSuggestions');
  if (!datalist) return;
  let recent = [];
  try { recent = JSON.parse(localStorage.getItem(RECENT_SEARCH_KEY) || '[]'); } catch { recent = []; }
  datalist.querySelectorAll('[data-recent]').forEach(option => option.remove());
  recent.forEach(value => {
    const option = document.createElement('option');
    option.value = value;
    option.dataset.recent = 'true';
    datalist.append(option);
  });
}

function resetVisibleResults() {
  visibleLimit = PAGE_SIZE;
}

function clearFilters(clearQuery = true) {
  if (clearQuery) {
    query = '';
    $('#catalogSearch').value = '';
  }
  topic = 'all';
  orientation = 'all';
  contentType = 'all';
  activeColor = 'all';
  sort = 'popular';
  $('#catalogSort').value = sort;
  document.querySelectorAll('[data-topic]').forEach(button => {
    const selected = button.dataset.topic === 'all';
    button.classList.toggle('active', selected);
    button.setAttribute('aria-selected', String(selected));
    button.tabIndex = selected ? 0 : -1;
  });
  document.querySelectorAll('[data-orientation],[data-content-type]').forEach(button => button.classList.toggle('active', button.dataset.orientation === 'all' || button.dataset.contentType === 'all'));
  document.querySelectorAll('[data-color]').forEach(button => {
    const selected = button.dataset.color === 'all';
    button.classList.toggle('active', selected);
    button.setAttribute('aria-pressed', String(selected));
  });
  resetVisibleResults();
  updateUrl();
  render();
}

async function statsAction(id, action) {
  const response = await fetch('api/stats/read', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, action, client_id: clientId }),
  });
  const data = await response.json();
  if (!response.ok || !data.ok) throw new Error(data.error || '統計更新失敗');
  const wallpaper = wallpapers.find(item => item.id === id);
  if (wallpaper) Object.assign(wallpaper, data.stats);
  if (action === 'like' || action === 'dislike') {
    if (data.vote) userVotes[id] = data.vote;
    else delete userVotes[id];
  }
  return data;
}

async function preferenceAction(action, id) {
  const response = await fetch('api/preferences/read', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ client_id: clientId, action, id }),
  });
  const data = await response.json();
  if (!response.ok || !data.ok) throw new Error(data.error || '收藏更新失敗');
  profile = data.profile;
  render();
  return data;
}

function queryFilters() {
  const value = query.toLocaleLowerCase('zh-Hant');
  const colors = { red: /紅|red/, orange: /橘|orange/, yellow: /黃|金|yellow|gold/, green: /綠|翠|green/, blue: /藍|blue/, purple: /紫|purple/, black: /黑|暗|black/, white: /白|white/, brown: /棕|褐|brown/ };
  const foundColors = Object.keys(colors).filter(color => colors[color].test(value));
  return { device: device === 'all' ? (/手機|mobile|phone/.test(value) ? 'mobile' : /pc|電腦|桌機|desktop/.test(value) ? 'pc' : '') : device, orientation: orientation === 'all' ? (/直式|直屏|portrait/.test(value) ? 'portrait' : /橫式|橫屏|landscape/.test(value) ? 'landscape' : '') : orientation, contentType: contentType === 'all' ? (/真人|寫真|real/.test(value) ? 'real' : /ai|人工智慧|生成/.test(value) ? 'ai' : '') : contentType, colors: foundColors, text: value.replace(/搜尋|桌布|壁紙|畫作|的畫|作品|手機|mobile|phone|pc|電腦|桌機|desktop|直式|直屏|portrait|橫式|橫屏|landscape|真人|寫真|real|ai|人工智慧|生成|紅色系|紅色|紅|red|橘色系|橘色|橘|orange|黃色系|黃色|黃|金色|金|yellow|gold|綠色系|綠色|綠|翠色|green|藍色系|藍色|藍|blue|紫色系|紫色|紫|purple|黑色系|黑色|黑|暗色|black|白色系|白色|白|white|棕色系|棕色|棕|褐色|brown/gu, '').trim() };
}

function filteredItems() {
  const filters = queryFilters();
  const list = wallpapers.filter(wallpaper => {
    const matchesTopic = topic === 'all' || wallpaper.topics.includes(topic);
    const matchesDevice = !filters.device || wallpaper.device === filters.device;
    const matchesOrientation = !filters.orientation || wallpaper.orientation === filters.orientation;
    const matchesType = !filters.contentType || wallpaper.content_type === filters.contentType;
    const wallpaperColors = wallpaper.colors || [];
    const matchesColor = (activeColor === 'all' || wallpaperColors.includes(activeColor)) && (!filters.colors.length || filters.colors.some(color => wallpaperColors.includes(color)));
    const searchText = `${wallpaper.title} ${wallpaper.creator || ''} ${wallpaper.content_type || ''} ${(wallpaper.labels || wallpaper.topics.map(item => names[item])).join(' ')} ${(wallpaper.colors || []).join(' ')}`.toLocaleLowerCase('zh-Hant');
    return matchesTopic && matchesDevice && matchesOrientation && matchesType && matchesColor && searchText.includes(filters.text);
  });
  return list.sort((a, b) => {
    const orientationOrder = wallpaper => wallpaper.orientation === 'portrait' ? 1 : 0;
    const orientationResult = orientationOrder(a) - orientationOrder(b);
    if (orientationResult !== 0) return orientationResult;
    if (sort === 'downloads') return b.downloads - a.downloads;
    if (sort === 'likes') return b.likes - a.likes;
    if (sort === 'newest') return new Date(b.created_at) - new Date(a.created_at);
    return (b.likes - b.dislikes) - (a.likes - a.dislikes);
  });
}

function render() {
  const list = filteredItems();
  const visibleList = list.slice(0, visibleLimit);
  const grid = $('#catalogGrid');
  const loadMore = $('#catalogLoadMore');
  $('#resultCount').textContent = list.length ? `顯示 ${visibleList.length} / ${list.length} 張桌布` : '找不到符合的桌布';
  grid.setAttribute('aria-busy', 'false');
  grid.innerHTML = visibleList.length ? visibleList.map((wallpaper, index) => {
    const labels = wallpaper.labels || wallpaper.topics.map(item => names[item]);
    const title = escapeHtml(wallpaper.title);
    const creator = escapeHtml(wallpaper.creator || '未署名');
    const hasActivity = wallpaper.likes > 0 || wallpaper.dislikes > 0 || wallpaper.downloads > 0;
    return `<article class="wallpaper-card${wallpaper.orientation === 'portrait' ? ' is-portrait' : ''}">
      <div class="image-wrap">
        <img src="${escapeHtml(wallpaper.file)}" alt="${title} 高畫質桌布" width="${wallpaper.width}" height="${wallpaper.height}" loading="${index < 4 ? 'eager' : 'lazy'}" decoding="async">
        <span class="resolution">${qualityLabel(wallpaper)} · ${fmt(wallpaper.width)} × ${fmt(wallpaper.height)}</span>
        <button class="card-preview-button" type="button" data-preview="${wallpaper.id}" aria-label="預覽 ${title}"><span class="sr-only">預覽 ${title}</span></button>
        <button class="quick-favorite ${profile.favorites.includes(wallpaper.id) ? 'active' : ''}" type="button" data-favorite="${wallpaper.id}" aria-label="收藏 ${title}" aria-pressed="${profile.favorites.includes(wallpaper.id)}">${icon('heart')}</button>
        <button class="quick-download" type="button" data-download="${wallpaper.id}" aria-label="下載 ${title}">${icon('download')}</button>
      </div>
      <div class="card-meta">
        <div class="card-copy"><h3>${title}</h3><p>作者：${creator} · ${wallpaper.content_type === 'real' ? '真人' : 'AI 創作'} · ${labels.map(escapeHtml).join(' · ')}</p></div>
        <div class="card-signals" aria-label="${title} 作品狀態">${hasActivity ? `<span title="喜歡數">${icon('thumbs-up')}${fmt(wallpaper.likes)}</span><span title="下載次數">${icon('download')}${fmt(wallpaper.downloads)}</span>` : '<span class="card-new">NEW</span>'}</div>
      </div>
    </article>`;
  }).join('') : `<div class="empty-state">${icon('search-x')}<h3>找不到符合的桌布</h3><p>試試較簡短的關鍵字，或直接查看推薦分類。</p><div class="empty-suggestions"><button type="button" data-suggest="玄靈">玄靈</button><button type="button" data-suggest="心靈幸福">幸福仙境</button><button type="button" data-suggest="真人 黑白">真人黑白</button><button type="button" data-clear-filters>查看全部</button></div></div>`;
  if (visibleList.length) {
    const landscapeGroup = document.createElement('div');
    const portraitGroup = document.createElement('div');
    landscapeGroup.className = 'wallpaper-orientation-group wallpaper-orientation-landscape';
    portraitGroup.className = 'wallpaper-orientation-group wallpaper-orientation-portrait';
    const landscapeGrid = document.createElement('div');
    const portraitGrid = document.createElement('div');
    landscapeGrid.className = portraitGrid.className = 'wallpaper-grid';
    [...grid.children].forEach(card => (card.classList.contains('is-portrait') ? portraitGrid : landscapeGrid).append(card));
    landscapeGroup.append(landscapeGrid);
    portraitGroup.append(portraitGrid);
    grid.replaceChildren(landscapeGroup, portraitGroup);
    landscapeGroup.hidden = landscapeGrid.children.length === 0;
    portraitGroup.hidden = portraitGrid.children.length === 0;
  }
  loadMore.hidden = visibleList.length >= list.length;
  if (!loadMore.hidden) loadMore.querySelector('span').textContent = `載入更多桌布（尚有 ${list.length - visibleList.length} 張）`;
  updateFilterSummary();
  refreshIcons();
}

function fillDialog(wallpaper) {
  active = wallpaper;
  const previewList = filteredItems();
  const previewIndex = previewList.findIndex(item => item.id === wallpaper.id);
  const previous = previewList[(previewIndex - 1 + previewList.length) % previewList.length];
  const next = previewList[(previewIndex + 1) % previewList.length];
  const hasMultiple = previewList.length > 1;
  dialog.classList.toggle('mobile-preview', wallpaper.device === 'mobile' || wallpaper.orientation === 'portrait');
  $('#dialogImage').src = wallpaper.file;
  $('#dialogImage').alt = `${wallpaper.title} 預覽`;
  $('#dialogTitle').textContent = wallpaper.title;
  $('#wallpaperDetails').innerHTML = detailRows(wallpaper);
  $('#dialogMeta').textContent = `${qualityLabel(wallpaper)} · ${wallpaper.width} × ${wallpaper.height} · 作者：${wallpaper.creator || '未署名'} · ${fmt(wallpaper.downloads)} 次下載`;
  $('#dialogLike span').textContent = fmt(wallpaper.likes);
  $('#dialogDislike span').textContent = fmt(wallpaper.dislikes);
  $('#dialogLike').classList.toggle('voted', userVotes[wallpaper.id] === 'like');
  $('#dialogDislike').classList.toggle('voted', userVotes[wallpaper.id] === 'dislike');
  $('#dialogLike').setAttribute('aria-pressed', String(userVotes[wallpaper.id] === 'like'));
  $('#dialogDislike').setAttribute('aria-pressed', String(userVotes[wallpaper.id] === 'dislike'));
  $('#dialogFavorite').classList.toggle('voted', profile.favorites.includes(wallpaper.id));
  $('#dialogFavorite').setAttribute('aria-pressed', String(profile.favorites.includes(wallpaper.id)));
  $('#dialogFavorite').setAttribute('aria-label', `${profile.favorites.includes(wallpaper.id) ? '取消收藏' : '收藏'} ${wallpaper.title}`);
  $('#dialogDownload').href = wallpaper.download_url || wallpaper.file;
  $('#dialogDownload').download = `帥龍萌姬桌布館-${wallpaper.title}.jpg`;
  const detailMarkup = $('#catalogDetailAdMarkup');
  const existingAd = dialog.querySelector('[data-dialog-ad]');
  if (detailMarkup?.innerHTML && !existingAd) {
    const mount = document.createElement('div');
    mount.dataset.dialogAd = 'true';
    mount.innerHTML = detailMarkup.innerHTML;
    dialog.querySelector('.dialog-info').before(mount);
  }
  $('#dialogPosition').textContent = `${previewIndex + 1} / ${previewList.length}`;
  $('#dialogPrev').disabled = !hasMultiple;
  $('#dialogNext').disabled = !hasMultiple;
  $('#dialogPrev').setAttribute('aria-label', previous ? `上一張桌布：${previous.title}` : '上一張桌布');
  $('#dialogNext').setAttribute('aria-label', next ? `下一張桌布：${next.title}` : '下一張桌布');
}

function moveDialog(offset) {
  const previewList = filteredItems();
  if (previewList.length < 2 || !active) return;
  const currentIndex = previewList.findIndex(item => item.id === active.id);
  const nextIndex = (Math.max(currentIndex, 0) + offset + previewList.length) % previewList.length;
  const nextWallpaper = previewList[nextIndex];
  fillDialog(nextWallpaper);
  statsAction(nextWallpaper.id, 'view').catch(() => {});
}

function showDownloadAd() {
  const adDialog = $('#downloadAdDialog');
  if (adDialog?.querySelector('.ad-slot') && !adDialog.open) adDialog.showModal();
}

async function vote(id, type, button) {
  button.disabled = true;
  try {
    const data = await statsAction(id, type);
    render();
    if (active?.id === id) fillDialog(active);
    showToast(data.vote ? (data.vote === 'like' ? '已按讚' : '已送出倒讚') : '已取消投票');
  } catch (error) {
    showToast(error.message);
  } finally {
    button.disabled = false;
  }
}

function setFilterPanelOpen(open) {
  const panel = $('#catalogFilterPanel');
  const backdrop = $('#filterSheetBackdrop');
  const toggle = $('#mobileFilterToggle');
  const mobile = window.matchMedia('(max-width: 760px)').matches;
  const shouldOpen = mobile && open;
  panel.classList.toggle('is-open', shouldOpen);
  backdrop.hidden = !shouldOpen;
  document.body.classList.toggle('filter-sheet-open', shouldOpen);
  toggle.setAttribute('aria-expanded', String(shouldOpen));
  if (mobile) {
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-hidden', String(!shouldOpen));
  } else {
    panel.removeAttribute('role');
    panel.removeAttribute('aria-modal');
    panel.removeAttribute('aria-hidden');
  }
  if (shouldOpen) $('#closeFilterPanel').focus();
}

function clearSingleFilter(key) {
  if (key === 'query') {
    query = '';
    $('#catalogSearch').value = '';
  }
  if (key === 'topic') {
    topic = 'all';
    document.querySelectorAll('[data-topic]').forEach(button => {
      const selected = button.dataset.topic === 'all';
      button.classList.toggle('active', selected);
      button.setAttribute('aria-selected', String(selected));
      button.tabIndex = selected ? 0 : -1;
    });
  }
  if (key === 'orientation') {
    orientation = 'all';
    document.querySelectorAll('[data-orientation]').forEach(button => button.classList.toggle('active', button.dataset.orientation === 'all'));
  }
  if (key === 'color') {
    activeColor = 'all';
    document.querySelectorAll('[data-color]').forEach(button => {
      const selected = button.dataset.color === 'all';
      button.classList.toggle('active', selected);
      button.setAttribute('aria-pressed', String(selected));
    });
  }
  if (key === 'contentType') {
    contentType = 'all';
    document.querySelectorAll('[data-content-type]').forEach(button => button.classList.toggle('active', button.dataset.contentType === 'all'));
  }
  resetVisibleResults();
  updateUrl();
  render();
}

function applyInitialControls() {
  const params = new URLSearchParams(location.search);
  query = params.get('q') || '';
  topic = document.querySelector(`[data-topic="${CSS.escape(params.get('topic') || 'all')}"]`) ? (params.get('topic') || 'all') : 'all';
  orientation = document.querySelector(`[data-orientation="${CSS.escape(params.get('orientation') || 'all')}"]`) ? (params.get('orientation') || 'all') : 'all';
  activeColor = document.querySelector(`[data-color="${CSS.escape(params.get('color') || 'all')}"]`) ? (params.get('color') || 'all') : 'all';
  contentType = document.querySelector(`[data-content-type="${CSS.escape(params.get('type') || 'all')}"]`) ? (params.get('type') || 'all') : 'all';
  sort = ['popular', 'downloads', 'likes', 'newest'].includes(params.get('sort')) ? params.get('sort') : 'popular';
  $('#catalogSearch').value = query;
  $('#catalogSort').value = sort;
  document.querySelectorAll('[data-topic]').forEach(button => {
    const selected = button.dataset.topic === topic;
    button.classList.toggle('active', selected);
    button.setAttribute('aria-selected', String(selected));
    button.tabIndex = selected ? 0 : -1;
  });
  document.querySelectorAll('[data-orientation]').forEach(button => button.classList.toggle('active', button.dataset.orientation === orientation));
  document.querySelectorAll('[data-content-type]').forEach(button => button.classList.toggle('active', button.dataset.contentType === contentType));
  document.querySelectorAll('[data-color]').forEach(button => {
    const selected = button.dataset.color === activeColor;
    button.classList.toggle('active', selected);
    button.setAttribute('aria-pressed', String(selected));
  });
  try {
    const saved = JSON.parse(sessionStorage.getItem(VIEW_STATE_KEY) || '{}');
    if (saved.url === location.href) {
      visibleLimit = Math.max(PAGE_SIZE, Number(saved.visibleLimit) || PAGE_SIZE);
      restoreScrollY = Math.max(0, Number(saved.scrollY) || 0);
    }
  } catch {
    visibleLimit = PAGE_SIZE;
    restoreScrollY = 0;
  }
  renderRecentSuggestions();
  setFilterPanelOpen(false);
}

document.addEventListener('click', async event => {
  const chip = event.target.closest('[data-topic]');
  if (chip) {
    topic = chip.dataset.topic;
    document.querySelectorAll('[data-topic]').forEach(button => {
      const selected = button === chip;
      button.classList.toggle('active', selected);
      button.setAttribute('aria-selected', String(selected));
      button.tabIndex = selected ? 0 : -1;
    });
    resetVisibleResults();
    updateUrl();
    render();
  }

  const filterButton = event.target.closest('[data-device],[data-orientation],[data-content-type]');
  if (filterButton) {
    const key = filterButton.dataset.device !== undefined ? 'device' : filterButton.dataset.orientation !== undefined ? 'orientation' : 'contentType';
    const value = filterButton.dataset.device ?? filterButton.dataset.orientation ?? filterButton.dataset.contentType;
    if (key === 'device') device = value;
    if (key === 'orientation') orientation = value;
    if (key === 'contentType') contentType = value;
    document.querySelectorAll(`[data-${key === 'contentType' ? 'content-type' : key}]`).forEach(button => button.classList.toggle('active', button === filterButton));
    resetVisibleResults();
    updateUrl();
    render();
  }

  const colorButton = event.target.closest('[data-color]');
  if (colorButton) {
    activeColor = colorButton.dataset.color;
    document.querySelectorAll('[data-color]').forEach(button => {
      const selected = button === colorButton;
      button.classList.toggle('active', selected);
      button.setAttribute('aria-pressed', String(selected));
    });
    resetVisibleResults();
    updateUrl();
    render();
  }

  const clearFilter = event.target.closest('[data-clear-filter]');
  if (clearFilter) clearSingleFilter(clearFilter.dataset.clearFilter);

  const suggestion = event.target.closest('[data-suggest]');
  if (suggestion) {
    query = suggestion.dataset.suggest;
    $('#catalogSearch').value = query;
    rememberSearch(query);
    resetVisibleResults();
    updateUrl();
    render();
    $('#catalogSearch').focus();
  }

  if (event.target.closest('[data-clear-filters]')) clearFilters();

  const preview = event.target.closest('[data-preview]');
  if (preview) {
    const wallpaper = wallpapers.find(item => item.id === preview.dataset.preview);
    if (wallpaper) {
      fillDialog(wallpaper);
      dialog.showModal();
      statsAction(wallpaper.id, 'view').catch(() => {});
    }
  }

  const voteButton = event.target.closest('[data-vote]');
  if (voteButton) await vote(voteButton.dataset.id, voteButton.dataset.vote, voteButton);

  const favorite = event.target.closest('[data-favorite]');
  if (favorite) preferenceAction('favorite_toggle', favorite.dataset.favorite).then(() => showToast('收藏已更新')).catch(error => showToast(error.message));

  const download = event.target.closest('[data-download]');
  if (download) {
    const wallpaper = wallpapers.find(item => item.id === download.dataset.download);
    if (!wallpaper) return;
    const anchor = document.createElement('a');
    anchor.href = wallpaper.download_url || wallpaper.file;
    anchor.download = `帥龍萌姬桌布館-${wallpaper.title}.jpg`;
    anchor.click();
    statsAction(wallpaper.id, 'download').then(render).catch(() => {});
    showDownloadAd();
  }
});

$('#catalogSearch').addEventListener('input', event => {
  window.clearTimeout(searchTimer);
  searchTimer = window.setTimeout(() => {
    query = event.target.value.trim();
    resetVisibleResults();
    updateUrl();
    render();
  }, 180);
});
$('#catalogSearch').addEventListener('keydown', event => {
  if (event.key !== 'Enter') return;
  event.preventDefault();
  window.clearTimeout(searchTimer);
  query = event.currentTarget.value.trim();
  rememberSearch(query);
  resetVisibleResults();
  updateUrl();
  render();
});
$('#catalogSort').addEventListener('change', event => {
  sort = event.target.value;
  resetVisibleResults();
  updateUrl();
  render();
});
$('#catalogLoadMore').addEventListener('click', () => {
  visibleLimit += PAGE_SIZE;
  render();
});
$('#mobileFilterToggle').addEventListener('click', () => setFilterPanelOpen(true));
$('#closeFilterPanel').addEventListener('click', () => setFilterPanelOpen(false));
$('#applyFilterPanel').addEventListener('click', () => {
  setFilterPanelOpen(false);
  $('#results').scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
});
$('#filterSheetBackdrop').addEventListener('click', () => setFilterPanelOpen(false));
$('#clearAllFilters').addEventListener('click', () => clearFilters());
$('#resetFilterPanel').addEventListener('click', () => clearFilters());
document.addEventListener('click', event => { if (event.target.closest('[data-reload]')) location.reload(); });
document.addEventListener('keydown', event => {
  const openFilterPanel = $('#catalogFilterPanel').classList.contains('is-open');
  if (event.key === 'Escape' && $('#catalogFilterPanel').classList.contains('is-open')) {
    setFilterPanelOpen(false);
    $('#mobileFilterToggle').focus();
    return;
  }
  if (openFilterPanel && event.key === 'Tab') {
    const focusable = [...$('#catalogFilterPanel').querySelectorAll('button:not([disabled]), select:not([disabled]), input:not([disabled]), a[href]')].filter(element => element.getClientRects().length > 0);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }
  if (dialog.open && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
    event.preventDefault();
    moveDialog(event.key === 'ArrowLeft' ? -1 : 1);
    return;
  }
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault();
    $('#catalogSearch').focus();
  }
});
document.querySelector('.category-chips')?.addEventListener('keydown', event => {
  if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
  const tabs = [...event.currentTarget.querySelectorAll('[role="tab"]')];
  const current = Math.max(0, tabs.indexOf(document.activeElement));
  const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (current + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
  event.preventDefault();
  tabs[next].focus();
  tabs[next].click();
});
window.addEventListener('resize', () => setFilterPanelOpen(false));
$('#closeDialog').addEventListener('click', () => dialog.close());
dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
$('#dialogPrev').addEventListener('click', () => moveDialog(-1));
$('#dialogNext').addEventListener('click', () => moveDialog(1));
$('#dialogLike').addEventListener('click', event => active && vote(active.id, 'like', event.currentTarget));
$('#dialogDislike').addEventListener('click', event => active && vote(active.id, 'dislike', event.currentTarget));
$('#dialogFavorite').addEventListener('click', () => active && preferenceAction('favorite_toggle', active.id).then(() => { fillDialog(active); showToast('收藏已更新'); }));
$('#dialogDownload').addEventListener('click', () => active && statsAction(active.id, 'download').then(render).catch(() => {}));
$('#dialogDownload').addEventListener('click', () => showDownloadAd());
$('#closeDownloadAd')?.addEventListener('click', () => $('#downloadAdDialog')?.close());
$('#downloadAdDialog')?.addEventListener('click', event => { if (event.target === event.currentTarget) event.currentTarget.close(); });

async function loadCatalog() {
  renderSkeleton();
  try {
    const [catalogResponse, statsResponse, profileResponse] = await Promise.all([
      fetch('api/catalog/list'),
      fetch(`api/stats/read?client_id=${encodeURIComponent(clientId)}`, { cache: 'no-store' }),
      fetch(`api/preferences/read?client_id=${encodeURIComponent(clientId)}`, { cache: 'no-store' }),
    ]);
    const [catalogData, statsData, profileData] = await Promise.all([
      catalogResponse.json(), statsResponse.json(), profileResponse.json(),
    ]);
    if (!catalogResponse.ok || !catalogData.ok) throw new Error(catalogData.error || '桌布目錄載入失敗');
    wallpapers = catalogData.wallpapers.map(item => ({ ...item, device: item.device || 'pc', orientation: item.orientation || 'landscape', content_type: item.content_type || 'ai', colors: item.colors || [], likes: 0, dislikes: 0, downloads: 0, views: 0, favorites: 0, rating_average: 0, rating_count: 0 }));
    if (statsData.ok) {
      wallpapers.forEach(item => statsData.wallpapers[item.id] && Object.assign(item, statsData.wallpapers[item.id]));
      userVotes = statsData.user_votes || {};
    }
    if (profileData.ok) profile = profileData.profile;
    render();
    if (restoreScrollY > 0) {
      requestAnimationFrame(() => window.scrollTo({ top: restoreScrollY, behavior: 'auto' }));
      restoreScrollY = 0;
    }
  } catch (error) {
    $('#catalogGrid').setAttribute('aria-busy', 'false');
    $('#catalogGrid').innerHTML = `<div class="empty-state">${icon('wifi-off')}<h3>暫時無法載入桌布</h3><p>${error.message}，請確認網路後重新整理。</p><button class="button outline" type="button" data-reload>重新載入</button></div>`;
    refreshIcons();
  }
}

applyInitialControls();
window.addEventListener('pagehide', () => {
  sessionStorage.setItem(VIEW_STATE_KEY, JSON.stringify({ url: location.href, visibleLimit, scrollY: window.scrollY }));
});
loadCatalog();
