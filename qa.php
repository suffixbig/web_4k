<?php
declare(strict_types=1);
$pageKey = 'qa';
$pageScript = '';
$pageBodyClass = 'qa-page';
require __DIR__ . '/_incview/header.php';
?>
<!-- QA 主要內容 STAR -->
<main id="main-content" class="section qa-content" tabindex="-1"><section class="qa-hero"><p class="section-label">QUESTION &amp; ANSWER</p><h1>桌布館 QA</h1><p>從 AI 搜尋、條件換桌布到手機直式作品，一次了解怎麼使用。</p><a class="button primary" href="ai-skill.php"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>安裝 AI 技能</a></section><div class="qa-grid"><section><h2><i class="fa-solid fa-comments" aria-hidden="true"></i>一句話搜尋</h2><details open><summary>AI 可以聽懂哪些搜尋說法？</summary><p>可說「搜尋桌布」、「桌布搜尋」、「搜尋壁紙」或「壁紙搜尋」，再描述作者、色系、真人或 AI、PC 或手機、橫式或直式，例如「搜尋桌布 墨凡」。</p></details><details><summary>「搜尋 真人綠色系畫作 手機桌布」會怎麼做？</summary><p>AI 會開啟帶入條件的搜尋頁，並交由網站依真人、綠色與手機直式篩選。若目錄沒有符合結果，會清楚顯示沒有結果，不會拿不相關的圖代替。</p></details><details><summary>我可以直接在網站搜尋嗎？</summary><p>可以。分類搜尋頁可輸入作品名、作者或色系，也可用 PC／手機、橫式／直式、AI 創作／真人三組篩選。</p></details></section><section><h2><i class="fa-solid fa-desktop" aria-hidden="true"></i>換桌布</h2><details open><summary>只說「換桌布」會發生什麼？</summary><p>本機 Windows 技能會取得目前桌布的下一張；若目前 ID 不在清單中，才會使用 API 的隨機備援。</p></details><details><summary>「換桌布 墨凡的畫」會隨機換到別人的作品嗎？</summary><p>不會。帶條件時只會從作者墨凡的符合結果隨機選一張；沒有結果就停止並回報。</p></details><details><summary>會自動修改遠端主機的桌布嗎？</summary><p>不會。技能僅能套用目前這台 Windows PC 的系統桌布，並在成功後回寫本站偏好資料。</p></details></section><section><h2><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>手機與下載</h2><details open><summary>手機桌布的尺寸與格式是什麼？</summary><p>目前手機作品為 941 × 1672、9:16 直式高品質 JPG。網站可直接以「手機」和「直式」篩選。</p></details><details><summary>Android APK 支援什麼？</summary><p>Android 8.0 以上可使用；選好手機桌布後，主畫面只提供「立即套用」與「下載圖片」兩個操作。</p></details><details><summary>技能檔案在哪裡下載？</summary><p>前往 AI 安裝技能頁即可預覽與下載最新版 SKILL.md；頁面會顯示版本與更新日期。</p></details></section></div></main>
<!-- QA 主要內容 END -->
<?php require __DIR__ . '/_incview/footer.php'; ?>
