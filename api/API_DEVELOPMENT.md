# API 開發規範

所有請求由 `api/index.php` 解析，網址格式為 `/api/{endpoint}/{action}`。新增端點時，先在入口檔的 `switch` 加入 `v_{endpoint}.php`，再於該檔依 `$action` 或 HTTP method 處理資料。

回應一律為 JSON；成功資料使用 `ok: true`，錯誤資料使用 `ok: false` 與 `error`。資料寫入須驗證輸入、使用檔案鎖定，並在錯誤時回傳適當 HTTP 狀態碼。

目前端點：`catalog/list|search`、`stats/read|record`、`preferences/read|update`、`automation/change_now`、`users/read|update`、`help`。

`catalog/search` 與 `catalog/list` 支援 `q`、`creator`（或 `author`）、`device=pc|mobile`、`orientation=landscape|portrait`、`content_type=ai|real`。JSON 資料使用 `creator`、`colors`、`content_type`、`device`、`orientation` 描述可搜尋欄位。

`automation/change_now` 是 AI 技能的即時換桌布端點。未帶條件時優先從 `current_wallpaper_id` 或使用者偏好取得目前桌布，再回傳下一張；帶 `q` 或任一篩選條件時，會隨機回傳符合條件的一張，若無結果則回傳 `decision: no_result`，絕不改選無關桌布。AI 成功套用 Windows 桌布後，必須以 `preferences` 的 `current_set` 回寫選取的 ID。
# 自動化換桌布 API

`GET /api/automation/change_now?client_id={client_id}&current_wallpaper_id={wallpaper_id}&q={條件}` 回傳 `api_version: "1.114"`、決策來源與桌布資料。

- `wallpaper.download_url` 必須是 `https://4k.1-0.tw/` 的 HTTPS 絕對圖片網址。
- `source` 只會是 `next_in_catalog`、`random_fallback` 或 `search_random`。
- 客戶端只可在下載、圖片驗證與系統套用成功後，以 `preferences/read` 的 `current_set` 回寫 ID。
