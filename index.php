<?php
declare(strict_types=1);

$pageKey = 'home';
$pageScript = 'home.js';
$pageBodyClass = 'home-page';
require_once __DIR__ . '/_inc/ads.php';

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
$latestWallpapers = array_slice($latestWallpapers, 0, 4);

$popularWallpapers = $homeWallpapers;
usort($popularWallpapers, static function (array $left, array $right): int {
    $leftScore = (int) ($left['likes'] ?? 0) - (int) ($left['dislikes'] ?? 0);
    $rightScore = (int) ($right['likes'] ?? 0) - (int) ($right['dislikes'] ?? 0);
    return ($rightScore <=> $leftScore)
        ?: ((int) ($right['downloads'] ?? 0) <=> (int) ($left['downloads'] ?? 0))
        ?: ((int) ($right['views'] ?? 0) <=> (int) ($left['views'] ?? 0));
});
$popularWallpapers = array_slice($popularWallpapers, 0, 10);

$wallpapersById = [];
foreach ($homeWallpapers as $wallpaper) {
    $wallpapersById[(string) ($wallpaper['id'] ?? '')] = $wallpaper;
}

// 首頁輪播固定使用目錄 ID 1–5；最新與熱門排序更新時不會跟著更換。
$featureBlueprints = [
    ['wallpaper_id' => '4', 'label' => 'AI 換桌布技能', 'nav' => 'AI 技能', 'title' => "技能安裝後，命令 ChatGPT「換桌布」，\n它就真的幫你換。", 'primary_href' => 'ai-skill.php', 'primary_label' => '安裝換桌布技能', 'icon' => 'fa-wand-magic-sparkles'],
    ['wallpaper_id' => '1', 'label' => '免費桌布', 'nav' => '免費高畫質', 'title' => "免費 4K 桌布，\n立即下載。", 'primary_href' => '#latestTitle', 'primary_label' => '看最新桌布', 'icon' => 'fa-layer-group'],
    ['wallpaper_id' => '2', 'label' => '智慧搜尋', 'nav' => '智慧搜尋', 'title' => "說出條件，\n快速找到桌布。", 'primary_href' => 'search.php', 'primary_label' => '開始搜尋', 'icon' => 'fa-magnifying-glass'],
    ['wallpaper_id' => '3', 'label' => '熱門排行', 'nav' => '最新熱門', 'title' => "看看大家\n最愛的桌布。", 'primary_href' => 'ranking.php', 'primary_label' => '查看排行榜', 'icon' => 'fa-chart-line'],
    ['wallpaper_id' => '5', 'label' => 'Android App', 'nav' => 'Android App', 'title' => "下載 App，\n直接套用桌布。", 'primary_href' => 'android-app.php', 'primary_label' => '下載 Android App', 'icon' => 'fa-mobile-screen-button'],
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
    <div class="home-top-search" aria-labelledby="homeSearchTitle">
      <div class="section home-top-search-layout">
        <p class="home-top-search-title" id="homeSearchTitle">想找什麼桌布？</p>
        <form class="home-search-panel" action="search.php" method="get" role="search">
          <label class="sr-only" for="homeSearch">輸入想找的桌布條件</label>
          <div><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="homeSearch" name="q" type="search" placeholder="例如：藍色、手機直式、AI 龍" autocomplete="off" enterkeyhint="search"><button type="submit">搜尋<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button></div>
        </form>
      </div>
    </div>
    <div class="home-feature-track">
      <?php foreach ($homeFeatureSlides as $index => $feature):
          $wallpaper = $feature['wallpaper'];
          $wallpaperTitle = (string) ($wallpaper['title'] ?? '未命名桌布');
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
              <div class="home-feature-actions">
                <a class="button primary" href="<?= htmlspecialchars((string) $feature['primary_href'], ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid <?= htmlspecialchars((string) $feature['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars((string) $feature['primary_label'], ENT_QUOTES, 'UTF-8') ?></a>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="section home-feature-controls" aria-label="輪播控制">
      <button id="homePrevSlide" class="home-feature-arrow" type="button" aria-label="顯示上一張專案特色"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
      <div class="home-feature-dots" aria-label="選擇專案特色">
        <?php foreach ($homeFeatureSlides as $index => $feature): ?>
          <button class="<?= $index === 0 ? 'is-active' : '' ?>" type="button" data-home-go-slide="<?= $index ?>"
                  aria-controls="homeFeatureSlide<?= $index + 1 ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                  aria-label="顯示第 <?= $index + 1 ?> 張：<?= htmlspecialchars((string) $feature['nav'], ENT_QUOTES, 'UTF-8') ?>">
            <span class="sr-only">第 <?= $index + 1 ?> 張：<?= htmlspecialchars((string) $feature['nav'], ENT_QUOTES, 'UTF-8') ?></span>
          </button>
        <?php endforeach; ?>
      </div>
      <button id="homeNextSlide" class="home-feature-arrow" type="button" aria-label="顯示下一張專案特色"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
    </div>
    <p id="homeSlideStatus" class="sr-only" aria-live="polite" aria-atomic="true"></p>
  </section>

  <?= renderAdPlacement('home_banner', 'section ad-slot--home') ?>

  <section class="home-gallery-section section" aria-labelledby="latestTitle" data-home-list="latest">
    <div class="home-gallery-head"><div><h2 id="latestTitle">最新上架</h2></div><a class="button outline" href="search.php">查看全部桌布<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
    <div class="home-wallpaper-grid">
      <?php if ($latestWallpapers === []): ?><div class="home-gallery-empty"><i class="fa-solid fa-image" aria-hidden="true"></i><h3>暫時沒有最新桌布</h3><p>請稍後重新整理，或先前往完整桌布目錄。</p></div><?php endif; ?>
      <?php foreach ($latestWallpapers as $index => $wallpaper) echo homeWallpaperCard($wallpaper, 'latest', $index); ?>
    </div>
  </section>

  <section class="home-popular-band" aria-labelledby="popularTitle" data-home-list="popular">
    <div class="section home-gallery-section">
      <div class="home-gallery-head"><div><h2 id="popularTitle">熱門桌布</h2></div><a class="button outline" href="ranking.php">前往完整排行榜<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
      <div class="home-wallpaper-grid">
        <?php if ($popularWallpapers === []): ?><div class="home-gallery-empty"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><h3>熱門統計準備中</h3><p>桌布仍可從完整目錄瀏覽與下載。</p></div><?php endif; ?>
        <?php foreach ($popularWallpapers as $index => $wallpaper) echo homeWallpaperCard($wallpaper, 'popular', $index); ?>
      </div>
    </div>
  </section>

  <section class="ai-section section" id="ai" aria-labelledby="homeAiTitle">
    <h2 class="sr-only" id="homeAiTitle">電腦換桌布技能與手機桌布 APK</h2>
    <div class="ai-skill-posters">
      <a class="ai-skill-poster" href="ai-skill.php" aria-label="前往安裝 ChatGPT 電腦換桌布技能">
        <img src="skin/img/assets/chatgpt-wallpaper-pc-skill-poster-v1.jpg"
             alt="電腦螢幕展示三張桌布，說明安裝 ChatGPT 換桌布技能後，只要說一聲換桌布就會自動更換"
             width="1672" height="941" loading="lazy" decoding="async">
      </a>
      <a class="ai-skill-poster" href="android-app.php" aria-label="前往下載手機桌布 APK">
        <img src="skin/img/assets/wallpaper-apk-mobile-poster-v1.jpg"
             alt="黑豹吉祥物展示手機鎖定畫面與完整 App 主桌面，說明安裝 APK 後可以固定桌布或每天隨機換桌布"
             width="1672" height="941" loading="lazy" decoding="async">
      </a>
    </div>
  </section>

  <nav class="home-links section" aria-label="更多桌布功能">
    <a href="search.php"><i data-lucide="search" aria-hidden="true"></i><div><small>完整搜尋</small><strong>用所有條件找到桌布</strong></div><i data-lucide="arrow-right" aria-hidden="true"></i></a>
    <a href="collection.php"><i data-lucide="heart" aria-hidden="true"></i><div><small>我的收藏</small><strong>保存喜歡的作品</strong></div><i data-lucide="arrow-right" aria-hidden="true"></i></a>
    <a href="ranking.php"><i data-lucide="trophy" aria-hidden="true"></i><div><small>下載排行榜</small><strong>查看前 100 名桌布</strong></div><i data-lucide="arrow-right" aria-hidden="true"></i></a>
  </nav>

</main>
<?php require __DIR__ . '/_incview/footer.php'; ?>
