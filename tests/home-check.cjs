const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");
const fs = require("node:fs");
const path = require("node:path");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const screenshotDir = process.env.HOME_SCREENSHOT_DIR || "";
const expectedNav = ["首頁", "分類搜尋", "我的收藏", "下載排行榜", "QA", "下載 APK", "AI 安裝技能"];

function descending(values) {
  return values.every((value, index) => index === 0 || values[index - 1] >= value);
}

async function loadLazyImages(page) {
  const height = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < height; y += 600) {
    await page.evaluate((top) => window.scrollTo(0, top), y);
    await page.waitForTimeout(80);
  }
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(250);
}

(async () => {
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const pageErrors = [];
  const desktop = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  desktop.on("pageerror", (error) => pageErrors.push(error.message));
  await desktop.goto(`${baseUrl}/index.php`, { waitUntil: "networkidle" });
  await desktop.locator("#homeCarouselToggle").click();
  await loadLazyImages(desktop);

  const desktopState = await desktop.evaluate(() => {
    const latest = [...document.querySelectorAll('[data-home-list="latest"] .home-wallpaper-card')];
    const popular = [...document.querySelectorAll('[data-home-list="popular"] .home-wallpaper-card')];
    const activeFeatureCopy = document.querySelector("[data-home-feature-slide].is-active .home-feature-copy");
    const activeFeatureRect = activeFeatureCopy.getBoundingClientRect();
    return {
      heading: document.querySelector("h1")?.textContent.replace(/\s+/g, " ").trim(),
      latestCount: latest.length,
      popularCount: popular.length,
      latestDates: latest.map((card) => card.dataset.created),
      popularScores: popular.map((card) => Number(card.dataset.score)),
      featureIds: [...document.querySelectorAll("[data-home-feature-slide]")].map((slide) => slide.dataset.wallpaperId),
      featureTitles: [...document.querySelectorAll("[data-home-feature-slide] h2")].map((title) => title.textContent.replace(/\s+/g, " ").trim()),
      activeFeatureId: document.querySelector("[data-home-feature-slide].is-active")?.dataset.wallpaperId,
      activeFeatureVisible: activeFeatureRect.width > 0 && activeFeatureRect.height > 0 && activeFeatureRect.top >= 0 && activeFeatureRect.bottom <= window.innerHeight,
      featureLineBreaks: document.querySelectorAll("[data-home-feature-slide] h2 br").length,
      featureDotCount: document.querySelectorAll("[data-home-go-slide]").length,
      carouselPaused: document.querySelector("#homeCarouselToggle")?.getAttribute("aria-pressed"),
      apkNavHasIcon: Boolean(document.querySelector('.nav-links a[href="android-app.php"] .nav-item-icon')),
      skillNavHasIcon: Boolean(document.querySelector('.nav-cta[href="ai-skill.php"] i')),
      searchAction: document.querySelector(".home-search-panel")?.getAttribute("action"),
      imagesLoaded: [...document.querySelectorAll(".home-wallpaper-card img")].every((image) => image.complete && image.naturalWidth > 0),
      desktopColumns: getComputedStyle(document.querySelector(".home-wallpaper-grid")).gridTemplateColumns.split(" ").length,
    };
  });

  await desktop.locator("#homeNextSlide").click();
  const carouselState = await desktop.evaluate(() => ({
    activeFeatureId: document.querySelector("[data-home-feature-slide].is-active")?.dataset.wallpaperId,
    hiddenSlides: document.querySelectorAll('[data-home-feature-slide][aria-hidden="true"]').length,
    inertSlides: document.querySelectorAll("[data-home-feature-slide][inert]").length,
    activeDot: document.querySelector('[data-home-go-slide][aria-current="true"]')?.dataset.homeGoSlide,
    status: document.querySelector("#homeSlideStatus")?.textContent,
  }));
  await desktop.locator('[data-home-go-slide="0"]').click();

  const mobile = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  mobile.on("pageerror", (error) => pageErrors.push(error.message));
  await mobile.goto(`${baseUrl}/index.php`, { waitUntil: "networkidle" });
  await mobile.locator("#homeCarouselToggle").click();
  await loadLazyImages(mobile);
  await mobile.locator("#mobileMenuToggle").click();
  const mobileState = await mobile.evaluate(() => {
    const brand = document.querySelector(".site-header .brand-mark").getBoundingClientRect();
    const searchInput = document.querySelector("#homeSearch").getBoundingClientRect();
    const searchButton = document.querySelector(".home-search-panel button").getBoundingClientRect();
    const carouselButton = document.querySelector("#homeNextSlide").getBoundingClientRect();
    return {
      viewport: window.innerWidth,
      scrollWidth: document.documentElement.scrollWidth,
      brand: { width: Math.round(brand.width), height: Math.round(brand.height) },
      navOpen: getComputedStyle(document.querySelector("#primaryNavLinks")).display !== "none",
      navItems: [...document.querySelectorAll("#primaryNavLinks a")].map((link) => link.textContent.trim()),
      iconNavItems: [...document.querySelectorAll('#primaryNavLinks a[href="android-app.php"], #primaryNavLinks a[href="ai-skill.php"]')].every((link) => Boolean(link.querySelector(".nav-item-icon"))),
      mobileColumns: getComputedStyle(document.querySelector(".home-wallpaper-grid")).gridTemplateColumns.split(" ").length,
      searchTargets: { input: Math.round(searchInput.height), button: Math.round(searchButton.height) },
      carouselTarget: { width: Math.round(carouselButton.width), height: Math.round(carouselButton.height) },
      featureCount: document.querySelectorAll("[data-home-feature-slide]").length,
      featureDotCount: document.querySelectorAll("[data-home-go-slide]").length,
      activeFeatureId: document.querySelector("[data-home-feature-slide].is-active")?.dataset.wallpaperId,
      latestCount: document.querySelectorAll('[data-home-list="latest"] .home-wallpaper-card').length,
      popularCount: document.querySelectorAll('[data-home-list="popular"] .home-wallpaper-card').length,
    };
  });

  if (screenshotDir) {
    fs.mkdirSync(screenshotDir, { recursive: true });
    await desktop.screenshot({ path: path.join(screenshotDir, "home-desktop.png"), fullPage: true });
    await mobile.screenshot({ path: path.join(screenshotDir, "home-mobile-menu.png"), fullPage: true });
    await mobile.locator("#mobileMenuToggle").click();
    await mobile.waitForTimeout(200);
    await mobile.screenshot({ path: path.join(screenshotDir, "home-mobile.png"), fullPage: true });
  }

  const searchProbe = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await searchProbe.goto(`${baseUrl}/index.php`, { waitUntil: "networkidle" });
  await searchProbe.locator("#homeSearch").fill("藍色手機直式 AI 龍");
  await Promise.all([
    searchProbe.waitForURL(/search\.php\?q=/),
    searchProbe.locator(".home-search-panel button").click(),
  ]);
  const searchValue = await searchProbe.locator("#catalogSearch").inputValue();

  const passed = pageErrors.length === 0
    && desktopState.heading.includes("專案特色")
    && desktopState.latestCount === 8
    && desktopState.popularCount === 8
    && JSON.stringify(desktopState.featureIds) === JSON.stringify(["1", "2", "3", "4", "5"])
    && desktopState.featureTitles.length === 5
    && desktopState.featureTitles.every((title) => title.length >= 12)
    && desktopState.activeFeatureId === "1"
    && desktopState.activeFeatureVisible
    && desktopState.featureLineBreaks === 5
    && desktopState.featureDotCount === 5
    && desktopState.carouselPaused === "true"
    && desktopState.apkNavHasIcon
    && desktopState.skillNavHasIcon
    && carouselState.activeFeatureId === "2"
    && carouselState.hiddenSlides === 4
    && carouselState.inertSlides === 4
    && carouselState.activeDot === "1"
    && carouselState.status.includes("第 2 張")
    && descending(desktopState.latestDates)
    && descending(desktopState.popularScores)
    && desktopState.searchAction === "search.php"
    && desktopState.imagesLoaded
    && desktopState.desktopColumns === 4
    && mobileState.scrollWidth <= mobileState.viewport
    && mobileState.brand.width >= 30
    && mobileState.brand.height >= 30
    && mobileState.navOpen
    && JSON.stringify(mobileState.navItems) === JSON.stringify(expectedNav)
    && mobileState.iconNavItems
    && mobileState.mobileColumns === 2
    && mobileState.searchTargets.input >= 44
    && mobileState.searchTargets.button >= 44
    && mobileState.carouselTarget.width >= 44
    && mobileState.carouselTarget.height >= 44
    && mobileState.featureCount === 5
    && mobileState.featureDotCount === 5
    && mobileState.activeFeatureId === "1"
    && mobileState.latestCount === 8
    && mobileState.popularCount === 8
    && searchValue === "藍色手機直式 AI 龍";

  console.log(JSON.stringify({ passed, pageErrors, desktopState, carouselState, mobileState, searchValue }));
  await desktop.close();
  await mobile.close();
  await searchProbe.close();
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => { console.error(error); process.exit(1); });
