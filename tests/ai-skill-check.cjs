const { chromium } = require(process.env.PLAYWRIGHT_PATH || "playwright");

const baseUrl = process.env.SITE_URL || "http://127.0.0.1:8788";

(async () => {
  const browser = await chromium.launch({ channel: "chrome", headless: true });
  const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
  const pageErrors = [];
  page.on("pageerror", (error) => pageErrors.push(error.message));

  await page.goto(`${baseUrl}/ai-skill.php`, { waitUntil: "networkidle" });
  const codexPreview = await page.locator("#skillMarkdownPreview").textContent();
  const codexDownload = await page.locator("#previewDownload").getAttribute("href");

  await page.locator("#claudeTab").click();
  const claudeState = await page.evaluate(() => ({
    title: document.querySelector("#skillFileTitle").textContent,
    preview: document.querySelector("#skillMarkdownPreview").textContent,
    download: document.querySelector("#previewDownload").getAttribute("href"),
    prompt: document.querySelector("#installPrompt").textContent,
    selected: document.querySelector("#claudeTab").getAttribute("aria-selected"),
  }));

  await page.locator("#claudeTab").press("ArrowLeft");
  const keyboardReturnedToCodex = await page.locator("#codexTab").getAttribute("aria-selected") === "true";
  const legacyResponse = await page.request.get(`${baseUrl}/downloads/SKILL.md`);
  const codexResponse = await page.request.get(`${baseUrl}/downloads/codex/SKILL.md`);
  const claudeResponse = await page.request.get(`${baseUrl}/downloads/claude/SKILL.md`);
  const codexVersionResponse = await page.request.get(`${baseUrl}/downloads/codex/VERSION`);
  const claudeVersionResponse = await page.request.get(`${baseUrl}/downloads/claude/VERSION`);
  const mobilePage = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await mobilePage.goto(`${baseUrl}/ai-skill.php`, { waitUntil: "networkidle" });
  await mobilePage.locator("#claudeTab").click();
  const mobileState = await mobilePage.evaluate(() => ({
    viewport: window.innerWidth,
    scrollWidth: document.documentElement.scrollWidth,
    claudePreview: document.querySelector("#skillMarkdownPreview").textContent.includes(".claude/state"),
  }));
  await mobilePage.close();
  const passed = pageErrors.length === 0
    && codexDownload === "downloads/codex/SKILL.md"
    && codexPreview.includes(".codex/state")
    && claudeState.title === "Claude 技能規格"
    && claudeState.download === "downloads/claude/SKILL.md"
    && claudeState.prompt.includes("/downloads/claude/SKILL.md")
    && claudeState.preview.includes(".claude/state")
    && !claudeState.preview.includes(".codex/state")
    && claudeState.selected === "true"
    && keyboardReturnedToCodex
    && legacyResponse.status() === 404
    && codexResponse.status() === 200
    && claudeResponse.status() === 200
    && (await codexVersionResponse.text()).trim() === "v1.116"
    && (await claudeVersionResponse.text()).trim() === "v1.116"
    && codexPreview.includes("Skill version: v1.116")
    && claudeState.preview.includes("Skill version: v1.116")
    && codexPreview.includes("device=pc&orientation=landscape")
    && claudeState.preview.includes("device=pc&orientation=landscape")
    && mobileState.scrollWidth <= mobileState.viewport
    && mobileState.claudePreview;

  console.log(JSON.stringify({ passed, pageErrors, codexDownload, claudeState, keyboardReturnedToCodex, legacyStatus: legacyResponse.status(), codexStatus: codexResponse.status(), claudeStatus: claudeResponse.status(), mobileState }));
  await browser.close();
  process.exit(passed ? 0 : 1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
