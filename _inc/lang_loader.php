<?php
/**
 * PostMaster 語言載入器 - 增強版
 */

// 從 URL 取得語言參數（由 .htaccess 設定）
$requested_lang = (string) ($_GET['lang'] ?? $DEFAULT_LANG);
$current_lang = array_key_exists($requested_lang, $SUPPORTED_LANGS) ? $requested_lang : $DEFAULT_LANG;

$current_locale = $LOCALE_MAP[$current_lang] ?? $DEFAULT_LANG;

// 初始化翻譯陣列
$translations = [];

// 1. 載入共用翻譯（頁尾、選單等）
$common_file = __DIR__ . "/../lang/{$current_lang}/common.php";
if (file_exists($common_file)) {
    $translations = array_merge($translations, include $common_file);
}

// 2. 載入頁面專屬翻譯
// $PAGE_KEY 應在引入此檔案前，於頁面腳本中定義
if (isset($PAGE_KEY)) {
    $page_file = __DIR__ . "/../lang/{$current_lang}/{$PAGE_KEY}.php";
    if (file_exists($page_file)) {
        $translations = array_merge($translations, include $page_file);
    }
}

/**
 * 翻譯函式
 */
if (!function_exists('__')) {
    function __($key) {
        global $translations;
        return $translations[$key] ?? $key;
    }
}

/**
 * 取得當前頁面切換至不同語言後的 URL
 */
function get_lang_url(string $target_lang): string {
    global $SUPPORTED_LANGS, $DEFAULT_LANG;
    $target_lang = array_key_exists($target_lang, $SUPPORTED_LANGS) ? $target_lang : $DEFAULT_LANG;
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';
    $query = $_GET;
    if ($target_lang === $DEFAULT_LANG) {
        unset($query['lang']);
    } else {
        $query['lang'] = $target_lang;
    }
    return $path . ($query ? '?' . http_build_query($query) : '');
}

// 靜態資源的基礎路徑
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = "{$protocol}://{$host}/";
?>
