const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

function readEnvFile(filePath) {
  const values = {};
  if (!fs.existsSync(filePath)) {
    return values;
  }

  const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) {
      continue;
    }
    const idx = trimmed.indexOf('=');
    if (idx === -1) {
      continue;
    }
    const key = trimmed.slice(0, idx).trim();
    const value = trimmed.slice(idx + 1).trim();
    values[key] = value;
  }
  return values;
}

function getConfig() {
  const envPath = path.resolve(process.cwd(), '.playwright', 'local.env');
  const fileEnv = readEnvFile(envPath);
  const fromEnv = (key, fallback = '') => process.env[key] || fileEnv[key] || fallback;

  return {
    baseUrl: fromEnv('MAGENTO_BASE_URL', 'https://magento.ddev.site')
  };
}

async function maybeCloseModal(page) {
  // The SM Market demo often shows newsletter/popups; try to close anything modal-ish so it
  // doesn't block the header search box.
  const closeCandidates = [
    '.fancybox-close',
    '.modal-popup .action-close',
    '.modal-popup .action-close[aria-label="Close"]',
    '.newsletter-popup .action-close',
    '.newsletter-popup .close',
    '.popup-newsletter .action-close',
    '.popup-newsletter .close'
  ];

  for (const selector of closeCandidates) {
    const loc = page.locator(selector).first();
    try {
      if (await loc.isVisible({ timeout: 500 })) {
        await loc.click({ timeout: 2000 });
        return;
      }
    } catch (_) {
      // ignore
    }
  }

  // Fallback: some of these popups are implemented as a Fancybox overlay.
  try {
    const overlay = page.locator('.fancybox-overlay, .fancybox-wrap').first();
    if (await overlay.isVisible({ timeout: 250 })) {
      await page.keyboard.press('Escape').catch(() => {});
    }
  } catch (_) {
    // ignore
  }
}

async function extractProductNames(page, limit = 12, linkSelector = '') {
  const selector =
    linkSelector ||
    'main#maincontent .column.main ol.products.list.items.product-items .product-item .product-item-link';
  const items = page.locator(selector);
  const count = await items.count();
  const take = Math.min(count, limit);
  const names = [];
  for (let i = 0; i < take; i++) {
    const text = (await items.nth(i).innerText()).trim();
    if (text) names.push(text);
  }
  return { count, names };
}

async function main() {
  const config = getConfig();

  const artifactDir = path.resolve(process.cwd(), '.playwright', 'artifacts');
  fs.mkdirSync(artifactDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({
    viewport: { width: 1440, height: 2200 },
    ignoreHTTPSErrors: true
  });

  // 1) Search via the actual header search form.
  await page.goto(new URL('/', config.baseUrl).toString(), { waitUntil: 'networkidle' });
  await maybeCloseModal(page);

  const query = 'Gree Malay';
  const searchInput = page.locator(
    '#searchbox_mini_form input#searchbox, #search_mini_form input#search, #search_mini_form_mobile input#search'
  ).first();
  await searchInput.waitFor({ state: 'visible', timeout: 30000 });
  await searchInput.fill(query);
  // Force submit via DOM API to avoid theme overlays and any JS key handlers.
  await page.evaluate(() => {
    const form =
      document.querySelector('#searchbox_mini_form') ||
      document.querySelector('#search_mini_form') ||
      document.querySelector('#search_mini_form_mobile');
    if (!form) {
      throw new Error('Search form not found');
    }
    form.submit();
  });
  await page.waitForURL(/\/catalogsearch\/result\/\?.*q=/i, { timeout: 30000 });
  await page.waitForLoadState('networkidle');

  const search = await extractProductNames(page, 12);
  const searchScreenshotPath = path.join(artifactDir, 'storefront-search-results.png');
  await page.screenshot({ path: searchScreenshotPath, fullPage: true });

  // 2) Category page check (the user-reported broken path).
  const categoryUrl = new URL('/green-vein-kratom.html', config.baseUrl).toString();
  await page.goto(categoryUrl, { waitUntil: 'networkidle' });
  await maybeCloseModal(page);

  const categoryEmptyMessage = page.locator('.message.info.empty').first();
  const categoryIsEmpty = await categoryEmptyMessage.isVisible().catch(() => false);
  const category = await extractProductNames(page, 12);

  const categoryScreenshotPath = path.join(artifactDir, 'storefront-category-green-vein.png');
  await page.screenshot({ path: categoryScreenshotPath, fullPage: true });

  const summary = {
    baseUrl: config.baseUrl,
    search: {
      query,
      resultsCount: search.count,
      firstNames: search.names,
      screenshotPath: searchScreenshotPath
    },
    category: {
      url: categoryUrl,
      emptyMessageVisible: categoryIsEmpty,
      productsCount: category.count,
      firstNames: category.names,
      screenshotPath: categoryScreenshotPath
    }
  };

  console.log(JSON.stringify(summary, null, 2));
  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
