<?php
declare(strict_types=1);

$pageKey = 'home';
$pageScript = 'home.js';
$pageBodyClass = 'home-page';

function homeAssetUrl(string $file): string {
    $normalized = ltrim(str_replace('\\', '/', $file), '/');
    return str_starts_with($normalized, 'assets/') ? 'skin/img/' . $normalized : $normalized;
}

function homeWallpaperCard(array $wallpaper, string $list, int $index): string {
    $title = (string) ($wallpaper['title'] ?? '未命名桌布');
    $creator = (string) ($wallpaper['creator'] ?? '未署名');
    $file = (string) ($wallpaper['home_file'] ?? '');
    $width = max(1, (int) ($wallpaper['width'] ?? 1920));
    $height = max(1, (int) ($wallpaper['height'] ?? 1080));
    $likes = (int) ($wallpaper['likes'] ?? 0);
    $downloads = (int) ($wallpaper['downloads'] ?? 0);
    $quality = $width >= 3840 && $height >= 2160 ? '4K' : ($width >= 1920 && $height >= 1080 ? 'FHD' : 'HD');
    $score = $likes - (int) ($wallpaper['dislikes'] ?? 0);
    $createdAt = (string) ($wallpaper['created_at'] ?? '');
    $dateTimestamp = strtotime($createdAt);
    $dateLabel = $dateTimestamp === false ? '最近上架' : date('Y.m.d', $dateTimestamp);
    $badge = $list === 'latest' ? 'NEW · ' . $dateLabel : 'TOP ' . ($index + 1);
    $loading = $list === 'latest' && $index < 4 ? 'eager' : 'lazy';
    $href = 'search.php?q=' . rawurlencode($title);

    ob_start();
    ?>
    <a class="home-wallpaper-card" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
       data-wallpaper-id="<?= htmlspecialchars((string) ($wallpaper['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
       data-created="<?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>"
       data-score="<?= $score ?>"
       aria-label="查看<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>桌布">
      <span class="home-wallpaper-image">
        <img src="<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>桌布"
             width="<?= $width ?>" height="<?= $height ?>" loading="<?= $loading ?>" decoding="async">
        <span class="home-wallpaper-badge"><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="home-wallpaper-device"><?= $quality ?> · <?= number_format($width) ?>×<?= number_format($height) ?></span>
      </span>
      <span class="home-wallpaper-copy">
        <span><strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($creator, ENT_QUOTES, 'UTF-8') ?></small></span>
        <?php if ($likes > 0 || $downloads > 0): ?>
          <span class="home-wallpaper-stats" aria-label="<?= number_format($likes) ?> 個讚，<?= number_format($downloads) ?> 次下載"><i class="fa-solid fa-thumbs-up" aria-hidden="true"></i><?= number_format($likes) ?><i class="fa-solid fa-download" aria-hidden="true"></i><?= number_format($downloads) ?></span>
        <?php else: ?>
          <span class="home-wallpaper-new" aria-label="新上架桌布">剛上架</span>
        <?php endif; ?>
      </span>
    </a>
    <?php
    return (string) ob_get_clean();
}

$catalog = json_decode((string) @file_get_contents(__DIR__ . '/json/wallpapers.json'), true);
$statsPath = is_file(__DIR__ . '/json/wallpaper-stats.json')
    ? __DIR__ . '/json/wallpaper-stats.json'
    : __DIR__ . '/json/wallpaper-stats.seed.json';
$stats = json_decode((string) @file_get_contents($statsPath), true);
$statsById = is_array($stats['wallpapers'] ?? null) ? $stats['wallpapers'] : [];
$homeWallpapers = [];

foreach (is_array($catalog['wallpapers'] ?? null) ? $catalog['wallpapers'] : [] as $wallpaper) {
    $id = (string) ($wallpaper['id'] ?? '');
    if ($id === '') continue;
    $itemStats = is_array($statsById[$id] ?? null) ? $statsById[$id] : [];
    if (!empty($itemStats['auto_hidden'])) continue;
    $wallpaper['home_file'] = homeAssetUrl((string) ($wallpaper['file'] ?? ''));
    foreach (['likes', 'dislikes', 'downloads', 'views'] as $field) {
        $wallpaper[$field] = (int) ($itemStats[$field] ?? 0);
    }
    $homeWallpapers[] = $wallpaper;
}

