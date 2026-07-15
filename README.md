# 帥龍與萌姬桌布館

本機網站：`https://4k.test.com/`
正式網站：`https://4k.1-0.tw/`

全新獨立的 PC 桌布網站，入口為 `index.php`。

網站分成六頁：

1. `index.php`：首頁與 AI 桌布概念。
2. `search.php`：大型搜尋欄、分類與桌布網格。
3. `collection.php`：我的收藏、輪播清單與平日排程。
4. `ranking.php`：沒有輪播的獨立下載排行榜。
5. `ai-skill.php`：限定 Codex 與 Claude 的技能啟用說明。
6. `api-docs.php`：網站版 API 文件。

`AI_API_GUIDE.md` 保留完整 AI 觸發指令、排程解析、API 契約與失敗處理。

## API 與 PWA 入口

- `GET /api/`：API 探索首頁，列出所有 JSON 端點與儲存方式。
- `GET /api/catalog.php`：讀取 `json/wallpapers.json` 的公開桌布目錄。
- `manifest.webmanifest`、`pwa.js`、`sw.js`：安裝式 PWA、離線頁與快取策略。
- `tests/rwd-check.cjs`：驗證 4／3／2／1 欄 RWD、44px 觸控區、PWA 啟用及功能頁載入。

## 統計 API

- `GET api/stats.php`：讀取所有公開統計。
- `POST api/stats.php`：更新 `like`、`dislike`、`view` 或 `download`。
- 資料寫入 `json/wallpaper-stats.json`，不使用資料庫。
- PHP 使用 `flock()` 鎖定檔案，避免並行請求互相覆蓋。
- 按讚與倒讚以瀏覽器產生的匿名 `client_id` 去重，同一人再次按相同選項會取消投票。

POST 範例：

```json
{
  "id": "1",
  "action": "like",
  "client_id": "anonymous-browser-id"
}
```

`json` 是全站唯一資料目錄，必須允許 PHP 寫入；`.htaccess` 已阻擋瀏覽器直接讀取 JSON。

## 個人偏好與自動化 API

- `api/preferences.php`：收藏、輪播清單、排程與目前桌布。
- `api/automation.php`：AI 搜尋、立即更換和每日 06:00 排程決策。
- `json/user-preferences.json`：所有個人化資料，不使用資料庫。
