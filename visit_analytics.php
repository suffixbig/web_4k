<?php
declare(strict_types=1);

define('WEB_ROOT_PATH', str_replace('\\', '/', __DIR__));
require_once __DIR__ . '/config_vote.php';
require_once INCLUDE_PATH . '/visitor_analytics_lib.php';

$days = (int) ($_GET['days'] ?? 7);
if (!in_array($days, [7, 14, 30], true)) {
    $days = 7;
}

$dates = visitor_date_range($days);
$startDate = $dates[0];
$endDate = end($dates);
$sum = visitor_sum_expr();
$error = '';
$refStats = $hostStats = $pageStats = [];
$summary = ['uv' => 0, 'pv' => 0, 'hosts' => 0, 'pages' => 0];

function analytics_pivot(array $rows, array $dates): array
{
    $result = [];
    foreach ($rows as $row) {
        $label = trim((string) ($row['label'] ?? ''));
        $label = $label !== '' ? $label : '直接進站 / 無來源';

        if (!isset($result[$label])) {
            $result[$label] = [
                'label' => $label,
                'dates' => array_fill_keys($dates, 0),
                'uv' => 0,
                'pv' => 0,
            ];
        }

        $date = (string) $row['visit_date'];
        $pv = (int) $row['pv'];
        $result[$label]['dates'][$date] = $pv;
        $result[$label]['uv'] += (int) $row['uv'];
        $result[$label]['pv'] += $pv;
    }

    uasort($result, static fn(array $a, array $b): int => $b['pv'] <=> $a['pv']);
    return array_values($result);
}

