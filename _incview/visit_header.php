<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="zh-Hant-TW" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? visitor_e($pageTitle) . ' - ' : '' ?>網站瀏覽統計系統</title>
<link rel="stylesheet" href="skin/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="skin/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="skin/css/visit_dark_theme.css?v=<?=CSSJSVERSION?>">
<script src="skin/vendor/chart/chart.umd.min.js"></script>
</head>
<body>
<nav class="visit-navbar">
  <div class="visit-navbar-container">
    <a class="visit-navbar-brand" href="visit_stats.php">
      <i class="fa-solid fa-chart-pie text-primary me-2"></i>
      <span>Visit Analytics</span>
    </a>
    <div class="visit-navbar-links">
      <a href="visit_stats.php" class="<?= $currentPage === 'visit_stats.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line me-1"></i>統計總覽</a>
      <a href="visit_analytics.php" class="<?= $currentPage === 'visit_analytics.php' ? 'active' : '' ?>"><i class="fa-solid fa-microscope me-1"></i>深度分析</a>
    </div>
    <div class="visit-navbar-actions">
      <a class="btn btn-outline-light btn-sm" href="index.php"><i class="fa-solid fa-house me-1"></i>回首頁</a>
    </div>
  </div>
</nav>
<main class="visit-main-content">
