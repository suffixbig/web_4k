<?php
declare(strict_types=1);

$seoPages = [
    'home' => ['title' => '免費高畫質桌布下載｜手機與電腦桌布｜帥龍萌姬', 'description' => '免費探索帥龍、萌姬、玄靈、幸福仙境、遊戲與動漫高畫質桌布；依主題、色系、版型與創作類型快速搜尋下載。', 'keywords' => '高畫質桌布,免費桌布,AI桌布,電腦桌布,手機桌布,桌布下載,4K桌布', 'canonical' => 'https://4k.1-0.tw/'],
    'search' => ['title' => '高畫質桌布分類搜尋與下載｜帥龍萌姬桌布館', 'description' => '依主題、色系、橫式直式與真人或 AI 創作，搜尋並下載適合手機與電腦的高畫質桌布。', 'keywords' => '桌布分類,高畫質桌布搜尋,遊戲桌布,動漫桌布,玄靈桌布', 'canonical' => 'https://4k.1-0.tw/search.php'],
    'collection' => ['title' => '我的收藏｜帥龍萌姬桌布館', 'description' => '管理喜愛的高畫質桌布，建立個人收藏與 AI 輪播清單。', 'keywords' => '桌布收藏,AI輪播桌布,桌布清單', 'canonical' => 'https://4k.1-0.tw/collection.php'],
    'ranking' => ['title' => '下載排行榜｜帥龍萌姬桌布館', 'description' => '查看本週最受歡迎的高畫質桌布與熱門下載排行。', 'keywords' => '熱門桌布,桌布排行榜,高畫質桌布下載', 'canonical' => 'https://4k.1-0.tw/ranking.php'],
    'skill' => ['title' => '換桌布技能 SKILL.md｜帥龍萌姬桌布館', 'description' => '預覽與下載 Codex、Claude 專用換桌布 SKILL.md，讓 AI 一句話自動換桌布。', 'keywords' => 'SKILL.md, Codex Skill,Claude Skill,AI換桌布,桌布自動化', 'canonical' => 'https://4k.1-0.tw/ai-skill.php'],
    'app' => ['title' => 'Android 手機桌布 App APK｜帥龍萌姬桌布館', 'description' => '下載無廣告、免帳號的 Android 手機桌布 APK，選圖後只要立即套用或下載圖片，介面直接、操作簡單。', 'keywords' => 'Android桌布App,手機桌布APK,立即套用桌布,下載手機桌布,免費APK', 'canonical' => 'https://4k.1-0.tw/android-app.php'],
    'api' => ['title' => 'API 說明｜帥龍萌姬桌布館', 'description' => '帥龍萌姬桌布館 API 與 AI Skill 串接說明。', 'keywords' => '桌布API,AI Skill API,桌布串接', 'canonical' => 'https://4k.1-0.tw/api-docs.php'],
    'offline' => ['title' => '離線模式｜帥龍萌姬桌布館', 'description' => '帥龍萌姬桌布館離線模式。', 'keywords' => '離線桌布', 'canonical' => 'https://4k.1-0.tw/offline.php'],
    'doc' => ['title' => '翻譯系統與瀏覽數統計須知｜帥龍萌姬桌布館', 'description' => '帥龍萌姬桌布館的翻譯系統與公開瀏覽數統計說明。', 'keywords' => '網站翻譯系統,瀏覽數統計,Visit Analytics', 'canonical' => 'https://4k.1-0.tw/DOC_translation_visitor_guide.php'],
    'sitemap' => ['title' => '網站地圖｜帥龍萌姬桌布館', 'description' => '帥龍萌姬桌布館的完整網站導覽與服務入口。', 'keywords' => '網站地圖,桌布分類,桌布排行榜', 'canonical' => 'https://4k.1-0.tw/sitemap.php'],
    'qa' => ['title' => '桌布館 QA｜帥龍萌姬桌布館', 'description' => '了解 AI 搜尋桌布、條件換桌布、手機直式桌布與 Android APK 的使用方式。', 'keywords' => '桌布QA,AI搜尋桌布,換桌布指令,手機桌布', 'canonical' => 'https://4k.1-0.tw/qa.php']
];
$seo = $seoPages[$pageKey ?? 'home'];
$pageKey = $pageKey ?? 'home';
$siteUrl = 'https://4k.1-0.tw';
$structuredGraph = [
    [
        '@type' => 'WebSite',
        '@id' => $siteUrl . '/#website',
        'url' => $siteUrl . '/',
        'name' => '帥龍萌姬桌布館',
        'inLanguage' => $current_locale ?? 'zh-TW',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $siteUrl . '/search.php?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ],
    [
        '@type' => $pageKey === 'search' ? 'ImageGallery' : 'WebPage',
        '@id' => $seo['canonical'] . '#webpage',
        'url' => $seo['canonical'],
        'name' => $seo['title'],
        'description' => $seo['description'],
        'isPartOf' => ['@id' => $siteUrl . '/#website'],
        'inLanguage' => $current_locale ?? 'zh-TW',
    ],
];

if ($pageKey === 'search') {
    $catalog = json_decode((string) @file_get_contents(dirname(__DIR__) . '/json/wallpapers.json'), true);
    $structuredImages = [];
    foreach (array_slice(is_array($catalog['wallpapers'] ?? null) ? $catalog['wallpapers'] : [], 0, 24) as $wallpaper) {
        $file = ltrim(str_replace('\\', '/', (string) ($wallpaper['file'] ?? '')), '/');
        $imageUrl = str_starts_with($file, 'assets/') ? $siteUrl . '/skin/img/' . $file : $siteUrl . '/' . $file;
        $structuredImages[] = [
            '@type' => 'ImageObject',
            'contentUrl' => $imageUrl,
            'name' => (string) ($wallpaper['title'] ?? '高畫質桌布'),
            'creator' => ['@type' => 'Person', 'name' => (string) ($wallpaper['creator'] ?? '帥龍萌姬桌布館')],
            'width' => (int) ($wallpaper['width'] ?? 0),
            'height' => (int) ($wallpaper['height'] ?? 0),
        ];
    }
    $structuredGraph[1]['associatedMedia'] = $structuredImages;
}
?>
<meta name="description" content="<?= htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="keywords" content="<?= htmlspecialchars($seo['keywords'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="index,follow,max-image-preview:large">
<link rel="canonical" href="<?= htmlspecialchars($seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
<?php foreach (($LOCALE_MAP ?? []) as $languageCode => $localeCode): ?>
<link rel="alternate" hreflang="<?= htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($seo['canonical'] . ($languageCode === ($DEFAULT_LANG ?? 'zh_TW') ? '' : (str_contains($seo['canonical'], '?') ? '&' : '?') . 'lang=' . rawurlencode($languageCode)), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
<link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
<link rel="sitemap" type="application/xml" title="桌布圖片 Sitemap" href="https://4k.1-0.tw/sitemap-xml.php">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?= htmlspecialchars(str_replace('-', '_', $current_locale ?? 'zh-TW'), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:site_name" content="帥龍萌姬桌布館">
<meta property="og:title" content="<?= htmlspecialchars($seo['title'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($seo['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="https://4k.1-0.tw/skin/img/assets/desktop/desktop-14.jpg">
<meta property="og:image:width" content="1920">
<meta property="og:image:height" content="1080">
<meta property="og:image:alt" content="帥龍萌姬桌布館高畫質桌布預覽">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image:alt" content="帥龍萌姬桌布館高畫質桌布預覽">
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => $structuredGraph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
