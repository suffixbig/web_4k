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
      featureTitles: [...document.querySelectorAll("[data-home-feature-slide] h1, [data-home-feature-slide] h2")].map((title) => title.textContent.replace(/\s+/g, " ").trim()),
      activeFeatureId: document.querySelector("[data-home-feature-slide].is-active")?.dataset.wallpaperId,
      activeFeatureVisible: activeFeatureRect.width > 0 && activeFeatureRect.height > 0 && activeFeatureRect.top >= 0 && activeFeatureRect.bottom <= window.innerHeight,
      featureLineBreaks: document.querySelectorAll("[data-home-feature-slide] h1 br, [data-home-feature-slide] h2 br").length,
      featureDotCount: document.querySelectorAll("[data-home-go-slide]").length,
      heroBodyCount: document.querySelectorAll("[data-home-feature-slide] .home-feature-copy > p:not(.home-feature-kicker)").length,
      heroSecondaryCount: document.querySelectorAll(".home-feature-secondary, .home-feature-credit").length,
      restoredPromoSections: Boolean(document.querySelector(".ai-section") && document.querySelector(".home-links")),
      carouselToggleMissing: !document.querySelector("#homeCarouselToggle"),
      navSearch: Boolean(document.querySelector(".nav-search-link[href=\"search.php\"]")),
      navSearchIsFirstAction: document.querySelector(".nav-shell > .nav-search-link")?.nextElementSibling?.id === "primaryNavLinks",
      navSearchStyle: (() => {
        const link = document.querySelector(".nav-search-link");
        const style = getComputedStyle(link);
        return { borderWidth: style.borderTopWidth, background: style.backgroundColor, color: style.color };
      })(),
      apkNavHasIcon: Boolean(document.querySelector('.nav-links a[href="android-app.php"] .nav-item-icon')),
      skillNavHasIcon: Boolean(document.querySelector('.nav-cta[href="ai-skill.php"] i')),
      searchAction: document.querySelector(".home-search-panel")?.getAttribute("action"),
      imagesLoaded: [...document.querySelectorAll(".home-wallpaper-card img")].every((image) => image.complete && image.naturalWidth > 0),
      desktopColumns: getComputedStyle(document.querySelector(".home-wallpaper-grid")).gridTemplateColumns.split(" ").length,
      topSearchTop: Math.round(document.querySelector(".home-top-search").getBoundingClientRect().top),
      topSearchBottom: Math.round(document.querySelector(".home-top-search").getBoundingClientRect().bottom),
      carouselTop: Math.round(document.querySelector(".home-feature-carousel").getBoundingClientRect().top),
      heroHeight: Math.round(document.querySelector(".home-feature-carousel").getBoundingClientRect().height),
      searchInsideCarousel: document.querySelector(".home-top-search")?.parentElement?.matches(".home-feature-carousel"),
      searchOverlayPosition: getComputedStyle(document.querySelector(".home-top-search")).position,
      deviceLabelFontSize: getComputedStyle(document.querySelector(".home-wallpaper-device")).fontSize,
      glassEffects: (() => {
        const device = getComputedStyle(document.querySelector(".home-wallpaper-device"));
        const advertisement = getComputedStyle(document.querySelector(".ad-slot__meta"));
        return {
          deviceBackdrop: device.backdropFilter || device.webkitBackdropFilter,
          advertisementBackdrop: advertisement.backdropFilter || advertisement.webkitBackdropFilter,
        };
      })(),
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
  await loadLazyImages(mobile);
  await mobile.locator("[data-home-carousel]").evaluate((element) => {
    element.dispatchEvent(new PointerEvent("pointerdown", { bubbles: true, pointerType: "touch", pointerId: 9, clientX: 280, clientY: 280 }));
    element.dispatchEvent(new PointerEvent("pointerup", { bubbles: true, pointerType: "touch", pointerId: 9, clientX: 110, clientY: 280 }));
  });
  await mobile.waitForTimeout(80);
  const swipeActiveFeatureId = await mobile.locator("[data-home-feature-slide].is-active").getAttribute("data-wallpaper-id");
  await mobile.locator('[data-home-go-slide="0"]').click();
  await mobile.locator("#mobileMenuToggle").click();
  const mobileState = await mobile.evaluate(() => {
    const brand = document.querySelector(".site-header .brand-mark").getBoundingClientRect();
    const searchInput = document.querySelector("#homeSearch").getBoundingClientRect();
    const searchButton = document.querySelector(".home-search-panel button").getBoundingClientRect();
    const carouselButton = document.querySelector("#homeNextSlide").getBoundingClientRect();
    return {
      viewport: window.innerWidth,
      viewportHeight: window.innerHeight,
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
      heroHeight: Math.round(document.querySelector('.home-feature-carousel').getBoundingClientRect().height),
      deviceLabelFontSize: getComputedStyle(document.querySelector(".home-wallpaper-device")).fontSize,
      topSearchTop: Math.round(document.querySelector(".home-top-search").getBoundingClientRect().top),
      topSearchBottom: Math.round(document.querySelector(".home-top-search").getBoundingClientRect().bottom),
      carouselTop: Math.round(document.querySelector(".home-feature-carousel").getBoundingClientRect().top),
      searchInsideCarousel: document.querySelector(".home-top-search")?.parentElement?.matches(".home-feature-carousel"),
      searchOverlayPosition: getComputedStyle(document.querySelector(".home-top-search")).position,
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

  const footerProbe = await browser.newPage({ viewport: { width: 1275, height: 720 } });
  footerProbe.on("pageerror", (error) => pageErrors.push(error.message));
  await footerProbe.goto(`${baseUrl}/index.php`, { waitUntil: "networkidle" });
  await footerProbe.locator(".visitor-footer").scrollIntoViewIfNeeded();
  const footerState = await footerProbe.evaluate(() => {
    const statistics = document.querySelector(".visitor-stats");
    const statisticItems = [...statistics.children];
    const columns = [...document.querySelectorAll(".visitor-footer-inner > .row > [class*='col-']")];
    return {
      bootstrapRow: Boolean(document.querySelector(".visitor-footer-inner > .row")),
      columnCount: columns.length,
      itemTopCount: new Set(statisticItems.map((item) => Math.round(item.getBoundingClientRect().top))).size,
      statisticsFitColumn: statistics.scrollWidth <= statistics.clientWidth,
      noOverflow: document.documentElement.scrollWidth <= window.innerWidth,
    };
  });
  if (screenshotDir) {
    await footerProbe.locator(".visitor-footer").screenshot({
      path: path.join(screenshotDir, "footer-bootstrap-1275.png"),
    });
  }

  const passed = pageErrors.length === 0
    && desktopState.heading.includes("命令 ChatGPT「換桌布」")
    && desktopState.latestCount === 4
    && desktopState.popularCount === 10
    && JSON.stringify(desktopState.featureIds) === JSON.stringify(["4", "1", "2", "3", "5"])
    && desktopState.featureTitles.length === 5
    && desktopState.featureTitles.every((title) => title.length >= 6 && title.length <= 40)
    && desktopState.activeFeatureId === "4"
    && desktopState.activeFeatureVisible
    && desktopState.featureLineBreaks === 5
    && desktopState.featureDotCount === 5
    && desktopState.heroBodyCount === 0
    && desktopState.heroSecondaryCount === 0
    && desktopState.restoredPromoSections
    && desktopState.carouselToggleMissing
    && desktopState.navSearch
    && desktopState.navSearchIsFirstAction
    && desktopState.navSearchStyle.borderWidth === "0px"
    && desktopState.navSearchStyle.background === "rgba(0, 0, 0, 0)"
    && desktopState.apkNavHasIcon
    && desktopState.skillNavHasIcon
    && carouselState.activeFeatureId === "1"
    && carouselState.hiddenSlides === 4
    && carouselState.inertSlides === 4
    && carouselState.activeDot === "1"
    && carouselState.status.includes("第 2 張")
    && descending(desktopState.latestDates)
    && descending(desktopState.popularScores)
    && desktopState.searchAction === "search.php"
    && desktopState.imagesLoaded
    && desktopState.desktopColumns === 4
    && desktopState.topSearchTop === 0
    && desktopState.topSearchBottom > desktopState.carouselTop
    && desktopState.heroHeight >= 660
    && desktopState.searchInsideCarousel
    && desktopState.searchOverlayPosition === "absolute"
    && desktopState.deviceLabelFontSize === "10px"
    && desktopState.glassEffects.deviceBackdrop.includes("blur(12px)")
    && desktopState.glassEffects.advertisementBackdrop.includes("blur(12px)")
    && mobileState.scrollWidth <= mobileState.viewport
    && mobileState.brand.width >= 30
    && mobileState.brand.height >= 30
    && mobileState.navOpen
    && mobileState.navItems.length === 5
    && mobileState.iconNavItems
    && mobileState.mobileColumns === 2
    && mobileState.searchTargets.input >= 44
    && mobileState.searchTargets.button >= 44
    && mobileState.carouselTarget.width >= 44
    && mobileState.carouselTarget.height >= 44
    && swipeActiveFeatureId === "1"
    && mobileState.featureCount === 5
    && mobileState.featureDotCount === 5
    && mobileState.activeFeatureId === "4"
    && mobileState.latestCount === 4
    && mobileState.popularCount === 10
    && mobileState.heroHeight >= 680
    && mobileState.heroHeight <= 700
    && mobileState.topSearchTop === 0
    && mobileState.topSearchBottom > mobileState.carouselTop
    && mobileState.searchInsideCarousel
    && mobileState.searchOverlayPosition === "absolute"
    && mobileState.deviceLabelFontSize === "10px"
    && searchValue === "藍色手機直式 AI 龍"
    && footerState.bootstrapRow
    && footerState.columnCount === 3
    && footerState.itemTopCount === 1
    && footerState.statisticsFitColumn
    && footerState.noOverflow;

  console.log(JSON.stringify({ passed, pageErrors, desktopState, carouselState, mobileState, searchValue, footerState }));
  await desktop.close();
  await mobile.close();
  await searchProbe.close();
  await footerProbe.close();
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => { console.error(error); process.exit(1); });
