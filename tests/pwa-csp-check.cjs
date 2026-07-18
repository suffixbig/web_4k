const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";
const expectedCache = "dragon-maiden-wallpaper-v5";
const requiredCachedPaths = [
  "/offline.php",
  "/skin/css/styles.css",
  "/skin/js/site.js",
  "/skin/js/home.js",
  "/skin/js/catalog.js",
  "/skin/js/collection.js",
  "/skin/js/ranking.js",
  "/skin/js/skill.js",
  "/skin/js/pwa.js",
  "/manifest.webmanifest",
];
const beaconUrl = "https://static.cloudflareinsights.com/beacon.min.js/v4513226cdae34746b4dedf0b4dfa099e1781791509496";

(async () => {
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const context = await browser.newContext({ serviceWorkers: "allow" });
  const page = await context.newPage();
  const pageErrors = [];
  const relevantConsoleErrors = [];

  page.on("pageerror", (error) => pageErrors.push(error.message));
  page.on("console", (message) => {
    if (message.type() !== "error") return;
    if (/Content Security Policy|cache\.addAll|Failed to execute 'addAll'/i.test(message.text())) {
      relevantConsoleErrors.push(message.text());
    }
  });

  await page.goto(`${baseUrl}/index.php`, { waitUntil: "networkidle" });
  const cspContent = await page.locator('meta[http-equiv="Content-Security-Policy"]').getAttribute("content");
  const registrationState = await page.evaluate(() => Promise.race([
    navigator.serviceWorker.ready.then((registration) => ({
      active: Boolean(registration.active),
      scriptURL: registration.active?.scriptURL || "",
    })),
    new Promise((resolve) => setTimeout(() => resolve({ active: false, scriptURL: "" }), 10000)),
  ]));

  const cacheState = await page.evaluate(async (cacheName) => {
    const names = await caches.keys();
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    return { names, urls: keys.map((request) => request.url) };
  }, expectedCache);
  const cachedPaths = cacheState.urls.map((url) => new URL(url).pathname);

  await page.evaluate(() => {
    window.__cspViolations = [];
    document.addEventListener("securitypolicyviolation", (event) => {
      window.__cspViolations.push({ blockedURI: event.blockedURI, directive: event.effectiveDirective });
    });
  });
  await page.evaluate((src) => new Promise((resolve) => {
    const script = document.createElement("script");
    const finish = (state) => resolve(state);
    script.src = src;
    script.onload = () => finish("loaded");
    script.onerror = () => finish("network-error");
    document.head.append(script);
    setTimeout(() => finish("timeout"), 5000);
  }), beaconUrl);
  const cspViolations = await page.evaluate(() => window.__cspViolations || []);

  await page.goto(`${baseUrl}/collection.php`, { waitUntil: "networkidle" });
  await page.locator('[data-library="all"]').click();
  await page.locator("#collectionGrid article").first().waitFor();
  const collectionItems = await page.locator("#collectionGrid article").count();

  const missingCachedPaths = requiredCachedPaths.filter((requiredPath) => !cachedPaths.includes(requiredPath));
  const staleRootScripts = cachedPaths.filter((cachedPath) => /^\/(home|catalog|collection|ranking|skill|pwa)\.js$/.test(cachedPath));
  const passed = pageErrors.length === 0
    && relevantConsoleErrors.length === 0
    && cspViolations.length === 0
    && cspContent.includes("https://static.cloudflareinsights.com")
    && cspContent.includes("https://cloudflareinsights.com")
    && registrationState.active
    && registrationState.scriptURL.endsWith("/sw.js")
    && cacheState.names.includes(expectedCache)
    && missingCachedPaths.length === 0
    && staleRootScripts.length === 0
    && collectionItems > 0;

  console.log(JSON.stringify({
    passed,
    pageErrors,
    relevantConsoleErrors,
    cspViolations,
    registrationState,
    cacheNames: cacheState.names,
    cachedItems: cachedPaths.length,
    missingCachedPaths,
    staleRootScripts,
    collectionItems,
  }));

  await context.close();
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
