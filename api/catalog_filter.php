<?php
declare(strict_types=1);

function catalogAssetUrl(string $file): string { $file = ltrim(str_replace('\\', '/', $file), '/'); return str_starts_with($file, 'assets/') ? '/skin/img/assets/' . substr($file, 7) : '/' . $file; }
function catalogSearchTerms(array $input): array {
    $q = trim((string) ($input['q'] ?? $input['query'] ?? ''));
    $lower = mb_strtolower($q, 'UTF-8');
    $map = ['red' => ['red', '紅'], 'orange' => ['orange', '橘'], 'yellow' => ['yellow', 'gold', '黃', '金'], 'green' => ['green', '綠', '翠'], 'blue' => ['blue', '藍'], 'purple' => ['purple', '紫'], 'black' => ['black', '黑', '暗'], 'white' => ['white', '白'], 'brown' => ['brown', '棕', '褐']];
    $colors = array_values(array_filter(array_keys($map), static fn(string $color): bool => array_reduce($map[$color], static fn(bool $found, string $word): bool => $found || str_contains($lower, $word), false)));
    $device = (string) ($input['device'] ?? ''); $orientation = (string) ($input['orientation'] ?? ''); $type = (string) ($input['content_type'] ?? $input['type'] ?? '');
    if ($device === '' && preg_match('/手機|mobile|phone/u', $lower)) $device = 'mobile';
    if ($device === '' && preg_match('/pc|電腦|桌機|desktop/u', $lower)) $device = 'pc';
    if ($orientation === '' && preg_match('/直式|直屏|portrait/u', $lower)) $orientation = 'portrait';
    if ($orientation === '' && preg_match('/橫式|橫屏|landscape/u', $lower)) $orientation = 'landscape';
    if ($type === '' && preg_match('/真人|寫真|real/u', $lower)) $type = 'real';
    if ($type === '' && preg_match('/ai|人工智慧|生成/u', $lower)) $type = 'ai';
    $clean = preg_replace('/搜尋|桌布|壁紙|畫作|的畫|作品|手機|mobile|phone|pc|電腦|桌機|desktop|直式|直屏|portrait|橫式|橫屏|landscape|真人|寫真|real|ai|人工智慧|生成|紅色系|紅色|紅|red|橘色系|橘色|橘|orange|黃色系|黃色|黃|金色|金|yellow|gold|綠色系|綠色|綠|翠色|green|藍色系|藍色|藍|blue|紫色系|紫色|紫|purple|黑色系|黑色|黑|暗色|black|白色系|白色|白|white|棕色系|棕色|棕|褐色|brown/ui', '', $q) ?? '';
    return ['q' => trim($clean), 'device' => $device, 'orientation' => $orientation, 'content_type' => $type, 'creator' => trim((string) ($input['creator'] ?? $input['author'] ?? '')), 'colors' => $colors];
}
function catalogFilter(array $wallpapers, array $input): array {
    $terms = catalogSearchTerms($input);
    return array_values(array_filter($wallpapers, static function (array $wallpaper) use ($terms): bool {
        if ($terms['device'] !== '' && ($wallpaper['device'] ?? 'pc') !== $terms['device']) return false;
        if ($terms['orientation'] !== '' && ($wallpaper['orientation'] ?? 'landscape') !== $terms['orientation']) return false;
        if ($terms['content_type'] !== '' && ($wallpaper['content_type'] ?? 'ai') !== $terms['content_type']) return false;
        if ($terms['creator'] !== '' && !str_contains(mb_strtolower((string) ($wallpaper['creator'] ?? ''), 'UTF-8'), mb_strtolower($terms['creator'], 'UTF-8'))) return false;
        if ($terms['colors'] !== [] && array_intersect($terms['colors'], (array) ($wallpaper['colors'] ?? [])) === []) return false;
        if ($terms['q'] === '') return true;
        $text = implode(' ', array_merge([(string) ($wallpaper['title'] ?? ''), (string) ($wallpaper['creator'] ?? ''), (string) ($wallpaper['content_type'] ?? '')], (array) ($wallpaper['labels'] ?? []), (array) ($wallpaper['topics'] ?? []), (array) ($wallpaper['colors'] ?? [])));
        return str_contains(mb_strtolower($text, 'UTF-8'), mb_strtolower($terms['q'], 'UTF-8'));
    }));
}
function catalogPublicWallpaper(array $wallpaper, bool $absolute = false): array { $url = catalogAssetUrl((string) ($wallpaper['file'] ?? '')); $wallpaper['file'] = $url; $wallpaper['download_url'] = $absolute ? 'https://4k.1-0.tw' . $url : $url; $wallpaper['size_bytes'] = is_file(dirname(__DIR__) . $url) ? (int) filesize(dirname(__DIR__) . $url) : 0; return $wallpaper; }
