const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const expectedTitles = [
  "晨光浮島",
  "花海靜湖",
  "森林心泉",
  "月華藍境",
  "雲端天境",
  "水晶虹泉",
  "紫暮星谷",
  "金色樂園灣",
  "極光靜心湖",
  "櫻花幸福境",
];

(async () => {
  const catalogResponse = await fetch(`${baseUrl}/api/catalog/list`);
  const catalog = await catalogResponse.json();
  const wellbeing = catalog.wallpapers.filter((wallpaper) => wallpaper.topics.includes("wellbeing"));
  if (!catalogResponse.ok || !catalog.ok || catalog.count !== catalog.wallpapers.length || wellbeing.length !== 10) {
    throw new Error(`Unexpected catalog response: status=${catalogResponse.status}, total=${catalog.count}, wellbeing=${wellbeing.length}`);
  }
  if (wellbeing.some((wallpaper, index) => (
    wallpaper.title !== expectedTitles[index]
    || !wallpaper.file.endsWith(`mind-happiness-${String(index + 1).padStart(2, "0")}.jpg`)
    || wallpaper.width !== 1920
    || wallpaper.height !== 1080
    || !Array.isArray(wallpaper.colors)
    || wallpaper.colors.length < 1
    || wallpaper.colors.length > 2
  ))) {
    throw new Error("心靈幸福感 catalog metadata is invalid.");
  }

  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));

  await page.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  await page.locator('[data-topic="wellbeing"]').click();
  await page.waitForFunction(() => document.querySelectorAll("#catalogGrid .wallpaper-card").length === 10);
  const renderedTitles = await page.locator("#catalogGrid .wallpaper-card h3").allTextContents();
  if (renderedTitles.join("|") !== expectedTitles.join("|")) {
    throw new Error(`Unexpected 幸福仙境 titles: ${renderedTitles.join("、")}`);
  }

  await page.locator('[data-preview="45"]').click();
  await page.locator("#previewDialog[open]").waitFor();
  if ((await page.locator("#dialogPosition").textContent()).trim() !== "1 / 10") throw new Error("Initial preview position is incorrect.");

  const controls = await page.locator("#dialogPrev, #dialogNext").evaluateAll((buttons) => buttons.map((button) => ({
    width: button.getBoundingClientRect().width,
    height: button.getBoundingClientRect().height,
    label: button.getAttribute("aria-label"),
  })));
  if (controls.some((button) => button.width < 48 || button.height < 48 || !button.label.includes("桌布"))) {
    throw new Error(`Preview controls are not accessible: ${JSON.stringify(controls)}`);
  }

  await page.locator("#dialogNext").click();
  if ((await page.locator("#dialogTitle").textContent()).trim() !== "花海靜湖") throw new Error("Next button did not advance the preview.");
  if ((await page.locator("#dialogPosition").textContent()).trim() !== "2 / 10") throw new Error("Next preview position is incorrect.");

  await page.keyboard.press("ArrowLeft");
  if ((await page.locator("#dialogTitle").textContent()).trim() !== "晨光浮島") throw new Error("ArrowLeft did not move to the previous preview.");
  await page.keyboard.press("ArrowLeft");
  const wrapped = await page.locator("#dialogImage").evaluate((image) => ({
    title: document.querySelector("#dialogTitle").textContent.trim(),
    position: document.querySelector("#dialogPosition").textContent.trim(),
    src: image.getAttribute("src"),
    width: image.naturalWidth,
    height: image.naturalHeight,
  }));
  if (wrapped.title !== "櫻花幸福境" || wrapped.position !== "10 / 10" || !wrapped.src.endsWith("mind-happiness-10.jpg") || wrapped.width !== 1920 || wrapped.height !== 1080) {
    throw new Error(`Preview wrap is incorrect: ${JSON.stringify(wrapped)}`);
  }

  const mobile = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await mobile.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  await mobile.locator('[data-topic="wellbeing"]').click();
  await mobile.locator('[data-preview="45"]').click();
  const mobileMetrics = await mobile.locator("#previewDialog").evaluate((dialog) => ({
    viewport: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    previous: document.querySelector("#dialogPrev").getBoundingClientRect().toJSON(),
    next: document.querySelector("#dialogNext").getBoundingClientRect().toJSON(),
    open: dialog.open,
  }));
  if (!mobileMetrics.open || mobileMetrics.scrollWidth > mobileMetrics.viewport || mobileMetrics.previous.width < 48 || mobileMetrics.previous.height < 48 || mobileMetrics.next.width < 48 || mobileMetrics.next.height < 48) {
    throw new Error(`Mobile preview controls are invalid: ${JSON.stringify(mobileMetrics)}`);
  }

  if (pageErrors.length) throw new Error(`Page errors: ${pageErrors.join(" | ")}`);
  await browser.close();
  console.log(JSON.stringify({ passed: true, total: catalog.count, wellbeing: wellbeing.length, renderedTitles, controls, wrapped, mobileMetrics }));
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
