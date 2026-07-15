# 帥龍與萌姬桌布館：AI 桌布自動化與 API 完整規格

正式網站與 API Base URL：`https://4k.1-0.tw`

## 1. 核心規則

所有日期與時間都使用 `Asia/Taipei`：

| 日期 | 來源 | 規則 |
|---|---|---|
| 週一至週五 | 使用者編輯的 AI 輪播清單 | 可選永不、每週或每天更換 |
| 週六、週日 | 本站系統推薦 | 每天 06:00 必定換成新的推薦桌布，使用者不能關閉 |

平日模式：

- `never`：週一至週五不自動更換，維持當前桌布。
- `weekly`：每週一 06:00 從個人輪播清單換一張；週二至週五維持不變。
- `daily`：週一至週五每天 06:00 依個人清單輪播。
- 平日清單為空時回傳 `keep`，AI 不應擅自使用推薦桌布。
- 週末永遠優先於平日模式。即使平日選擇 `never`，週六與週日仍會換成本站推薦新桌布。

建議只建立一個每天 06:00 的 AI 排程。每天由 `automation.php` 判斷當天應該 `change` 或 `keep`，不要建立互相競爭的多組系統排程。

## 2. 資料儲存

本專案不使用資料庫：

- `json/wallpapers.json`：網站共用桌布目錄與搜尋標籤。
- `json/wallpaper-stats.json`：讚、倒讚、瀏覽與下載統計。
- `json/user-preferences.json`：匿名使用者的收藏、輪播順序、平日模式與目前桌布。
- PHP 寫入時使用 `flock(LOCK_EX)`，避免並行請求覆蓋資料。
- `json/.htaccess` 阻止網站訪客直接下載 JSON。
- `client_id` 是瀏覽器或 AI Skill 產生並持久保存的匿名 ID，只允許英數、底線和連字號，最長 80 字元。

## 3. 偏好與輪播清單 API

端點：

```text
GET  /api/preferences.php?client_id={client_id}
POST /api/preferences.php
```

讀取偏好：

```http
GET /api/preferences.php?client_id=device-abc123
```

收藏或取消收藏：

```json
{
  "client_id": "device-abc123",
  "action": "favorite_toggle",
  "id": "2"
}
```

加入輪播清單：

```json
{
  "client_id": "device-abc123",
  "action": "playlist_add",
  "id": "3"
}
```

從輪播清單移除：

```json
{
  "client_id": "device-abc123",
  "action": "playlist_remove",
  "id": "3"
}
```

儲存完整順序：

```json
{
  "client_id": "device-abc123",
  "action": "playlist_save",
  "ids": ["3", "2", "7", "1"]
}
```

設定平日更換規則：

```json
{
  "client_id": "device-abc123",
  "action": "schedule_set",
  "weekday_mode": "daily"
}
```

`weekday_mode` 只能是 `never`、`weekly` 或 `daily`。時間固定為 `06:00`，週末推薦固定開啟，所以 API 不接受修改時間或關閉週末規則。

桌布成功套用後回寫目前桌布：

```json
{
  "client_id": "device-abc123",
  "action": "current_set",
  "id": "3"
}
```

## 4. AI 自動化解析 API

端點：

```text
GET  /api/automation.php
POST /api/automation.php
```

### 每天 06:00 解析排程

```http
GET /api/automation.php?client_id=device-abc123&intent=resolve_schedule&date=2026-07-18T06:00:00%2B08:00
```

需要更換時：

```json
{
  "ok": true,
  "intent": "resolve_schedule",
  "decision": "change",
  "source": "system_weekend_recommendation",
  "run_time": "06:00",
  "wallpaper": {
    "id": "3",
    "title": "星火守望者",
    "width": 1920,
    "height": 1080,
    "download_url": "/skin/img/assets/desktop/desktop-3.jpg"
  }
}
```

不需更換時回傳：

```json
{
  "ok": true,
  "decision": "keep",
  "source": "user_playlist",
  "reason": "每週模式只在週一 06:00 更換",
  "wallpaper": null
}
```

AI 必須只在 `decision` 為 `change` 且 `wallpaper.download_url` 存在時下載並套用。`keep` 不是錯誤，不需重試。

### 搜尋桌布

```json
{
  "client_id": "device-abc123",
  "intent": "find",
  "topic": "black"
}
```

支援主題：`game`、`dragon`、`anime`、`black`。不提供主題時，依排行榜回傳前五名。

