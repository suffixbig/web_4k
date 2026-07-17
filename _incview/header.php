<?php declare(strict_types=1); if (!defined('WEB_ROOT_PATH')) { define('WEB_ROOT_PATH', str_replace('\\', '/', dirname(__DIR__))); } require_once dirname(__DIR__) . '/config.local.php'; $PAGE_KEY = $pageKey ?? 'home'; require_once dirname(__DIR__) . '/_inc/lang_loader.php'; ob_start(); require __DIR__ . '/inc_seo.php'; $seoTags = ob_get_clean(); $styleVersion = (string) (@filemtime(__DIR__ . '/../skin/css/styles.css') ?: time()); $manifestVersion = (string) (@filemtime(__DIR__ . '/../manifest.webmanifest') ?: time()); ?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_locale, ENT_QUOTES, 'UTF-8') ?>" data-translation-lang="<?= htmlspecialchars($current_lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; base-uri 'self'; object-src 'none'; script-src 'self' 'unsafe-inline' https://translate.google.com https://translate.googleapis.com https://translate-pa.googleapis.com; style-src 'self' 'unsafe-inline' https://www.gstatic.com; font-src 'self' https://www.gstatic.com; img-src 'self' data: https://www.google.com https://translate.google.com https://www.gstatic.com https://fonts.gstatic.com https://translate.googleapis.com; connect-src 'self' https://translate.googleapis.com https://translate-pa.googleapis.com; frame-src 'self' https://translate.google.com; manifest-src 'self'; worker-src 'self'">
<meta name="theme-color" content="#090b10">
<?= $pageKey === 'home' ? '<meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' : '' ?>
<title><?= htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8') ?></title>
<?= $seoTags ?>
<?php if ($current_lang !== $DEFAULT_LANG): ?><script data-cfasync="false" src="skin/js/translation.js?v=<?= (string) (@filemtime(__DIR__ . '/../skin/js/translation.js') ?: time()) ?>"></script><?php endif; ?>
<link rel="manifest" href="manifest.webmanifest?v=<?= htmlspecialchars($manifestVersion, ENT_QUOTES, 'UTF-8') ?>">
<link rel="icon" href="skin/img/assets/favicon-dragon-tail.ico" sizes="any">
<link rel="icon" href="skin/img/assets/favicon-dragon-tail.png" type="image/png" sizes="64x64">
<link rel="apple-touch-icon" href="skin/img/assets/apple-touch-icon.png">
<link rel="stylesheet" href="skin/css/vendor/bootstrap.min.css">
<link rel="stylesheet" href="skin/css/vendor/all.min.css">
<link rel="stylesheet" href="skin/css/styles.css?v=<?= htmlspecialchars($styleVersion, ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="skin/css/mobile-preview.css?v=<?= (string) (@filemtime(__DIR__ . '/../skin/css/mobile-preview.css') ?: time()) ?>">
<link rel="stylesheet" href="skin/css/wallpaper-details.css?v=<?= (string) (@filemtime(__DIR__ . '/../skin/css/wallpaper-details.css') ?: time()) ?>">
</head>
<body class="<?= htmlspecialchars($pageBodyClass ?: $pageKey . '-page', ENT_QUOTES, 'UTF-8') ?>">
<a class="skip-link" href="#main-content">跳至主要內容</a>
<!-- 頁首 STAR -->
<header class="site-header<?= $pageKey === 'home' ? '' : ' solid' ?>"><nav class="nav-shell" aria-label="主要導覽"><a class="brand" href="index.php" aria-label="帥龍萌姬桌布館首頁"><img class="brand-mark" src="skin/img/assets/logo-dragon-tail.png" alt="龍尾巴標誌"><span>帥龍萌姬</span><small>桌布館</small></a><div class="nav-links"><a href="index.php"<?= $pageKey === 'home' ? ' aria-current="page"' : '' ?>>首頁</a><a href="search.php"<?= $pageKey === 'search' ? ' aria-current="page"' : '' ?>>分類搜尋</a><a href="collection.php"<?= $pageKey === 'collection' ? ' aria-current="page"' : '' ?>>我的收藏</a><a href="ranking.php"<?= $pageKey === 'ranking' ? ' aria-current="page"' : '' ?>>下載排行榜</a><a href="qa.php"<?= $pageKey === 'qa' ? ' aria-current="page"' : '' ?>>QA</a><a href="android-app.php"<?= $pageKey === 'app' ? ' aria-current="page"' : '' ?>>下載 APK</a></div><a class="nav-apk-mobile<?= $pageKey === 'app' ? ' active' : '' ?>" href="android-app.php" aria-label="下載 Android APK"><i class="fa-brands fa-android" aria-hidden="true"></i></a><a class="nav-cta<?= $pageKey === 'skill' ? ' active' : '' ?>" href="ai-skill.php" aria-label="AI安裝技能：預覽與下載 SKILL.md"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i><span>AI安裝技能</span></a></nav></header>
<!-- 頁首 END -->
