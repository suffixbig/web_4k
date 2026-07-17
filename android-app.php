<?php
declare(strict_types=1);
$pageKey = 'app';
$pageBodyClass = 'android-app-page';
$versionPath = __DIR__ . '/downloads/apk/VERSION';
$appVersion = is_file($versionPath) ? trim((string) file_get_contents($versionPath)) : 'v2.1.0';
$safeVersion = preg_match('/^v[\d.]+$/', $appVersion) ? $appVersion : 'v2.1.0';
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
        <p>無廣告、免帳號。打開 App 選一張手機桌布，只保留「立即套用」與「下載圖片」兩個操作；立即套用會直接設為主畫面。</p>
        <div class="app-badges"><span><i class="fa-brands fa-android" aria-hidden="true"></i>Android 8.0+</span><span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>僅連線本站</span><span><i class="fa-solid fa-ban" aria-hidden="true"></i>無廣告</span></div>
        <div class="app-hero-actions">
          <?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><span class="button primary disabled" aria-disabled="true">APK 建置中</span><?php endif; ?>
          <a class="button outline" href="#features">了解兩項功能</a>
        </div>
        <p class="app-build-note">檔案 <?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · 更新 <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?> · 測試簽章版</p>
      </div>
      <div class="app-phone-stage" aria-label="手機 App 介面示意">
        <div class="app-phone"><div class="app-phone-speaker"></div><div class="app-phone-screen"><img src="skin/img/assets/mobile/mobile-01.jpg" alt="翠嶺龍息手機桌布預覽"><div class="app-phone-shade"></div><div class="app-phone-ui"><small>SELECTED WALLPAPER</small><strong>翠嶺龍息</strong><span>AI 幻境 · 941 × 1672</span><div><b>立即套用</b><b>下載圖片</b></div></div></div></div>
      </div>
    </div>
  </section>
  <section class="app-metrics"><div class="section"><div><strong>20</strong><span>手機直式作品</span></div><div><strong>2</strong><span>清楚操作按鈕</span></div><div><strong>1</strong><span>一步套用主畫面</span></div><div><strong>0</strong><span>廣告與帳號</span></div></div></section>
  <section class="app-features section" id="features">
    <div class="section-head"><div><p class="section-label">TWO ACTIONS ONLY</p><h2>選好桌布，<br>下一步只有兩個。</h2></div><p>移除搜尋、收藏、輪播、排程與動態桌布入口，讓選圖、套用、下載更直接。</p></div>
    <div class="app-feature-grid">
      <article class="feature-wide"><i class="fa-solid fa-images" aria-hidden="true"></i><h3>滑動選圖，點一下預覽</h3><p>自動載入本站手機直式作品，不用輸入關鍵字；左右滑動瀏覽，點選作品即可查看大圖。</p></article>
      <article class="feature-tall"><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i><h3>立即套用</h3><p>按一次就下載、置中裁切並直接設為手機主畫面桌布。</p><ul><li>一個步驟</li><li>清楚進度</li><li>失敗可重試</li></ul></article>
      <article><i class="fa-solid fa-download" aria-hidden="true"></i><h3>下載圖片</h3><p>Android 10 以上直接存入「圖片／帥龍與萌姬桌布館」，完成後會顯示儲存位置。</p></article>
      <article><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><h3>沒有多餘入口</h3><p>不含搜尋、收藏、輪播、排程、動態桌布或開站按鈕，主畫面只有兩個操作按鈕。</p></article>
    </div>
  </section>
  <section class="app-trust"><div class="section app-trust-grid"><div><p class="section-label">PRIVACY &amp; PERMISSIONS</p><h2>權限用在哪，<br>全部說清楚。</h2><p>App 不含廣告 SDK、不建立帳號，也不讀取聯絡人或使用者既有相簿內容。</p></div><div class="permission-list"><article><i class="fa-solid fa-wifi" aria-hidden="true"></i><span><strong>網路</strong><small>讀取本站目錄與下載所選桌布</small></span></article><article><i class="fa-solid fa-display" aria-hidden="true"></i><span><strong>設定桌布</strong><small>只有按下「立即套用」時使用</small></span></article><article><i class="fa-solid fa-folder-open" aria-hidden="true"></i><span><strong>舊版儲存空間</strong><small>只有 Android 8／9 首次下載圖片時詢問</small></span></article></div></div></section>
  <section class="app-install section" id="download">
    <div class="section-head"><div><p class="section-label">INSTALL APK</p><h2>三步驟開始使用</h2></div><p>本站直接提供 APK，Android 第一次安裝時可能要求允許瀏覽器安裝未知來源 App。</p></div>
    <div class="install-cards"><article><b>1</b><h3>下載 APK</h3><p>按下載並保留安裝檔。</p></article><article><b>2</b><h3>允許本次安裝</h3><p>依 Android 提示允許目前瀏覽器安裝。</p></article><article><b>3</b><h3>開啟並選桌布</h3><p>點選作品，再按立即套用或下載圖片。</p></article></div>
    <div class="app-download-card"><div><span>ANDROID PACKAGE</span><h3>帥龍萌姬桌布館 <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · Android 8.0 以上 · <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?></p></div><?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK</a><?php endif; ?></div>
    <?php if ($apkHash !== ''): ?><details class="apk-checksum"><summary>檢查 APK 的 SHA-256</summary><code><?= htmlspecialchars($apkHash, ENT_QUOTES, 'UTF-8') ?></code></details><?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/_incview/footer.php'; ?>
