<?php
declare(strict_types=1);

const VISITOR_LOG_TABLE = 'vo_visit_logs';

function visitor_db(): PDO
{
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function visitor_fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function visitor_fetch_one(PDO $pdo, string $sql, array $params = []): array|false
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function visitor_scalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) ($stmt->fetchColumn() ?: 0);
}

function visitor_sum_expr(): string
{
    return 'COALESCE(SUM(visit_count), 0)';
}

function visitor_ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `" . VISITOR_LOG_TABLE . "` (
          `id` int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主鍵，自動遞增',
          `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '訪問者 IP (支援 IPv4/IPv6)',
          `host` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '主機網域 (例如: https://evol.tw)',
          `path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '頁面路徑與參數 (例如: /p502/?ref=ai)',
          `full_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '完整原始請求網址',
          `visit_date` date NOT NULL COMMENT '訪問日期 (年月日)',
          `visit_time` datetime NOT NULL COMMENT '最後一次訪問的精確時間',
          `visit_count` int UNSIGNED NOT NULL DEFAULT '1' COMMENT '同一 IP 於當日對此網頁的點擊次數',
          `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '瀏覽器 UA 資訊',
          `referer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '來源網址 (Referer)',
          PRIMARY KEY (`id`) USING BTREE,
          KEY `idx_ip_date` (`ip`,`visit_date`) USING BTREE COMMENT '加速 IP 統計',
          KEY `idx_host_path` (`host`(100),`path`(100)) USING BTREE COMMENT '加速頁面排行榜查詢',
          KEY `idx_visit_date` (`visit_date`) USING BTREE COMMENT '加速日期趨勢查詢'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='優化版網址瀏覽統計系統'
    ");
}

function visitor_client_ip(): string
{
    $headers = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_REAL_IP'] ?? '',
        $_SERVER['HTTP_CLIENT_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($headers as $header) {
        foreach (explode(',', (string) $header) as $candidate) {
            $ip = trim($candidate);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}

function visitor_current_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    return "{$scheme}://{$host}{$uri}";
}

function visitor_normalize_url(string $url): array
{
    $url = trim($url);
    $url = $url !== '' ? $url : visitor_current_url();
    $parts = parse_url($url);

    if (!is_array($parts) || empty($parts['host'])) {
        $base = parse_url(visitor_current_url()) ?: [];
        $scheme = $base['scheme'] ?? 'http';
        $hostName = $base['host'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $relative = is_array($parts) ? $parts : parse_url('/' . ltrim($url, '/'));
        $path = ($relative['path'] ?? '/')
            . (isset($relative['query']) ? '?' . $relative['query'] : '');
        $fragment = isset($relative['fragment']) ? '#' . $relative['fragment'] : '';
        $host = "{$scheme}://{$hostName}{$port}";
        $host = visitor_limit_text($host, 255);
        $path = visitor_limit_text($path, 4096);
        return [$host, $path, visitor_limit_text($host . $path . $fragment, 8192)];
    }

    $scheme = $parts['scheme'] ?? 'http';
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $host = "{$scheme}://{$parts['host']}{$port}";
    $path = ($parts['path'] ?? '/')
        . (isset($parts['query']) ? '?' . $parts['query'] : '');
    $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

    $host = visitor_limit_text($host, 255);
    $path = visitor_limit_text($path, 4096);
    return [$host, $path, visitor_limit_text($host . $path . $fragment, 8192)];
}

function visitor_e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function visitor_limit_text(string $value, int $length): string
{
    $value = trim($value);
    return mb_strlen($value, 'UTF-8') > $length
        ? mb_substr($value, 0, $length, 'UTF-8')
        : $value;
}

function visitor_period_dates(string $period): array
{
    $today = date('Y-m-d');

    return match ($period) {
        'today' => [$today, $today, '今天'],
        'yesterday' => [date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('-1 day')), '昨天'],
        '7days' => [date('Y-m-d', strtotime('-6 days')), $today, '最近 7 天'],
        'thisMonth' => [date('Y-m-01'), date('Y-m-t'), '本月'],
        default => [date('Y-m-d', strtotime('-29 days')), $today, '最近 30 天'],
    };
}

function visitor_date_range(int $days): array
{
    $dates = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $dates[] = date('Y-m-d', strtotime("-{$i} days"));
    }
    return $dates;
}

function visitor_device_label(string $userAgent): string
{
    if (preg_match('/Mobile|Android|iPhone|iPad|Windows Phone/i', $userAgent)) {
        return 'Mobile';
    }
    return 'PC';
}

function visitor_trim_label(?string $value, int $length = 90): string
{
    $value = trim((string) $value);
    if ($value === '') {
        return '-';
    }
    return mb_strlen($value, 'UTF-8') > $length
        ? mb_substr($value, 0, $length, 'UTF-8') . '...'
        : $value;
}
