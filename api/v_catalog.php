<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60, stale-while-revalidate=300');
require_once __DIR__ . '/catalog_filter.php';
$catalog = json_decode((string) @file_get_contents(dirname(__DIR__) . '/json/wallpapers.json'), true);
if (!is_array($catalog) || !is_array($catalog['wallpapers'] ?? null)) { http_response_code(500); echo json_encode(['ok' => false, 'error' => '桌布目錄 JSON 格式錯誤'], JSON_UNESCAPED_UNICODE); exit; }
$filters = array_merge($_GET, is_array($input ?? null) ? $input : []);
$stats = json_decode((string) @file_get_contents(dirname(__DIR__) . '/json/wallpaper-stats.json'), true);
$hidden = array_keys(array_filter($stats['wallpapers'] ?? [], static fn(array $item): bool => !empty($item['auto_hidden'])));
$wallpapers = array_map(static fn(array $wallpaper): array => catalogPublicWallpaper($wallpaper), array_filter(catalogFilter($catalog['wallpapers'], $filters), static fn(array $wallpaper): bool => !in_array((string) ($wallpaper['id'] ?? ''), $hidden, true)));
echo json_encode(['ok' => true, 'updated_at' => $catalog['updated_at'] ?? null, 'count' => count($wallpapers), 'filters' => catalogSearchTerms($filters), 'wallpapers' => $wallpapers], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
