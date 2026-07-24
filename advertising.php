<?php
declare(strict_types=1);

$pageKey = 'advertising';
$pageScript = '';
$pageBodyClass = 'advertising-page';

$placementCards = [
    ['slot' => '首頁橫幅', 'size' => '1920 × 600', 'route' => '/index.php', 'moment' => '首頁搜尋區下方、最新上架作品之前', 'note' => '使用者開始瀏覽作品前看見的橫幅版位，適合新品或主活動視覺。', 'image' => 'skin/img/ads/original-brands/home-sunyu-poster.png', 'class' => 'wide'],
    ['slot' => '分類橫幅', 'size' => '1200 × 300', 'route' => '/search.php', 'moment' => '分類搜尋與作品結果頁上方', 'note' => '使用者帶著明確主題或搜尋需求瀏覽時曝光，適合對應內容興趣的廣告。', 'image' => 'skin/img/ads/original-brands/category-mist-isle-poster.png', 'class' => 'wide'],
    ['slot' => '作品頁橫幅', 'size' => '1200 × 250', 'route' => '作品詳情畫面', 'moment' => '開啟單一作品詳情時', 'note' => '緊貼作品內容出現，適合與作品主題、風格或使用情境建立連結。', 'image' => 'skin/img/ads/original-brands/detail-tide-brew-poster.png', 'class' => 'wide'],
    ['slot' => '下載完成廣告', 'size' => '1200 × 400', 'route' => '下載完成畫面', 'moment' => '作品下載成功之後', 'note' => '在使用者完成主要任務後出現，提供完整品牌訊息，不阻擋下載操作。', 'image' => 'skin/img/ads/original-brands/complete-orange-room-poster.png', 'class' => 'wide'],
    ['slot' => '手機版圖片', 'size' => '1080 × 1350', 'route' => '/search.php（手機）', 'moment' => '手機版分類與搜尋頁', 'note' => '行動裝置專用直式版位，避免橫幅縮小後文字難以閱讀。', 'image' => 'skin/img/ads/original-brands/mobile-aster-sip-poster.png', 'class' => 'portrait'],
];

