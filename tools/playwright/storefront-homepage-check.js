const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

async function main() {
  const screenshotDir = path.resolve(process.cwd(), '.playwright', 'artifacts');
  fs.mkdirSync(screenshotDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({
    viewport: { width: 1440, height: 2200 },
    ignoreHTTPSErrors: true
  });

  await page.goto('https://magento.ddev.site/', { waitUntil: 'networkidle' });

  const summary = await page.evaluate(() => ({
    title: document.title,
    bodyFont: getComputedStyle(document.body).fontFamily,
    bodyColor: getComputedStyle(document.body).color,
    cssLinks: [...document.querySelectorAll('link[rel="stylesheet"]')]
      .map((el) => el.href)
      .filter((href) => /settings_|configed_css/.test(href))
  }));

  const screenshotPath = path.join(screenshotDir, 'storefront-homepage.png');
  await page.screenshot({ path: screenshotPath, fullPage: true });

  console.log(JSON.stringify({ screenshotPath, summary }, null, 2));
  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
