<?php
declare(strict_types=1);
$pageKey = 'app';
$pageBodyClass = 'android-app-page';
$versionPath = __DIR__ . '/downloads/apk/VERSION';
$appVersion = is_file($versionPath) ? trim((string) file_get_contents($versionPath)) : 'v2.0.0';
$safeVersion = preg_match('/^v[\d.]+$/', $appVersion) ? $appVersion : 'v2.0.0';
$apkRelativePath = 'downloads/apk/dragon-maiden-wallpaper-' . $safeVersion . '.apk';
$apkPath = __DIR__ . '/' . $apkRelativePath;
$apkExists = is_file($apkPath);
$apkBytes = $apkExists ? (int) filesize($apkPath) : 0;
$apkSize = $apkBytes > 0 ? number_format($apkBytes / 1048576, 1) . ' MB' : '建置中';
$apkHash = $apkExists ? strtoupper((string) hash_file('sha256', $apkPath)) : '';
$apkUpdatedAt = $apkExists ? date('Y-m-d', filemtime($apkPath)) : date('Y-m-d');
require __DIR__ . '/_incview/header.php';
?>
<main id="main-content" tabindex="-1">
  <section class="app-hero">
    <div class="section app-hero-grid">
      <div class="app-hero-copy">
        <p class="section-label">ANDROID WALLPAPER APP · <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></p>
        <h1>把整座桌布館<br><span>裝進手機。</span></h1>
        <p>無廣告、免帳號。搜尋本站手機直式作品，先預覽裁切，再一鍵套用主畫面、鎖定畫面或兩者；收藏、輪播與 06:00 排程都在同一個 App 完成。</p>
        <div class="app-badges"><span><i class="fa-brands fa-android" aria-hidden="true"></i>Android 8.0+</span><span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>僅連線本站</span><span><i class="fa-solid fa-ban" aria-hidden="true"></i>無廣告</span></div>
        <div class="app-hero-actions">
          <?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><span class="button primary disabled" aria-disabled="true">APK 建置中</span><?php endif; ?>
          <a class="button outline" href="#features">看看完整功能</a>
        </div>
        <p class="app-build-note">檔案 <?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · 更新 <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?> · 測試簽章版</p>
      </div>
      <div class="app-phone-stage" aria-label="手機 App 介面示意">
        <div class="app-phone"><div class="app-phone-speaker"></div><div class="app-phone-screen"><img src="skin/img/assets/mobile/mobile-01.jpg" alt="翠嶺龍息手機桌布預覽"><div class="app-phone-shade"></div><div class="app-phone-ui"><small>SELECTED WALLPAPER</small><strong>翠嶺龍息</strong><span>AI 幻境 · 941 × 1672</span><div><b>主畫面</b><b>鎖定畫面</b><b>兩者</b></div></div></div></div>
        <div class="app-floating-card"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span><strong>排程已啟用</strong><small>下次執行：週一 06:00</small></span></div>
      </div>
    </div>
  </section>
  <section class="app-metrics"><div class="section"><div><strong>20</strong><span>手機直式作品</span></div><div><strong>3</strong><span>低耗電動態場景</span></div><div><strong>3</strong><span>套用目標</span></div><div><strong>0</strong><span>廣告與帳號</span></div></div></section>
  <section class="app-features section" id="features">
    <div class="section-head"><div><p class="section-label">BUILT FOR DAILY USE</p><h2>好看的桌布 App，<br>也要真的好用。</h2></div><p>把主流桌布 App 最實用的功能留下，去掉廣告、強制登入與不必要權限。</p></div>
    <div class="app-feature-grid">
      <article class="feature-wide"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><h3>搜尋本站手機作品</h3><p>依作品、作者、色系或風格搜尋，只顯示適合手機的 9:16 直式高畫質內容。</p><div class="feature-search"><span>暗色護眼</span><span>藍色</span><span>綠色</span></div></article>
      <article class="feature-tall"><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i><h3>分開套用</h3><p>主畫面、鎖定畫面或兩者皆可，不必反覆進入系統設定。</p><ul><li>主畫面</li><li>鎖定畫面</li><li>兩者同步</li></ul></article>
      <article><i class="fa-solid fa-crop-simple" aria-hidden="true"></i><h3>可控裁切重心</h3><p>選擇上方、中央或下方，減少主體被手機螢幕裁掉。</p></article>
      <article><i class="fa-solid fa-heart" aria-hidden="true"></i><h3>收藏與輪播同步</h3><p>同一組裝置識別碼同步本站收藏及個人輪播清單。</p></article>
      <article class="feature-wide"><i class="fa-solid fa-clock" aria-hidden="true"></i><h3>可靠的 06:00 排程</h3><p>可選不啟用、每週一或每天；只挑手機直式作品。網路或圖片驗證失敗時保留原桌布，不用擔心空白畫面。</p><div class="schedule-demo"><span>每天</span><b>06:00</b><em>下一張手機桌布</em></div></article>
      <article><i class="fa-solid fa-image" aria-hidden="true"></i><h3>保存高畫質原圖</h3><p>Android 10 以上直接保存至圖片庫，不要求整個儲存空間權限。</p></article>
      <article><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i><h3>三種原生動態桌布</h3><p>龍焰流光、星界守望、翡翠龍息；畫面不可見時立即停止繪製。</p></article>
    </div>
  </section>
  <section class="app-trust"><div class="section app-trust-grid"><div><p class="section-label">PRIVACY &amp; PERMISSIONS</p><h2>權限用在哪，<br>全部說清楚。</h2><p>App 不含廣告 SDK、不建立帳號，也不讀取聯絡人或整個相簿。</p></div><div class="permission-list"><article><i class="fa-solid fa-wifi" aria-hidden="true"></i><span><strong>網路</strong><small>讀取本站目錄與下載桌布</small></span></article><article><i class="fa-solid fa-display" aria-hidden="true"></i><span><strong>設定桌布</strong><small>只有按下套用或排程執行時使用</small></span></article><article><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span><strong>鬧鐘與開機</strong><small>只在啟用每週／每日排程後使用</small></span></article><article><i class="fa-solid fa-bell" aria-hidden="true"></i><span><strong>通知</strong><small>只在啟用排程時詢問，用來回報結果</small></span></article></div></div></section>
  <section class="app-install section" id="download">
    <div class="section-head"><div><p class="section-label">INSTALL APK</p><h2>三步驟開始使用</h2></div><p>本站直接提供 APK，Android 第一次安裝時可能要求允許瀏覽器安裝未知來源 App。</p></div>
    <div class="install-cards"><article><b>1</b><h3>下載 APK</h3><p>按下載並保留安裝檔。</p></article><article><b>2</b><h3>允許本次安裝</h3><p>依 Android 提示允許目前瀏覽器安裝。</p></article><article><b>3</b><h3>開啟並選桌布</h3><p>搜尋、預覽後選擇套用位置。</p></article></div>
    <div class="app-download-card"><div><span>ANDROID PACKAGE</span><h3>帥龍與萌姬桌布館 <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · Android 8.0 以上 · <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?></p></div><?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK</a><?php endif; ?></div>
    <?php if ($apkHash !== ''): ?><details class="apk-checksum"><summary>檢查 APK 的 SHA-256</summary><code><?= htmlspecialchars($apkHash, ENT_QUOTES, 'UTF-8') ?></code></details><?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/_incview/footer.php'; ?>
