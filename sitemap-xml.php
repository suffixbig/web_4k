<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');

$siteUrl = 'https://4k.1-0.tw';
$catalog = json_decode((string) @file_get_contents(__DIR__ . '/json/wallpapers.json'), true);
$wallpapers = is_array($catalog['wallpapers'] ?? null) ? $catalog['wallpapers'] : [];
$lastModified = (string) ($catalog['updated_at'] ?? date('Y-m-d'));
$pages = [
    ['/', '1.0', 'daily'],
    ['/search.php', '1.0', 'daily'],
    ['/ranking.php', '0.8', 'daily'],
    ['/collection.php', '0.6', 'weekly'],
    ['/android-app.php', '0.7', 'weekly'],
    ['/ai-skill.php', '0.7', 'weekly'],
    ['/qa.php', '0.5', 'monthly'],
    ['/sitemap.php', '0.4', 'monthly'],
];

$xml = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($pages as [$path, $priority, $changeFrequency]) {
    echo "  <url>\n";
    echo '    <loc>' . $xml($siteUrl . $path) . "</loc>\n";
    echo '    <lastmod>' . $xml($lastModified) . "</lastmod>\n";
    echo '    <changefreq>' . $xml($changeFrequency) . "</changefreq>\n";
    echo '    <priority>' . $xml($priority) . "</priority>\n";
    if ($path === '/search.php') {
        foreach ($wallpapers as $wallpaper) {
            $file = ltrim(str_replace('\\', '/', (string) ($wallpaper['file'] ?? '')), '/');
            $imageUrl = str_starts_with($file, 'assets/') ? $siteUrl . '/skin/img/' . $file : $siteUrl . '/' . $file;
            echo "    <image:image>\n";
            echo '      <image:loc>' . $xml($imageUrl) . "</image:loc>\n";
            echo '      <image:title>' . $xml((string) ($wallpaper['title'] ?? '高畫質桌布')) . "</image:title>\n";
            echo '      <image:caption>' . $xml(implode('、', (array) ($wallpaper['labels'] ?? [])) . '｜' . (string) ($wallpaper['creator'] ?? '帥龍萌姬桌布館')) . "</image:caption>\n";
            echo "    </image:image>\n";
        }
    }
    echo "  </url>\n";
}
echo '</urlset>';

