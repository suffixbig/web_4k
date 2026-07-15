<?php
declare(strict_types=1);
require_once __DIR__ . '/catalog_filter.php';
$catalogFile = dirname(__DIR__) . '/json/wallpapers.json'; $preferencesFile = dirname(__DIR__) . '/json/user-preferences.json'; $apiVersion = '1.114';
$input = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? json_decode((string) file_get_contents('php://input'), true) : $_GET; $input = is_array($input) ? $input : [];
$intent = (string) ($action ?: $input['intent'] ?? 'change_now'); $catalogDocument = json_decode((string) file_get_contents($catalogFile), true); $catalog = array_values(is_array($catalogDocument['wallpapers'] ?? null) ? $catalogDocument['wallpapers'] : []);
if ($catalog === []) apiReply(['ok' => false, 'error' => '目前沒有可用桌布。'], 503);
if ($intent !== 'change_now') apiReply(['ok' => false, 'error' => 'Unsupported automation action: ' . $intent], 422);
$clientId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($input['client_id'] ?? '')) ?? ''; $currentId = (string) ($input['current_wallpaper_id'] ?? '');
if ($currentId === '' && $clientId !== '') { $preferences = json_decode((string) @file_get_contents($preferencesFile), true); $currentId = (string) ($preferences['users'][$clientId]['current_wallpaper_id'] ?? ''); }
$isSearch = trim((string) ($input['q'] ?? $input['query'] ?? $input['creator'] ?? $input['author'] ?? '')) !== '';
$eligible = $isSearch ? catalogFilter($catalog, $input) : $catalog;
if ($eligible === []) apiReply(['ok' => true, 'api_version' => $apiVersion, 'intent' => 'change_now', 'decision' => 'no_result', 'source' => 'search', 'reason' => '沒有符合條件的桌布。', 'catalog_count' => count($catalog), 'match_count' => 0]);
if ($isSearch) { $pool = count($eligible) > 1 ? array_values(array_filter($eligible, static fn(array $wallpaper): bool => (string) ($wallpaper['id'] ?? '') !== $currentId)) : $eligible; $selected = $pool[array_rand($pool)]; $source = 'search_random'; $reason = '已從符合條件的桌布中隨機選擇一張。'; }
else { $currentIndex = null; foreach ($catalog as $index => $wallpaper) if ((string) ($wallpaper['id'] ?? '') === $currentId) { $currentIndex = $index; break; } if ($currentIndex === null) { $selected = $catalog[array_rand($catalog)]; $source = 'random_fallback'; $reason = '目前桌布不在本站清單，已隨機選擇一張。'; } else { $selected = $catalog[($currentIndex + 1) % count($catalog)]; $source = 'next_in_catalog'; $reason = '已取得目前桌布在本站清單中的下一張。'; } }
$selected = catalogPublicWallpaper($selected, true);
apiReply(['ok' => true, 'api_version' => $apiVersion, 'intent' => 'change_now', 'decision' => 'change', 'source' => $source, 'reason' => $reason, 'current_wallpaper_id' => $currentId ?: null, 'catalog_count' => count($catalog), 'match_count' => count($eligible), 'filters' => catalogSearchTerms($input), 'wallpaper' => $selected]);
function apiReply(array $payload, int $status = 200): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
