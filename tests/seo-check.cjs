const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";

(async () => {
  const catalogResponse = await fetch(`${baseUrl}/api/catalog/list`);
  const catalog = await catalogResponse.json();
  const sitemapResponse = await fetch(`${baseUrl}/sitemap-xml.php`);
  const sitemap = await sitemapResponse.text();
  const robotsResponse = await fetch(`${baseUrl}/robots.txt`);
  const robots = await robotsResponse.text();
  if (!catalogResponse.ok || !catalog.ok || !sitemapResponse.ok || !robotsResponse.ok) throw new Error("SEO endpoints did not load.");

  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));
  await page.goto(`${baseUrl}/index.php`, { waitUntil: "networkidle" });
  const home = await page.evaluate(() => {
    const schemas = [...document.querySelectorAll('script[type="application/ld+json"]')].map((node) => JSON.parse(node.textContent));
    const graph = schemas.flatMap((schema) => schema['@graph'] || []);
    const h1 = document.querySelector("h1");
    return {
      title: document.title,
      h1: h1?.textContent.replace(/\s+/g, " ").trim(),
      h1Visible: Boolean(h1 && h1.getBoundingClientRect().width && h1.getBoundingClientRect().height),
      hreflang: [...document.querySelectorAll('link[rel="alternate"][hreflang]')].map((link) => link.hreflang),
      hasSearchAction: graph.some((item) => item['@type'] === 'WebSite' && item.potentialAction?.['@type'] === 'SearchAction'),
      ogWidth: document.querySelector('meta[property="og:image:width"]')?.content,
      ogHeight: document.querySelector('meta[property="og:image:height"]')?.content,
    };
  });

  await page.goto(`${baseUrl}/search.php`, { waitUntil: "networkidle" });
  const search = await page.evaluate(() => {
    const schemas = [...document.querySelectorAll('script[type="application/ld+json"]')].map((node) => JSON.parse(node.textContent));
    const graph = schemas.flatMap((schema) => schema['@graph'] || []);
    const gallery = graph.find((item) => item['@type'] === 'ImageGallery');
    return { galleryImages: gallery?.associatedMedia?.length || 0, sitemapLink: document.querySelector('link[rel="sitemap"]')?.href || '' };
  });

  const imageCount = (sitemap.match(/<image:image>/g) || []).length;
  const passed = pageErrors.length === 0
    && home.title.includes("免費高畫質桌布下載")
    && home.h1.includes("免費高畫質桌布下載")
    && home.h1Visible
    && home.hreflang.length === 9
    && home.hreflang.includes("x-default")
    && home.hasSearchAction
    && home.ogWidth === "1920"
    && home.ogHeight === "1080"
    && search.galleryImages === Math.min(24, catalog.count)
    && search.sitemapLink.endsWith("/sitemap-xml.php")
    && sitemapResponse.headers.get("content-type")?.includes("application/xml")
    && imageCount === catalog.count
    && robots.includes("Sitemap: https://4k.1-0.tw/sitemap-xml.php");

  console.log(JSON.stringify({ passed, pageErrors, home, search, imageCount, catalogCount: catalog.count }));
  await page.close();
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});

