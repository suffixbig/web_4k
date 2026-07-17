const $ = (selector) => document.querySelector(selector);
const API = { catalog: "api/catalog/list", stats: "api/stats/read" };
const clientId = localStorage.getItem("luma-client-id") || (crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`);
localStorage.setItem("luma-client-id", clientId);

let wallpapers = [];
let savedVotes = {};
let mode = "downloads";
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

async function statsAction(id, action) {
  return fetchJson(API.stats, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id: String(id), action, client_id: clientId })
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
  });
}

function render() {
  const list = sortedWallpapers();
  if (!list.length) {
    $("#podium").innerHTML = "";
    $("#rankingList").innerHTML = '<div class="collection-empty"><h3>排行榜載入中</h3><p>正在取得最新 JSON 統計資料。</p></div>';
    return;
  }

  const medals = ["本期冠軍", "第二名", "第三名"];
  $("#podium").innerHTML = list.slice(0, 3).map((wallpaper, index) => `
    <article class="podium-card rank-${index + 1}" data-preview="${wallpaper.id}" tabindex="0" role="button" aria-label="預覽 ${escapeHtml(wallpaper.title)}">
      <div class="rank-badge">${index + 1}</div>
      <img src="${wallpaper.file}" alt="${escapeHtml(wallpaper.title)} 4K 桌布">
      <div><small>${medals[index]}</small><h3>${escapeHtml(wallpaper.title)}</h3><span>${icon("thumbs-up")} ${formatNumber(wallpaper.likes)} · ${icon("thumbs-down")} ${formatNumber(wallpaper.dislikes)} · ${icon("download")} ${formatNumber(wallpaper.downloads)} · 好評 ${Math.round(approval(wallpaper) * 100)}%</span></div>
    </article>`).join("");

  $("#rankingList").innerHTML = list.slice(3).map((wallpaper, index) => `
    <button type="button" data-preview="${wallpaper.id}" aria-label="第 ${index + 4} 名，預覽 ${escapeHtml(wallpaper.title)}">
      <b>${index + 4}</b><img src="${wallpaper.file}" alt=""><span><strong>${escapeHtml(wallpaper.title)}</strong><small>${wallpaper.labels.map(escapeHtml).join(" · ")}</small></span>
      <em class="positive">${icon("thumbs-up")} ${formatNumber(wallpaper.likes)}</em><em>${icon("thumbs-down")} ${formatNumber(wallpaper.dislikes)}</em><em>${icon("download")} ${formatNumber(wallpaper.downloads)}</em><i data-lucide="chevron-right" aria-hidden="true"></i>
    </button>`).join("");
  refreshIcons();
}

function fillDialog(wallpaper) {
  activeId = wallpaper.id;
  $("#dialogImage").src = wallpaper.file;
  $("#dialogImage").alt = `${wallpaper.title} 桌布預覽`;
  $("#dialogTitle").textContent = wallpaper.title;
  $("#dialogMeta").textContent = `目前排名 ${sortedWallpapers().findIndex((item) => item.id === wallpaper.id) + 1} · ${wallpaper.width} × ${wallpaper.height} · ${formatNumber(wallpaper.views)} 次瀏覽`;
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
  statsAction(id, "view").then((data) => {
    Object.assign(wallpaper, data.stats);
    fillDialog(wallpaper);
  }).catch(() => {});
}

async function vote(type) {
  const wallpaper = wallpapers.find((item) => item.id === activeId);
  if (!wallpaper) return;
  try {
    const data = await statsAction(wallpaper.id, type);
    Object.assign(wallpaper, data.stats);
    if (data.vote) savedVotes[wallpaper.id] = data.vote;
    else delete savedVotes[wallpaper.id];
    fillDialog(wallpaper);
    render();
    showToast(data.vote ? (data.vote === "like" ? "已按讚" : "已送出倒讚") : "已取消投票");
  } catch (error) {
    showToast(error.message);
  }
}

document.addEventListener("click", (event) => {
  const rank = event.target.closest("[data-rank]");
  if (rank) {
    mode = rank.dataset.rank;
    document.querySelectorAll("[data-rank]").forEach((button) => button.classList.toggle("active", button === rank));
    render();
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
  statsAction(activeId, "download").then((data) => {
    const wallpaper = wallpapers.find((item) => item.id === activeId);
    if (wallpaper) Object.assign(wallpaper, data.stats);
    render();
  }).catch(() => {});
});

async function load() {
  try {
    const [catalog, stats] = await Promise.all([
      fetchJson(API.catalog, { cache: "no-store" }),
      fetchJson(`${API.stats}?client_id=${encodeURIComponent(clientId)}`, { cache: "no-store" })
    ]);
    savedVotes = stats.user_votes || {};
    wallpapers = catalog.wallpapers.map((item) => ({
      ...item,
      id: String(item.id),
      labels: Array.isArray(item.labels) ? item.labels : [],
      likes: Number(stats.wallpapers[item.id]?.likes || 0),
      dislikes: Number(stats.wallpapers[item.id]?.dislikes || 0),
      downloads: Number(stats.wallpapers[item.id]?.downloads || 0),
      views: Number(stats.wallpapers[item.id]?.views || 0),
      weekly_views: Number(stats.wallpapers[item.id]?.weekly_views || 0),
      favorites: Number(stats.wallpapers[item.id]?.favorites || 0)
    }));
    $("#updatedAt").textContent = stats.updated_at ? `更新於 ${new Date(stats.updated_at).toLocaleString("zh-TW")}` : "已取得最新票數";
    render();
  } catch (error) {
    $("#updatedAt").textContent = "即時資料暫時無法取得";
    $("#rankingList").innerHTML = `<div class="collection-empty"><h3>排行榜載入失敗</h3><p>${escapeHtml(error.message)}</p></div>`;
  }
}

render();
load();
refreshIcons();
