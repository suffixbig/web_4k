<?php
declare(strict_types=1);

const ADS_ADMIN_USERNAME = 'admin';
const ADS_ADMIN_PASSWORD_HASH = '$2y$12$ncHhq/axBSx0Hczc3YJUKuQDRGdD2SqQtW/ISXfQ0A.IX0AhD/F0a';
const ADS_STATS_RETENTION_DAYS = 365;

function startAdsAdminSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('wallpaper_ads_admin');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function adsAdminSessionActive(): bool
{
    startAdsAdminSession();
    return ($_SESSION['ads_admin_user'] ?? '') === ADS_ADMIN_USERNAME;
}

function loginAdsAdmin(string $username, string $password): bool
{
    startAdsAdminSession();
    if (!hash_equals(ADS_ADMIN_USERNAME, $username) || !password_verify($password, ADS_ADMIN_PASSWORD_HASH)) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['ads_admin_user'] = ADS_ADMIN_USERNAME;
    return true;
}

function logoutAdsAdmin(): void
{
    startAdsAdminSession();
    $_SESSION = [];
    session_destroy();
}

function adsConfigPath(): string
{
    return dirname(__DIR__) . '/json/ads.json';
}

function adsNow(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Asia/Taipei'));
}

function loadAdsConfig(): array
{
    $decoded = json_decode((string) @file_get_contents(adsConfigPath()), true);
    return is_array($decoded) ? $decoded : ['global_enabled' => false, 'placements' => []];
}

function adsSchedule(array $placement): array
{
    $schedule = is_array($placement['schedule'] ?? null) ? $placement['schedule'] : [];
    $weekdays = array_values(array_unique(array_filter(
        array_map('intval', is_array($schedule['weekdays'] ?? null) ? $schedule['weekdays'] : []),
        static fn(int $day): bool => $day >= 1 && $day <= 7
    )));
    sort($weekdays);
    return [
        'start_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($schedule['start_date'] ?? '')) ? (string) $schedule['start_date'] : '',
        'end_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($schedule['end_date'] ?? '')) ? (string) $schedule['end_date'] : '',
        'weekdays' => $weekdays,
    ];
}

function adsStats(array $placement): array
{
    $stats = is_array($placement['stats'] ?? null) ? $placement['stats'] : [];
    $byDay = [];
    foreach ((array) ($stats['by_day'] ?? []) as $date => $count) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date) && (int) $count > 0) {
            $byDay[(string) $date] = (int) $count;
        }
    }
    return ['total_impressions' => max(0, (int) ($stats['total_impressions'] ?? 0)), 'by_day' => $byDay];
}

function adPlacementIsScheduled(array $placement, ?DateTimeImmutable $now = null): bool
{
    $now ??= adsNow();
    $schedule = adsSchedule($placement);
    $today = $now->format('Y-m-d');
    if ($schedule['start_date'] !== '' && $today < $schedule['start_date']) return false;
    if ($schedule['end_date'] !== '' && $today > $schedule['end_date']) return false;
    return $schedule['weekdays'] === [] || in_array((int) $now->format('N'), $schedule['weekdays'], true);
}

function adPlacementWithinLimits(array $placement, ?DateTimeImmutable $now = null): bool
{
    $now ??= adsNow();
    $limits = is_array($placement['limits'] ?? null) ? $placement['limits'] : [];
    $dailyLimit = max(0, (int) ($limits['daily_impressions'] ?? 0));
    $totalLimit = max(0, (int) ($limits['total_impressions'] ?? 0));
    $stats = adsStats($placement);
    if ($totalLimit > 0 && $stats['total_impressions'] >= $totalLimit) return false;
    return $dailyLimit === 0 || (int) ($stats['by_day'][$now->format('Y-m-d')] ?? 0) < $dailyLimit;
}

function adPlacementCanServe(array $config, array $placement, ?DateTimeImmutable $now = null): bool
{
    return !empty($config['global_enabled']) && !empty($placement['enabled'])
        && adPlacementIsScheduled($placement, $now) && adPlacementWithinLimits($placement, $now);
}

