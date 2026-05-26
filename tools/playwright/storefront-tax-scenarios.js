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
    baseUrl: fromEnv('MAGENTO_BASE_URL', 'https://magento.ddev.site'),
    headless: fromEnv('PW_HEADLESS', '1') !== '0',
    onlyProvinces: fromEnv('PW_ONLY_PROVINCES', '')
  };
}

function timestampLocal() {
  const d = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  return (
    `${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}-` +
    `${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`
  );
}

function safeSegment(value) {
  return String(value)
    .trim()
    .replace(/[^a-z0-9]+/gi, '-')
    .replace(/(^-|-$)/g, '')
    .slice(0, 120);
}

function parseMoney(value) {
  if (value == null) return null;
  const normalized = String(value)
    .replace(/\u00a0/g, ' ')
    .replace(/[^0-9,.\-]/g, '')
    .replace(/,/g, '');
  const out = parseFloat(normalized);
  return Number.isFinite(out) ? out : null;
}

function round2(n) {
  return Math.round((n + Number.EPSILON) * 100) / 100;
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
        // ignore
      }
    }

    try {
      await page.keyboard.press('Escape', { delay: 25 });
    } catch (_) {
      // ignore
    }

    await page.waitForTimeout(250);
  }

  return null;
}

async function openProduct(page, baseUrl, productPath) {
  await page.goto(new URL(productPath, baseUrl).toString(), { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
  await dismissNewsletterPopup(page);
}

async function selectKratomWeight(page, weightLabel) {
  const option = page
    .locator('.swatch-attribute.kratom_weight .swatch-attribute-options .swatch-option')
    .filter({ hasText: weightLabel })
    .first();
  await dismissNewsletterPopup(page);
  await option.waitFor({ state: 'visible', timeout: 30000 });
  await option.click({ force: true });
  await page.waitForTimeout(300);

  const deadline = Date.now() + 10000;
  let lastClick = Date.now();
  while (Date.now() < deadline) {
    const klass = (await option.getAttribute('class').catch(() => '')) || '';
    const checked = (await option.getAttribute('aria-checked').catch(() => '')) || '';
    if (klass.includes('selected') || checked === 'true') {
      return;
    }
    await dismissNewsletterPopup(page);
    if (Date.now() - lastClick > 1000) {
      await option.click({ force: true }).catch(() => {});
      lastClick = Date.now();
    }
    await page.waitForTimeout(200);
  }

  throw new Error(`Failed to select kratom weight option "${weightLabel}"`);
}

async function addToCart(page) {
  const addButton = page.locator('button#product-addtocart-button, button.action.tocart.primary').first();
  await addButton.waitFor({ state: 'visible', timeout: 30000 });
  await dismissNewsletterPopup(page);
  await addButton.click({ force: true });

  const success = page.locator('.message-success, .messages .message-success').first();
  const error = page.locator('.message-error, .messages .message-error').first();
  const counter = page.locator('.minicart-wrapper .counter-number, .minicart-wrapper .counter.qty').first();

  const deadline = Date.now() + 30000;
  while (Date.now() < deadline) {
    if (await error.isVisible().catch(() => false)) {
      const text = await error.innerText().catch(() => 'Unknown add-to-cart error');
      throw new Error(`Add to cart failed: ${text}`);
    }

    if (await success.isVisible().catch(() => false)) {
      return;
    }

    if (/\/checkout\/cart/i.test(page.url())) {
      return;
    }

    const text = await counter.innerText().catch(() => '');
    if (/\d+/.test(text) && parseInt(text.replace(/\D/g, '') || '0', 10) > 0) {
      return;
    }

    await dismissNewsletterPopup(page);
    await page.waitForTimeout(250);
  }

  return;
}

async function goToCheckout(page, baseUrl) {
  await page.goto(new URL('/checkout/cart/', baseUrl).toString(), { waitUntil: 'networkidle' });
  await dismissNewsletterPopup(page);

  const cartItem = page.locator('.cart.item, .cart.table-wrapper .item-info').first();
  await cartItem.waitFor({ state: 'visible', timeout: 30000 });

  const proceed = page
    .locator(
      'button[data-role="proceed-to-checkout"], .checkout-methods-items .action.primary.checkout, a.action.primary.checkout'
    )
    .first();

  if (await proceed.isVisible().catch(() => false)) {
    await proceed.click({ force: true });
    await page.waitForTimeout(500);
    try {
      await page.waitForURL(/\/checkout\/(#|$)/i, { timeout: 10000 });
    } catch (_) {
      // ignore
    }
    if (!/\/checkout\/(#|$)/i.test(page.url())) {
      await page.goto(new URL('/checkout/', baseUrl).toString(), { waitUntil: 'domcontentloaded' });
    }
  } else {
    await page.goto(new URL('/checkout/', baseUrl).toString(), { waitUntil: 'domcontentloaded' });
  }

  await page.waitForSelector('#checkout, .checkout-container', { timeout: 60000 });
  await page.waitForTimeout(1000);
}

async function fillShippingAddress(page, address) {
  await page.locator('input[name="firstname"]').first().fill('Test');
  await page.locator('input[name="lastname"]').first().fill('User');
  await page.locator('input[name="street[0]"]').first().fill('1 King St');
  await page.locator('input[name="city"]').first().fill(address.city);

  const countrySelect = page.locator('select[name="country_id"]').first();
  if (await countrySelect.isVisible().catch(() => false)) {
    await countrySelect.selectOption('CA');
  }

  const regionSelect = page.locator('select[name="region_id"]').first();
  if (await regionSelect.isVisible().catch(() => false)) {
    await regionSelect.selectOption({ label: address.region });
  } else {
    const regionInput = page.locator('input[name="region"]').first();
    if (await regionInput.isVisible().catch(() => false)) {
      await regionInput.fill(address.region);
    }
  }

  await page.locator('input[name="postcode"]').first().fill(address.postcode);
  await page.locator('input[name="telephone"]').first().fill('0000000000');

  await page.waitForTimeout(1500);
}

async function ensureGuestEmail(page, emailValue) {
  const email = page.locator('#customer-email').first();
  if (!(await email.isVisible().catch(() => false))) {
    return;
  }

  const current = await email.inputValue().catch(() => '');
  if (current && current.includes('@')) {
    return;
  }

  await page.fill('#customer-email', emailValue);
  await page.dispatchEvent('#customer-email', 'input').catch(() => {});
  await page.dispatchEvent('#customer-email', 'change').catch(() => {});
  await email.press('Tab').catch(() => {});

  const after = await email.inputValue().catch(() => '');
  if (after && after.includes('@')) {
    return;
  }

  await page.evaluate((val) => {
    const el = document.querySelector('#customer-email');
    if (!el) return;
    el.value = val;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.dispatchEvent(new Event('blur', { bubbles: true }));
  }, emailValue);

  const finalValue = await email.inputValue().catch(() => '');
  if (!finalValue || !finalValue.includes('@')) {
    throw new Error('Unable to set guest email on checkout (#customer-email remained empty)');
  }
}

async function selectShippingMethod(page, methodTitle) {
  const methodRow = page.locator('.table-checkout-shipping-method tbody tr').filter({ hasText: methodTitle }).first();
  await methodRow.waitFor({ state: 'visible', timeout: 60000 });

  const radio = methodRow.locator('input[type="radio"]').first();
  const deadline = Date.now() + 60000;
  while (Date.now() < deadline) {
    if (await radio.isChecked().catch(() => false)) {
      await page.waitForTimeout(750);
      return;
    }

    const rowCount = await page.locator('.table-checkout-shipping-method tbody tr').count().catch(() => 0);
    const isDisabled = await radio.isDisabled().catch(() => false);
    if (isDisabled && rowCount <= 1) {
      await page.waitForTimeout(750);
      return;
    }

    await methodRow.click({ force: true }).catch(() => {});
    await radio.check({ force: true }).catch(() => {});

    await page.waitForTimeout(500);
  }

  throw new Error(`Unable to select shipping method "${methodTitle}" (radio remained unchecked/disabled)`);
}

async function proceedToPayment(page, emailValue) {
  await ensureGuestEmail(page, emailValue);

  const next = page.locator('button.continue, button[data-role="opc-continue"]').first();
  await next.waitFor({ state: 'visible', timeout: 60000 });
  await next.click({ force: true });

  const deadline = Date.now() + 60000;
  while (Date.now() < deadline) {
    const payment = page.locator('#checkout-payment-method-load').first();
    if (await payment.isVisible().catch(() => false)) {
      await page.waitForTimeout(1500);
      return;
    }

    const errs = page.locator('.mage-error, .field-error, .message-error, .messages .message-error');
    if ((await errs.count().catch(() => 0)) > 0) {
      const texts = (await errs.allInnerTexts().catch(() => [])).map((t) => t.trim()).filter(Boolean);
      if (texts.length) {
        throw new Error(`Checkout did not advance to payment: ${texts.join(' | ')}`);
      }
    }

    await page.waitForTimeout(500);
  }

  throw new Error('Checkout did not advance to payment within timeout');
}

async function waitForSummaryTotals(page) {
  const deadline = Date.now() + 60000;
  while (Date.now() < deadline) {
    const hasTaxRow = await page.locator('.opc-block-summary tr.totals-tax, .opc-block-summary tr.totals-tax-summary').count().catch(() => 0);
    const hasShippingRow = await page.locator('.opc-block-summary tr.totals.shipping, .opc-block-summary tr.totals-shipping').count().catch(() => 0);
    const hasGrandTotal = await page.locator('.opc-block-summary tr.grand.totals, .opc-block-summary tr.totals.grand').count().catch(() => 0);
    if (hasTaxRow > 0 && hasShippingRow > 0 && hasGrandTotal > 0) {
      await page.waitForTimeout(750);
      return;
    }
    await page.waitForTimeout(500);
  }
  throw new Error('Timed out waiting for checkout order summary totals to render');
}

async function expandTaxSummary(page) {
  const summary = page.locator('.opc-block-summary tr.totals-tax-summary').first();
  if (await summary.isVisible().catch(() => false)) {
    await summary.click({ force: true }).catch(() => {});
    await page.waitForTimeout(500);
  }
}

async function extractSummary(page) {
  return page.evaluate(() => {
    const root = document.querySelector('.opc-block-summary') || document;
    const parseMoney = (value) => {
      if (value == null) return null;
      const normalized = String(value)
        .replace(/\u00a0/g, ' ')
        .replace(/[^0-9,.\-]/g, '')
        .replace(/,/g, '');
      const out = parseFloat(normalized);
      return Number.isFinite(out) ? out : null;
    };

    const pick = (...selectors) => {
      for (const sel of selectors) {
        const node = root.querySelector(sel);
        if (node && node.textContent) {
          const v = parseMoney(node.textContent.trim());
          if (v != null) return v;
        }
      }
      return null;
    };

    const subtotal = pick('tr.totals.sub td.amount', 'tr.totals.sub td .price', 'tr.totals.subtotal td.amount');
    const shipping = pick('tr.totals.shipping td.amount', 'tr.totals.shipping td .price', 'tr.totals-shipping td.amount');
    const tax = pick('tr.totals-tax td.amount', 'tr.totals-tax-summary td.amount');
    const grandTotal = pick('tr.grand.totals td.amount', 'tr.grand.totals td .price', 'tr.totals.grand td.amount');

    const taxDetails = Array.from(root.querySelectorAll('tr.totals-tax-details th.mark')).map((th) => {
      const raw = (th.textContent || '').replace(/\s+/g, ' ').trim();
      const m = raw.match(/^(.*?)(?:\s*\(([-\d.]+)%\))?$/);
      const title = m ? m[1].trim() : raw;
      const percent = m && m[2] ? parseFloat(m[2]) : null;
      return { title, percent, raw };
    });

    return { subtotal, shipping, tax, grandTotal, taxDetails };
  });
}

function assertClose(actual, expected, label, tolerance = 0.02) {
  if (actual == null || expected == null) {
    throw new Error(`Missing value for ${label} (actual=${actual}, expected=${expected})`);
  }
  if (Math.abs(actual - expected) > tolerance) {
    throw new Error(`${label} mismatch: actual=${actual} expected=${expected} (tol=${tolerance})`);
  }
}

function assertTaxDetails(actualDetails, expectedDetails) {
  const actual = actualDetails
    .map((d) => ({ title: d.title, percent: d.percent }))
    .sort((a, b) => String(a.title).localeCompare(String(b.title)));
  const expected = expectedDetails
    .map((d) => ({ title: d.title, percent: d.percent }))
    .sort((a, b) => String(a.title).localeCompare(String(b.title)));

  if (actual.length !== expected.length) {
    throw new Error(`Tax detail count mismatch: actual=${actual.length} expected=${expected.length} actual=${JSON.stringify(actual)}`);
  }

  for (let i = 0; i < expected.length; i++) {
    const a = actual[i];
    const e = expected[i];
    if (a.title !== e.title) {
      throw new Error(`Tax detail title mismatch: actual=${a.title} expected=${e.title} actual=${JSON.stringify(actual)}`);
    }
    if (a.percent == null || Math.abs(a.percent - e.percent) > 0.001) {
      throw new Error(`Tax detail percent mismatch for ${e.title}: actual=${a.percent} expected=${e.percent}`);
    }
  }
}

function computeExpectedTaxTotal({ subtotal, shipping, taxes }) {
  let out = 0;
  for (const tax of taxes) {
    const pct = Number(tax.percent);
    const productTax = round2((subtotal * pct) / 100);
    const shippingTax = round2((shipping * pct) / 100);
    out += productTax + shippingTax;
  }
  return round2(out);
}

async function captureScenario({ sku, province, methodTitle, stage, page, outDir, ts }) {
  const fileName = `${ts}__${safeSegment(sku)}__${safeSegment(province)}__${safeSegment(methodTitle)}__${safeSegment(stage)}.png`;
  const filePath = path.join(outDir, fileName);
  await page.screenshot({ path: filePath, fullPage: true });
  return filePath;
}

async function runScenario(config, fixture, provinceCode) {
  const ts = timestampLocal();
  const outDir = path.resolve(process.cwd(), '.playwright', 'artifacts', 'tax-scenarios');
  fs.mkdirSync(outDir, { recursive: true });

  const province = fixture.provinces[provinceCode];
  if (!province) {
    throw new Error(`Unknown province code "${provinceCode}" in fixture`);
  }

  const defaults = fixture.scenarioDefaults;
  const context = await config.browser.newContext({
    viewport: { width: 1440, height: 2200 },
    ignoreHTTPSErrors: true
  });
  const page = await context.newPage();

  let shippingShot = null;
  let paymentShot = null;
  let summary = null;
  let expectedTaxTotal = null;
  let error = null;

  try {
    await page.goto(new URL('/', config.baseUrl).toString(), { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
    await dismissNewsletterPopup(page);

    await openProduct(page, config.baseUrl, defaults.productPath);
    await selectKratomWeight(page, defaults.weightLabel);
    await addToCart(page);

    await goToCheckout(page, config.baseUrl);
    await dismissNewsletterPopup(page);

    await fillShippingAddress(page, province);
    await selectShippingMethod(page, defaults.shippingMethodTitle);

    const emailValue = `playwright+tax-${provinceCode.toLowerCase()}@example.com`;

    shippingShot = await captureScenario({
      sku: defaults.skuLabel || defaults.sku,
      province: province.label || provinceCode,
      methodTitle: defaults.shippingMethodTitle,
      stage: 'shipping-step',
      page,
      outDir,
      ts
    });

    await proceedToPayment(page, emailValue);

    await waitForSummaryTotals(page);
    await expandTaxSummary(page);

    paymentShot = await captureScenario({
      sku: defaults.skuLabel || defaults.sku,
      province: province.label || provinceCode,
      methodTitle: defaults.shippingMethodTitle,
      stage: 'payment-step',
      page,
      outDir,
      ts
    });

    summary = await extractSummary(page);
    if (summary.subtotal == null || summary.shipping == null || summary.tax == null || summary.grandTotal == null) {
      throw new Error(`Unable to parse totals from checkout summary: ${JSON.stringify(summary)}`);
    }

    if (typeof defaults.expectedSubtotalAmount === 'number') {
      assertClose(summary.subtotal, defaults.expectedSubtotalAmount, `Subtotal amount (${provinceCode})`, 0.02);
    }
    assertClose(summary.shipping, defaults.expectedShippingAmount, `Shipping amount (${provinceCode})`, 0.02);
    assertTaxDetails(summary.taxDetails, province.taxes);

    expectedTaxTotal = computeExpectedTaxTotal({
      subtotal: summary.subtotal,
      shipping: summary.shipping,
      taxes: province.taxes
    });
    assertClose(summary.tax, expectedTaxTotal, `Tax total (${provinceCode})`, 0.02);

    const expectedGrand = round2(summary.subtotal + summary.shipping + summary.tax);
    assertClose(summary.grandTotal, expectedGrand, `Grand total (${provinceCode})`, 0.02);
  } catch (e) {
    error = String(e && e.stack ? e.stack : e);
    // Best-effort screenshot on failures that happen before we hit the normal capture points.
    if (!shippingShot) {
      shippingShot = await captureScenario({
        sku: defaults.skuLabel || defaults.sku,
        province: province.label || provinceCode,
        methodTitle: defaults.shippingMethodTitle,
        stage: 'error-shipping-step',
        page,
        outDir,
        ts
      }).catch(() => null);
    }
    if (!paymentShot && /\/checkout/i.test(page.url())) {
      paymentShot = await captureScenario({
        sku: defaults.skuLabel || defaults.sku,
        province: province.label || provinceCode,
        methodTitle: defaults.shippingMethodTitle,
        stage: 'error-payment-step',
        page,
        outDir,
        ts
      }).catch(() => null);
    }
  } finally {
    await context.close().catch(() => {});
  }

  return { provinceCode, shippingShot, paymentShot, ts, summary, expectedTaxTotal, error };
}

async function main() {
  const config = getConfig();
  const fixturePath = path.resolve(process.cwd(), 'tools', 'playwright', 'fixtures', 'canada-tax-baseline.json');
  const fixture = JSON.parse(fs.readFileSync(fixturePath, 'utf8'));

  config.browser = await chromium.launch({ headless: config.headless });

  const provinceCodes = Object.keys(fixture.provinces || {});
  const only = config.onlyProvinces
    ? config.onlyProvinces.split(',').map((v) => v.trim()).filter(Boolean)
    : provinceCodes;

  const results = [];
  const errors = [];
  for (const provinceCode of only) {
    console.log(`Running tax scenario province=${provinceCode}`);
    const out = await runScenario(config, fixture, provinceCode);
    results.push(out);
    if (out.error) {
      errors.push({ provinceCode, error: out.error });
    }
  }

  const outDir = path.resolve(process.cwd(), '.playwright', 'artifacts', 'tax-scenarios');
  fs.mkdirSync(outDir, { recursive: true });
  const manifestPath = path.join(outDir, 'manifest.json');
  fs.writeFileSync(
    manifestPath,
    JSON.stringify(
      {
        baseUrl: config.baseUrl,
        fixturePath,
        generatedAt: new Date().toISOString(),
        results,
        errors
      },
      null,
      2
    )
  );

  const screenshots = [];
  for (const r of results) {
    if (r && r.shippingShot) screenshots.push(r.shippingShot);
    if (r && r.paymentShot) screenshots.push(r.paymentShot);
  }

  console.log(
    JSON.stringify(
      {
        manifestPath,
        screenshots,
        screenshotCount: screenshots.length,
        errorCount: errors.length
      },
      null,
      2
    )
  );

  await config.browser.close();

  if (errors.length) {
    process.exit(1);
  }
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
