const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");
const fs = require("node:fs");
const path = require("node:path");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const screenshotDir = process.env.SEARCH_UX_SCREENSHOT_DIR || "";

(async () => {
  const catalogResponse = await fetch(`${baseUrl}/api/catalog/list`);
  const catalog = await catalogResponse.json();
  if (!catalogResponse.ok || !catalog.ok) throw new Error("Catalog API did not load.");

  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));
  await page.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  await page.locator("#catalogGrid .wallpaper-card").first().waitFor();

  const initialCount = await page.locator("#catalogGrid .wallpaper-card").count();
  if (initialCount !== Math.min(24, catalog.count)) throw new Error(`Unexpected first batch: ${initialCount}`);
  if (catalog.count > 24) {
    await page.locator("#catalogLoadMore").click();
    const secondCount = await page.locator("#catalogGrid .wallpaper-card").count();
    if (secondCount !== Math.min(48, catalog.count)) throw new Error(`Unexpected second batch: ${secondCount}`);
    await page.evaluate(() => window.scrollTo(0, 500));
    await page.reload({ waitUntil: "networkidle" });
    await page.locator("#catalogGrid .wallpaper-card").first().waitFor();
    await page.waitForTimeout(120);
    const restored = await page.evaluate(() => ({ cards: document.querySelectorAll("#catalogGrid .wallpaper-card").length, scrollY: window.scrollY }));
    if (restored.cards !== Math.min(48, catalog.count) || restored.scrollY < 400) throw new Error(`Catalog view state was not restored: ${JSON.stringify(restored)}`);
  }

  await page.locator('[data-topic="xuanling"]').click();
  await page.waitForFunction(() => document.querySelectorAll("#catalogGrid .wallpaper-card").length === 10);
  const filteredState = await page.evaluate(() => ({
    topic: new URL(location.href).searchParams.get("topic"),
    summary: document.querySelector("#activeFilterSummary")?.textContent.replace(/\s+/g, " ").trim(),
    result: document.querySelector("#resultCount")?.textContent,
    cardVotes: document.querySelectorAll("#catalogGrid .card-vote").length,
    playlistButtons: document.querySelectorAll("#catalogGrid [data-playlist]").length,
  }));
  if (filteredState.topic !== "xuanling" || !filteredState.summary.includes("玄靈") || !filteredState.result.includes("10 / 10") || filteredState.cardVotes || filteredState.playlistButtons) {
    throw new Error(`Filtered state is invalid: ${JSON.stringify(filteredState)}`);
  }

  await page.locator("#catalogGrid [data-preview]").first().click();
  await page.locator("#previewDialog[open]").waitFor();
  const firstDialogTitle = (await page.locator("#dialogTitle").textContent()).trim();
  await page.locator("#dialogNext").click();
  const secondDialogTitle = (await page.locator("#dialogTitle").textContent()).trim();
  if (!firstDialogTitle || firstDialogTitle === secondDialogTitle) throw new Error("Preview next navigation did not advance.");
  await page.locator("#closeDialog").click();

  await page.locator("#clearAllFilters").click();
  await page.waitForFunction(() => document.querySelectorAll("#catalogGrid .wallpaper-card").length === Math.min(24, window.__catalogCount || 24));
  const clearedState = await page.evaluate(() => ({
    query: location.search,
    summaryHidden: document.querySelector("#activeFilterSummary").hidden,
    cards: document.querySelectorAll("#catalogGrid .wallpaper-card").length,
  }));
  if (clearedState.query || !clearedState.summaryHidden || clearedState.cards !== Math.min(24, catalog.count)) throw new Error(`Clear filters failed: ${JSON.stringify(clearedState)}`);

  await page.locator('[data-topic="all"]').focus();
  await page.keyboard.press("ArrowRight");
  if (new URL(page.url()).searchParams.get("topic") !== "game") throw new Error("Category tabs do not support arrow-key navigation.");
  await page.keyboard.press("Home");
  if (new URL(page.url()).searchParams.has("topic")) throw new Error("Category Home key did not return to all topics.");

  await page.locator("#catalogSearch").fill("完全不存在的桌布條件XYZ");
  await page.waitForTimeout(260);
  await page.locator(".empty-suggestions").waitFor();
  await page.locator('[data-suggest="玄靈"]').click();
  await page.locator("#catalogGrid .wallpaper-card").first().waitFor();

  const mobile = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await mobile.goto(`${baseUrl}/search.php?topic=xuanling&color=black`, { waitUntil: "networkidle" });
  await mobile.locator("#catalogGrid .wallpaper-card").first().waitFor();
  await mobile.locator("#mobileFilterToggle").click();
  await mobile.locator("#catalogFilterPanel.is-open").waitFor();
  await mobile.waitForTimeout(320);
  const mobileState = await mobile.evaluate(() => {
    const toggle = document.querySelector("#mobileFilterToggle").getBoundingClientRect();
    const close = document.querySelector("#closeFilterPanel").getBoundingClientRect();
    const panel = document.querySelector("#catalogFilterPanel");
    return {
      viewport: window.innerWidth,
      viewportHeight: window.innerHeight,
      scrollWidth: document.documentElement.scrollWidth,
      role: panel.getAttribute("role"),
      modal: panel.getAttribute("aria-modal"),
      bodyLocked: document.body.classList.contains("filter-sheet-open"),
      toggle: { width: toggle.width, height: toggle.height },
      close: { width: close.width, height: close.height },
      panelBox: (() => { const box = panel.getBoundingClientRect(); return { top: box.top, bottom: box.bottom, height: box.height }; })(),
      summary: document.querySelector("#activeFilterSummary")?.textContent.replace(/\s+/g, " ").trim(),
    };
  });
  if (mobileState.scrollWidth > mobileState.viewport || mobileState.role !== "dialog" || mobileState.modal !== "true" || !mobileState.bodyLocked || mobileState.toggle.width < 44 || mobileState.toggle.height < 44 || mobileState.close.width < 44 || mobileState.close.height < 44 || mobileState.panelBox.height < 300 || mobileState.panelBox.top >= mobileState.viewportHeight || !mobileState.summary.includes("玄靈") || !mobileState.summary.includes("深色")) {
    throw new Error(`Mobile filter sheet is invalid: ${JSON.stringify(mobileState)}`);
  }
  await mobile.locator("#applyFilterPanel").focus();
  await mobile.keyboard.press("Tab");
  if (await mobile.evaluate(() => document.activeElement?.id) !== "closeFilterPanel") throw new Error("Mobile filter focus is not trapped inside the dialog.");
  if (screenshotDir) {
    fs.mkdirSync(screenshotDir, { recursive: true });
    await mobile.screenshot({ path: path.join(screenshotDir, "search-mobile-filter.png") });
  }
  await mobile.locator("#applyFilterPanel").click();
  if (await mobile.locator("#catalogFilterPanel").getAttribute("aria-hidden") !== "true") throw new Error("Mobile filter sheet did not close.");

  if (pageErrors.length) throw new Error(`Page errors: ${pageErrors.join(" | ")}`);
  console.log(JSON.stringify({ passed: true, total: catalog.count, initialCount, filteredState, clearedState, mobileState }));
  await page.close();
  await mobile.close();
  await browser.close();
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
