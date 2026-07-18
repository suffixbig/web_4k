<?php
declare(strict_types=1);
$pageKey = 'app';
$pageBodyClass = 'android-app-page';
$versionPath = __DIR__ . '/downloads/apk/VERSION';
$appVersion = is_file($versionPath) ? trim((string) file_get_contents($versionPath)) : 'v2.4.0';
$safeVersion = preg_match('/^v[\d.]+$/', $appVersion) ? $appVersion : 'v2.4.0';
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
        <p>無廣告、免帳號。v2.4 把搜尋移到首屏，搭配快速籤與可收合的進階條件；橫圖跨整列、直圖雙欄顯示，點選後從底部面板完成預覽、下載與套用。</p>
        <div class="app-badges"><span><i class="fa-brands fa-android" aria-hidden="true"></i>Android 8.0+</span><span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>僅連線本站</span><span><i class="fa-solid fa-ban" aria-hidden="true"></i>無廣告</span></div>
        <div class="app-hero-actions">
          <?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><span class="button primary disabled" aria-disabled="true">APK 建置中</span><?php endif; ?>
          <a class="button outline" href="#features">查看新版功能</a>
        </div>
        <p class="app-build-note">檔案 <?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · 更新 <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?> · 測試簽章版</p>
      </div>
      <div class="app-phone-stage" aria-label="手機 App 介面示意">
        <div class="app-phone"><div class="app-phone-speaker"></div><div class="app-phone-screen"><img src="skin/img/assets/mobile/mobile-01.jpg" alt="翠嶺龍息手機桌布預覽"><div class="app-phone-shade"></div><div class="app-phone-ui"><small>PREVIEW &amp; APPLY</small><strong>翠嶺龍息</strong><span>AI 幻境 · 941 × 1672</span><p class="app-phone-targets"><i class="active">兩者皆套</i><i>主畫面</i><i>鎖定畫面</i></p><p class="app-phone-crop"><span>完整顯示（推薦）</span><i aria-hidden="true"></i></p><div><b>立即套用</b><b>下載圖片</b></div></div></div></div>
      </div>
    </div>
  </section>
  <section class="app-metrics"><div class="section"><div><strong>89</strong><span>目前 API 作品</span></div><div><strong>5</strong><span>搜尋篩選面向</span></div><div><strong>3</strong><span>桌布套用位置</span></div><div><strong>0</strong><span>廣告與帳號</span></div></div></section>
  <section class="app-features section" id="features">
    <div class="section-head"><div><p class="section-label">SEARCH TO APPLY</p><h2>從完整搜尋，<br>一路做到套用。</h2></div><p>搜尋條件與網站桌布目錄同步，找到作品後直接預覽、下載，或選擇手機桌布套用位置。</p></div>
    <div class="app-feature-grid">
      <article class="feature-wide"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><h3>首屏搜尋與漸進篩選</h3><p>支援搜尋自動完成、推薦關鍵字與直式／橫式／AI 快速籤；分類、色系與排序需要時再展開。結果採橫圖整列、直圖雙欄混合網格。</p></article>
      <article class="feature-tall"><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i><h3>底部套用面板</h3><p>點選作品後，預覽與操作從螢幕下方出現。</p><ul><li>兩者皆套／主畫面／鎖定畫面</li><li>完整顯示／填滿螢幕</li><li>下載與套用進度</li></ul></article>
      <article><i class="fa-solid fa-crop-simple" aria-hidden="true"></i><h3>完整顯示為預設</h3><p>橫圖可完整置中、不切左右內容；若想鋪滿手機，也能改選填滿螢幕並在套用前看懂差異。</p></article>
      <article><i class="fa-solid fa-images" aria-hidden="true"></i><h3>目前設定雙預覽</h3><p>主畫面與鎖定畫面會標示使用中狀態、最後確認時間；可重新確認，也能點縮圖全螢幕查看。</p></article>
    </div>
  </section>
  <section class="app-trust"><div class="section app-trust-grid"><div><p class="section-label">PRIVACY &amp; PERMISSIONS</p><h2>權限用在哪，<br>全部說清楚。</h2><p>App 不含廣告 SDK、不建立帳號，也不讀取聯絡人或使用者既有相簿內容。底部縮圖只使用 App 自己最近套用且經系統識別碼確認的版本。</p></div><div class="permission-list"><article><i class="fa-solid fa-wifi" aria-hidden="true"></i><span><strong>網路</strong><small>讀取本站目錄與下載所選桌布</small></span></article><article><i class="fa-solid fa-display" aria-hidden="true"></i><span><strong>設定桌布</strong><small>只有按下「立即套用」時使用</small></span></article><article><i class="fa-solid fa-folder-open" aria-hidden="true"></i><span><strong>舊版儲存空間</strong><small>只有 Android 8／9 首次下載圖片時詢問</small></span></article></div></div></section>
  <section class="app-install section" id="download">
    <div class="section-head"><div><p class="section-label">INSTALL APK</p><h2>三步驟開始使用</h2></div><p>本站直接提供 APK，Android 第一次安裝時可能要求允許瀏覽器安裝未知來源 App。</p></div>
    <div class="install-cards"><article><b>1</b><h3>下載 APK</h3><p>按下載並保留安裝檔。</p></article><article><b>2</b><h3>允許本次安裝</h3><p>依 Android 提示允許目前瀏覽器安裝。</p></article><article><b>3</b><h3>搜尋並套用</h3><p>搜尋作品、選擇套用位置，再按立即套用。</p></article></div>
    <div class="app-download-card"><div><span>ANDROID PACKAGE</span><h3>帥龍萌姬桌布館 <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · Android 8.0 以上 · <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?></p></div><?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK</a><?php endif; ?></div>
    <?php if ($apkHash !== ''): ?><details class="apk-checksum"><summary>檢查 APK 的 SHA-256</summary><code><?= htmlspecialchars($apkHash, ENT_QUOTES, 'UTF-8') ?></code></details><?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/_incview/footer.php'; ?>
