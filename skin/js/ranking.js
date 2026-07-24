const $ = (selector) => document.querySelector(selector);
const API = { catalog: "api/catalog/list" };
const PAGE_SIZE = 50;
const MAX_RANKING = 100;

let wallpapers = [];
let savedVotes = {};
let mode = "downloads";
let currentPage = 1;
let activeId = null;

const dialog = $("#previewDialog");
const toast = $("#toast");
const icon = (name) => `<i data-lucide="${name}" aria-hidden="true"></i>`;
const escapeHtml = (value) => String(value).replace(/[&<>"]/g, (character) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[character]);
const formatNumber = (number) => new Intl.NumberFormat("zh-TW", { notation: number > 9999 ? "compact" : "standard", maximumFractionDigits: 1 }).format(number);
const approval = (wallpaper) => wallpaper.likes / (wallpaper.likes + wallpaper.dislikes || 1);
const score = (wallpaper) => wallpaper.likes - wallpaper.dislikes;
const refreshIcons = () => window.lucide && lucide.createIcons();

function showToast(message) {
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(showToast.timer);
  showToast.timer = setTimeout(() => toast.classList.remove("show"), 2600);
}

async function fetchJson(url, options) {
  const response = await fetch(url, options);
  const data = await response.json();
  if (!response.ok || !data.ok) throw new Error(data.error || "服務暫時無法使用");
  return data;
}

function buildDemoRanking(source) {
  const catalog = source.filter((item) => item && item.file);
  if (!catalog.length) return [];

  const startDate = new Date("2026-07-23T12:00:00+08:00");
  return Array.from({ length: MAX_RANKING }, (_, index) => {
    const sourceItem = catalog[index % catalog.length];
    const rank = index + 1;
    const duplicateRound = Math.floor(index / catalog.length);
    const createdAt = new Date(startDate);
    createdAt.setDate(createdAt.getDate() - index * 2 - (index % 5));

    return {
      ...sourceItem,
      id: `demo-ranking-${String(rank).padStart(3, "0")}`,
      source_id: String(sourceItem.id || ""),
      title: duplicateRound ? `${sourceItem.title}・典藏 ${rank}` : sourceItem.title,
      labels: Array.isArray(sourceItem.labels) ? sourceItem.labels : [],
      likes: 42000 - index * 260 + (index * 97) % 900,
      dislikes: 120 + (index * 29) % 650,
      downloads: 258000 - index * 1680 + (index * 733) % 4200,
      views: 1400000 - index * 8200 + (index * 6151) % 28000,
      weekly_views: 98000 - index * 620 + (index * 1877) % 7600,
      favorites: 34000 - index * 205 + (index * 449) % 2400,
      created_at: createdAt.toISOString()
    };
  });
}

function sortedWallpapers() {
  return [...wallpapers].sort((a, b) => {
    if (mode === "weekly_views") return b.weekly_views - a.weekly_views;
    if (mode === "views") return b.views - a.views;
    if (mode === "favorites") return b.favorites - a.favorites;
    if (mode === "newest") return new Date(b.created_at || 0) - new Date(a.created_at || 0);
    if (mode === "likes") return b.likes - a.likes;
    if (mode === "approval") return approval(b) - approval(a);
    if (mode === "score") return score(b) - score(a);
    return b.downloads - a.downloads;
  }).slice(0, MAX_RANKING);
}

function activeMetric(wallpaper) {
  if (mode === "weekly_views") return { icon: "flame", label: "本週", value: formatNumber(wallpaper.weekly_views) };
  if (mode === "views") return { icon: "eye", label: "瀏覽", value: formatNumber(wallpaper.views) };
  if (mode === "favorites") return { icon: "heart", label: "收藏", value: formatNumber(wallpaper.favorites) };
  if (mode === "newest") return { icon: "calendar-days", label: "上架", value: new Date(wallpaper.created_at).toLocaleDateString("zh-TW") };
  if (mode === "likes") return { icon: "thumbs-up", label: "讚", value: formatNumber(wallpaper.likes) };
  if (mode === "approval") return { icon: "badge-check", label: "好評", value: `${Math.round(approval(wallpaper) * 100)}%` };
  return { icon: "download", label: "下載", value: formatNumber(wallpaper.downloads) };
}

function renderPagination(total) {
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  currentPage = Math.min(currentPage, totalPages);
  const start = (currentPage - 1) * PAGE_SIZE + 1;
  const end = Math.min(currentPage * PAGE_SIZE, total);

  $("#rankingPagination").innerHTML = `
    <p class="ranking-pagination__summary" aria-live="polite">顯示第 ${start}–${end} 名，共 ${total} 名</p>
    <button type="button" data-page="${currentPage - 1}" aria-label="上一頁" ${currentPage === 1 ? "disabled" : ""}>上一頁</button>
    ${Array.from({ length: totalPages }, (_, index) => {
      const page = index + 1;
      return `<button type="button" data-page="${page}" data-page-number="${page}" aria-label="第 ${page} 頁" ${page === currentPage ? 'aria-current="page"' : ""}>${page}</button>`;
    }).join("")}
    <button type="button" data-page="${currentPage + 1}" aria-label="下一頁" ${currentPage === totalPages ? "disabled" : ""}>下一頁</button>
  `;
}

function render() {
  const list = sortedWallpapers();
  if (!list.length) {
    $("#podium").innerHTML = "";
    $("#rankingList").innerHTML = '<div class="collection-empty"><h3>排行榜載入中</h3><p>正在建立 100 筆展示排行資料。</p></div>';
    $("#rankingPagination").innerHTML = "";
    return;
  }

  const pageStart = (currentPage - 1) * PAGE_SIZE;
  const pageItems = list.slice(pageStart, pageStart + PAGE_SIZE);
  const showPodium = currentPage === 1;
  const medals = ["本期冠軍", "第二名", "第三名"];

  $("#podium").hidden = !showPodium;
  $("#podium").innerHTML = showPodium ? pageItems.slice(0, 3).map((wallpaper, index) => `
    <article class="podium-card rank-${index + 1}" data-preview="${wallpaper.id}" tabindex="0" role="button" aria-label="預覽 ${escapeHtml(wallpaper.title)}">
      <div class="rank-badge">${index + 1}</div>
      <img src="${wallpaper.file}" alt="${escapeHtml(wallpaper.title)} 4K 桌布">
      <div><small>${medals[index]}</small><h3>${escapeHtml(wallpaper.title)}</h3><span>${icon("thumbs-up")} ${formatNumber(wallpaper.likes)} · ${icon("thumbs-down")} ${formatNumber(wallpaper.dislikes)} · ${icon("download")} ${formatNumber(wallpaper.downloads)} · 好評 ${Math.round(approval(wallpaper) * 100)}%</span></div>
    </article>`).join("") : "";

  const listItems = showPodium ? pageItems.slice(3) : pageItems;
  const rankOffset = showPodium ? 3 : pageStart;
  $("#rankingList").innerHTML = listItems.map((wallpaper, index) => {
    const rank = rankOffset + index + 1;
    const metric = activeMetric(wallpaper);
    return `
      <button type="button" data-preview="${wallpaper.id}" aria-label="第 ${rank} 名，預覽 ${escapeHtml(wallpaper.title)}">
        <b>${rank}</b><img src="${wallpaper.file}" alt=""><span><strong>${escapeHtml(wallpaper.title)}</strong><small>${wallpaper.labels.map(escapeHtml).join(" · ") || "精選桌布"}</small></span>
        <em class="positive">${icon(metric.icon)} ${metric.label} ${metric.value}</em><em>${icon("thumbs-up")} ${formatNumber(wallpaper.likes)}</em><em>${icon("download")} ${formatNumber(wallpaper.downloads)}</em><i data-lucide="chevron-right" aria-hidden="true"></i>
      </button>`;
  }).join("");

  renderPagination(list.length);
  refreshIcons();
}

function fillDialog(wallpaper) {
  activeId = wallpaper.id;
  $("#dialogImage").src = wallpaper.file;
  $("#dialogImage").alt = `${wallpaper.title} 桌布預覽`;
  $("#dialogTitle").textContent = wallpaper.title;
  $("#dialogMeta").textContent = `展示排名 ${sortedWallpapers().findIndex((item) => item.id === wallpaper.id) + 1} · ${wallpaper.width} × ${wallpaper.height} · ${formatNumber(wallpaper.views)} 次瀏覽`;
  $("#dialogDownload").href = wallpaper.download_url || wallpaper.file;
  $("#dialogDownload").download = `帥龍萌姬桌布館-${wallpaper.title}.png`;
  $("#dialogLike span").textContent = formatNumber(wallpaper.likes);
  $("#dialogDislike span").textContent = formatNumber(wallpaper.dislikes);
  $("#dialogLike").classList.toggle("voted", savedVotes[wallpaper.id] === "like");
  $("#dialogDislike").classList.toggle("voted", savedVotes[wallpaper.id] === "dislike");
  $("#dialogLike").setAttribute("aria-pressed", savedVotes[wallpaper.id] === "like" ? "true" : "false");
  $("#dialogDislike").setAttribute("aria-pressed", savedVotes[wallpaper.id] === "dislike" ? "true" : "false");
}

function openDialog(id) {
  const wallpaper = wallpapers.find((item) => item.id === String(id));
  if (!wallpaper) return;
  fillDialog(wallpaper);
  dialog.showModal();
}

function vote(type) {
  const wallpaper = wallpapers.find((item) => item.id === activeId);
  if (!wallpaper) return;
  const previous = savedVotes[wallpaper.id];

  if (previous === "like") wallpaper.likes = Math.max(0, wallpaper.likes - 1);
  if (previous === "dislike") wallpaper.dislikes = Math.max(0, wallpaper.dislikes - 1);

  if (previous === type) {
    delete savedVotes[wallpaper.id];
  } else {
    savedVotes[wallpaper.id] = type;
    if (type === "like") wallpaper.likes += 1;
    if (type === "dislike") wallpaper.dislikes += 1;
  }

  fillDialog(wallpaper);
  render();
  showToast(savedVotes[wallpaper.id] ? (type === "like" ? "展示排行：已按讚" : "展示排行：已送出倒讚") : "展示排行：已取消投票");
}

document.addEventListener("click", (event) => {
  const rank = event.target.closest("[data-rank]");
  if (rank) {
    mode = rank.dataset.rank;
    currentPage = 1;
    document.querySelectorAll("[data-rank]").forEach((button) => button.classList.toggle("active", button === rank));
    render();
    return;
  }

  const pageButton = event.target.closest("[data-page]");
  if (pageButton && !pageButton.disabled) {
    currentPage = Number(pageButton.dataset.page);
    render();
    $("#rankingBoard").scrollIntoView({ behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth", block: "start" });
    return;
  }

  const preview = event.target.closest("[data-preview]");
  if (preview) openDialog(preview.dataset.preview);
});

document.addEventListener("keydown", (event) => {
  const preview = event.target.closest("article[data-preview]");
  if (preview && (event.key === "Enter" || event.key === " ")) {
    event.preventDefault();
    openDialog(preview.dataset.preview);
  }
});

$("#closeDialog").addEventListener("click", () => dialog.close());
dialog.addEventListener("click", (event) => { if (event.target === dialog) dialog.close(); });
$("#dialogLike").addEventListener("click", () => vote("like"));
$("#dialogDislike").addEventListener("click", () => vote("dislike"));
$("#dialogDownload").addEventListener("click", () => {
  const wallpaper = wallpapers.find((item) => item.id === activeId);
  if (wallpaper) {
    wallpaper.downloads += 1;
    render();
  }
});

async function load() {
  try {
    const catalog = await fetchJson(API.catalog, { cache: "no-store" });
    wallpapers = buildDemoRanking(catalog.wallpapers || []);
    $("#updatedAt").textContent = `展示數據 · 共 ${wallpapers.length} 名`;
    render();
  } catch (error) {
    $("#updatedAt").textContent = "展示資料暫時無法建立";
    $("#rankingList").innerHTML = `<div class="collection-empty"><h3>排行榜載入失敗</h3><p>${escapeHtml(error.message)}</p></div>`;
  }
}

render();
load();
refreshIcons();
