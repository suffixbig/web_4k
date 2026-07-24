<?php
$pageKey = 'ranking';
$pageScript = 'ranking.js';
$pageBodyClass = 'ranking-page';
require __DIR__ . '/_incview/header.php';
?>
<!-- 主要內容 STAR -->
<main id="main-content" tabindex="-1">
    <section class="ranking-board section" id="rankingBoard">
      <div class="ranking-page-title"><p class="section-label">WALLPAPER RANKING</p><h1>桌布排行榜</h1><p>展示 100 名作品，可依下載、瀏覽、讚數、好評、收藏與上架日期切換排序。</p></div><div class="ranking-toolbar"><div><span class="live-dot"></span><strong>展示排名</strong><small id="updatedAt">正在建立展示數據…</small></div><div class="ranking-tabs"><button class="active" data-rank="downloads">最多下載</button><button data-rank="weekly_views">本週熱門</button><button data-rank="views">最高瀏覽</button><button data-rank="likes">最多讚</button><button data-rank="approval">最高好評率</button><button data-rank="favorites">最高收藏量</button><button data-rank="newest">新增日期</button></div></div>
      <div class="podium" id="podium"></div><div class="ranking-list" id="rankingList"></div>
      <nav class="ranking-pagination" id="rankingPagination" aria-label="排行榜分頁"></nav>
      <div class="rank-note"><i data-lucide="info"></i><p>本頁排名與所有統計數字皆為版面展示用模擬資料，不代表網站真實數據。每頁最多 50 名，最多顯示 100 名。</p></div>
    </section>
  </main>
  <dialog id="previewDialog" class="preview-dialog" aria-labelledby="dialogTitle"><button class="dialog-close" id="closeDialog" aria-label="關閉預覽"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button><img id="dialogImage" src="" alt=""><div class="dialog-info"><div><small id="dialogMeta"></small><h3 id="dialogTitle"></h3></div><div class="dialog-actions"><button class="vote-button" id="dialogLike" aria-label="喜歡" aria-pressed="false"><i class="fa-solid fa-thumbs-up" aria-hidden="true"></i><span></span></button><button class="vote-button" id="dialogDislike" aria-label="不喜歡" aria-pressed="false"><i class="fa-solid fa-thumbs-down" aria-hidden="true"></i><span></span></button><a class="button primary" id="dialogDownload" href="#" download><i class="fa-solid fa-download" aria-hidden="true"></i>下載</a></div></div></dialog>
<!-- 主要內容 END -->
<?php require __DIR__ . '/_incview/footer.php'; ?>
