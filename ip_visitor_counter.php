<?php
declare(strict_types=1);

define('WEB_ROOT_PATH', str_replace('\\', '/', __DIR__));
require_once __DIR__ . '/config_vote.php';
require_once INCLUDE_PATH . '/visitor_analytics_lib.php';

function visitor_json(bool $success, array $data = [], string $message = '', int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$isApi = (string) ($_GET['api'] ?? '') === '1';
$data = [
    'today_visits' => 0,
    'month_visits' => 0,
    'total_visits' => 0,
    'page_today' => 0,
    'page_total' => 0,
    'host' => '',
    'path' => '',
];

try {
    $pdo = visitor_db();
    visitor_ensure_schema($pdo);

    $ip = visitor_client_ip();
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');
    $now = date('Y-m-d H:i:s');
    [$host, $path, $fullUrl] = visitor_normalize_url((string) ($_POST['url'] ?? $_GET['url'] ?? ''));
    $referer = visitor_limit_text((string) ($_POST['referrer'] ?? $_GET['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? ''), 4096);
    $userAgent = visitor_limit_text((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 1024);

    $row = visitor_fetch_one($pdo, "
        SELECT id
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE ip = :ip
          AND visit_date = :visit_date
          AND host = :host
          AND path = :path
        LIMIT 1
    ", [
        ':ip' => $ip,
        ':visit_date' => $today,
        ':host' => $host,
        ':path' => $path,
    ]);

    if ($row) {
        $stmt = $pdo->prepare("
            UPDATE `" . VISITOR_LOG_TABLE . "`
            SET visit_count = visit_count + 1,
                visit_time = :visit_time,
                full_url = :full_url,
                user_agent = :user_agent,
                referer = :referer
            WHERE id = :id
        ");
        $stmt->execute([
            ':visit_time' => $now,
            ':full_url' => $fullUrl,
            ':user_agent' => $userAgent,
            ':referer' => $referer,
            ':id' => (int) $row['id'],
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO `" . VISITOR_LOG_TABLE . "` (
                ip, host, path, full_url, visit_date, visit_time, visit_count, user_agent, referer
            ) VALUES (
                :ip, :host, :path, :full_url, :visit_date, :visit_time, 1, :user_agent, :referer
            )
        ");
        $stmt->execute([
            ':ip' => $ip,
            ':host' => $host,
            ':path' => $path,
            ':full_url' => $fullUrl,
            ':visit_date' => $today,
            ':visit_time' => $now,
            ':user_agent' => $userAgent,
            ':referer' => $referer,
        ]);
    }

    $sum = visitor_sum_expr();
    $data = [
        'today_visits' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date = :today", [':today' => $today]),
        'month_visits' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date >= :month_start", [':month_start' => $monthStart]),
        'total_visits' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "`"),
        'page_today' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date = :today AND host = :host AND path = :path", [
            ':today' => $today,
            ':host' => $host,
            ':path' => $path,
        ]),
        'page_total' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "` WHERE host = :host AND path = :path", [
            ':host' => $host,
            ':path' => $path,
        ]),
        'today_unique' => visitor_scalar($pdo, "SELECT COUNT(DISTINCT ip) FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date = :today", [':today' => $today]),
        'page_today_unique' => visitor_scalar($pdo, "SELECT COUNT(DISTINCT ip) FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date = :today AND host = :host AND path = :path", [
            ':today' => $today,
            ':host' => $host,
            ':path' => $path,
        ]),
        'host' => $host,
        'path' => $path,
    ];

    if ($isApi) {
        visitor_json(true, $data);
    }
} catch (Throwable $exception) {
    error_log('Visitor counter failed: ' . $exception->getMessage());
    if ($isApi) {
        visitor_json(false, [], 'Visitor counter database error.', 500);
    }
    http_response_code(500);
    echo 'Visitor counter database error.';
    exit;
}
?>
<!doctype html>
<html lang="zh-Hant-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>網站瀏覽統計</title>
<style>
body{font-family:"Microsoft JhengHei",system-ui,sans-serif;background:#080b10;color:#f7f9fd;margin:0;padding:40px;}
.card{max-width:720px;margin:auto;padding:28px;border:1px solid rgba(255,255,255,.12);border-radius:10px;background:#172130;}
strong{font-size:2rem;color:#22c55e;}
p{color:#aeb9ca;}
</style>
</head>
<body>
<div class="card">
<h1>網站瀏覽統計 API</h1>
<p>累計瀏覽</p>
<strong><?= number_format((int) $data['total_visits']) ?></strong>
<p>目前頁面：<?= visitor_e($data['host'] . $data['path']) ?></p>
</div>
</body>
</html>
