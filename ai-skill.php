<?php
$pageKey = 'skill';
$pageScript = 'skill.js';
$pageBodyClass = 'skill-page';
$skillPlatforms = [];
foreach (['codex' => 'Codex', 'claude' => 'Claude'] as $platformKey => $platformName) {
    $relativePath = "downloads/{$platformKey}/SKILL.md";
    $absolutePath = __DIR__ . '/' . $relativePath;
    $versionPath = __DIR__ . "/downloads/{$platformKey}/VERSION";
    $markdown = is_file($absolutePath) ? (string) file_get_contents($absolutePath) : '';
    $versionFile = is_file($versionPath) ? trim((string) file_get_contents($versionPath)) : '';
    $version = preg_match('/^v[\d.]+$/i', $versionFile)
        ? $versionFile
        : (preg_match('/Skill version:\s*(v[\d.]+)/i', $markdown, $matches) ? $matches[1] : 'v1.117');
    $skillPlatforms[$platformKey] = [
        'name' => $platformName,
        'path' => $relativePath,
        'url' => 'https://raw.githubusercontent.com/suffixbig/4k/main/' . $relativePath,
        'markdown' => $markdown,
        'version' => $version,
        'versionPath' => "downloads/{$platformKey}/VERSION",
        'updatedAt' => is_file($absolutePath) ? date('Y-m-d', filemtime($absolutePath)) : date('Y-m-d'),
    ];
}
$activeSkill = $skillPlatforms['codex'];
require __DIR__ . '/_incview/header.php';
?>
<!-- 主要內容 STAR -->
<main id="main-content" tabindex="-1">
    <section class="skill-hero"><div class="section"><div><p class="section-label">ONE SENTENCE WALLPAPER SKILL</p><h1>讓我的 AI 學會<br><span>一句話換桌布。</span></h1><p>不用自己下載與打開設定。告訴 Codex 或 Claude 想要什麼，它會搜尋本站 API、下載正確尺寸並替你套用。</p><div class="platform-badges"><span><i data-lucide="terminal-square"></i>Codex</span><span><i data-lucide="bot"></i>Claude</span><em>僅限這兩個平台</em></div></div><div class="skill-demo"><small>YOU</small><p>幫我換一張暗色的帥龍 4K 桌布</p><small>CODEX / CLAUDE</small><div><i data-lucide="circle-check-big"></i><span><strong>已換好桌布</strong>使用「暗夜龍魂」· 1920 × 1080</span></div></div></div></section>
    <section class="skill-proof-poster section" aria-labelledby="skillProofPosterTitle">
      <h2 class="sr-only" id="skillProofPosterTitle">Codex 實際執行換桌布技能</h2>
      <img src="skin/img/assets/ai-skill-codex-panther-poster-v1.jpg"
           alt="黑豹吉祥物指向真實 Codex 畫面，畫面顯示換桌布技能已套用作品並完成網站偏好更新"
           width="1672" height="941" loading="lazy" decoding="async">
    </section>
    <section class="install-section section" id="install">
      <div class="section-head"><div><p class="section-label">INSTALL SKILL</p><h2>選擇你的 AI</h2></div><p>安裝後可說「換桌布 墨凡的畫」隨機套用符合條件的作品，或說「搜桌布 墨凡」直接開啟搜尋結果。</p></div>
      <div class="platform-tabs" role="tablist" aria-label="選擇 AI 平台"><button class="active" id="codexTab" role="tab" aria-selected="true" aria-controls="skillPlatformPanel" tabindex="0" data-platform="codex">Codex</button><button id="claudeTab" role="tab" aria-selected="false" aria-controls="skillPlatformPanel" tabindex="-1" data-platform="claude">Claude</button></div>
      <div class="install-layout" id="skillPlatformPanel" role="tabpanel" aria-labelledby="codexTab"><div class="install-steps"><article><b>1</b><div><h3>複製安裝指令</h3><p>使用下方為你的平台準備好的完整指令。</p></div></article><article><b>2</b><div><h3>貼到 Codex 或 Claude</h3><p>AI 會建立可辨識換桌布與「搜桌布」的本機技能。</p></div></article><article><b>3</b><div><h3>允許本機桌布操作</h3><p>AI 會下載、驗證並直接套用 Windows 桌布。</p></div></article><article><b>4</b><div><h3>以後只要說一句話</h3><p>例如：「搜桌布 墨凡」或「換桌布 墨凡的畫」。</p></div></article></div><div class="install-command"><div class="command-head"><span id="platformLabel">CODEX INSTALL PROMPT</span><i data-lucide="shield-check"></i></div><pre id="installPrompt">請下載並安裝 <?= htmlspecialchars($activeSkill['url'], ENT_QUOTES, 'UTF-8') ?>（帥龍萌姬桌布館 <?= htmlspecialchars($activeSkill['version'], ENT_QUOTES, 'UTF-8') ?>）。依 SKILL.md 處理「換桌布」、帶條件換桌布，以及搜尋桌布指令。</pre><button class="button primary" id="copyInstall"><i data-lucide="copy"></i>複製 Codex 安裝指令</button><a class="button outline skill-markdown-download" href="<?= htmlspecialchars($activeSkill['path'], ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>下載 Codex SKILL.md</a><p><i data-lucide="info"></i>此技能不支援 ChatGPT 網頁版、Gemini 或其他 AI 平台安裝。</p></div></div>
    </section>
    <section class="skill-file-preview section" aria-labelledby="skillFileTitle"><div class="skill-file-heading"><div><p class="section-label">SKILL.MD PREVIEW</p><h2 id="skillFileTitle">Codex 技能規格</h2></div><div class="skill-file-meta" aria-live="polite"><strong id="skillVersion">版本 <?= htmlspecialchars($activeSkill['version'], ENT_QUOTES, 'UTF-8') ?></strong><span id="skillUpdatedAt">更新日期 <?= htmlspecialchars($activeSkill['updatedAt'], ENT_QUOTES, 'UTF-8') ?></span><a class="button outline skill-markdown-download" id="previewDownload" href="<?= htmlspecialchars($activeSkill['path'], ENT_QUOTES, 'UTF-8') ?>" download>下載 Codex SKILL.md</a></div></div><p id="skillPreviewDescription">目前預覽 Codex 專用檔案；切換平台後，這裡會同步顯示對應的 SKILL.md。</p><pre class="skill-file-code"><code id="skillMarkdownPreview"><?= htmlspecialchars($activeSkill['markdown'], ENT_QUOTES, 'UTF-8') ?></code></pre></section>
    <script type="application/json" id="skillPlatformData"><?= json_encode($skillPlatforms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <section class="skill-rules section" aria-labelledby="androidAppTitle">
      <div class="section-head"><div><p class="section-label">ANDROID APP · v2.2.0</p><h2 id="androidAppTitle">手機搜尋，選好直接套用</h2></div><p>Android 8.0 以上適用。完整搜尋本站作品，並選擇兩者皆套、主畫面或鎖定畫面。</p></div>
      <div class="rule-cards"><article><i data-lucide="sparkles"></i><h3>AI 技能換桌布</h3><p>在 Windows 使用自然語言搜尋與換桌布，條件不符合時會停止並清楚回報。</p></article><article><i data-lucide="search"></i><h3>Android 完整搜尋</h3><p>手機 App 同步網站分類、尺寸、色系、創作類型與熱門統計。</p></article><article><i data-lucide="download"></i><h3>安裝新版 Android App</h3><p>選圖後可下載圖片，或套用至主畫面、鎖定畫面與兩者。</p><a class="button primary" href="android-app.php">查看功能並下載 APK</a></article></div>
    </section>
    <section class="skill-rules section"><div class="section-head"><div><p class="section-label">AUTOMATION RULES</p><h2>安裝後怎麼運作？</h2></div><a class="button outline" href="api-docs.php"><i data-lucide="braces"></i>查看完整 API 說明</a></div><div class="rule-cards"><article><i data-lucide="calendar-days"></i><h3>週一至週五</h3><p>使用你的 AI 輪播清單，可選永不、每週一或每天 06:00 更換。</p></article><article><i data-lucide="sparkles"></i><h3>週六與週日</h3><p>固定於 06:00 使用本站推薦的新桌布，避免桌面一成不變。</p></article><article><i data-lucide="message-square"></i><h3>隨時一句話</h3><p>立即換圖、搜尋、收藏、加入清單與修改平日規則都能直接說。</p></article></div></section>
    <section class="trigger-examples section"><p class="section-label">TRY SAYING</p><h2>安裝後可以這樣說</h2><p class="trigger-intro">把你的情境說完整：想找誰的作品、什麼色調、真人或 AI、要放在 PC 還是手機；每一張例句都可點擊複製。</p><div class="example-groups"><section><h3><i data-lucide="search"></i>搜桌布</h3><div class="command-grid"><button data-command="搜桌布 墨凡"><span>指定作者</span><code>搜桌布 墨凡</code><i data-lucide="copy"></i></button><button data-command="搜桌布 真人綠色系畫作 手機桌布"><span>情境與裝置</span><code>搜桌布 真人綠色系畫作 手機桌布</code><i data-lucide="copy"></i></button><button data-command="搜桌布 藍色星空 手機直式"><span>色系與尺寸</span><code>搜桌布 藍色星空 手機直式</code><i data-lucide="copy"></i></button></div></section><section><h3><i data-lucide="monitor-cog"></i>直接換桌布</h3><div class="command-grid"><button data-command="換桌布"><span>下一張作品</span><code>換桌布</code><i data-lucide="copy"></i></button><button data-command="換桌布 墨凡的畫"><span>指定作者隨機換</span><code>換桌布 墨凡的畫</code><i data-lucide="copy"></i></button><button data-command="換桌布 綠色系 AI 桌布"><span>指定風格隨機換</span><code>換桌布 綠色系 AI 桌布</code><i data-lucide="copy"></i></button></div></section></div></section>
  </main>
<!-- 主要內容 END -->
<?php require __DIR__ . '/_incview/footer.php'; ?>
