<?php
declare(strict_types=1);

$seoPages = [
    'home' => ['title' => '帥龍與萌姬桌布館｜AI 精選 4K 高畫質桌布', 'description' => '探索奇幻、遊戲、動漫與暗色系 4K 高畫質桌布；一句話交給 AI，快速找到適合今天的桌布。', 'keywords' => '4K桌布,高畫質桌布,AI桌布,電腦桌布,手機桌布,桌布下載', 'canonical' => 'https://4k.1-0.tw/'],
    'search' => ['title' => '分類搜尋｜帥龍與萌姬桌布館', 'description' => '以主題、風格與關鍵字搜尋奇幻、遊戲、動漫與暗色高畫質桌布。', 'keywords' => '桌布分類,4K桌布搜尋,遊戲桌布,動漫桌布', 'canonical' => 'https://4k.1-0.tw/search.php'],
    'collection' => ['title' => '我的收藏｜帥龍與萌姬桌布館', 'description' => '管理喜愛的高畫質桌布，建立個人收藏與 AI 輪播清單。', 'keywords' => '桌布收藏,AI輪播桌布,桌布清單', 'canonical' => 'https://4k.1-0.tw/collection.php'],
    'ranking' => ['title' => '下載排行榜｜帥龍與萌姬桌布館', 'description' => '查看本週最受歡迎的 4K 高畫質桌布與熱門下載排行。', 'keywords' => '熱門桌布,桌布排行榜,4K桌布下載', 'canonical' => 'https://4k.1-0.tw/ranking.php'],
    'skill' => ['title' => '換桌布技能 SKILL.md｜帥龍與萌姬桌布館', 'description' => '預覽與下載 Codex、Claude 專用換桌布 SKILL.md，讓 AI 一句話自動換桌布。', 'keywords' => 'SKILL.md, Codex Skill,Claude Skill,AI換桌布,桌布自動化', 'canonical' => 'https://4k.1-0.tw/ai-skill.php'],
    'app' => ['title' => 'Android 手機桌布 App APK｜帥龍與萌姬桌布館', 'description' => '下載無廣告、免帳號的 Android 手機桌布 APK，支援搜尋預覽、主畫面與鎖定畫面套用、收藏輪播、排程及原生動態桌布。', 'keywords' => 'Android桌布App,手機桌布APK,自動換桌布,動態桌布,免費APK', 'canonical' => 'https://4k.1-0.tw/android-app.php'],
    'api' => ['title' => 'API 說明｜帥龍與萌姬桌布館', 'description' => '帥龍與萌姬桌布館 API 與 AI Skill 串接說明。', 'keywords' => '桌布API,AI Skill API,桌布串接', 'canonical' => 'https://4k.1-0.tw/api-docs.php'],
    'offline' => ['title' => '離線模式｜帥龍與萌姬桌布館', 'description' => '帥龍與萌姬桌布館離線模式。', 'keywords' => '離線桌布', 'canonical' => 'https://4k.1-0.tw/offline.php'],
    'doc' => ['title' => '翻譯系統與瀏覽數統計須知｜帥龍與萌姬桌布館', 'description' => '帥龍與萌姬桌布館的翻譯系統與公開瀏覽數統計說明。', 'keywords' => '網站翻譯系統,瀏覽數統計,Visit Analytics', 'canonical' => 'https://4k.1-0.tw/DOC_translation_visitor_guide.php'],
    'sitemap' => ['title' => '網站地圖｜帥龍與萌姬桌布館', 'description' => '帥龍與萌姬桌布館的完整網站導覽與服務入口。', 'keywords' => '網站地圖,桌布分類,桌布排行榜', 'canonical' => 'https://4k.1-0.tw/sitemap.php'],
    'qa' => ['title' => '桌布館 QA｜帥龍與萌姬桌布館', 'description' => '了解 AI 搜尋桌布、條件換桌布、手機直式桌布與 Android 動態桌布的使用方式。', 'keywords' => '桌布QA,AI搜尋桌布,換桌布指令,手機桌布', 'canonical' => 'https://4k.1-0.tw/qa.php']
];
$seo = $seoPages[$pageKey ?? 'home'];
?>
<meta name="description" content="<?= htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="keywords" content="<?= htmlspecialchars($seo['keywords'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="<?= htmlspecialchars($seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?= htmlspecialchars(str_replace('-', '_', $current_locale ?? 'zh-TW'), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:site_name" content="帥龍與萌姬桌布館">
<meta property="og:title" content="<?= htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="https://4k.1-0.tw/skin/img/assets/desktop/desktop-14.jpg">
<meta name="twitter:card" content="summary_large_image">
