const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const expectedTitles = [
  "天門開財",
  "玄壇夜護",
  "金路護行",
  "八德聚財",
  "北辰玄帝",
  "玄武鎮流",
  "靜水玄心",
  "開路聚財咒",
  "心定運轉咒",
  "天賜財祿咒"
];

(async () => {
  const catalogResponse = await fetch(`${baseUrl}/api/catalog/list`);
  const catalog = await catalogResponse.json();
  const xuanling = catalog.wallpapers.filter((wallpaper) => wallpaper.topics.includes("xuanling"));
  if (!catalogResponse.ok || !catalog.ok || catalog.count !== catalog.wallpapers.length || xuanling.length !== 10) {
    throw new Error(`Unexpected catalog response: status=${catalogResponse.status}, total=${catalog.count}, xuanling=${xuanling.length}`);
  }
  if (xuanling.some((wallpaper) => !/xuanling-\d{2}\.jpg$/.test(wallpaper.file) || wallpaper.width !== 1920 || wallpaper.height !== 1080)) {
    throw new Error("玄靈 catalog metadata is invalid.");
  }

  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));

  await page.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  await page.locator("#catalogGrid .wallpaper-card").first().waitFor();
  if (await page.locator("#catalogGrid .wallpaper-card").count() !== Math.min(24, catalog.count)) throw new Error("The first progressive catalog batch did not render correctly.");

  await page.locator('[data-topic="xuanling"]').click();
  await page.waitForFunction(() => document.querySelectorAll("#catalogGrid .wallpaper-card").length === 10);
  const renderedTitles = await page.locator("#catalogGrid .wallpaper-card h3").allTextContents();
  if (renderedTitles.join("|") !== expectedTitles.join("|")) throw new Error(`Unexpected 玄靈 titles: ${renderedTitles.join("、")}`);

  await page.locator("#catalogSearch").fill("武財神");
  await page.waitForFunction(() => document.querySelectorAll("#catalogGrid .wallpaper-card").length === 4);
  await page.locator("#catalogSearch").fill("招財轉運咒");
  await page.waitForFunction(() => document.querySelectorAll("#catalogGrid .wallpaper-card").length === 3);
  await page.locator("#catalogSearch").fill("");
  await page.waitForFunction(() => document.querySelectorAll("#catalogGrid .wallpaper-card").length === 10);

  await page.locator('[data-preview="44"]').evaluate((button) => button.click());
  await page.locator("#previewDialog[open]").waitFor();
  await page.waitForFunction(() => {
    const image = document.querySelector("#dialogImage");
    return image?.complete && image.naturalWidth > 0;
  });
  const preview = await page.locator("#dialogImage").evaluate((image) => ({
    src: image.getAttribute("src"),
    width: image.naturalWidth,
    height: image.naturalHeight
  }));
  if (!preview.src.endsWith("xuanling-10.jpg") || preview.width !== 1920 || preview.height !== 1080) {
    throw new Error(`Unexpected preview image: ${JSON.stringify(preview)}`);
  }
  if (pageErrors.length) throw new Error(`Page errors: ${pageErrors.join(" | ")}`);

  await browser.close();
  console.log(JSON.stringify({ passed: true, total: catalog.count, xuanling: xuanling.length, renderedTitles, preview }));
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