function analytics_render_table(string $title, string $icon, array $rows, array $dates, int $limit = 30): void
{
    ?>
    <div class="analytics-panel">
    <div class="analytics-panel-head">
    <h2><i class="<?= visitor_e($icon) ?>"></i><?= visitor_e($title) ?></h2>
    <input class="analytics-filter form-control form-control-sm" type="search" placeholder="搜尋此表格">
    </div>
    <div class="table-responsive">
    <table class="table analytics-table align-middle mb-0">
    <thead>
    <tr>
    <th>項目</th>
    <?php foreach ($dates as $date): ?>
    <th class="text-end"><?= visitor_e(date('m/d', strtotime($date))) ?></th>
    <?php endforeach; ?>
    <th class="text-end">UV</th>
    <th class="text-end">PV</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (array_slice($rows, 0, $limit) as $row): ?>
    <tr>
    <td class="analytics-label" title="<?= visitor_e($row['label']) ?>"><?= visitor_e(visitor_trim_label($row['label'], 96)) ?></td>
    <?php foreach ($dates as $date): ?>
    <td class="text-end <?= ((int) $row['dates'][$date]) === 0 ? 'text-muted' : 'fw-bold' ?>"><?= number_format((int) $row['dates'][$date]) ?></td>
    <?php endforeach; ?>
    <td class="text-end"><?= number_format((int) $row['uv']) ?></td>
    <td class="text-end fw-black"><?= number_format((int) $row['pv']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
    <tr><td colspan="<?= count($dates) + 3 ?>" class="text-center text-muted py-4">目前沒有資料。</td></tr>
    <?php endif; ?>
    </tbody>
    </table>
    </div>
    </div>
    <?php
}

try {
    $pdo = visitor_db();
    visitor_ensure_schema($pdo);

    $summary = [
        'uv' => visitor_scalar($pdo, "SELECT COUNT(DISTINCT ip) FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date BETWEEN :start_date AND :end_date", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]),
        'pv' => visitor_scalar($pdo, "SELECT {$sum} FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date BETWEEN :start_date AND :end_date", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]),
        'hosts' => visitor_scalar($pdo, "SELECT COUNT(DISTINCT host) FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date BETWEEN :start_date AND :end_date", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]),
        'pages' => visitor_scalar($pdo, "SELECT COUNT(*) FROM (SELECT host, path FROM `" . VISITOR_LOG_TABLE . "` WHERE visit_date BETWEEN :start_date AND :end_date GROUP BY host, path) AS pages", [
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]),
    ];

    $refRows = visitor_fetch_all($pdo, "
        SELECT COALESCE(NULLIF(referer, ''), '直接進站 / 無來源') AS label,
               visit_date,
               COUNT(DISTINCT ip) AS uv,
               {$sum} AS pv
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE visit_date BETWEEN :start_date AND :end_date
        GROUP BY label, visit_date
    ", [':start_date' => $startDate, ':end_date' => $endDate]);

    $hostRows = visitor_fetch_all($pdo, "
        SELECT host AS label,
               visit_date,
               COUNT(DISTINCT ip) AS uv,
               {$sum} AS pv
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE visit_date BETWEEN :start_date AND :end_date
        GROUP BY label, visit_date
    ", [':start_date' => $startDate, ':end_date' => $endDate]);

    $pageRows = visitor_fetch_all($pdo, "
        SELECT CONCAT(host, path) AS label,
               visit_date,
               COUNT(DISTINCT ip) AS uv,
               {$sum} AS pv
        FROM `" . VISITOR_LOG_TABLE . "`
        WHERE visit_date BETWEEN :start_date AND :end_date
        GROUP BY label, visit_date
    ", [':start_date' => $startDate, ':end_date' => $endDate]);

    $refStats = analytics_pivot($refRows, $dates);
    $hostStats = analytics_pivot($hostRows, $dates);
    $pageStats = analytics_pivot($pageRows, $dates);
} catch (Throwable $exception) {
    error_log('Visit analytics failed: ' . $exception->getMessage());
    $error = '無法讀取深度分析資料，請確認資料庫與 vo_visit_logs 資料表設定。';
}

$pageTitle = '深度分析';
require_once __DIR__ . '/_incview/visit_header.php';
?>
<div class="analytics-shell">

<header class="analytics-hero">
<div>
<p class="analytics-kicker">Visit Analytics</p>
<h1>網站瀏覽深度分析</h1>
<p>追蹤來源網址、主機網域與完整頁面路徑，快速看懂哪些入口真的帶來瀏覽。</p>
</div>
<div class="analytics-actions">
<form method="get" class="d-flex gap-2">
<select class="form-select" name="days" onchange="this.form.submit()">
<?php foreach ([7 => '最近 7 天', 14 => '最近 14 天', 30 => '最近 30 天'] as $value => $label): ?>
<option value="<?= $value ?>" <?= $days === $value ? 'selected' : '' ?>><?= visitor_e($label) ?></option>
<?php endforeach; ?>
</select>
</form>
</div>
</header>

<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?= visitor_e($error) ?></div>
<?php endif; ?>

<section class="analytics-summary">
<article><span>區間 UV</span><strong><?= number_format($summary['uv']) ?></strong></article>
<article><span>區間 PV</span><strong><?= number_format($summary['pv']) ?></strong></article>
<article><span>Host 數</span><strong><?= number_format($summary['hosts']) ?></strong></article>
<article><span>頁面數</span><strong><?= number_format($summary['pages']) ?></strong></article>
</section>

<div class="analytics-panel mb-4">
<div class="analytics-panel-head">
<h2><i class="fa-solid fa-chart-line"></i>前 5 大來源網址趨勢</h2>
</div>
<div style="height: 300px; padding: 15px;"><canvas id="refTrendChart"></canvas></div>
</div>

<ul class="nav nav-pills analytics-tabs" id="analyticsTabs" role="tablist">
<li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#referer-pane" type="button" role="tab">來源 Referer</button></li>
<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#host-pane" type="button" role="tab">Host</button></li>
<li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#page-pane" type="button" role="tab">完整頁面</button></li>
</ul>

<div class="tab-content">
<section class="tab-pane fade show active" id="referer-pane" role="tabpanel">
<?php analytics_render_table('來源網址趨勢', 'fa-solid fa-link', $refStats, $dates, 30); ?>
</section>
<section class="tab-pane fade" id="host-pane" role="tabpanel">
<?php analytics_render_table('Host 趨勢', 'fa-solid fa-server', $hostStats, $dates, 30); ?>
</section>
<section class="tab-pane fade" id="page-pane" role="tabpanel">
<?php analytics_render_table('完整頁面趨勢', 'fa-solid fa-file-lines', $pageStats, $dates, 40); ?>
</section>
</div>
</div>
<?php
$chartData = [
    'dates' => array_map(static fn($d) => date('m/d', strtotime($d)), $dates),
    'ref' => array_slice(array_map(static fn($row) => [
        'label' => visitor_trim_label($row['label'], 40),
        'data' => array_values($row['dates'])
    ], $refStats), 0, 5),
];
ob_start();
?>
<script>
const chartData = <?= json_encode($chartData) ?>;
Chart.defaults.color = '#a0a0a0';
Chart.defaults.font.family = '"Microsoft JhengHei", "Noto Sans TC", sans-serif';

const colors = ['#d4af37', '#cd7f32', '#38bdf8', '#22c55e', '#f59e0b'];
const datasets = chartData.ref.map((item, index) => ({
    label: item.label,
    data: item.data,
    borderColor: colors[index % colors.length],
    backgroundColor: 'transparent',
    tension: 0.4
}));

const ctx = document.getElementById('refTrendChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: { labels: chartData.dates, datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#f5f5f5' } } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' } }
            }
        }
    });
}
</script>
<script src="/skin/js/visit_analytics.js?v=<?=CSSJSVERSION?>"></script>
<?php
$extraScript = ob_get_clean();
require_once __DIR__ . '/_incview/visit_footer.php';
?>
