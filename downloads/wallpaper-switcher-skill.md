# 帥龍與萌姬桌布館：換桌布技能

## 觸發詞

使用者只要說：`換桌布`

## 工作流程

1. 讀取本機保存的 `client_id` 與 `current_wallpaper_id`；初次使用時建立新的 UUID 作為 `client_id`。
2. 呼叫：

   ```text
   GET https://4k.1-0.tw/api/automation/change_now?client_id={client_id}&current_wallpaper_id={current_wallpaper_id}
   ```

3. 驗證回應為 `ok: true`、`decision: change`，並取得 `wallpaper.id` 與 `wallpaper.download_url`。
4. 顯示選中的桌布名稱與來源後，立即下載圖片、驗證圖片可用，並套用為本機 Windows 桌布。
6. 成功套用後，保存新的 `current_wallpaper_id`，並回寫：

   ```json
   POST https://4k.1-0.tw/api/preferences/read
   {"client_id":"{client_id}","action":"current_set","id":"{wallpaper.id}"}
   ```

## 選圖規則

- `source: next_in_catalog`：目前桌布存在於本站清單，使用下一張。
- `source: random_fallback`：沒有目前桌布資料或不在清單中，直接使用 API 回傳的隨機桌布。

## 安全規則

- 只允許下載 `https://4k.1-0.tw/` 的圖片。
- API 與圖片驗證成功後，直接修改系統桌布。
- API、下載、驗證或套用失敗時，不要更新目前桌布 ID。
