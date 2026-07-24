const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";

(async () => {
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));

  await page.goto(`${baseUrl}/index.php`, { waitUntil: "networkidle" });
  const initialState = await page.evaluate(() => ({
    regionHidden: document.querySelector("[data-pwa-install-region]")?.hidden,
    buttonHidden: document.querySelector("#pwaInstall")?.hidden,
  }));

  await page.evaluate(() => {
    window.__pwaPromptCalls = 0;
    const event = new Event("beforeinstallprompt", { cancelable: true });
    event.prompt = async () => { window.__pwaPromptCalls += 1; };
    event.userChoice = Promise.resolve({ outcome: "dismissed" });
    window.dispatchEvent(event);
  });

  const visibleState = await page.evaluate(() => {
    const region = document.querySelector("[data-pwa-install-region]");
    const button = document.querySelector("#pwaInstall");
    const buttonRect = button.getBoundingClientRect();
    return {
      regionVisible: !region.hidden && getComputedStyle(region).display !== "none",
      buttonVisible: !button.hidden && getComputedStyle(button).display !== "none",
      buttonText: button.textContent.replace(/\s+/g, " ").trim(),
      buttonTarget: { width: Math.round(buttonRect.width), height: Math.round(buttonRect.height) },
      insideHomeMain: region.parentElement?.tagName === "MAIN",
      beforeFooter: Boolean(region.compareDocumentPosition(document.querySelector("footer")) & Node.DOCUMENT_POSITION_FOLLOWING),
    };
  });

  await page.locator("#pwaInstall").click();
  await page.waitForFunction(() => document.querySelector("[data-pwa-install-region]")?.hidden === true);
  const dismissedState = await page.evaluate(() => ({
    regionHidden: document.querySelector("[data-pwa-install-region]")?.hidden,
    promptCalls: window.__pwaPromptCalls,
  }));

  await page.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  const searchState = await page.evaluate(() => ({
    installButtons: document.querySelectorAll("#pwaInstall").length,
    installRegions: document.querySelectorAll("[data-pwa-install-region]").length,
  }));

  const passed = pageErrors.length === 0
    && initialState.regionHidden === true
    && initialState.buttonHidden === true
    && visibleState.regionVisible
    && visibleState.buttonVisible
    && visibleState.buttonText === "安裝桌布館"
    && visibleState.buttonTarget.width >= 44
    && visibleState.buttonTarget.height >= 44
    && visibleState.insideHomeMain
    && visibleState.beforeFooter
    && dismissedState.regionHidden === true
    && dismissedState.promptCalls === 1
    && searchState.installButtons === 0
    && searchState.installRegions === 0;

  console.log(JSON.stringify({ passed, pageErrors, initialState, visibleState, dismissedState, searchState }));
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
