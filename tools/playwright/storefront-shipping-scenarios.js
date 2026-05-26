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
    onlySkus: fromEnv('PW_ONLY_SKUS', ''),
    onlyProvinces: fromEnv('PW_ONLY_PROVINCES', ''),
    onlyMethods: fromEnv('PW_ONLY_METHODS', '')
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

async function submitHeaderSearch(page, query) {
  const searchInput = page
    .locator('#searchbox_mini_form input#searchbox, #search_mini_form input#search, #search_mini_form_mobile input#search')
    .first();
  await searchInput.waitFor({ state: 'visible', timeout: 30000 });
  await searchInput.fill(query);
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

  // Ensure the option is selected (class "selected" or aria-checked=true).
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
  // Don't hard-fail here; some theme variants don't update the minicart or show success.
  // We'll validate cart contents when we hit /checkout/cart/.
  return;
}

async function goToCheckout(page, baseUrl) {
  // Go through cart page; it's more reliable than hitting /checkout/ directly in custom themes.
  await page.goto(new URL('/checkout/cart/', baseUrl).toString(), { waitUntil: 'networkidle' });
  await dismissNewsletterPopup(page);

  // Ensure we actually have items in cart (if add-to-cart used an overlay, it can silently fail).
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
    // Some themes don't navigate reliably; force /checkout/ after clicking.
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

async function fillShippingAddress(page, regionCode) {
  const address = {
    QC: {
      city: 'Montreal',
      postcode: 'H2Y1C6',
      region: 'Quebec',
      regionId: '76'
    },
    ON: {
      city: 'Toronto',
      postcode: 'M5V1A1',
      region: 'Ontario',
      regionId: '74'
    },
    BC: {
      city: 'Vancouver',
      postcode: 'V5K0A1',
      region: 'British Columbia',
      regionId: '67'
    }
  }[regionCode];

  // Email is filled right before continuing to payment, because some checkout flows re-render
  // the email field after address changes and can wipe it.

  await page.locator('input[name="firstname"]:visible').first().fill('Test');
  await page.locator('input[name="lastname"]:visible').first().fill('User');
  await page.locator('input[name="street[0]"]:visible').first().fill('1 King St');
  await page.locator('input[name="city"]:visible').first().fill(address.city);

  // Country.
  const countrySelect = page.locator('select[name="country_id"]').first();
  if (await countrySelect.isVisible().catch(() => false)) {
    await countrySelect.selectOption('CA');
  }

  // Region can be a select or an input depending on config/theme.
  const regionSelect = page.locator('select[name="region_id"]').first();
  if (await regionSelect.isVisible().catch(() => false)) {
    await regionSelect.selectOption(address.regionId).catch(async () => {
      await regionSelect.selectOption({ label: address.region });
    });
  } else {
    const regionInput = page.locator('input[name="region"]:visible').first();
    if (await regionInput.isVisible().catch(() => false)) {
      await regionInput.fill(address.region);
    }
  }

  await page.locator('input[name="postcode"]:visible').first().fill(address.postcode);
  await page.locator('input[name="telephone"]:visible').first().fill('0000000000');

  // Let the checkout re-calc shipping rates after address edits.
  await page.waitForTimeout(1500);

  // Wait for shipping methods to finish re-rendering (theme/KO bindings can refresh rows after postcode/region changes).
  const methodsTable = page.locator('.table-checkout-shipping-method tbody').first();
  if (await methodsTable.isVisible().catch(() => false)) {
    await page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
    await page.waitForTimeout(1000);
  }
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

  // Last-resort: set the value via JS and trigger input/change.
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
  // Method titles come from MatrixRate rows: "Regular Shipping" / "Express Shipping"
  const methodRow = page.locator('.table-checkout-shipping-method tbody tr').filter({ hasText: methodTitle }).first();
  await methodRow.waitFor({ state: 'visible', timeout: 60000 });

  const radio = methodRow.locator('input[type="radio"]').first();
  const deadline = Date.now() + 60000;
  while (Date.now() < deadline) {
    if (await radio.isChecked().catch(() => false)) {
      // Ensure the selection sticks; KO can re-render the shipping rates table and clear the checked state.
      await page.waitForTimeout(750);
      if (await radio.isChecked().catch(() => false)) {
        return;
      }
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

  // If we can't advance, capture common inline validation errors.
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
      const fieldDetails = await page
        .evaluate(() => {
          const nodes = Array.from(document.querySelectorAll('.mage-error'));
          return nodes.map((node) => {
            const field = node.closest('.field') || node.parentElement;
            const label = field ? (field.querySelector('label') ? field.querySelector('label').innerText.trim() : '') : '';
            const input = field ? field.querySelector('input, select, textarea') : null;
            return {
              message: node.innerText.trim(),
              label,
              name: input && input.name ? input.name : '',
              id: input && input.id ? input.id : ''
            };
          });
        })
        .catch(() => []);

      if (texts.length) {
        throw new Error(
          `Checkout did not advance to payment: ${texts.join(' | ')} (fields=${JSON.stringify(fieldDetails)})`
        );
      }
    }

    await page.waitForTimeout(500);
  }

  throw new Error('Checkout did not advance to payment within timeout');
}

async function captureScenario({ sku, province, methodTitle, stage, page, outDir, ts }) {
  const fileName = `${ts}__${safeSegment(sku)}__${safeSegment(province)}__${safeSegment(methodTitle)}__${safeSegment(stage)}.png`;
  const filePath = path.join(outDir, fileName);
  await page.screenshot({ path: filePath, fullPage: true });
  return filePath;
}

async function runScenario(config, scenario) {
  const ts = timestampLocal();
  const outDir = path.resolve(process.cwd(), '.playwright', 'artifacts', 'shipping-scenarios');
  fs.mkdirSync(outDir, { recursive: true });

  const context = await config.browser.newContext({
    viewport: { width: 1440, height: 2200 },
    ignoreHTTPSErrors: true
  });
  const page = await context.newPage();

  await page.goto(new URL('/', config.baseUrl).toString(), { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
  await dismissNewsletterPopup(page);

  await openProduct(page, config.baseUrl, scenario.productPath);
  await selectKratomWeight(page, scenario.weightLabel);
  await addToCart(page);

  await goToCheckout(page, config.baseUrl);
  await dismissNewsletterPopup(page);
  await fillShippingAddress(page, scenario.province);
  await selectShippingMethod(page, scenario.methodTitle);

  const emailValue = `playwright+shipping-${scenario.province.toLowerCase()}@example.com`;

  const shippingShot = await captureScenario({
    sku: scenario.skuLabel || scenario.sku,
    province: scenario.provinceLabel || scenario.province,
    methodTitle: scenario.methodTitle,
    stage: 'shipping-step',
    page,
    outDir,
    ts
  });

  await proceedToPayment(page, emailValue);

  const paymentShot = await captureScenario({
    sku: scenario.skuLabel || scenario.sku,
    province: scenario.provinceLabel || scenario.province,
    methodTitle: scenario.methodTitle,
    stage: 'payment-step',
    page,
    outDir,
    ts
  });

  await context.close().catch(() => {});
  return { shippingShot, paymentShot, ts };
}

async function main() {
  const config = getConfig();
  config.browser = await chromium.launch({ headless: config.headless });

  const baseSkus = ['RB25', 'RB50', 'RB100', 'RB250', 'RB500'];
  const baseProvinces = ['QC', 'ON', 'BC'];

  const productPath = '/red-bali.html';
  const weightBySku = {
    RB25: '25g',
    RB50: '50g',
    RB100: '100g',
    RB250: '250g',
    RB500: '500g'
  };

  const methodsBySku = {
    RB25: ['Regular Shipping', 'Express Shipping'],
    RB50: ['Regular Shipping', 'Express Shipping'],
    RB100: ['Regular Shipping', 'Express Shipping'],
    RB250: ['Express Shipping'],
    RB500: ['Express Shipping']
  };

  const scenarios = [];
  const onlySkus = config.onlySkus
    ? config.onlySkus.split(',').map((v) => v.trim()).filter(Boolean)
    : baseSkus;
  const onlyProvinces = config.onlyProvinces
    ? config.onlyProvinces.split(',').map((v) => v.trim()).filter(Boolean)
    : baseProvinces;
  const onlyMethods = config.onlyMethods
    ? config.onlyMethods.split(',').map((v) => v.trim()).filter(Boolean)
    : null;

  for (const sku of onlySkus) {
    for (const province of onlyProvinces) {
      for (const methodTitle of methodsBySku[sku] || []) {
        if (onlyMethods && !onlyMethods.includes(methodTitle)) continue;
        const provinceLabel = { QC: 'QC-Quebec', ON: 'ON-Ontario', BC: 'BC-British-Columbia' }[province] || province;
        scenarios.push({
          sku,
          weightLabel: weightBySku[sku] || sku,
          skuLabel: `${sku}-${weightBySku[sku] || ''}`.replace(/-$/, ''),
          productPath,
          province,
          provinceLabel,
          methodTitle
        });
      }
    }
  }

  const results = [];
  for (const scenario of scenarios) {
    console.log(`Running scenario sku=${scenario.sku} province=${scenario.province} method="${scenario.methodTitle}"`);
    const out = await runScenario(config, scenario);
    results.push({ ...scenario, ...out });
  }

  const manifestPath = path.resolve(process.cwd(), '.playwright', 'artifacts', 'shipping-scenarios', 'manifest.json');
  fs.writeFileSync(manifestPath, JSON.stringify({ baseUrl: config.baseUrl, generatedAt: new Date().toISOString(), results }, null, 2));

  console.log(JSON.stringify({ manifestPath, count: results.length }, null, 2));
  await config.browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