$latestWallpapers = $homeWallpapers;
usort($latestWallpapers, static function (array $left, array $right): int {
    $dateResult = strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''));
    return $dateResult !== 0 ? $dateResult : ((int) ($right['id'] ?? 0) <=> (int) ($left['id'] ?? 0));
});
$latestWallpapers = array_slice($latestWallpapers, 0, 8);

$popularWallpapers = $homeWallpapers;
usort($popularWallpapers, static function (array $left, array $right): int {
    $leftScore = (int) ($left['likes'] ?? 0) - (int) ($left['dislikes'] ?? 0);
    $rightScore = (int) ($right['likes'] ?? 0) - (int) ($right['dislikes'] ?? 0);
    return ($rightScore <=> $leftScore)
        ?: ((int) ($right['downloads'] ?? 0) <=> (int) ($left['downloads'] ?? 0))
        ?: ((int) ($right['views'] ?? 0) <=> (int) ($left['views'] ?? 0));
});
$popularWallpapers = array_slice($popularWallpapers, 0, 8);

$wallpapersById = [];
foreach ($homeWallpapers as $wallpaper) {
    $wallpapersById[(string) ($wallpaper['id'] ?? '')] = $wallpaper;
}

// 首頁輪播固定使用目錄 ID 1–5；最新與熱門排序更新時不會跟著更換。
$featureBlueprints = [
    ['wallpaper_id' => '1', 'label' => 'FREE HD WALLPAPERS', 'nav' => '免費高畫質', 'title' => "免費高畫質桌布下載，\n手機與電腦都能用。", 'body' => '帥龍、萌姬、遊戲、奇幻與暗色風格集中於同一個可搜尋、可收藏、可排行的桌布空間；每張作品清楚標示實際解析度，找到喜歡的就能立即下載。', 'primary_href' => '#latestTitle', 'primary_label' => '看最新上架桌布', 'secondary_href' => 'search.php', 'secondary_label' => '開始搜尋桌布', 'icon' => 'fa-layer-group'],
    ['wallpaper_id' => '2', 'label' => 'SMART SEARCH', 'nav' => '智慧搜尋', 'title' => "描述你想要的畫面，\n搜尋會理解條件。", 'body' => '輸入作者、色系、手機或電腦、橫式或直式、真人或 AI 等線索，就能快速縮小範圍，直接找到合適桌布。', 'primary_href' => 'search.php', 'primary_label' => '使用完整搜尋', 'secondary_href' => 'search.php?q=暗色護眼', 'secondary_label' => '試找暗色護眼', 'icon' => 'fa-magnifying-glass'],
    ['wallpaper_id' => '3', 'label' => 'LIVE DISCOVERY', 'nav' => '最新熱門', 'title' => "每天看最新，\n也看大家真正喜歡什麼。", 'body' => '首頁固定呈現最新上架 8 張與熱門 8 張；熱門依按讚淨值、下載與瀏覽資料排序，讓好作品更容易被看見。', 'primary_href' => '#popularTitle', 'primary_label' => '查看本週熱門', 'secondary_href' => 'ranking.php', 'secondary_label' => '前往完整排行榜', 'icon' => 'fa-chart-line'],
    ['wallpaper_id' => '4', 'label' => 'AI SKILL', 'nav' => 'AI 技能', 'title' => "一句「換桌布」，\nCodex 與 Claude 就能代勞。", 'body' => 'Codex 與 Claude 各有專用 SKILL.MD，可依你的條件搜尋網站桌布，也能在 Windows 上接手下一張與條件換圖。', 'primary_href' => 'ai-skill.php', 'primary_label' => '查看 AI 安裝技能', 'secondary_href' => 'ai-skill.php#skillPreview', 'secondary_label' => '預覽技能內容', 'icon' => 'fa-wand-magic-sparkles'],
    ['wallpaper_id' => '5', 'label' => 'ANDROID APP', 'nav' => 'Android App', 'title' => "在手機搜尋、下載，\n再套用到主畫面或鎖定畫面。", 'body' => 'Android App 串接網站搜尋 API，支援最新 APK 下載，並可選擇兩者皆套、只套主畫面或只套鎖定畫面。', 'primary_href' => 'android-app.php', 'primary_label' => '下載最新版 APK', 'secondary_href' => 'collection.php', 'secondary_label' => '管理我的收藏', 'icon' => 'fa-mobile-screen-button'],
];

