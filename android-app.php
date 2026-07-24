<?php
declare(strict_types=1);
$pageKey = 'app';
$pageBodyClass = 'android-app-page';
$versionPath = __DIR__ . '/downloads/apk/VERSION';
$appVersion = is_file($versionPath) ? trim((string) file_get_contents($versionPath)) : 'v2.7.0';
$safeVersion = preg_match('/^v[\d.]+$/', $appVersion) ? $appVersion : 'v2.7.0';
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
        <p>無廣告、免帳號。v2.7 讓每張桌布都有按讚、倒讚、收藏與下載；投票數與網站統計同步，並保留單列色票、滿寬排序和主畫面／鎖定畫面的正確套用規則。</p>
        <div class="app-badges"><span><i class="fa-brands fa-android" aria-hidden="true"></i>Android 8.0+</span><span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>僅連線本站</span><span><i class="fa-solid fa-ban" aria-hidden="true"></i>無廣告</span></div>
        <div class="app-hero-actions">
          <?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><span class="button primary disabled" aria-disabled="true">APK 建置中</span><?php endif; ?>
          <a class="button outline" href="#features">查看新版功能</a>
        </div>
        <p class="app-build-note">檔案 <?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · 更新 <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?> · 測試簽章版</p>
      </div>
      <div class="app-phone-stage" aria-label="手機 App 介面示意">
        <div class="app-phone"><div class="app-phone-speaker"></div><div class="app-phone-screen"><img src="skin/img/assets/mobile/mobile-01.jpg" alt="翠嶺龍息手機桌布預覽"><div class="app-phone-shade"></div><div class="app-phone-ui"><small>PREVIEW &amp; APPLY</small><strong>翠嶺龍息</strong><span>AI 幻境 · 941 × 1672</span><p class="app-phone-targets"><i class="active">兩者皆套</i><i>主畫面</i><i>鎖定畫面</i></p><p class="app-phone-crop"><span>主畫面高度滿版</span><i aria-hidden="true"></i></p><div><b>立即套用</b><b>下載圖片</b></div></div></div></div>
      </div>
    </div>
  </section>
  <section class="app-metrics"><div class="section"><div><strong>89</strong><span>目前 API 作品</span></div><div><strong>5</strong><span>搜尋篩選面向</span></div><div><strong>3</strong><span>桌布套用位置</span></div><div><strong>0</strong><span>廣告與帳號</span></div></div></section>
  <section class="app-features section" id="features">
    <div class="section-head"><div><p class="section-label">SEARCH TO APPLY</p><h2>從完整搜尋，<br>一路做到套用。</h2></div><p>搜尋條件與網站桌布目錄同步，找到作品後直接預覽、下載，或選擇手機桌布套用位置。</p></div>
    <div class="app-feature-grid">
      <article class="feature-wide"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><h3>首屏搜尋與視覺篩選</h3><p>支援搜尋自動完成與推薦關鍵字；比例列固定為全部／橫式／直式，排序使用獨立滿寬下拉。色系改成彩色圓盤加九種單列色票，可直接辨識與選取。</p></article>
      <article class="feature-tall"><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i><h3>每張桌布都有完整操作</h3><p>點選任何作品後，預覽與操作從螢幕下方出現。</p><ul><li>按讚、倒讚、收藏與下載</li><li>兩者皆套／主畫面／鎖定畫面</li><li>主畫面高度滿版／直式填滿</li><li>鎖定畫面固定直式裁切</li></ul></article>
      <article><i class="fa-solid fa-crop-simple" aria-hidden="true"></i><h3>主畫面高度滿版</h3><p>完整橫圖依手機高度滿版，不再產生上下留白；若選直式填滿，才會裁切左右內容。</p></article>
      <article><i class="fa-solid fa-images" aria-hidden="true"></i><h3>系統實際／動態預覽</h3><p>靜態桌布在支援版本可直接讀取；動態桌布顯示服務縮圖，並能開啟 Android 系統真正繪製的目前主畫面動畫。</p></article>
    </div>
  </section>
  <section class="app-trust"><div class="section app-trust-grid"><div><p class="section-label">PRIVACY &amp; PERMISSIONS</p><h2>權限用在哪，<br>全部說清楚。</h2><p>App 不含廣告 SDK、不建立帳號，也不讀取聯絡人或使用者相簿。Android 13+ 遵守系統限制，不使用「所有檔案存取權」繞過桌布原圖保護。</p></div><div class="permission-list"><article><i class="fa-solid fa-wifi" aria-hidden="true"></i><span><strong>網路</strong><small>讀取本站目錄與下載所選桌布</small></span></article><article><i class="fa-solid fa-display" aria-hidden="true"></i><span><strong>設定桌布</strong><small>只有按下「立即套用」時使用</small></span></article><article><i class="fa-solid fa-photo-film" aria-hidden="true"></i><span><strong>目前靜態桌布</strong><small>只有 Android 12 以下按重新確認時詢問</small></span></article></div></div></section>
  <section class="app-install section" id="download">
    <div class="section-head"><div><p class="section-label">INSTALL APK</p><h2>三步驟開始使用</h2></div><p>本站直接提供 APK，Android 第一次安裝時可能要求允許瀏覽器安裝未知來源 App。</p></div>
    <div class="install-cards"><article><b>1</b><h3>下載 APK</h3><p>按下載並保留安裝檔。</p></article><article><b>2</b><h3>允許本次安裝</h3><p>依 Android 提示允許目前瀏覽器安裝。</p></article><article><b>3</b><h3>搜尋並套用</h3><p>搜尋作品、選擇套用位置，再按立即套用。</p></article></div>
    <div class="app-download-card"><div><span>ANDROID PACKAGE</span><h3>帥龍萌姬桌布館 <?= htmlspecialchars($safeVersion, ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($apkSize, ENT_QUOTES, 'UTF-8') ?> · Android 8.0 以上 · <?= htmlspecialchars($apkUpdatedAt, ENT_QUOTES, 'UTF-8') ?></p></div><?php if ($apkExists): ?><a class="button primary" href="<?= htmlspecialchars($apkRelativePath, ENT_QUOTES, 'UTF-8') ?>" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載 APK</a><?php endif; ?></div>
    <?php if ($apkHash !== ''): ?><details class="apk-checksum"><summary>檢查 APK 的 SHA-256</summary><code><?= htmlspecialchars($apkHash, ENT_QUOTES, 'UTF-8') ?></code></details><?php endif; ?>
  </section>
</main>
<?php require __DIR__ . '/_incview/footer.php'; ?>