function getAdPlacement(string $slot): ?array
{
    $config = loadAdsConfig();
    $placement = $config['placements'][$slot] ?? null;
    return is_array($placement) && adPlacementCanServe($config, $placement) ? $placement : null;
}

function recordAdImpression(string $slot): ?array
{
    $handle = @fopen(adsConfigPath(), 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) fclose($handle);
        return null;
    }
    try {
        rewind($handle);
        $config = json_decode((string) stream_get_contents($handle), true);
        $placement = is_array($config) ? ($config['placements'][$slot] ?? null) : null;
        if (!is_array($config) || !is_array($placement) || !adPlacementCanServe($config, $placement)) return null;
        $now = adsNow();
        $stats = adsStats($placement);
        $today = $now->format('Y-m-d');
        $stats['total_impressions']++;
        $stats['by_day'][$today] = (int) ($stats['by_day'][$today] ?? 0) + 1;
        $cutoff = $now->modify('-' . ADS_STATS_RETENTION_DAYS . ' days')->format('Y-m-d');
        $stats['by_day'] = array_filter($stats['by_day'], static fn(int $count, string $date): bool => $date >= $cutoff && $count > 0, ARRAY_FILTER_USE_BOTH);
        $config['placements'][$slot]['stats'] = $stats;
        $encoded = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($encoded === false) return null;
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $encoded . PHP_EOL);
        fflush($handle);
        return $stats;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function adPlacementCreatives(array $placement): array
{
    $creatives = array_values(array_filter(
        is_array($placement['creatives'] ?? null) ? $placement['creatives'] : [],
        static fn(mixed $creative): bool => is_array($creative)
            && !empty($creative['enabled'])
            && trim((string) ($creative['image'] ?? '')) !== ''
    ));
    if ($creatives !== []) return $creatives;
    return [[
        'id' => 'default',
        'enabled' => true,
        'brand' => (string) ($placement['brand'] ?? '品牌廣告'),
        'headline' => (string) ($placement['headline'] ?? ''),
        'image' => (string) ($placement['image'] ?? ''),
        'disclosure' => '',
    ]];
}

function renderAdPlacement(string $slot, string $extraClass = ''): string
{
    $placement = getAdPlacement($slot);
    if ($placement === null) return '';
    $escape = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $classes = trim('ad-slot ad-slot--' . preg_replace('/[^a-z0-9_-]/i', '', $slot) . ' ' . $extraClass);
    $creatives = adPlacementCreatives($placement);
    if (count($creatives) > 1) {
        $creative = $creatives[random_int(0, count($creatives) - 1)];
        $brand = $escape($creative['brand'] ?? '品牌廣告');
        $headline = $escape($creative['headline'] ?? '');
        $image = $escape($creative['image'] ?? '');
        $format = $escape($placement['format'] ?? '1920×600');
        return '<aside class="' . $classes . '" data-ad-slot="' . $escape($slot) . '" aria-label="' . $brand . ' 平台廣告">'
            . '<div class="ad-random-poster">'
            . '<img src="' . $image . '" alt="' . $brand . '｜' . $headline . '" width="1920" height="600" loading="eager" decoding="async">'
            . '<div class="ad-slot__meta"><span>' . $brand . '</span><strong>' . $headline . '</strong><small>平台廣告 · ' . $format . '</small></div>'
            . '<p class="sr-only">本次載入隨機顯示一張品牌概念視覺，非官方合作。</p>'
            . '</div></aside>';
    }
    $brand = $escape($placement['brand'] ?? '品牌廣告');
    $headline = $escape($placement['headline'] ?? '');
    $image = $escape($placement['image'] ?? '');
    $format = $escape($placement['format'] ?? '');
    return '<aside class="' . $classes . '" data-ad-slot="' . $escape($slot) . '" aria-label="' . $brand . ' 廣告">'
        . '<div class="ad-slot__poster"><img src="' . $image . '" alt="' . $brand . ' 廣告" loading="lazy" decoding="async">'
        . '<div class="ad-slot__meta"><span>' . $brand . '</span><strong>' . $headline . '</strong><small>平台廣告 · ' . $format . '</small></div></div></aside>';
}
