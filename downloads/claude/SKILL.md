---
name: wallpaper-switcher
description: "Claude Code skill for 帥龍萌姬桌布館 commands: 換桌布 to apply the next local Windows wallpaper; 換桌布 with a condition to randomly apply a matching wallpaper; and 搜桌布 followed by conditions to open or return the matching website search URL."
---

# 帥龍萌姬桌布館 Claude 搜尋與換桌布技能

Skill version: v1.116

This skill is for Claude Code on the local Windows PC only. Never change a remote host wallpaper. Do not ask for confirmation before changing the local wallpaper.

## Triggers

- `換桌布`: select the next catalog item, or the random fallback when the current ID is unknown.
- `換桌布 <條件>`: select one random item matching the condition. Examples: `換桌布 墨凡的畫` and `換桌布 綠色系 AI 桌布`.
- `搜桌布 <條件>`: return and, when browser control is available, open `https://4k.1-0.tw/search?device=pc&orientation=landscape&q={urlencoded 條件}`. Examples: `搜桌布 真人綠色系畫作` and `搜桌布 真人墨凡桌布`.

Recognize work title, creator, colors, and `真人` / `AI 創作` as search conditions. This Windows PC skill always searches only PC landscape wallpapers, so every catalog, search, and change request must include `device=pc&orientation=landscape`. Keep the condition after removing only the trigger phrase and optional `的畫` / `桌布` wording.

## Change workflow

1. Read `$HOME/.claude/state/wallpaper-switcher.json`. If absent, create a UUID `client_id` and set `current_wallpaper_id` to `null`.
2. For the bare trigger, call `GET https://4k.1-0.tw/api/automation/change_now?device=pc&orientation=landscape&client_id={client_id}&current_wallpaper_id={current_wallpaper_id}`.
3. For a conditioned trigger, use the same required `device=pc&orientation=landscape` parameters and add `&q={urlencoded 條件}`. Do not fall back to an unrelated wallpaper.
4. If `decision` is `no_result`, report `沒有符合條件的桌布。` and do not alter the system or state.
5. Require `ok: true`, `decision: change`, `api_version`, `wallpaper.id`, `wallpaper.device: "pc"`, `wallpaper.orientation: "landscape"`, and a HTTPS `wallpaper.download_url` on `4k.1-0.tw`. When width and height are present, also require `width > height`. Accept `source` only as `next_in_catalog`, `random_fallback`, or `search_random`.
6. Run `scripts/set-wallpaper.ps1 -Url <download_url> -WallpaperId <id>`. The script downloads below `$HOME/.claude/state/wallpapers`, verifies image size and dimensions, then applies it to Windows.
7. Only after the script succeeds, write `wallpaper.id` to `current_wallpaper_id`.
8. Call `POST https://4k.1-0.tw/api/preferences/read` with JSON `{ "client_id": "...", "action": "current_set", "id": "..." }`.
9. Report title, creator, ID, source, and the completed preference update.

## Failure handling

- Do not alter state if API validation, no result, download, image validation, Windows update, or preference update fails.
- Apply `random_fallback` only for a bare `換桌布`; never use it when a condition was supplied.
- Never remove or override the required `device=pc&orientation=landscape` filters, even when the user mentions a phone or portrait wallpaper.
- Reject a non-HTTPS URL, an unexpected host, a missing ID, or an image smaller than the script validation limit.
