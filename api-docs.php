<?php
$pageKey = 'api';
$pageScript = '';
$pageBodyClass = 'docs-page';
require __DIR__ . '/_incview/header.php';
?>
<!-- 主要內容 STAR -->
<main id="main-content" tabindex="-1" class="docs-shell section" id="apiContent">
    <aside class="docs-nav"><strong>API 說明</strong><a href="#overview">總覽</a><a href="#stats">統計 API</a><a href="#preferences">收藏與清單</a><a href="#automation">AI 自動化</a><a href="#rules">排程規則</a><a href="#errors">錯誤處理</a><a href="AI_API_GUIDE.md">原始 Markdown</a></aside>
    <article class="docs-content">
      <section id="overview"><p class="section-label">DEVELOPER API</p><h1>API 說明</h1><p>提供 Codex 與 Claude Skill 使用的 JSON API。所有資料由 PHP 透過檔案鎖寫入單一 <code>/json</code> 目錄，不使用資料庫。</p><p>正式 API Base URL：<code>https://4k.1-0.tw/api/</code></p><div class="api-live-links"><a href="api/" target="_blank" rel="noopener"><i data-lucide="braces"></i><span><strong>查看 API 首頁</strong><small>GET /api/</small></span></a><a href="api/catalog.php" target="_blank" rel="noopener"><i data-lucide="images"></i><span><strong>查看桌布目錄</strong><small>GET api/catalog.php</small></span></a><a href="api/stats.php" target="_blank" rel="noopener"><i data-lucide="activity"></i><span><strong>查看統計 API</strong><small>GET api/stats.php</small></span></a><a href="api/preferences.php?client_id=demo-device" target="_blank" rel="noopener"><i data-lucide="heart"></i><span><strong>查看偏好 API</strong><small>GET api/preferences.php</small></span></a><a href="api/automation.php?client_id=demo-device&amp;intent=find" target="_blank" rel="noopener"><i data-lucide="bot"></i><span><strong>查看自動化 API</strong><small>GET api/automation.php</small></span></a></div></section>
      <section id="stats"><p class="section-label">01 / STATS</p><h2>統計 API</h2><p>讀取或更新讚、倒讚、瀏覽與下載數。</p><pre><code>GET /api/stats.php

POST /api/stats.php
{
  "client_id": "device-abc123",
  "id": "2",
  "action": "like"
}</code></pre><p>支援的 action：<code>like</code>、<code>dislike</code>、<code>view</code>、<code>download</code>。</p></section>
      <section id="preferences"><p class="section-label">02 / PREFERENCES</p><h2>收藏與輪播清單 API</h2><pre><code>GET /api/preferences.php?client_id=device-abc123

POST /api/preferences.php
{
  "client_id": "device-abc123",
  "action": "playlist_add",
  "id": "3"
}</code></pre><div class="docs-table"><div><b>favorite_toggle</b><span>收藏或取消收藏</span></div><div><b>playlist_add</b><span>加入輪播清單</span></div><div><b>playlist_remove</b><span>移除桌布</span></div><div><b>playlist_save</b><span>儲存完整排序</span></div><div><b>schedule_set</b><span>設定 never／weekly／daily</span></div><div><b>current_set</b><span>回寫目前桌布</span></div></div></section>
      <section id="automation"><p class="section-label">03 / AUTOMATION</p><h2>AI 自動化 API</h2><pre><code>GET /api/automation.php
  ?client_id=device-abc123
  &amp;intent=resolve_schedule
  &amp;date=2026-07-18T06:00:00+08:00</code></pre><p><code>decision=change</code> 時，AI 才能下載並套用回傳的桌布；<code>decision=keep</code> 是正常結果，不應重試。</p><div class="docs-table"><div><b>resolve_schedule</b><span>立即取得目前桌布的下一張；無資料時隨機選擇</span></div><div><b>find</b><span>依 game／dragon／anime／black 搜尋</span></div><div><b>change_now</b><span>立即取得指定或推薦桌布</span></div></div></section>
      <section id="rules"><p class="section-label">04 / RULES</p><h2>三個字即時換桌布</h2><ul><li>只要對已安裝的 AI 說「換桌布」。</li><li>AI 讀取目前桌布 ID 後呼叫 <code>automation/change_now</code>。</li><li>目前桌布存在於清單時，取得清單的下一張。</li><li>沒有目前資料時，API 回傳隨機桌布。</li><li>成功套用 Windows 桌布後，AI 以 <code>current_set</code> 回寫 ID。</li></ul></section>
      <section id="errors"><p class="section-label">05 / ERRORS</p><h2>錯誤與安全處理</h2><ul><li>API 失敗最多重試三次，使用 10、30、60 秒間隔。</li><li>圖片下載或驗證失敗時保留目前桌布。</li><li>只有成功套用後才呼叫 current_set。</li><li>正式上線應加入登入權杖、CSRF、頻率限制與允許網域檢查。</li></ul><a class="button primary" href="AI_API_GUIDE.md"><i data-lucide="file-text"></i>閱讀完整技術文件</a></section>
    </article>
  </main>
<!-- 主要內容 END -->
<?php require __DIR__ . '/_incview/footer.php'; ?>
