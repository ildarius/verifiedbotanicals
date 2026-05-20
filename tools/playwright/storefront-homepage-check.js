const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

async function dismissNewsletterPopup(page) {
  const selectors = [
    'button.mfp-close',
    '.mfp-close',
    'button.action-close',
    'a.fancybox-item.fancybox-close',
    'a.fancybox-close',
    'button[aria-label="Close"]',
    '.modal-popup .action-close',
    '.newsletter-popup button.close',
    '.newsletter-popup .close'
  ];

  const deadline = Date.now() + 10000;

  while (Date.now() < deadline) {
    for (const selector of selectors) {
      try {
        const locator = page.locator(selector);
        const count = await locator.count();

        for (let i = 0; i < count; i++) {
          const candidate = locator.nth(i);
          if (!(await candidate.isVisible({ timeout: 250 }))) continue;
          await candidate.click({ timeout: 2000, force: true });
          await page.waitForTimeout(250);
          return selector;
        }
      } catch (_) {
        // ignore and try next selector
      }
    }

    // Some popups close with ESC even if the close button isn't found yet.
    try {
      await page.keyboard.press('Escape', { delay: 25 });
    } catch (_) {
      // ignore
    }

    await page.waitForTimeout(250);
  }

  return null;
}

async function main() {
  const screenshotDir = path.resolve(process.cwd(), '.playwright', 'artifacts');
  fs.mkdirSync(screenshotDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({
    viewport: { width: 1440, height: 2200 },
    ignoreHTTPSErrors: true
  });

  const consoleErrors = [];
  const pageErrors = [];
  const failedRequests = [];
  const badAssetResponses = [];

  page.on('console', (msg) => {
    if (msg.type() !== 'error') return;
    consoleErrors.push({
      text: msg.text(),
      location: msg.location()
    });
  });

  page.on('pageerror', (error) => {
    pageErrors.push(String(error && error.message ? error.message : error));
  });

  page.on('requestfailed', (request) => {
    failedRequests.push({
      url: request.url(),
      resourceType: request.resourceType(),
      failure: request.failure()
    });
  });

  page.on('response', (response) => {
    const status = response.status();
    if (status < 400) return;

    const url = response.url();
    const isAsset = /\.(css|js|map|png|jpe?g|gif|svg|webp|woff2?|ttf)(\?|$)/i.test(url);
    if (!isAsset) return;

    badAssetResponses.push({ url, status });
  });

  await page.goto('https://magento.ddev.site/', { waitUntil: 'networkidle' });

  const dismissedPopupSelector = await dismissNewsletterPopup(page);

  // Wait for SM Market "New Arrivals" carousel to fully initialize (owl-loaded),
  // otherwise the page can look "blank" even though products are in the HTML.
  try {
    await page.locator('.block-home-37').first().scrollIntoViewIfNeeded({ timeout: 5000 });
    await page.waitForSelector('.block-home-37 .owl-carousel.owl-loaded', { timeout: 15000 });
  } catch (_) {
    // best-effort; keep going but report issues in summary
  }

  // Trigger lazyloaded images by scrolling through the page.
  try {
    const maxScrolls = 12;
    for (let i = 0; i < maxScrolls; i++) {
      await page.evaluate((step) => window.scrollBy(0, step), 900);
      await page.waitForTimeout(250);
    }
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(500);
  } catch (_) {
    // ignore
  }

  const summary = await page.evaluate(() => ({
    title: document.title,
    bodyFont: getComputedStyle(document.body).fontFamily,
    bodyColor: getComputedStyle(document.body).color,
    cssLinks: [...document.querySelectorAll('link[rel="stylesheet"]')]
      .map((el) => el.href)
      .filter((href) => /settings_|configed_css/.test(href)),
    lazy: (() => {
      const lazyImages = [...document.querySelectorAll('img.lazyload[data-src]')];
      const blankCount = lazyImages.filter((img) =>
        String(img.getAttribute('src') || '').includes('/lazyloading/blank.png')
      ).length;
      return {
        total: lazyImages.length,
        blankCount,
        loadedCount: Math.max(0, lazyImages.length - blankCount),
        hasLazySizes: typeof window.lazySizes !== 'undefined'
      };
    })()
  }));

  const screenshotPath = path.join(screenshotDir, 'storefront-homepage.png');
  await page.screenshot({ path: screenshotPath, fullPage: true });

  console.log(
    JSON.stringify(
      {
        screenshotPath,
        summary,
        dismissedPopupSelector,
        issues: {
          consoleErrors,
          pageErrors,
          failedRequests,
          badAssetResponses
        }
      },
      null,
      2
    )
  );
  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
