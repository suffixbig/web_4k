<?php $scriptVersion = static fn(string $file): string => (string) (@filemtime(__DIR__ . '/../skin/js/' . $file) ?: time()); ?>
<!-- 頁尾 STAR -->
<footer>
  <div class="footer-inner">
    <a class="brand" href="index.php"><img class="brand-mark" src="skin/img/assets/logo-dragon-tail.png" alt="龍尾巴標誌"><span>帥龍萌姬</span><small style="font-size:1.25rem">桌布館</small></a>
    <p>命令你的 ChatGPT「換桌布」，它就真的幫你換桌布。</p>
    <div><a href="search.php">分類搜尋</a><a href="collection.php"><?php if ($pageKey === 'home'): ?><i class="fa-solid fa-heart favorite-heart-icon" aria-hidden="true"></i><?php endif; ?>我的收藏</a><a href="ranking.php">排行榜</a><a href="sitemap">網站地圖</a><a href="advertising">刊登廣告</a></div>
  </div>
  <div class="visitor-footer">
    <div class="visitor-footer-inner">
      <div class="row align-items-center g-3">
        <div class="col-12 col-xl-7">
          <div class="visitor-stats" id="visitorStats" aria-label="公開瀏覽統計"><span class="visitor-stat-label"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>公開瀏覽統計</span><a class="visitor-stat visitor-stat-primary" href="visit_stats" aria-label="查看完整瀏覽統計">累計 <strong id="total-visits">0</strong></a><span class="visitor-stat">今日 <strong id="today-visits">0</strong></span><span class="visitor-stat">本月 <strong id="month-visits">0</strong></span><span class="visitor-stat">本頁今日 <strong id="page-today">0</strong></span><span class="visitor-stat">本頁累計 <strong id="page-total">0</strong></span></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2">
          <p class="visitor-footer-note"><a href="https://phpmytool.com/" target="_blank" rel="noopener noreferrer">黑豹開發作品</a></p>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
          <div class="footer-language notranslate" translate="no" aria-label="網站語言切換"><span class="footer-language-icon"><i class="fa-solid fa-language" aria-hidden="true"></i></span><span class="footer-language-label">Language</span><span class="footer-language-control"><select id="languageSelect" aria-label="選擇網站語言"><?php foreach ($SUPPORTED_LANGS as $languageCode => $languageName): ?><option value="<?= htmlspecialchars($languageCode, ENT_QUOTES, 'UTF-8') ?>" data-url="<?= htmlspecialchars(get_lang_url($languageCode), ENT_QUOTES, 'UTF-8') ?>"<?= $current_lang === $languageCode ? ' selected' : '' ?>><?= htmlspecialchars($languageName, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span><span id="google_translate_element" class="google-translate-element" aria-hidden="true"></span></div>
        </div>
      </div>
    </div>
  </div>
</footer>
<button class="back-to-top" id="backToTop" type="button" aria-label="回到頁面頂端"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i><span>頂端</span></button>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<!-- 頁尾 END -->
<script data-cfasync="false" src="skin/js/vendor/jquery-3.7.1.min.js"></script>
<script data-cfasync="false" src="skin/js/site.js?v=<?= $scriptVersion('site.js') ?>"></script>
<script data-cfasync="false" src="skin/js/visitor-counter.js?v=<?= $scriptVersion('visitor-counter.js') ?>"></script>
<script data-cfasync="false" src="skin/js/ads-tracking.js?v=<?= $scriptVersion('ads-tracking.js') ?>"></script>
<?php if ($pageKey === 'home'): ?><script data-cfasync="false" src="skin/js/vendor/slick-1.8.1.min.js"></script><?php endif; ?>
<?php if (!empty($pageScript)): ?><script data-cfasync="false" src="skin/js/<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>?v=<?= $scriptVersion($pageScript) ?>"></script><?php endif; ?>
<script data-cfasync="false" src="skin/js/pwa.js?v=<?= $scriptVersion('pwa.js') ?>"></script>
</body>
</html>
