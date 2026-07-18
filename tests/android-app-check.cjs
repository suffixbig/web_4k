const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");
const fs = require("node:fs");
const path = require("node:path");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const screenshotDir = process.env.RWD_SCREENSHOT_DIR || "";
const publishedVersion = fs.readFileSync(path.join(__dirname, "..", "downloads", "apk", "VERSION"), "utf8").trim();
const versionName = publishedVersion.replace(/^v/, "");
const androidRoot = path.resolve(__dirname, "..", "..", "web_4kapk");
const androidLayout = fs.readFileSync(path.join(androidRoot, "app", "src", "main", "res", "layout", "activity_main.xml"), "utf8");
const advancedFilterLayout = fs.readFileSync(path.join(androidRoot, "app", "src", "main", "res", "layout", "panel_advanced_filters.xml"), "utf8");
const actionSheetLayout = fs.readFileSync(path.join(androidRoot, "app", "src", "main", "res", "layout", "dialog_wallpaper_actions.xml"), "utf8");
const androidActivity = fs.readFileSync(path.join(androidRoot, "app", "src", "main", "java", "com", "shuailong", "mengji", "wallpaper", "MainActivity.java"), "utf8");
const androidVersion = fs.readFileSync(path.join(androidRoot, "VERSION"), "utf8").trim();

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
  const apkResponse = await page.request.get(`${baseUrl}/downloads/apk/dragon-maiden-wallpaper-v${versionName}.apk`);
  const versionResponse = await page.request.get(`${baseUrl}/downloads/apk/VERSION`);

  const mobile = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await mobile.goto(`${baseUrl}/android-app.php`, { waitUntil: "networkidle" });
  await mobile.locator("#mobileMenuToggle").click();
  const mobileState = await mobile.evaluate(() => ({
    viewport: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    downloadVisible: Boolean(document.querySelector('.app-hero-actions a[download]')),
    brandVisible: document.querySelector(".site-header .brand-mark")?.getBoundingClientRect().width >= 30,
    mobileNavVisible: getComputedStyle(document.querySelector("#primaryNavLinks")).display !== "none",
    mobileNavItems: [...document.querySelectorAll("#primaryNavLinks a")].map((link) => link.textContent.trim()),
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
    && desktop.download === `downloads/apk/dragon-maiden-wallpaper-v${versionName}.apk`
    && JSON.stringify(desktop.actions) === JSON.stringify(["立即套用", "下載圖片"])
    && desktop.pageText.includes("首屏搜尋與漸進篩選")
    && desktop.pageText.includes("兩者皆套")
    && desktop.pageText.includes("底部套用面板")
    && desktop.pageText.includes("完整顯示為預設")
    && desktop.pageText.includes("目前設定雙預覽")
    && !desktop.pageText.includes("排程已啟用")
    && /^[A-F0-9]{64}$/.test(desktop.checksum)
    && apkResponse.status() === 200
    && (await versionResponse.text()).trim() === publishedVersion
    && mobileState.scrollWidth <= mobileState.viewport
    && mobileState.downloadVisible
    && mobileState.brandVisible
    && mobileState.mobileNavVisible
    && mobileState.mobileNavItems.length === 7
    && androidVersion === publishedVersion
    && androidLayout.includes('android:id="@+id/catalogGrid"')
    && androidLayout.includes('android:id="@+id/advancedFilterStub"')
    && androidLayout.includes('android:layout="@layout/panel_advanced_filters"')
    && advancedFilterLayout.includes('android:id="@+id/topicSpinner"')
    && advancedFilterLayout.includes('android:id="@+id/contentTypeSpinner"')
    && androidLayout.includes('android:id="@+id/retryCatalogButton"')
    && androidLayout.includes('android:id="@+id/currentHomeBadge"')
    && !androidLayout.includes('android:id="@+id/deviceSpinner"')
    && actionSheetLayout.includes('android:id="@+id/cropFit"')
    && actionSheetLayout.includes('android:id="@+id/cropFill"')
    && actionSheetLayout.includes('android:id="@+id/applyButton"')
    && androidActivity.includes("RESULTS_BATCH_SIZE = 18")
    && androidActivity.includes("openActionSheet(item)");

  console.log(JSON.stringify({ passed, pageErrors, desktop, apkStatus: apkResponse.status(), mobileState }));
  await page.close();
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => { console.error(error); process.exit(1); });
