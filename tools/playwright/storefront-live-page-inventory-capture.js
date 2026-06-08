const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

function parseCsvLine(line) {
  const out = [];
  let cur = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const ch = line[i];
    const next = line[i + 1];

    if (inQuotes) {
      if (ch === '"' && next === '"') {
        cur += '"';
        i++;
      } else if (ch === '"') {
        inQuotes = false;
      } else {
        cur += ch;
      }
    } else if (ch === '"') {
      inQuotes = true;
    } else if (ch === ',') {
      out.push(cur);
      cur = '';
    } else {
      cur += ch;
    }
  }

  out.push(cur);
  return out;
}

function readCsvRows(csvPath) {
  const lines = fs.readFileSync(csvPath, 'utf8').split(/\r?\n/).filter(Boolean);
  if (!lines.length) {
    return [];
  }

  const headers = parseCsvLine(lines[0]);
  return lines.slice(1).map((line) => {
    const values = parseCsvLine(line);
    const row = {};
    headers.forEach((header, index) => {
      row[header] = values[index] || '';
    });
    return row;
  });
}

function slugify(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/https?:\/\//g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 80) || 'page';
}

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

  const deadline = Date.now() + 8000;

  while (Date.now() < deadline) {
    for (const selector of selectors) {
      try {
        const locator = page.locator(selector);
        const count = await locator.count();

        for (let i = 0; i < count; i++) {
          const candidate = locator.nth(i);
          if (!(await candidate.isVisible({ timeout: 200 }))) continue;
          await candidate.click({ timeout: 1000, force: true });
          await page.waitForTimeout(250);
          return selector;
        }
      } catch (_) {
        // try the next selector
      }
    }

    try {
      await page.keyboard.press('Escape', { delay: 20 });
    } catch (_) {
      // ignore
    }

    await page.waitForTimeout(250);
  }

  return null;
}

async function warmLazyContent(page) {
  try {
    const maxHeight = await page.evaluate(() => {
      const body = document.body;
      const doc = document.documentElement;
      return Math.max(
        body ? body.scrollHeight : 0,
        doc ? doc.scrollHeight : 0,
        body ? body.offsetHeight : 0,
        doc ? doc.offsetHeight : 0
      );
    });

    const step = 1000;
    for (let y = 0; y < maxHeight; y += step) {
      await page.evaluate((scrollY) => window.scrollTo(0, scrollY), y);
      await page.waitForTimeout(200);
    }

    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(400);
  } catch (_) {
    // best effort only
  }
}

async function main() {
  const rootDir = path.resolve(__dirname, '..', '..');
  const csvPath = path.resolve(rootDir, 'var', 'tmp', 'live-public-url-inventory-20260606.csv');
  const artifactDir = path.resolve(rootDir, '.playwright', 'artifacts', 'live-page-inventory-20260606');
  const manifestPath = path.join(artifactDir, 'manifest.json');
  const rows = readCsvRows(csvPath);

  fs.mkdirSync(artifactDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({
    viewport: { width: 1440, height: 2200 },
    ignoreHTTPSErrors: true
  });

  const manifest = [];

  for (let index = 0; index < rows.length; index++) {
    const row = rows[index];
    const ordinal = String(index + 1).padStart(3, '0');
    const baseName = `${ordinal}-${slugify(row.group)}-${slugify(row.identifier_or_sku || row.title_or_type || row.url)}`;
    const screenshotPath = path.join(artifactDir, `${baseName}.png`);

    let status = null;
    let title = null;
    let dismissedPopupSelector = null;
    let error = null;

    try {
      const response = await page.goto(row.url, {
        waitUntil: 'networkidle',
        timeout: 60000
      });

      status = response ? response.status() : null;
      dismissedPopupSelector = await dismissNewsletterPopup(page);
      await warmLazyContent(page);
      dismissedPopupSelector = dismissedPopupSelector || await dismissNewsletterPopup(page);
      title = await page.title();

      await page.screenshot({
        path: screenshotPath,
        fullPage: true
      });
    } catch (captureError) {
      error = String(captureError && captureError.message ? captureError.message : captureError);
    }

    manifest.push({
      index: index + 1,
      ...row,
      status,
      title,
      dismissedPopupSelector,
      screenshotPath,
      error
    });

    console.log(JSON.stringify({
      index: index + 1,
      url: row.url,
      status,
      screenshotPath,
      error
    }));
  }

  fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2) + '\n');
  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
