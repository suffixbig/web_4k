const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");
const fs = require("fs");
const path = require("path");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const screenshotDir = process.env.RANKING_SCREENSHOT_DIR || "";

(async () => {
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const pageErrors = [];
  const results = [];

  for (const viewport of [{ name: "desktop", width: 1440, height: 1000 }, { name: "mobile", width: 390, height: 844 }]) {
    const page = await browser.newPage({ viewport });
    page.on("pageerror", (error) => pageErrors.push(`${viewport.name}: ${error.message}`));
    await page.goto(`${baseUrl}/ranking.php`, { waitUntil: "networkidle" });
    await page.locator("#podium [data-preview]").first().waitFor();

    const firstPage = await page.evaluate(() => ({
      podium: document.querySelectorAll("#podium [data-preview]").length,
      rows: document.querySelectorAll("#rankingList [data-preview]").length,
      ranks: [...document.querySelectorAll("#rankingList > button > b")].map((item) => Number(item.textContent)),
      summary: document.querySelector(".ranking-pagination__summary")?.textContent || "",
      pageButtons: document.querySelectorAll('[data-page-number]').length,
      minimumTarget: Math.min(...[...document.querySelectorAll("#rankingPagination button")].map((button) => button.getBoundingClientRect().height)),
      overflow: document.documentElement.scrollWidth > innerWidth,
      note: document.querySelector(".rank-note")?.textContent || ""
    }));

    if (screenshotDir) {
      fs.mkdirSync(screenshotDir, { recursive: true });
      await page.screenshot({
        path: path.join(screenshotDir, `ranking-${viewport.name}-page1.png`),
        fullPage: true
      });
    }

    await page.locator('[data-page-number="2"]').click();
    const secondPage = await page.evaluate(() => ({
      podiumHidden: document.querySelector("#podium").hidden,
      rows: document.querySelectorAll("#rankingList [data-preview]").length,
      ranks: [...document.querySelectorAll("#rankingList > button > b")].map((item) => Number(item.textContent)),
      summary: document.querySelector(".ranking-pagination__summary")?.textContent || "",
      current: document.querySelector('[data-page-number][aria-current="page"]')?.dataset.pageNumber
    }));

    if (screenshotDir) {
      await page.screenshot({
        path: path.join(screenshotDir, `ranking-${viewport.name}-page2.png`),
        fullPage: true
      });
    }

    await page.locator('[data-rank="views"]').click();
    const modeReset = await page.evaluate(() => ({
      page: document.querySelector('[data-page-number][aria-current="page"]')?.dataset.pageNumber,
      firstRank: Number(document.querySelector("#rankingList > button > b")?.textContent),
      totalVisible: document.querySelectorAll("[data-preview]").length
    }));

    results.push({ viewport: viewport.name, firstPage, secondPage, modeReset });
    await page.close();
  }

  await browser.close();
  const passed = pageErrors.length === 0 && results.every(({ firstPage, secondPage, modeReset }) =>
    firstPage.podium === 3
    && firstPage.rows === 47
    && firstPage.ranks[0] === 4
    && firstPage.ranks.at(-1) === 50
    && firstPage.summary.includes("1–50")
    && firstPage.summary.includes("100")
    && firstPage.pageButtons === 2
    && firstPage.minimumTarget >= 44
    && !firstPage.overflow
    && firstPage.note.includes("模擬資料")
    && secondPage.podiumHidden
    && secondPage.rows === 50
    && secondPage.ranks[0] === 51
    && secondPage.ranks.at(-1) === 100
    && secondPage.summary.includes("51–100")
    && secondPage.current === "2"
    && modeReset.page === "1"
    && modeReset.firstRank === 4
    && modeReset.totalVisible === 50
  );

  console.log(JSON.stringify({ passed, pageErrors, results }, null, 2));
  process.exit(passed ? 0 : 1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
