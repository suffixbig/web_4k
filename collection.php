<?php
$pageKey = 'collection';
$pageScript = 'collection.js';
$pageBodyClass = 'collection-page';
require __DIR__ . '/_incview/header.php';
?>
<!-- 主要內容 STAR -->
<main id="main-content" tabindex="-1">
    <section class="collection-hero"><div class="section"><p class="section-label">MY FAVORITES & PLAYLIST</p><h1>我的收藏，<br><span>也是 AI 的選圖靈感。</span></h1><p>收藏喜歡的作品，再編輯輪播清單與平日更換頻率。週末由帥龍萌姬桌布館推薦新桌布。</p></div></section>
    <section class="library-section section" id="favorites">
      <div class="collection-tabs"><button class="active" data-library="favorites"><i data-lucide="heart"></i>我的收藏 <span id="favoriteCount">0</span></button><button data-library="all"><i data-lucide="images"></i>全部桌布</button></div>
      <div class="collection-grid" id="collectionGrid"></div>
    </section>
    <section class="rule-strip"><div class="section"><div><b>一</b><b>二</b><b>三</b><b>四</b><b>五</b><span>你的輪播清單</span></div><i data-lucide="arrow-right"></i><div class="weekend"><b>六</b><b>日</b><span>本站推薦 · 必定換新</span></div><em><i data-lucide="clock-3"></i> 統一 06:00 執行</em></div></section>
    <section class="planner section" id="planner">
      <div class="planner-main">
        <div class="panel-head"><div><p class="section-label">PLAYLIST EDITOR</p><h2>AI 輪播清單</h2></div><span id="playlistCount">0 張</span></div>
        <div class="playlist-empty" id="playlistEmpty"><i data-lucide="list-plus"></i><h3>清單還是空的</h3><p>從下方收藏或桌布庫加入，AI 才知道平日要換哪些桌布。</p></div>
        <div class="playlist-editor" id="playlistEditor"></div>
      </div>
      <aside class="schedule-panel">
        <p class="section-label">WEEKDAY SCHEDULE</p><h2>平日更換規則</h2><p>只套用於週一至週五；週末推薦規則無法關閉。</p>
        <fieldset class="schedule-options"><legend class="sr-only">選擇平日更換頻率</legend><label><input type="radio" name="schedule" value="never"><span><i data-lucide="pause"></i><b>永不更換</b><small>平日維持目前桌布</small></span></label><label><input type="radio" name="schedule" value="weekly"><span><i data-lucide="calendar-range"></i><b>每週更換</b><small>每週一 06:00 換一張</small></span></label><label><input type="radio" name="schedule" value="daily"><span><i data-lucide="refresh-cw"></i><b>每天更換</b><small>週一至週五每天 06:00</small></span></label></fieldset>
        <div class="locked-setting"><span><i data-lucide="clock-3"></i>更換時間</span><strong>06:00</strong><i data-lucide="lock"></i></div>
        <div class="weekend-lock"><i data-lucide="shield-check"></i><div><strong>週末探索模式</strong><p>週六、週日 06:00 必定使用本站推薦新桌布。</p></div></div>
        <button class="button primary save-schedule" id="saveSchedule">儲存排程設定</button>
      </aside>
    </section>
    <section class="commands-section section" id="aiCommands">
      <div class="section-head"><div><p class="section-label">AI TRIGGER COMMANDS</p><h2>用這些話直接控制 AI</h2></div><a class="text-link" href="api-docs.php">查看完整 API 與指令說明 <i data-lucide="file-text"></i></a></div>
      <div class="command-grid"><button data-command="幫我收藏暗夜龍魂"><span>收藏</span><code>幫我收藏暗夜龍魂</code><i data-lucide="copy"></i></button><button data-command="把星火守望者加入我的輪播清單"><span>加入清單</span><code>把星火守望者加入我的輪播清單</code><i data-lucide="copy"></i></button><button data-command="把我的平日桌布設定成每天早上 6 點更換"><span>排程</span><code>平日每天早上 6 點換桌布</code><i data-lucide="copy"></i></button><button data-command="現在幫我換成排行榜第一名"><span>立即更換</span><code>現在幫我換成排行榜第一名</code><i data-lucide="copy"></i></button></div>
    </section>
  </main>
<!-- 主要內容 END -->
<?php require __DIR__ . '/_incview/footer.php'; ?>
