<?php
declare(strict_types=1);

define('WEB_ROOT_PATH', str_replace('\\', '/', __DIR__));
require_once __DIR__ . '/config_vote.php';
require_once INCLUDE_PATH . '/visitor_analytics_lib.php';

$period = (string) ($_GET['period'] ?? '30days');
$allowedPeriods = ['today', 'yesterday', '7days', '30days', 'thisMonth'];
if (!in_array($period, $allowedPeriods, true)) {
    $period = '30days';
}

[$startDate, $endDate, $periodLabel] = visitor_period_dates($period);
$hostFilter = trim((string) ($_GET['host'] ?? ''));
$ipFilter = trim((string) ($_GET['ip'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;
$today = date('Y-m-d');
$sum = visitor_sum_expr();
$error = '';

$stats = [
    'total_uv' => 0,
    'total_pv' => 0,
    'all_time_uv' => 0,
    'all_time_pv' => 0,
    'today_uv' => 0,
    'today_pv' => 0,
];
$topHosts = $topPaths = $dailyTrend = $hourlyDist = $recentLogs = [];
$totalLogs = $totalPages = 0;

try {
    $pdo = visitor_db();
    visitor_ensure_schema($pdo);

    $where = 'visit_date BETWEEN :start_date AND :end_date';
    $params = [
        ':start_date' => $startDate,
        ':end_date' => $endDate,
    ];

    if ($hostFilter !== '') {
        $where .= ' AND host = :host';
        $params[':host'] = $hostFilter;
    }

    if ($ipFilter !== '') {
        $where .= ' AND ip LIKE :ip';
        $params[':ip'] = '%' . $ipFilter . '%';
    }

    $stats = [
        'total_uv' => visitor_scalar($pdo, "SELECT COUNT(DISTINCT ip) FROM `" . VISITOR_LOG_TABLE . "` WHERE {$where}", $params),
        'total_pv' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "` WHERE {$where}", $params),
        'all_time_uv' => visitor_scalar($pdo, "SELECT COUNT(DISTINCT ip) FROM `" . VISITOR_LOG_TABLE . "`"),
        'all_time_pv' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "`"),
        'today_uv' => visitor_scalar($pdo, "SELECT COUNT(DISTINCT ip) FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date = :today", [':today' => $today]),
        'today_pv' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date = :today", [':today' => $today]),
    ];

    $topHosts = visitor_fetch_all($pdo, "
        SELECT host, COUNT(DISTINCT ip) AS uv, {$sum} AS pv
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE {$where}
        GROUP BY host
        ORDER BY pv DESC, uv DESC
        LIMIT 10
    ", $params);

    $topPaths = visitor_fetch_all($pdo, "
        SELECT host, path, MAX(full_url) AS full_url, COUNT(DISTINCT ip) AS uv, {$sum} AS pv
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE {$where}
        GROUP BY host, path
        ORDER BY pv DESC, uv DESC
        LIMIT 15
    ", $params);

    $dailyTrend = visitor_fetch_all($pdo, "
        SELECT visit_date, COUNT(DISTINCT ip) AS uv, {$sum} AS pv
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE {$where}
        GROUP BY visit_date
        ORDER BY visit_date ASC
    ", $params);

    $hourlyDist = visitor_fetch_all($pdo, "
        SELECT HOUR(visit_time) AS hour_value, {$sum} AS pv
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE {$where}
        GROUP BY hour_value
        ORDER BY hour_value ASC
    ", $params);

    $recentParams = $params;
    $recentLogs = visitor_fetch_all($pdo, "
        SELECT *
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE {$where}
        ORDER BY visit_time DESC
        LIMIT {$offset}, {$limit}
    ", $recentParams);

    $totalLogs = visitor_scalar($pdo, "SELECT COUNT(*) FROM `" . VISITOR_LOG_TABLE . "` WHERE {$where}", $params);
    $totalPages = max(1, (int) ceil($totalLogs / $limit));
} catch (Throwable $exception) {
    error_log('Visit stats failed: ' . $exception->getMessage());
    $error = '無法讀取瀏覽統計資料，請確認資料庫與 vo_visit_logs 資料表設定。';
}

$trendMax = max(1, ...array_map(static fn(array $row): int => (int) $row['pv'], $dailyTrend ?: [['pv' => 0]]));
$hourMap = array_fill(0, 24, 0);
foreach ($hourlyDist as $row) {
    $hourMap[(int) $row['hour_value']] = (int) $row['pv'];
}
$hourMax = max(1, ...$hourMap);

function visit_stats_query(array $overrides = []): string
{
    $query = array_merge($_GET, $overrides);
    return http_build_query(array_filter($query, static fn($value): bool => $value !== '' && $value !== null));
}

$pageTitle = '統計總覽';
require_once __DIR__ . '/_incview/visit_header.php';
?>
<div class="shell">

<div class="mb-4">
<h1 class="h3 fw-black mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i>統計總覽</h1>
<div class="text-muted">統計區間：<?= visitor_e($startDate) ?> 至 <?= visitor_e($endDate) ?>（<?= visitor_e($periodLabel) ?>）</div>
</div>

<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?= visitor_e($error) ?></div>
<?php endif; ?>

<form class="panel mb-4" method="get">
<div class="row g-3 align-items-end">
<div class="col-md-3">
<label class="form-label fw-bold">區間</label>
<select class="form-select" name="period">
<?php foreach (['today' => '今天', 'yesterday' => '昨天', '7days' => '最近 7 天', '30days' => '最近 30 天', 'thisMonth' => '本月'] as $key => $label): ?>
<option value="<?= visitor_e($key) ?>" <?= $period === $key ? 'selected' : '' ?>><?= visitor_e($label) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<label class="form-label fw-bold">Host</label>
<input class="form-control" name="host" value="<?= visitor_e($hostFilter) ?>" placeholder="例如：https://phpmytool.com">
</div>
<div class="col-md-3">
<label class="form-label fw-bold">IP 搜尋</label>
<input class="form-control" name="ip" value="<?= visitor_e($ipFilter) ?>" placeholder="輸入 IP 片段">
</div>
<div class="col-md-2 d-grid">
<button class="btn btn-primary"><i class="fa-solid fa-filter me-1"></i>篩選</button>
</div>
</div>
</form>

<div class="row g-3 mb-4">
<div class="col-md-3"><div class="stat-card"><div class="stat-label">區間不重複訪客 UV</div><div class="stat-value"><?= number_format($stats['total_uv']) ?></div><div class="text-muted">全部累計 UV：<?= number_format($stats['all_time_uv']) ?></div></div></div>
<div class="col-md-3"><div class="stat-card"><div class="stat-label">區間瀏覽 PV</div><div class="stat-value"><?= number_format($stats['total_pv']) ?></div><div class="text-muted">平均每位 <?= $stats['total_uv'] > 0 ? number_format($stats['total_pv'] / $stats['total_uv'], 1) : '0' ?> 次</div></div></div>
<div class="col-md-3"><div class="stat-card"><div class="stat-label">今日瀏覽</div><div class="stat-value"><?= number_format($stats['today_pv']) ?></div><div class="text-muted">今日 UV：<?= number_format($stats['today_uv']) ?></div></div></div>
<div class="col-md-3"><div class="stat-card"><div class="stat-label">全部累計瀏覽</div><div class="stat-value"><?= number_format($stats['all_time_pv']) ?></div><div class="text-muted"><?= $hostFilter !== '' ? visitor_e($hostFilter) : '全部 Host' ?></div></div></div>
</div>

<div class="row g-3 mb-4">
<div class="col-lg-7">
<div class="panel h-100">
<h2 class="h5 fw-bold mb-3"><i class="fa-solid fa-chart-line me-2 text-primary"></i>每日趨勢</h2>
<div style="height: 250px;"><canvas id="dailyChart"></canvas></div>
</div>
</div>
<div class="col-lg-5">
<div class="panel h-100">
<h2 class="h5 fw-bold mb-3"><i class="fa-solid fa-clock me-2 text-success"></i>24 小時分布</h2>
<div style="height: 250px;"><canvas id="hourlyChart"></canvas></div>
</div>
</div>
</div>

<div class="row g-3 mb-4">
<div class="col-lg-4">
<div class="panel">
<h2 class="h5 fw-bold mb-3"><i class="fa-solid fa-globe me-2 text-primary"></i>熱門 Host</h2>
<div class="mb-4" style="height: 200px;"><canvas id="hostChart"></canvas></div>
<div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
<thead><tr><th>Host</th><th class="text-end">UV</th><th class="text-end">PV</th></tr></thead>
<tbody>
<?php foreach ($topHosts as $row): ?>
<tr>
<td class="text-break-any"><a href="?<?= visitor_e(visit_stats_query(['host' => $row['host'], 'page' => 1])) ?>"><?= visitor_e($row['host']) ?></a></td>
<td class="text-end"><?= number_format((int) $row['uv']) ?></td>
<td class="text-end fw-bold"><?= number_format((int) $row['pv']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<div class="col-lg-8">
<div class="panel">
<h2 class="h5 fw-bold mb-3"><i class="fa-solid fa-file-lines me-2 text-primary"></i>熱門頁面</h2>
<div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
<thead><tr><th>頁面</th><th class="text-end">UV</th><th class="text-end">PV</th></tr></thead>
<tbody>
<?php foreach ($topPaths as $row): ?>
<tr>
<td class="text-break-any"><small class="text-muted d-block"><?= visitor_e($row['host']) ?></small><a href="<?= visitor_e($row['full_url'] ?: ($row['host'] . $row['path'])) ?>" target="_blank" rel="noopener"><?= visitor_e($row['path']) ?></a></td>
<td class="text-end"><?= number_format((int) $row['uv']) ?></td>
<td class="text-end fw-bold"><?= number_format((int) $row['pv']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<div class="panel">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
<h2 class="h5 fw-bold mb-0"><i class="fa-solid fa-history me-2 text-primary"></i>最近訪問紀錄</h2>
<span class="text-muted">共 <?= number_format($totalLogs) ?> 筆</span>
</div>
<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
<thead><tr><th>IP</th><th>時間</th><th>頁面</th><th>來源</th><th>裝置</th><th class="text-end">次數</th></tr></thead>
<tbody>
<?php foreach ($recentLogs as $log): ?>
<tr>
<td><a href="?<?= visitor_e(visit_stats_query(['ip' => $log['ip'], 'page' => 1])) ?>"><?= visitor_e($log['ip']) ?></a></td>
<td class="text-muted"><?= visitor_e($log['visit_time']) ?></td>
<td class="text-break-any"><small class="text-muted d-block"><?= visitor_e($log['host']) ?></small><?= visitor_e(visitor_trim_label((string) $log['path'], 110)) ?></td>
<td class="text-break-any"><?= visitor_e(visitor_trim_label((string) ($log['referer'] ?? ''), 70)) ?></td>
<td><?= visitor_e(visitor_device_label((string) ($log['user_agent'] ?? ''))) ?></td>
<td class="text-end fw-bold"><?= number_format((int) $log['visit_count']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php if ($totalPages > 1): ?>
<nav aria-label="瀏覽紀錄分頁">
<ul class="pagination pagination-sm justify-content-center mb-0">
<?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
<li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= visitor_e(visit_stats_query(['page' => $i])) ?>"><?= $i ?></a></li>
<?php endfor; ?>
</ul>
</nav>
<?php endif; ?>
</div>
<?php
$chartData = [
    'daily' => [
        'labels' => array_map(static fn($row) => date('m/d', strtotime((string)$row['visit_date'])), $dailyTrend ?: []),
        'pv' => array_map(static fn($row) => (int) $row['pv'], $dailyTrend ?: []),
        'uv' => array_map(static fn($row) => (int) $row['uv'], $dailyTrend ?: []),
    ],
    'hourly' => [
        'labels' => array_map(static fn($h) => $h . ':00', array_keys($hourMap)),
        'pv' => array_values($hourMap),
    ],
    'hosts' => [
        'labels' => array_map(static fn($row) => $row['host'], $topHosts),
        'pv' => array_map(static fn($row) => (int) $row['pv'], $topHosts),
    ]
];
ob_start();
?>
<script>
const chartData = <?= json_encode($chartData) ?>;

Chart.defaults.color = '#a0a0a0';
Chart.defaults.font.family = '"Microsoft JhengHei", "Noto Sans TC", sans-serif';

function createChart(ctxId, type, labels, datasets, extraOpts = {}) {
    const ctx = document.getElementById(ctxId);
    if (!ctx) return;
    new Chart(ctx, {
        type: type,
        data: { labels: labels, datasets: datasets },
        options: Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { color: '#f5f5f5' } } },
            scales: type === 'doughnut' ? {} : {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0a0' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a0a0' } }
            }
        }, extraOpts)
    });
}

// 每日趨勢
createChart('dailyChart', 'line', chartData.daily.labels, [
    { label: '瀏覽量 (PV)', data: chartData.daily.pv, borderColor: '#d4af37', backgroundColor: 'rgba(212, 175, 55, 0.2)', fill: true, tension: 0.4 },
    { label: '訪客數 (UV)', data: chartData.daily.uv, borderColor: '#38bdf8', backgroundColor: 'transparent', tension: 0.4 }
]);

// 24小時分布
createChart('hourlyChart', 'bar', chartData.hourly.labels, [
    { label: 'PV', data: chartData.hourly.pv, backgroundColor: 'rgba(205, 127, 50, 0.8)', borderRadius: 4 }
]);

// 熱門 Host
createChart('hostChart', 'doughnut', chartData.hosts.labels, [
    { label: 'PV', data: chartData.hosts.pv, backgroundColor: ['#d4af37', '#cd7f32', '#9ca3af', '#38bdf8', '#22c55e', '#f59e0b'], borderWidth: 0 }
], { plugins: { legend: { position: 'right' } } });
</script>
<?php
$extraScript = ob_get_clean();
require_once __DIR__ . '/_incview/visit_footer.php';
?>
