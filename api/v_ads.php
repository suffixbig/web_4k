<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/_inc/ads.php';

const ADS_SLOTS = ['home_banner', 'category_banner', 'detail_banner', 'download_complete', 'mobile_poster'];

function adsPath(): string { return adsConfigPath(); }
function readAds(): array { return loadAdsConfig(); }
function adsAdminAllowed(): bool { return adsAdminSessionActive(); }

function saveAds(array $config): bool
{
    $encoded = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($encoded === false || ($handle = @fopen(adsPath(), 'c+')) === false) return false;
    try {
        if (!flock($handle, LOCK_EX)) return false;
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $encoded . PHP_EOL);
        return fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function adsInputDate(mixed $value): string
{
    $value = trim((string) $value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

function normalizeAdsForWrite(array $input, array $current): array
{
    $next = $current;
    if (array_key_exists('global_enabled', $input)) $next['global_enabled'] = filter_var($input['global_enabled'], FILTER_VALIDATE_BOOL);
    $next['placements'] = is_array($current['placements'] ?? null) ? $current['placements'] : [];
    $incoming = is_array($input['placements'] ?? null) ? $input['placements'] : [];
    foreach (ADS_SLOTS as $slot) {
        if (!isset($next['placements'][$slot]) || !is_array($next['placements'][$slot]) || !is_array($incoming[$slot] ?? null)) continue;
        $item = $incoming[$slot];
        $next['placements'][$slot]['enabled'] = !empty($item['enabled']);
        $next['placements'][$slot]['campaign_name'] = mb_substr(trim((string) ($item['campaign_name'] ?? ($next['placements'][$slot]['campaign_name'] ?? ''))), 0, 60);
        $raw = is_array($item['schedule'] ?? null) ? $item['schedule'] : [];
        $weekdays = array_values(array_unique(array_filter(array_map('intval', (array) ($raw['weekdays'] ?? [])), static fn(int $day): bool => $day >= 1 && $day <= 7)));
        sort($weekdays);
        $start = adsInputDate($raw['start_date'] ?? ''); $end = adsInputDate($raw['end_date'] ?? '');
        if ($start !== '' && $end !== '' && $start > $end) { $end = ''; }
        $next['placements'][$slot]['schedule'] = ['start_date' => $start, 'end_date' => $end, 'weekdays' => $weekdays];
        $limits = is_array($item['limits'] ?? null) ? $item['limits'] : [];
        $next['placements'][$slot]['limits'] = [
            'daily_impressions' => min(99999999, max(0, (int) ($limits['daily_impressions'] ?? 0))),
            'total_impressions' => min(999999999, max(0, (int) ($limits['total_impressions'] ?? 0))),
        ];
        $next['placements'][$slot]['stats'] = adsStats($next['placements'][$slot]);
    }
    $next['updated_at'] = adsNow()->format(DATE_ATOM);
    return $next;
}

if ($action === 'impression') {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sendError('Method not allowed', 405);
    $slot = (string) (($jsonInput['slot'] ?? null) ?: ($_POST['slot'] ?? ''));
    if (!in_array($slot, ADS_SLOTS, true)) sendError('Unknown advertising slot', 422);
    $stats = recordAdImpression($slot);
    sendSuccess(['recorded' => $stats !== null, 'stats' => $stats], $stats !== null ? 'Impression recorded' : 'Placement is not currently eligible');
}

if (!adsAdminAllowed()) sendError('Admin login required', 401);
if ($action === '' || $action === 'read') sendSuccess(['ads' => readAds(), 'can_write' => true], 'Advertising configuration loaded');
if ($action !== 'write' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') sendError('Unsupported advertising operation', 405);
$next = normalizeAdsForWrite(is_array($jsonInput ?? null) ? $jsonInput : $_REQUEST, readAds());
if (!saveAds($next)) sendError('Unable to save advertising configuration', 500);
sendSuccess(['ads' => $next], 'Advertising configuration saved');
