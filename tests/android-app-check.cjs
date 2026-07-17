const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");
const fs = require("node:fs");
const path = require("node:path");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const screenshotDir = process.env.RWD_SCREENSHOT_DIR || "";

(async () => {
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));
  await page.goto(`${baseUrl}/android-app.php`, { waitUntil: "networkidle" });
  const desktop = await page.evaluate(() => ({
    title: document.title,
    heading: document.querySelector("h1")?.textContent.trim(),
    navLabel: [...document.querySelectorAll(".nav-links a")].find((link) => link.href.includes("android-app"))?.textContent.trim(),
    navCurrent: document.querySelector('.nav-links a[href="android-app.php"]')?.getAttribute("aria-current"),
    download: document.querySelector('.app-hero-actions a[download]')?.getAttribute("href"),
    checksum: document.querySelector(".apk-checksum code")?.textContent.trim(),
    actions: [...document.querySelectorAll(".app-phone-ui b")].map((item) => item.textContent.trim()),
    pageText: document.querySelector("main")?.textContent || "",
  }));
  const apkResponse = await page.request.get(`${baseUrl}/downloads/apk/dragon-maiden-wallpaper-v2.1.0.apk`);
  const versionResponse = await page.request.get(`${baseUrl}/downloads/apk/VERSION`);

  const mobile = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await mobile.goto(`${baseUrl}/android-app.php`, { waitUntil: "networkidle" });
  const mobileState = await mobile.evaluate(() => ({
    viewport: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    downloadVisible: Boolean(document.querySelector('.app-hero-actions a[download]')),
    mobileNavVisible: getComputedStyle(document.querySelector(".nav-apk-mobile")).display !== "none",
  }));
  if (screenshotDir) {
    fs.mkdirSync(screenshotDir, { recursive: true });
    await mobile.screenshot({ path: path.join(screenshotDir, "android-app-mobile-playwright.png"), fullPage: true });
  }
  await mobile.close();

  const passed = pageErrors.length === 0
    && desktop.title.includes("Android 手機桌布 App APK")
    && desktop.heading.includes("裝進手機")
    && desktop.navLabel === "下載 APK"
    && desktop.navCurrent === "page"
    && desktop.download === "downloads/apk/dragon-maiden-wallpaper-v2.1.0.apk"
    && JSON.stringify(desktop.actions) === JSON.stringify(["立即套用", "下載圖片"])
    && desktop.pageText.includes("主畫面只有兩個操作按鈕")
    && !desktop.pageText.includes("排程已啟用")
    && /^[A-F0-9]{64}$/.test(desktop.checksum)
    && apkResponse.status() === 200
    && (await versionResponse.text()).trim() === "v2.1.0"
    && mobileState.scrollWidth <= mobileState.viewport
    && mobileState.downloadVisible
    && mobileState.mobileNavVisible;

  console.log(JSON.stringify({ passed, pageErrors, desktop, apkStatus: apkResponse.status(), mobileState }));
  await page.close();
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => { console.error(error); process.exit(1); });
