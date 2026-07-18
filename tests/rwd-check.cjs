const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");
const fs = require("node:fs");
const path = require("node:path");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const screenshotDir = process.env.RWD_SCREENSHOT_DIR || "";
const catalog = JSON.parse(fs.readFileSync(path.join(__dirname, "..", "json", "wallpapers.json"), "utf8"));
const statsPath = path.join(__dirname, "..", "json", "wallpaper-stats.json");
const stats = fs.existsSync(statsPath) ? JSON.parse(fs.readFileSync(statsPath, "utf8")) : { wallpapers: {} };
const expectedCatalogItems = (catalog.wallpapers || []).filter((item) => !stats.wallpapers?.[item.id]?.auto_hidden).length;
const expectedInitialItems = Math.min(24, expectedCatalogItems);
const cases = [
  { name: "desktop", width: 1440, height: 1000, columns: 4 },
  { name: "laptop", width: 1024, height: 900, columns: 3 },
  { name: "tablet", width: 768, height: 900, columns: 2 },
  { name: "mobile", width: 390, height: 844, columns: 1 }
];

(async () => {
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  let failed = false;

  for (const testCase of cases) {
    const page = await browser.newPage({
      viewport: { width: testCase.width, height: testCase.height },
      deviceScaleFactor: 1,
      isMobile: testCase.width < 600,
      hasTouch: testCase.width < 900
    });
    await page.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
    await page.locator("#catalogGrid .wallpaper-card").first().waitFor();

    const metrics = await page.evaluate(() => {
      const grid = document.querySelector("#catalogGrid .wallpaper-orientation-landscape .wallpaper-grid");
      const quickActions = [...document.querySelectorAll(".quick-favorite, .quick-download")].slice(0, 4);
      const filterToggle = document.querySelector("#mobileFilterToggle");
      return {
        viewport: window.innerWidth,
        scrollWidth: document.documentElement.scrollWidth,
        columns: getComputedStyle(grid).gridTemplateColumns.split(" ").length,
        cards: document.querySelectorAll("#catalogGrid .wallpaper-card").length,
        actionTargets: quickActions.map((button) => {
          const box = button.getBoundingClientRect();
          return { width: Math.round(box.width), height: Math.round(box.height) };
        }),
        filterTarget: filterToggle ? (() => { const box = filterToggle.getBoundingClientRect(); return { width: Math.round(box.width), height: Math.round(box.height) }; })() : null,
        resultText: document.querySelector("#resultCount")?.textContent || "",
      };
    });

    const noOverflow = metrics.scrollWidth <= metrics.viewport;
    const correctColumns = metrics.columns === testCase.columns;
    const touchTargets = metrics.actionTargets.every((target) => target.height >= 44 && target.width >= 44)
      && (testCase.width > 760 || (metrics.filterTarget.height >= 44 && metrics.filterTarget.width >= 44));
    const pwaReady = testCase.name !== "mobile" || await page.evaluate(() => Promise.race([
      navigator.serviceWorker.ready.then((registration) => Boolean(registration.active)),
      new Promise((resolve) => setTimeout(() => resolve(false), 5000))
    ]));
    const passed = noOverflow && correctColumns && metrics.cards === expectedInitialItems && metrics.resultText.includes(`/ ${expectedCatalogItems}`) && touchTargets && pwaReady;
    failed ||= !passed;
    console.log(JSON.stringify({ name: testCase.name, passed, noOverflow, correctColumns, touchTargets, pwaReady, ...metrics }));

    if (testCase.name === "mobile" && screenshotDir) {
      await page.screenshot({ path: `${screenshotDir}/search-mobile-playwright.png` });
    }
    await page.close();
  }

  const featurePage = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const pageErrors = [];
  featurePage.on("pageerror", (error) => pageErrors.push(error.message));

  await featurePage.goto(`${baseUrl}/ranking.php`, { waitUntil: "networkidle" });
  await featurePage.locator("#podium [data-preview]").first().waitFor();
  const rankingItems = await featurePage.locator("[data-preview]").count();

  await featurePage.goto(`${baseUrl}/collection.php`, { waitUntil: "networkidle" });
  await featurePage.locator('[data-library="all"]').click();
  await featurePage.locator("#collectionGrid article").first().waitFor();
  const collectionItems = await featurePage.locator("#collectionGrid article").count();
  const featuresPassed = rankingItems === expectedCatalogItems && collectionItems === expectedCatalogItems && pageErrors.length === 0;
  failed ||= !featuresPassed;
  console.log(JSON.stringify({ name: "json-feature-pages", passed: featuresPassed, rankingItems, collectionItems, pageErrors }));
  await featurePage.close();

  await browser.close();
  process.exit(failed ? 1 : 0);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