$homeFeatureSlides = [];
foreach ($featureBlueprints as $blueprint) {
    $wallpaperId = (string) $blueprint['wallpaper_id'];
    if (!isset($wallpapersById[$wallpaperId])) continue;
    $blueprint['wallpaper'] = $wallpapersById[$wallpaperId];
    $homeFeatureSlides[] = $blueprint;
}

require __DIR__ . '/_incview/header.php';
?>
<main id="main-content" tabindex="-1">
  <section class="home-feature-carousel" data-home-carousel aria-roledescription="carousel" aria-labelledby="homeHeroLabel">
    <p id="homeHeroLabel" class="sr-only">帥龍萌姬桌布館精選桌布與主要功能</p>
    <div class="home-feature-track">
      <?php foreach ($homeFeatureSlides as $index => $feature):
          $wallpaper = $feature['wallpaper'];
          $wallpaperTitle = (string) ($wallpaper['title'] ?? '未命名桌布');
          $creator = (string) ($wallpaper['creator'] ?? '未署名');
          $isActive = $index === 0;
      ?>
        <article class="home-feature-slide<?= $isActive ? ' is-active' : '' ?>" id="homeFeatureSlide<?= $index + 1 ?>"
                 data-home-feature-slide data-wallpaper-id="<?= htmlspecialchars((string) $feature['wallpaper_id'], ENT_QUOTES, 'UTF-8') ?>"
                 aria-roledescription="slide" aria-label="第 <?= $index + 1 ?> 張，共 <?= count($homeFeatureSlides) ?> 張：<?= htmlspecialchars((string) $feature['nav'], ENT_QUOTES, 'UTF-8') ?>"
                 aria-hidden="<?= $isActive ? 'false' : 'true' ?>"<?= $isActive ? '' : ' inert' ?>>
          <img class="home-feature-image" src="<?= htmlspecialchars((string) $wallpaper['home_file'], ENT_QUOTES, 'UTF-8') ?>"
               alt="<?= htmlspecialchars($wallpaperTitle, ENT_QUOTES, 'UTF-8') ?>桌布"
               width="<?= max(1, (int) ($wallpaper['width'] ?? 1920)) ?>" height="<?= max(1, (int) ($wallpaper['height'] ?? 1080)) ?>"
               <?= $isActive ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
          <span class="home-feature-scrim" aria-hidden="true"></span>
          <div class="section home-feature-inner">
            <div class="home-feature-copy">
              <p class="home-feature-kicker"><span>0<?= $index + 1 ?> / 0<?= count($homeFeatureSlides) ?></span><?= htmlspecialchars((string) $feature['label'], ENT_QUOTES, 'UTF-8') ?></p>
              <?php if ($index === 0): ?>
                <h1><?= nl2br(htmlspecialchars((string) $feature['title'], ENT_QUOTES, 'UTF-8')) ?></h1>
              <?php else: ?>
                <h2><?= nl2br(htmlspecialchars((string) $feature['title'], ENT_QUOTES, 'UTF-8')) ?></h2>
              <?php endif; ?>
              <p><?= htmlspecialchars((string) $feature['body'], ENT_QUOTES, 'UTF-8') ?></p>
              <div class="home-feature-actions">
                <a class="button primary" href="<?= htmlspecialchars((string) $feature['primary_href'], ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid <?= htmlspecialchars((string) $feature['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars((string) $feature['primary_label'], ENT_QUOTES, 'UTF-8') ?></a>
                <a class="home-feature-secondary" href="<?= htmlspecialchars((string) $feature['secondary_href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $feature['secondary_label'], ENT_QUOTES, 'UTF-8') ?><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
              </div>
              <a class="home-feature-credit" href="search.php?q=<?= rawurlencode($wallpaperTitle) ?>"><i class="fa-regular fa-image" aria-hidden="true"></i>本張桌布：<?= htmlspecialchars($wallpaperTitle, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($creator, ENT_QUOTES, 'UTF-8') ?></a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="section home-feature-controls">
      <div class="home-feature-dots" aria-label="選擇專案特色">
        <?php foreach ($homeFeatureSlides as $index => $feature): ?>
          <button class="<?= $index === 0 ? 'is-active' : '' ?>" type="button" data-home-go-slide="<?= $index ?>"
                  aria-controls="homeFeatureSlide<?= $index + 1 ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                  aria-label="顯示第 <?= $index + 1 ?> 張：<?= htmlspecialchars((string) $feature['nav'], ENT_QUOTES, 'UTF-8') ?>">
            <span>0<?= $index + 1 ?></span><small><?= htmlspecialchars((string) $feature['nav'], ENT_QUOTES, 'UTF-8') ?></small>
          </button>
        <?php endforeach; ?>
      </div>
      <div class="home-feature-buttons">
        <button id="homePrevSlide" type="button" aria-label="顯示上一張專案特色"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
        <button id="homeCarouselToggle" class="home-carousel-toggle" type="button" aria-pressed="false"><i class="fa-solid fa-pause" aria-hidden="true"></i><span>暫停</span></button>
        <button id="homeNextSlide" type="button" aria-label="顯示下一張專案特色"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
      </div>
    </div>
    <p id="homeSlideStatus" class="sr-only" aria-live="polite" aria-atomic="true"></p>
  </section>

  <section class="home-search-strip" aria-labelledby="homeSearchTitle">
    <div class="section home-search-strip-layout">
      <div class="home-search-intro"><span><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span><div><p class="section-label">SEARCH THE COLLECTION</p><h2 id="homeSearchTitle">一句話，搜尋整座桌布館。</h2><p>作者、色系、裝置、橫直式與創作類型都能直接描述。</p></div></div>
      <div>
        <form class="home-search-panel" action="search.php" method="get" role="search">
          <label for="homeSearch">輸入想找的桌布條件</label>
          <div><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="homeSearch" name="q" type="search" placeholder="例如：藍色手機直式 AI 龍" autocomplete="off" enterkeyhint="search"><button type="submit">搜尋桌布<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button></div>
          <p>搜尋會帶你到完整結果頁，並保留輸入的條件。</p>
        </form>
        <nav class="home-quick-search" aria-label="熱門搜尋建議"><span>快速找：</span><a href="search.php?q=暗色護眼">暗色護眼</a><a href="search.php?q=手機直式">手機直式</a><a href="search.php?q=帥龍奇幻">帥龍奇幻</a><a href="search.php?q=幸福仙境">幸福仙境</a></nav>
      </div>
    </div>
  </section>

  <section class="home-gallery-section section" aria-labelledby="latestTitle" data-home-list="latest">
    <div class="home-gallery-head"><div><p class="section-label">LATEST WALLPAPERS</p><h2 id="latestTitle">最新上架的 8 張桌布</h2><p>依發布日期由新到舊排列，每次回到首頁都能先看到剛加入的作品。</p></div><a class="button outline" href="search.php">查看全部桌布<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
    <div class="home-wallpaper-grid">
      <?php if ($latestWallpapers === []): ?><div class="home-gallery-empty"><i class="fa-solid fa-image" aria-hidden="true"></i><h3>暫時沒有最新桌布</h3><p>請稍後重新整理，或先前往完整桌布目錄。</p></div><?php endif; ?>
      <?php foreach ($latestWallpapers as $index => $wallpaper) echo homeWallpaperCard($wallpaper, 'latest', $index); ?>
    </div>
  </section>

  <section class="home-popular-band" aria-labelledby="popularTitle" data-home-list="popular">
    <div class="section home-gallery-section">
      <div class="home-gallery-head"><div><p class="section-label">TRENDING NOW</p><h2 id="popularTitle">現在最熱門的 8 張桌布</h2><p>依按讚淨值排序，下載與瀏覽數作為同分依據，呈現大家真正喜歡的作品。</p></div><a class="button outline" href="ranking.php">前往完整排行榜<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
      <div class="home-wallpaper-grid">
        <?php if ($popularWallpapers === []): ?><div class="home-gallery-empty"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><h3>熱門統計準備中</h3><p>桌布仍可從完整目錄瀏覽與下載。</p></div><?php endif; ?>
        <?php foreach ($popularWallpapers as $index => $wallpaper) echo homeWallpaperCard($wallpaper, 'popular', $index); ?>
      </div>
    </div>
  </section>

  <section class="ai-section section" id="ai">
    <div class="ai-card">
      <div class="ai-copy">
        <p class="section-label">AI WALLPAPER SKILL</p>
        <h2>先在首頁找到喜歡的，<br><span>之後交給 AI 幫你換。</span></h2>
        <p>Codex 與 Claude 技能支援自然語言搜尋與本機 Windows 換桌布；Android App 則可搜尋、下載並套用主畫面或鎖定畫面。</p>
        <div class="prompt-box"><div><i data-lucide="terminal"></i><code id="skillPrompt">幫我安裝帥龍萌姬桌布館技能，以後我說「換桌布」或「搜尋桌布」時就依技能規則執行。</code></div><button id="copyPrompt" type="button" aria-label="複製 AI 安裝指令"><i data-lucide="copy"></i><span>複製指令</span></button></div>
        <div class="ai-actions"><a class="button primary" href="ai-skill.php"><i data-lucide="sparkles"></i>前往技能啟用頁</a><a href="android-app.php">下載 Android App <i data-lucide="arrow-up-right"></i></a></div>
      </div>
      <div class="automation-card">
        <div class="automation-top"><div class="ai-orb"><i data-lucide="bot"></i></div><div><small>WALLPAPER AI SKILL</small><strong>一句話搜尋與換圖</strong></div><span class="status">可安裝</span></div>
        <div class="schedule-row"><span><i data-lucide="search"></i> 範例指令</span><strong>搜尋桌布 藍色手機直式</strong></div>
        <div class="preference"><span>AI 可辨識這些條件</span><div><b>作者</b><b>色系</b><b>裝置</b><b>橫直式</b></div></div>
        <?php if ($popularWallpapers !== []): $featured = $popularWallpapers[0]; ?><div class="next-wallpaper"><img src="<?= htmlspecialchars((string) $featured['home_file'], ENT_QUOTES, 'UTF-8') ?>" alt="熱門桌布<?= htmlspecialchars((string) $featured['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy"><div><small>MOST POPULAR</small><strong><?= htmlspecialchars((string) $featured['title'], ENT_QUOTES, 'UTF-8') ?></strong><span><?= number_format((int) $featured['downloads']) ?> 次下載</span></div><i data-lucide="chevron-right"></i></div><?php endif; ?>
      </div>
    </div>
  </section>

  <section class="home-links section" aria-label="更多桌布功能"><a href="search.php"><i data-lucide="search"></i><div><small>完整搜尋</small><strong>用所有條件找到桌布</strong></div><i data-lucide="arrow-right"></i></a><a href="collection.php"><i data-lucide="heart"></i><div><small>我的收藏</small><strong>編輯 AI 輪播清單</strong></div><i data-lucide="arrow-right"></i></a><a href="ranking.php"><i data-lucide="trophy"></i><div><small>下載排行榜</small><strong>看看大家正在用什麼</strong></div><i data-lucide="arrow-right"></i></a></section>
</main>
<?php require __DIR__ . '/_incview/footer.php'; ?>
