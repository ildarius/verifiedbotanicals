const path = require('path');
const { chromium } = require('playwright');

async function main() {
  const target = process.argv[2];
  if (!target) {
    throw new Error('Usage: node tools/playwright/inspect-admin-page.js <path>');
  }

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    storageState: path.resolve(process.cwd(), '.playwright', 'admin-auth.json')
  });
  const page = await context.newPage();

  await page.goto(`https://magento.ddev.site${target}`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);

  console.log('URL:', page.url());
  console.log('TITLE:', await page.title());
  console.log('BODY:', (await page.locator('body').innerText()).slice(0, 4000));

  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
