<?php
declare(strict_types=1);
require_once __DIR__ . '/_inc/ads.php';

$adminPagePath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH) ?: '/admin');
if (!defined('ADS_ADMIN_CANONICAL') && basename($adminPagePath) === 'admin-ads.php') {
    header('Location: admin', true, 302);
    exit;
}

$loginError = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'logout') {
        logoutAdsAdmin();
        header('Location: ' . $adminPagePath, true, 303);
        exit;
    }
    if ($action === 'login' && !loginAdsAdmin(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
        $loginError = '帳號或密碼不正確，請重新輸入。';
    } elseif ($action === 'login') {
        header('Location: ' . $adminPagePath, true, 303);
        exit;
    }
}

$adminCssVersion = (string) (@filemtime(__DIR__ . '/skin/css/ads-admin.css') ?: time());
$adminJsVersion = (string) (@filemtime(__DIR__ . '/skin/js/ads-admin.js') ?: time());

if (!adsAdminSessionActive()):
?><!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#080b10">
  <title>登入｜4K 廣告營運後台</title>
  <link rel="stylesheet" href="skin/css/vendor/all.min.css">
  <link rel="stylesheet" href="skin/css/ads-admin.css?v=<?= htmlspecialchars($adminCssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="ads-login-page">
  <main class="ads-login-shell">
    <section class="ads-login-card" aria-labelledby="adsLoginTitle">
      <div class="ads-login-brand"><span><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span><b>4K ADS</b></div>
      <p class="admin-eyebrow">SECURE OPERATIONS CONSOLE</p>
      <h1 id="adsLoginTitle">廣告營運後台</h1>
      <p>登入後管理素材、排程、每週投放日、曝光額度與成效紀錄。</p>
      <?php if ($loginError !== ''): ?><p class="ads-login-error" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      <form method="post" class="ads-login-form">
        <input type="hidden" name="action" value="login">
        <label for="adsUsername">管理員帳號</label>
        <input id="adsUsername" name="username" type="text" autocomplete="username" required autofocus>
        <label for="adsPassword">登入密碼</label>
        <input id="adsPassword" name="password" type="password" autocomplete="current-password" required>
        <button class="admin-button admin-button--primary" type="submit"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>登入後台</button>
      </form>
      <small><i class="fa-solid fa-lock" aria-hidden="true"></i> 此頁不會載入前台導覽、輪播或訪客統計。</small>
    </section>
  </main>
</body>
</html>
<?php exit; endif; ?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#080b10">
  <title>4K 廣告營運後台</title>
  <link rel="stylesheet" href="skin/css/vendor/all.min.css">
  <link rel="stylesheet" href="skin/css/ads-admin.css?v=<?= htmlspecialchars($adminCssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="ads-admin-page">
  <a class="admin-skip-link" href="#main-content">跳至主要內容</a>
  <div class="admin-app">
    <aside class="admin-sidebar" aria-label="後台導覽">
      <a class="admin-brand" href="#overview" aria-label="4K 廣告營運後台">
        <span><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span>
        <b>4K ADS<small>OPERATIONS</small></b>
      </a>
      <nav class="admin-nav">
        <a class="is-active" href="#overview"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>投放總覽</span></a>
        <a href="#placements"><i class="fa-solid fa-rectangle-ad" aria-hidden="true"></i><span>廣告版位</span></a>
        <a href="#scheduleGuide"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i><span>排程規則</span></a>
        <a href="advertising" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span>前台版位說明</span></a>
      </nav>
      <div class="admin-sidebar-foot">
        <span class="admin-user-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
        <span><small>目前帳號</small><b>admin</b></span>
      </div>
    </aside>

    <div class="admin-workspace">
      <header class="admin-topbar">
        <div>
          <p class="admin-eyebrow">AD OPERATIONS / CONTROL CENTER</p>
          <h1>廣告投放管理</h1>
        </div>
        <div class="admin-topbar-actions">
          <a class="admin-button admin-button--secondary" href="index.php" target="_blank" rel="noopener"><i class="fa-solid fa-display" aria-hidden="true"></i>查看首頁</a>
          <form method="post">
            <input type="hidden" name="action" value="logout">
            <button class="admin-button admin-button--ghost" type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>登出</button>
          </form>
        </div>
      </header>

      <main id="main-content" tabindex="-1" class="admin-main">
        <section id="overview" class="admin-section" aria-labelledby="overviewTitle">
          <div class="admin-section-heading">
            <div><p class="admin-eyebrow">LIVE DELIVERY STATUS</p><h2 id="overviewTitle">投放總覽</h2></div>
            <label class="ads-switch ads-switch--global"><input id="adsGlobalToggle" type="checkbox"><span aria-hidden="true"></span><b>全站廣告投放</b></label>
          </div>
          <div id="adsDashboard" class="ads-dashboard" aria-label="廣告成效摘要"></div>
          <div id="scheduleGuide" class="ads-admin-note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span><b>排程規則：</b>每週投放日未勾選代表每天顯示；開始／結束日期與曝光上限會共同判斷。條件不符時版位自動暫停，不會刪除素材。</span>
          </div>
        </section>

        <section id="placements" class="admin-section admin-section--placements" aria-labelledby="placementsTitle">
          <div class="admin-section-heading">
            <div><p class="admin-eyebrow">PLACEMENTS & CREATIVES</p><h2 id="placementsTitle">廣告版位與素材</h2></div>
            <p>首頁橫幅包含 5 張隨機素材；每次載入前台固定顯示其中 1 張，不會自動輪播。點縮圖可看 1920×600 原圖。</p>
          </div>
          <p id="adsAdminMessage" class="ads-admin-message" role="status" aria-live="polite"></p>
          <div id="adsAdminGrid" class="ads-admin-grid" aria-busy="true"></div>
          <div class="ads-admin-actions">
            <button id="adsSaveButton" class="admin-button admin-button--primary" type="button"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>儲存全部設定</button>
          </div>
        </section>
      </main>

      <footer class="admin-footer"><span>4K.1-0.TW AD OPERATIONS</span><span>素材、排程與曝光紀錄均儲存在本機 JSON。</span></footer>
    </div>
  </div>

  <dialog id="adsPreviewDialog" class="ads-preview-dialog" aria-labelledby="adsPreviewTitle">
    <button id="adsPreviewClose" class="dialog-close" type="button" aria-label="關閉原圖預覽"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    <img id="adsPreviewImage" src="" alt="">
    <div class="ads-preview-dialog__copy"><p class="admin-eyebrow">CREATIVE PREVIEW</p><h2 id="adsPreviewTitle"></h2><p id="adsPreviewMeta"></p></div>
  </dialog>
  <script src="skin/js/ads-admin.js?v=<?= htmlspecialchars($adminJsVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