require __DIR__ . '/_incview/header.php';
?>
<main id="main-content" tabindex="-1">
  <section class="adguide-hero" aria-labelledby="adGuideTitle">
    <img class="adguide-hero__art" src="skin/img/assets/advertising-panther-traffic-hero-v1.jpg" alt="黑豹吉祥物站在多螢幕廣告與流量成長圖表前" width="1920" height="1080" fetchpriority="high" decoding="async">
    <div class="section adguide-hero__content">
      <p class="section-label">4K.1-0.TW · ADVERTISING</p>
      <h1 id="adGuideTitle">刊登廣告</h1>
      <p>讓品牌出現在使用者瀏覽、搜尋與下載桌布的關鍵畫面。選擇廣告商品、指定版位與檔期，就能開始投放。</p>
      <div class="adguide-hero__actions"><a class="button primary" href="#pricing"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i>查看版位與價格</a><a class="button outline" href="#assetSpecs"><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i>查看素材規格</a></div>
      <div class="adguide-hero__facts" aria-label="廣告商品重點">
        <span><b>4</b> 種廣告商品</span>
        <span><b>5</b> 個圖片版位</span>
        <span><b>30 秒</b> 影片廣告</span>
      </div>
    </div>
  </section>

  <nav class="adguide-anchor-nav" aria-label="廣告說明頁導覽"><div class="section"><a href="#adProducts">商品</a><a href="#namingRules">冠名</a><a href="#placementMap">版位說明</a><a href="#videoFlow">影片流程</a><a href="#pricing">價格</a><a href="#assetSpecs">素材規格</a></div></nav>

  <section class="section adguide-section" id="adProducts" aria-labelledby="adProductsTitle">
    <div class="adguide-heading"><div><p class="section-label">PRODUCT PORTFOLIO</p><h2 id="adProductsTitle">四種商品，對應不同參與時刻</h2></div><p>用清楚的商品邊界、素材規格與上架節奏，讓每一種合作都可被衡量與執行。</p></div>
    <div class="adguide-product-grid">
      <article class="adguide-product-card adguide-product-card--feature"><span>01</span><i class="fa-solid fa-crown" aria-hidden="true"></i><h3>作品冠名贊助</h3><p>廣告主自行挑選指定作品，名稱跟隨作品在分類、詳情與下載流程曝光。</p><strong>NT$3,000 <small>／席／30 天</small></strong></article>
      <article class="adguide-product-card"><span>02</span><i class="fa-solid fa-align-left" aria-hidden="true"></i><h3>文字廣告</h3><p>以品牌、標題、說明與行動文字，快速呈現可閱讀的訊息。</p><strong>NT$2,000 <small>／30 天</small></strong></article>
      <article class="adguide-product-card"><span>03</span><i class="fa-regular fa-image" aria-hidden="true"></i><h3>純圖片廣告</h3><p>首頁、分類、作品頁、下載完成與手機版五種圖片版位。</p><strong>NT$8,000 <small>／30 天</small></strong></article>
      <article class="adguide-product-card"><span>04</span><i class="fa-solid fa-circle-play" aria-hidden="true"></i><h3>免費下載前影片</h3><p>只在不使用點數的免費下載流程播放，完成觀看才計入播放。</p><strong>NT$0.30 <small>／每次完整播放</small></strong></article>
    </div>
  </section>

  <section class="adguide-band" id="namingRules" aria-labelledby="namingRulesTitle"><div class="section">
    <div class="adguide-heading adguide-heading--compact"><div><p class="section-label">NAMING SPONSORSHIP</p><h2 id="namingRulesTitle">一件作品，最多 3 席冠名</h2></div><p>品牌可指定想要合作的作品；單一品牌一次購買全部 3 席時，以「獨家冠名」顯示。</p></div>
    <div class="adguide-naming-layout">
      <div class="adguide-seat-board" aria-label="冠名席次示意"><div class="adguide-seat-board__top"><span>指定作品</span><b>作品名稱區</b></div><div class="adguide-seats"><span>冠名席 01</span><span>冠名席 02</span><span>冠名席 03</span></div><p><i class="fa-solid fa-sparkles" aria-hidden="true"></i>購買 3 席時，作品頁以「獨家冠名」呈現。</p></div>
      <ol class="adguide-rule-list"><li><b>01</b><div><strong>指定作品</strong><span>冠名贊助由廣告主自行挑選指定作品。</span></div></li><li><b>02</b><div><strong>最多 3 席</strong><span>每件作品最多接受 3 家冠名贊助。</span></div></li><li><b>03</b><div><strong>獨家冠名</strong><span>同一品牌購買全部 3 席時，顯示為獨家冠名。</span></div></li></ol>
    </div>
  </div></section>

  <section class="section adguide-section" id="placementMap" aria-labelledby="placementMapTitle">
    <div class="adguide-heading"><div><p class="section-label">IMAGE PLACEMENT MAP</p><h2 id="placementMapTitle">先選商品，再決定廣告出現在哪裡</h2></div><p>「版位」是廣告在網站前台出現的固定位置。不同版位有自己的頁面、尺寸、檔期、開關與曝光統計。</p></div>
    <div class="adguide-placement-definition" aria-label="廣告商品、版位與素材的差異">
      <article><span>01 · 商品</span><i class="fa-solid fa-box-open" aria-hidden="true"></i><h3>買哪一種廣告</h3><p>本站只有 4 種廣告商品：作品冠名、文字、純圖片、免費下載前影片。</p></article>
      <article><span>02 · 版位</span><i class="fa-solid fa-location-dot" aria-hidden="true"></i><h3>廣告出現在哪裡</h3><p>以下 5 個位置都屬於「純圖片廣告」的可選版位，不是另外 5 種商品。</p></article>
      <article><span>03 · 素材</span><i class="fa-solid fa-crop-simple" aria-hidden="true"></i><h3>依版位製作尺寸</h3><p>選定版位後提供對應比例圖片；每個版位可獨立設定日期、每週投放日及開關。</p></article>
    </div>
    <div class="adguide-placement-notice"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><p><strong>購買時不只要選「圖片廣告」，還要指定投放版位。</strong>實際合作會在確認單中列明版位、素材尺寸、投放期間與曝光規則；測試價的版位組合以雙方確認內容為準。</p></div>
    <div class="adguide-placement-grid">
      <?php foreach ($placementCards as $placement): ?>
        <article class="adguide-placement-card adguide-placement-card--<?= htmlspecialchars($placement['class'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="adguide-placement-card__image"><img src="<?= htmlspecialchars($placement['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($placement['slot'], ENT_QUOTES, 'UTF-8') ?>版位範例" width="<?= $placement['class'] === 'portrait' ? '1080' : '1200' ?>" height="<?= $placement['class'] === 'portrait' ? '1350' : '600' ?>" loading="eager" decoding="async"></div>
          <div><span><?= htmlspecialchars($placement['size'], ENT_QUOTES, 'UTF-8') ?></span><h3><?= htmlspecialchars($placement['slot'], ENT_QUOTES, 'UTF-8') ?></h3><dl><div><dt>出現頁面</dt><dd><?= htmlspecialchars($placement['route'], ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>出現時機</dt><dd><?= htmlspecialchars($placement['moment'], ENT_QUOTES, 'UTF-8') ?></dd></div></dl><p><?= htmlspecialchars($placement['note'], ENT_QUOTES, 'UTF-8') ?></p></div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="adguide-text-section" aria-labelledby="textAdTitle"><div class="section adguide-text-layout">
    <div><p class="section-label">TEXT AD</p><h2 id="textAdTitle">文字廣告，快速上線</h2><p>清楚的欄位限制，讓廣告訊息能在各種閱讀情境保持可讀性。</p><div class="adguide-copy-limits"><span><b>品牌名稱</b><strong>15 字內</strong></span><span><b>標題</b><strong>20 字內</strong></span><span><b>說明</b><strong>40 字內</strong></span><span><b>行動文字</b><strong>10 字內</strong></span></div></div>
    <aside class="adguide-text-preview" aria-label="文字廣告版位範例"><p>BRAND MESSAGE</p><strong>晴柚茶研</strong><h3>日光剛好的清爽</h3><span>清新果香，陪你把今天過得剛剛好。</span><a href="#assetSpecs">立即了解</a></aside>
  </div></section>

  <section class="section adguide-section" id="videoFlow" aria-labelledby="videoFlowTitle">
    <div class="adguide-heading"><div><p class="section-label">REWARDED VIDEO</p><h2 id="videoFlowTitle">免費下載前，完整觀看 2 則 30 秒影片</h2></div><p>會員或使用點數者可直接跳過；影片廣告僅在不消耗點數的免費下載流程中播放。</p></div>
    <div class="adguide-video-flow" aria-label="免費下載影片廣告流程"><article><span>01</span><i class="fa-solid fa-download" aria-hidden="true"></i><strong>選擇免費下載</strong><small>不消耗點數</small></article><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><article><span>02</span><i class="fa-solid fa-circle-play" aria-hidden="true"></i><strong>影片 A</strong><small>完整播放 30 秒</small></article><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><article><span>03</span><i class="fa-solid fa-circle-play" aria-hidden="true"></i><strong>影片 B</strong><small>完整播放 30 秒</small></article><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><article class="adguide-video-flow__end"><span>04</span><i class="fa-solid fa-check" aria-hidden="true"></i><strong>開始下載</strong><small>一次下載最多 2 次完整播放</small></article></div>
  </section>

  <section class="adguide-pricing-section" id="pricing" aria-labelledby="pricingTitle"><div class="section">
    <div class="adguide-heading"><div><p class="section-label">TEST PRICING</p><h2 id="pricingTitle">測試價格，30 天驗證</h2></div><p>正式報價與曝光預估，以網站後台近 30 天實際數據為準。</p></div>
    <div class="adguide-pricing-grid"><article><span>指定作品冠名</span><strong>NT$3,000</strong><small>1 席／30 天</small></article><article class="is-highlight"><span>指定作品獨家冠名</span><strong>NT$9,000</strong><small>3 席／30 天</small></article><article><span>文字廣告</span><strong>NT$2,000</strong><small>30 天</small></article><article><span>圖片廣告</span><strong>NT$8,000</strong><small>30 天</small></article><article class="adguide-pricing-grid__video"><span>30 秒影片廣告</span><strong>NT$0.30</strong><small>每次完整播放 · 最低採購 100,000 次</small></article></div>
  </div></section>

  <section class="section adguide-section" id="assetSpecs" aria-labelledby="assetSpecsTitle">
    <div class="adguide-heading"><div><p class="section-label">ASSETS & ONBOARDING</p><h2 id="assetSpecsTitle">素材規格與上架節奏</h2></div><p>素材確認後，依序進行版位設定、審稿與上線；完整素材建議於預計上線前 3 個工作天送達。</p></div>
    <div class="adguide-spec-grid">
      <article><i class="fa-solid fa-signature" aria-hidden="true"></i><h3>冠名字樣</h3><p>PNG 或 SVG<br>建議 512 × 512</p></article>
      <article><i class="fa-regular fa-image" aria-hidden="true"></i><h3>圖片廣告</h3><p>JPG、PNG 或 WebP<br>單檔 5MB 內 · 不使用 GIF</p></article>
      <article><i class="fa-solid fa-film" aria-hidden="true"></i><h3>影片廣告</h3><p>MP4 H.264 · 1920 × 1080<br>最長 30 秒 · 單檔 100MB 內</p></article>
    </div>
    <ol class="adguide-onboarding"><li><b>01</b><span>確認需求</span></li><li><b>02</b><span>選擇商品</span></li><li><b>03</b><span>確認版位</span></li><li><b>04</b><span>廣告上稿</span></li><li><b>05</b><span>審稿上線</span></li><li><b>06</b><span>成效回報</span></li></ol>
  </section>

  <section class="section adguide-closing"><p class="section-label">LET'S GROW THE NEXT AI WORK</p><h2>讓品牌成為下一件 AI 作品生命的一部分</h2><p>選擇指定作品、確認商品與素材規格，即可開始 30 天的測試合作。</p><a class="button primary" href="#adProducts"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i>回到商品總覽</a></section>
</main>
<?php require __DIR__ . '/_incview/footer.php'; ?>