### 立即更換

指定桌布：

```json
{
  "client_id": "device-abc123",
  "intent": "change_now",
  "wallpaper_id": "2"
}
```

沒有指定 ID 時，使用目前系統推薦：

```json
{
  "client_id": "device-abc123",
  "intent": "change_now"
}
```

## 5. AI Skill 標準執行流程

```text
每天 06:00 喚醒
  → resolve_schedule
  → decision = keep：記錄原因並結束
  → decision = change：下載原圖到暫存位置
  → 驗證檔案類型、尺寸與下載成功
  → 呼叫 Windows 桌布設定
  → current_set 回寫成功套用的桌布 ID
  → 顯示通知（來源為個人清單或週末推薦）
```

失敗處理：

1. API 無法連線：最多重試 3 次，間隔可採 10、30、60 秒。
2. 圖片下載失敗：保留目前桌布，不回寫 `current_set`。
3. Windows 套用失敗：保留下載檔供診斷，不更新目前桌布。
4. JSON 回傳 `keep`：正常結束，不重試。
5. 個人清單為空：提醒使用者加入桌布；週末仍正常推薦。

## 6. 自然語言觸發指令對照

| 使用者說法 | AI 意圖 | API 動作 |
|---|---|---|
| 幫我收藏暗夜龍魂 | 收藏 | `favorite_toggle`，桌布 ID 2 |
| 取消收藏暗夜龍魂 | 取消收藏 | 先 GET 確認已收藏，再呼叫 `favorite_toggle` |
| 顯示我的收藏 | 查看收藏 | GET `preferences.php` |
| 把星火守望者加入輪播清單 | 加入輪播 | `playlist_add`，桌布 ID 3 |
| 把暗夜龍魂從輪播清單移除 | 移除輪播 | `playlist_remove` |
| 把星火守望者排到第一張 | 編輯順序 | GET 清單、調整陣列、`playlist_save` |
| 清空我的輪播清單 | 清空清單 | `playlist_save`，`ids` 傳空陣列 |
| 平日不要自動換桌布 | 平日永不更換 | `schedule_set: never` |
| 平日每週換一張 | 平日每週更換 | `schedule_set: weekly` |
| 平日每天早上六點換桌布 | 平日每天更換 | `schedule_set: daily` |
| 週末不要換桌布 | 不允許 | 說明週末是鎖定推薦規則，不修改設定 |
| 改成晚上八點換 | 不允許 | 說明執行時間固定 06:00 |
| 找暗色護眼桌布 | 搜尋 | `find`，`topic: black` |
| 找最熱門的龍桌布 | 搜尋 | `find`，`topic: dragon`，取第一筆 |
| 現在幫我換桌布 | 立即更換 | `change_now` |
| 換成排行榜第一名 | 立即更換熱門榜首 | 先讀排行榜或 `change_now` 不指定 ID |
| 換成我的下一張輪播桌布 | 手動輪播 | GET 個人清單，取下一張，再 `change_now` 指定 ID |
| 今天為什麼沒有換？ | 診斷 | `resolve_schedule` 帶今天日期，向使用者解釋 `reason` |

## 7. AI 回覆範本

成功加入清單：

```text
已把「星火守望者」加入你的 AI 輪播清單，目前排在第 3 張。平日排程會依你的更換模式使用這份清單。
```

成功設定每天更換：

```text
已設定週一至週五每天 06:00 從個人輪播清單更換桌布。週六、週日仍會在 06:00 自動換成本站推薦新桌布。
```

拒絕關閉週末規則：

```text
平日模式可以改成永不、每週或每天更換；週六與週日的本站推薦模式是固定規則，無法關閉，會在 06:00 各換一張推薦新桌布。
```

空清單提醒：

```text
你的輪播清單目前沒有桌布，所以平日不會自動更換。週末推薦仍會正常執行。要我先從排行榜加入 5 張嗎？
```

## 8. 安全與正式上線建議

- 正式環境應把瀏覽器產生的 `client_id` 升級為登入帳號或簽章裝置權杖。
- API 應限制同來源、加入 CSRF 保護與請求頻率限制。
- AI Skill 下載檔案後應驗證 MIME、最大檔案大小與圖片尺寸。
- 不要把任意 URL 直接交給系統桌布設定，只接受 API 回傳且屬於允許網域的網址。
- 使用者手動說「現在換」可以越過平日排程，但不能永久改寫週末規則。
