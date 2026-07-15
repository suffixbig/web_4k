</main>
<footer class="visit-footer">
  <div class="visit-footer-container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <p class="mb-0 text-muted">&copy; <?= date('Y') ?> Visit Analytics System. All rights reserved.</p>
        <p class="mb-0 text-muted">資料最後更新時間：<?= visitor_e(date('Y-m-d H:i:s')) ?></p>
    </div>
  </div>
</footer>
<script src="skin/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<?php if(isset($extraScript)) echo $extraScript; ?>
</body>
</html>
