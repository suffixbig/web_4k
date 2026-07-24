const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const visibleColors = ["red", "orange", "yellow", "green", "blue", "purple", "black", "white", "brown"];

const idsForColor = (wallpapers, color) => wallpapers
  .filter((wallpaper) => wallpaper.colors.includes(color))
  .map((wallpaper) => wallpaper.id)
  .sort((a, b) => Number(a) - Number(b));

const renderedIds = async (page) => (await page.locator("#catalogGrid [data-preview]").evaluateAll((buttons) =>
  buttons.map((button) => button.dataset.preview)
)).sort((a, b) => Number(a) - Number(b));

async function loadAllResults(page) {
  while (await page.locator("#catalogLoadMore").isVisible()) {
    await page.locator("#catalogLoadMore").click();
  }
}

(async () => {
  const catalogResponse = await fetch(`${baseUrl}/api/catalog/list`);
  const catalog = await catalogResponse.json();
  if (!catalogResponse.ok || !catalog.ok) throw new Error("Catalog API did not load.");
  for (const [query, color] of [["金色", "yellow"], ["橘色", "orange"]]) {
    const response = await fetch(`${baseUrl}/api/catalog/list?q=${encodeURIComponent(query)}`);
    const result = await response.json();
    const expected = idsForColor(catalog.wallpapers, color);
    const actual = result.wallpapers.map((wallpaper) => wallpaper.id).sort((a, b) => Number(a) - Number(b));
    if (!response.ok || !result.ok || actual.join(",") !== expected.join(",")) {
      throw new Error(`${query} API filter mismatch: expected ${expected}, received ${actual}`);
    }
  }

  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));
  await page.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  await page.locator("#catalogGrid .wallpaper-card").first().waitFor();

  const results = {};
  for (const color of visibleColors) {
    await page.locator(`[data-color="${color}"]`).click();
    const expected = idsForColor(catalog.wallpapers, color);
    await page.waitForFunction((count) => document.querySelectorAll("#catalogGrid .wallpaper-card").length === count, Math.min(24, expected.length));
    await loadAllResults(page);
    const actual = await renderedIds(page);
    if (actual.join(",") !== expected.join(",")) throw new Error(`${color} filter mismatch: expected ${expected}, received ${actual}`);
    results[color] = actual.length;
  }

  if (!idsForColor(catalog.wallpapers, "red").includes("25") || !idsForColor(catalog.wallpapers, "orange").includes("25")) {
    throw new Error("Dual-color wallpaper 25 is not represented in both red and orange categories.");
  }
  if (pageErrors.length) throw new Error(`Page errors: ${pageErrors.join(" | ")}`);

  const mobilePage = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await mobilePage.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  await mobilePage.locator("#catalogGrid .wallpaper-card").first().waitFor();
  await mobilePage.locator("#mobileFilterToggle").click();
  await mobilePage.locator("#catalogFilterPanel.is-open").waitFor();
  const mobileMetrics = await mobilePage.evaluate(() => ({
    viewport: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    colorButtons: [...document.querySelectorAll("[data-color]")].map((button) => {
      const box = button.getBoundingClientRect();
      return { width: Math.round(box.width), height: Math.round(box.height) };
    })
  }));
  if (mobileMetrics.scrollWidth > mobileMetrics.viewport || mobileMetrics.colorButtons.length !== 10 || mobileMetrics.colorButtons.some((button) => button.width < 44 || button.height < 44)) {
    throw new Error(`Mobile color controls are invalid: ${JSON.stringify(mobileMetrics)}`);
  }

  await browser.close();
  console.log(JSON.stringify({ passed: true, results, dualColorExample: "25", mobileMetrics }));
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
