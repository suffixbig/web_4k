const $ = (selector) => document.querySelector(selector);
const API = { catalog: "api/catalog/list", preferences: "api/preferences/read" };
const clientId = localStorage.getItem("luma-client-id") || (crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`);
localStorage.setItem("luma-client-id", clientId);

let wallpapers = [];
let profile = { favorites: [], playlist: [], schedule: { weekday_mode: "weekly" } };
let libraryMode = "favorites";
const toast = $("#toast");

const icon = (name) => `<i data-lucide="${name}" aria-hidden="true"></i>`;
const escapeHtml = (value) => String(value).replace(/[&<>"]/g, (character) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[character]);
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

async function updatePreferences(payload) {
  const data = await fetchJson(API.preferences, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ client_id: clientId, ...payload })
  });
  profile = data.profile;
  render();
  return data;
}

function render() {
  const playlist = profile.playlist.map((id) => wallpapers.find((wallpaper) => wallpaper.id === String(id))).filter(Boolean);
  $("#playlistCount").textContent = `${playlist.length} 張`;
  $("#playlistEmpty").hidden = playlist.length > 0;
  $("#playlistEditor").innerHTML = playlist.map((wallpaper, index) => `
    <article>
      <b>${String(index + 1).padStart(2, "0")}</b><img src="${wallpaper.file}" alt="${escapeHtml(wallpaper.title)}"><div><strong>${escapeHtml(wallpaper.title)}</strong><small>${wallpaper.labels.map(escapeHtml).join(" · ")}</small></div><span>${index === 0 ? "下一張" : `第 ${index + 1} 張`}</span>
      <div class="playlist-actions"><button type="button" data-move="up" data-index="${index}" aria-label="向上移動" ${index === 0 ? "disabled" : ""}>${icon("arrow-up")}</button><button type="button" data-move="down" data-index="${index}" aria-label="向下移動" ${index === playlist.length - 1 ? "disabled" : ""}>${icon("arrow-down")}</button><button type="button" data-remove="${wallpaper.id}" aria-label="移除 ${escapeHtml(wallpaper.title)}">${icon("trash-2")}</button></div>
    </article>`).join("");

  $("#favoriteCount").textContent = profile.favorites.length;
  const ids = libraryMode === "favorites" ? profile.favorites : wallpapers.map((wallpaper) => wallpaper.id);
  const items = ids.map((id) => wallpapers.find((wallpaper) => wallpaper.id === String(id))).filter(Boolean);
  $("#collectionGrid").innerHTML = items.length ? items.map((wallpaper) => {
    const isFavorite = profile.favorites.includes(wallpaper.id);
    const isAdded = profile.playlist.includes(wallpaper.id);
    return `<article><div><img src="${wallpaper.file}" alt="${escapeHtml(wallpaper.title)}"><button type="button" class="favorite ${isFavorite ? "active" : ""}" data-favorite="${wallpaper.id}" aria-pressed="${isFavorite}" aria-label="${isFavorite ? "移除收藏" : "收藏"} ${escapeHtml(wallpaper.title)}">${icon("heart")}</button></div><h3>${escapeHtml(wallpaper.title)}</h3><p>${wallpaper.labels.map(escapeHtml).join(" · ")}</p><button type="button" class="add-playlist ${isAdded ? "added" : ""}" data-add="${wallpaper.id}" ${isAdded ? "disabled" : ""}>${icon(isAdded ? "check" : "list-plus")} ${isAdded ? "已在 AI 輪播清單" : "加入 AI 輪播清單"}</button></article>`;
  }).join("") : `<div class="collection-empty">${icon("heart")}<h3>${libraryMode === "favorites" ? "尚未收藏桌布" : "桌布載入中"}</h3><p>${libraryMode === "favorites" ? "到分類搜尋頁按下愛心，桌布就會出現在這裡。" : "正在讀取 JSON 桌布目錄。"}</p></div>`;

  const selected = document.querySelector(`input[name="schedule"][value="${profile.schedule.weekday_mode}"]`);
  if (selected) selected.checked = true;
  refreshIcons();
}

document.addEventListener("click", async (event) => {
  const favorite = event.target.closest("[data-favorite]");
  const add = event.target.closest("[data-add]");
  const remove = event.target.closest("[data-remove]");
  const move = event.target.closest("[data-move]");
  const tab = event.target.closest("[data-library]");
  const command = event.target.closest("[data-command]");

  try {
    if (favorite) {
      await updatePreferences({ action: "favorite_toggle", id: favorite.dataset.favorite });
      showToast("收藏已更新");
    }
    if (add) {
      await updatePreferences({ action: "playlist_add", id: add.dataset.add });
      showToast("已加入 AI 輪播清單");
    }
    if (remove) {
      await updatePreferences({ action: "playlist_remove", id: remove.dataset.remove });
      showToast("已從輪播清單移除");
    }
    if (move) {
      const ids = [...profile.playlist];
      const from = Number(move.dataset.index);
      const to = move.dataset.move === "up" ? from - 1 : from + 1;
      if (to >= 0 && to < ids.length) {
        [ids[from], ids[to]] = [ids[to], ids[from]];
        await updatePreferences({ action: "playlist_save", ids });
        showToast("輪播順序已更新");
      }
    }
    if (tab) {
      libraryMode = tab.dataset.library;
      document.querySelectorAll("[data-library]").forEach((button) => button.classList.toggle("active", button === tab));
      render();
    }
    if (command) {
      await navigator.clipboard.writeText(command.dataset.command);
      showToast("AI 指令已複製");
    }
  } catch (error) {
    showToast(error.message);
  }
});

$("#saveSchedule").addEventListener("click", async () => {
  const selected = document.querySelector('input[name="schedule"]:checked');
  if (!selected) return showToast("請選擇平日更換頻率");
  try {
    await updatePreferences({ action: "schedule_set", weekday_mode: selected.value });
    showToast("排程已儲存，固定於 06:00 執行");
  } catch (error) {
    showToast(error.message);
  }
});

async function boot() {
  render();
  try {
    const [catalog, preferences] = await Promise.all([
      fetchJson(API.catalog, { cache: "no-store" }),
      fetchJson(`${API.preferences}?client_id=${encodeURIComponent(clientId)}`, { cache: "no-store" })
    ]);
    wallpapers = catalog.wallpapers.map((item) => ({ ...item, id: String(item.id), labels: Array.isArray(item.labels) ? item.labels : [] }));
    profile = preferences.profile;
    render();
  } catch (error) {
    $("#collectionGrid").innerHTML = `<div class="collection-empty"><h3>收藏資料載入失敗</h3><p>${escapeHtml(error.message)}</p></div>`;
    showToast(error.message);
  }
}

boot();
refreshIcons();
