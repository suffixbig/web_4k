<?php
$pageKey = 'home';
$pageScript = 'home.js';
$pageBodyClass = '';
require __DIR__ . '/_incview/header.php';
?>
<!-- 主要內容 STAR -->
<main id="main-content" tabindex="-1">
    <section class="hero hero-carousel" aria-label="帥龍與萌姬 AI 桌布技能介紹">
      <article class="hero-slide active" data-slide="0" aria-hidden="false">
        <img class="slide-bg" src="skin/img/assets/desktop/desktop-14.jpg" alt="緋紅奇幻 4K 桌布" fetchpriority="high">
        <div class="hero-shade"></div>
        <div class="hero-content">
          <p class="kicker"><span></span> THE NEW WAY TO WALLPAPER</p>
          <h1>重新改變<br><span>桌布下載習慣。</span></h1>
          <p>不用再逛網站、下載檔案、打開設定。平日依你的輪播清單執行，週六、週日則由帥龍與萌姬桌布館推薦新桌布。</p>
          <div class="hero-actions"><a class="button primary" href="ai-skill.php"><i data-lucide="sparkles"></i>啟用一句話換桌布</a><a class="button glass" href="#how"><i data-lucide="play"></i>看看有多簡單</a></div>
        </div>
      </article>
      <article class="hero-slide" data-slide="1" aria-hidden="true">
        <img class="slide-bg" src="skin/img/assets/desktop/desktop-3.jpg" alt="星火守望者 4K 桌布" loading="lazy">
        <div class="hero-shade"></div>
        <div class="hero-content">
          <p class="kicker"><span></span> JUST SAY IT</p>
          <h1>跟 AI 說一句：<br><span>「幫我換桌布。」</span></h1>
          <p>就這樣。AI 會理解你的螢幕與喜好，自動找到高畫質桌布並替你換好。不用下載、不用設定，真的爽。</p>
          <div class="hero-actions"><button class="button primary hero-copy" data-prompt="幫我換一張有質感的暗色 4K 桌布"><i data-lucide="copy"></i>複製這句話</button><a class="button glass" href="#explore">先看熱門桌布</a></div>
        </div>
      </article>
      <article class="hero-slide" data-slide="2" aria-hidden="true">
        <img class="slide-bg" src="skin/img/assets/desktop/desktop-7.jpg" alt="霓虹城市 4K 桌布" loading="lazy">
        <div class="hero-shade"></div>
        <div class="hero-content">
          <p class="kicker"><span></span> SEARCH. PICK. APPLY.</p>
          <h1>找桌布、換桌布<br><span>都叫 AI 幫你。</span></h1>
          <p>想要「不刺眼的藍色夜景」或「本週排行榜第一名」？用自然語言說出來，AI 幫你找，也幫你直接套用。</p>
          <div class="hero-actions"><a class="button primary" href="ranking.php"><i data-lucide="trophy"></i>查看下載排行榜</a><a class="button glass" href="ai-skill.php">安裝 AI 技能</a></div>
        </div>
      </article>
      <div class="carousel-ui" aria-label="輪播控制">
        <button class="carousel-arrow" id="prevSlide" aria-label="上一張"><i data-lucide="arrow-left"></i></button>
        <div class="carousel-dots" role="tablist"><button class="active" data-go-slide="0" aria-label="第 1 張" aria-selected="true"></button><button data-go-slide="1" aria-label="第 2 張" aria-selected="false"></button><button data-go-slide="2" aria-label="第 3 張" aria-selected="false"></button></div>
        <span class="slide-count"><b id="currentSlide">01</b> / 03</span>
        <button class="carousel-arrow" id="nextSlide" aria-label="下一張"><i data-lucide="arrow-right"></i></button>
      </div>
      <div class="hero-promise"><span class="live-dot"></span><div><small>SMART AUTOMATION</small><strong>每天 06:00 · 自動判斷</strong></div><i data-lucide="calendar-check"></i></div>
    </section>

    <section class="habit-section section" id="how">
      <div class="section-head"><div><p class="section-label">ONE SENTENCE. THAT'S IT.</p><h2>以前要做 5 件事，<br>現在只要說 1 句話。</h2></div><p>從搜尋到套用都交給 AI。你只需要描述今天想看到的感覺。</p></div>
      <div class="habit-compare">
        <div class="old-way"><span class="compare-label">以前</span><ol><li>打開桌布網站</li><li>慢慢搜尋圖片</li><li>確認尺寸與畫質</li><li>下載到電腦</li><li>進入系統設定套用</li></ol></div>
        <div class="compare-arrow"><i data-lucide="arrow-right"></i></div>
        <div class="new-way"><span class="compare-label">現在</span><div class="ai-quote"><i data-lucide="message-square"></i><p>幫我換一張<br><strong>暗色系 4K 桌布</strong></p></div><div class="done"><i data-lucide="circle-check-big"></i><span>AI 已搜尋並換好桌布</span></div></div>
      </div>
    </section>

    <section class="search-scenarios section" aria-labelledby="scenarioTitle">
      <div class="section-head"><div><p class="section-label">TELL AI THE MOOD</p><h2 id="scenarioTitle">一句話，把今天想看的畫面說出來。</h2></div><p>不用學關鍵字。把人物、色系、作者、裝置與感覺連成一句，AI 會帶你到正確的桌布結果。</p></div>
      <div class="scenario-grid"><article><i data-lucide="user-round-search"></i><span>找作者作品</span><h3>「搜尋桌布 墨凡」</h3><p>下班後想重看熟悉作者的奇幻作品，直接說出作者名字。</p><button class="hero-copy" data-prompt="搜尋桌布 墨凡"><i data-lucide="copy"></i>複製例句</button></article><article><i data-lucide="palette"></i><span>找色系與情境</span><h3>「搜尋 真人綠色系畫作 手機桌布」</h3><p>想讓通勤時的手機主畫面像一片安靜森林，就把風格與裝置一起說。</p><button class="hero-copy" data-prompt="搜尋 真人綠色系畫作 手機桌布"><i data-lucide="copy"></i>複製例句</button></article><article><i data-lucide="sparkles"></i><span>直接換一張</span><h3>「換桌布 墨凡的畫」</h3><p>AI 會在符合條件的作品中隨機挑一張，立刻替 Windows 桌面換上新畫面。</p><button class="hero-copy" data-prompt="換桌布 墨凡的畫"><i data-lucide="copy"></i>複製例句</button></article></div>
    </section>

    <section class="ai-section section" id="ai">
      <div class="ai-card">
        <div class="ai-copy">
          <p class="section-label">AI WALLPAPER SKILL</p>
          <h2>輪播清單自己排，<br><span>每天 06:00 交給 AI。</span></h2>
          <p>此技能限定 Codex 與 Claude 安裝。平日可選永不、每週或每天更換；週六、週日使用本站推薦新桌布。</p>
          <div class="prompt-box"><div><i data-lucide="terminal"></i><code id="skillPrompt">幫我安裝帥龍與萌姬桌布館技能：以後我只要說「換桌布」，就取得目前桌布的下一張；無資料時隨機換一張。</code></div><button id="copyPrompt" aria-label="複製 AI 安裝指令"><i data-lucide="copy"></i><span>複製指令</span></button></div>
          <div class="ai-actions"><a class="button primary" href="ai-skill.php"><i data-lucide="sparkles"></i>前往技能啟用頁</a><a href="api-docs.php">查看 API 說明 <i data-lucide="arrow-up-right"></i></a></div>
        </div>
        <div class="automation-card">
          <div class="automation-top"><div class="ai-orb"><i data-lucide="bot"></i></div><div><small>WALLPAPER AI SKILL</small><strong>桌布自動換新</strong></div><span class="status">已啟用</span></div>
          <div class="schedule-row"><span><i data-lucide="calendar-days"></i> 預設平日頻率</span><strong>每週一 · 06:00</strong></div>
          <div class="preference"><span>AI 會依這些條件找圖</span><div><b>你的偏好</b><b>螢幕比例</b><b>排行榜</b><b>4K</b></div></div>
          <div class="next-wallpaper"><img src="skin/img/assets/desktop/desktop-3.jpg" alt="下次預定更換的桌布"><div><small>NEXT SCHEDULE</small><strong>星火守望者</strong><span>AI 已選好下一張桌布</span></div><i data-lucide="chevron-right"></i></div>
        </div>
      </div>
    </section>

    <section class="home-links section"><a href="search.php"><i data-lucide="search"></i><div><small>分類搜尋</small><strong>找到你想要的桌布</strong></div><i data-lucide="arrow-right"></i></a><a href="collection.php"><i data-lucide="heart"></i><div><small>我的收藏</small><strong>編輯 AI 輪播清單</strong></div><i data-lucide="arrow-right"></i></a><a href="ranking.php"><i data-lucide="trophy"></i><div><small>下載排行榜</small><strong>看看大家都在用什麼</strong></div><i data-lucide="arrow-right"></i></a></section>
  </main>
<!-- 主要內容 END -->
<?php require __DIR__ . '/_incview/footer.php'; ?>
