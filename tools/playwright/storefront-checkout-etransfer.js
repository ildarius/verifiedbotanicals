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
    mailpitUrl: fromEnv('MAILPIT_URL', 'http://magento.ddev.site:8025'),
    productPath: fromEnv('PW_PRODUCT_PATH', '/red-bali.html'),
    weightLabel: fromEnv('PW_WEIGHT_LABEL', '25g'),
    province: fromEnv('PW_PROVINCE', 'QC')
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

function logStage(message) {
  console.log(`[checkout-etransfer] ${message}`);
}

function isCheckoutDiagnosticsUrl(url) {
  return /\/rest\/[^/]+\/V1\/(guest-carts|carts)\/.+\/(shipping-information|set-payment-information|payment-information|totals-information)/i.test(url);
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

  await option.waitFor({ state: 'visible', timeout: 30000 });
  await dismissNewsletterPopup(page);
  await option.click({ force: true });

  const deadline = Date.now() + 10000;
  while (Date.now() < deadline) {
    const klass = (await option.getAttribute('class').catch(() => '')) || '';
    const checked = (await option.getAttribute('aria-checked').catch(() => '')) || '';
    if (klass.includes('selected') || checked === 'true') {
      return;
    }
    await page.waitForTimeout(200);
  }

  throw new Error(`Failed to select kratom weight "${weightLabel}"`);
}

async function addToCart(page) {
  const addButton = page.locator('button#product-addtocart-button, button.action.tocart.primary').first();
  await addButton.waitFor({ state: 'visible', timeout: 30000 });
  await dismissNewsletterPopup(page);
  await addButton.click({ force: true });

  const success = page.locator('.message-success, .messages .message-success').first();
  const error = page.locator('.message-error, .messages .message-error').first();
  const deadline = Date.now() + 30000;

  while (Date.now() < deadline) {
    if (await error.isVisible().catch(() => false)) {
      throw new Error(`Add to cart failed: ${await error.innerText().catch(() => 'Unknown error')}`);
    }
    if (await success.isVisible().catch(() => false)) {
      return;
    }
    await page.waitForTimeout(250);
  }

  throw new Error('Timed out waiting for add-to-cart success');
}

async function goToCheckout(page, baseUrl) {
  await page.goto(new URL('/checkout/cart/', baseUrl).toString(), { waitUntil: 'networkidle', timeout: 60000 });
  await dismissNewsletterPopup(page);

  await page.locator('.cart.item, .cart.table-wrapper .item-info').first().waitFor({ state: 'visible', timeout: 30000 });

  const proceed = page
    .locator('button[data-role="proceed-to-checkout"], .checkout-methods-items .action.primary.checkout, a.action.primary.checkout')
    .first();
  await proceed.click({ force: true });
  await page.waitForTimeout(500);

  if (!/\/checkout\/(#|$)/i.test(page.url())) {
    await page.goto(new URL('/checkout/', baseUrl).toString(), { waitUntil: 'domcontentloaded', timeout: 60000 });
  }

  await page.waitForSelector('#checkout, .checkout-container', { timeout: 60000 });
  await page.waitForTimeout(1000);
}

function addressForProvince(regionCode) {
  return {
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
  }[regionCode] || {
    city: 'Montreal',
    postcode: 'H2Y1C6',
    region: 'Quebec',
    regionId: '76'
  };
}

async function fillShippingAddress(page, province) {
  const address = addressForProvince(province);

  await page.locator('input[name="firstname"]:visible').first().fill('Playwright');
  await page.locator('input[name="lastname"]:visible').first().fill('Checkout');
  await page.locator('input[name="street[0]"]:visible').first().fill('1 King St');
  await page.locator('input[name="city"]:visible').first().fill(address.city);

  const countrySelect = page.locator('select[name="country_id"]').first();
  if (await countrySelect.isVisible().catch(() => false)) {
    await countrySelect.selectOption('CA');
  }

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

  const finalValue = await email.inputValue().catch(() => '');
  if (!finalValue || !finalValue.includes('@')) {
    throw new Error('Unable to set guest email on checkout');
  }
}

async function selectFirstShippingMethod(page) {
  const table = page.locator('.table-checkout-shipping-method tbody').first();
  await table.waitFor({ state: 'visible', timeout: 60000 });
  await page.waitForTimeout(1000);

  const rows = table.locator('tr');
  const count = await rows.count();
  if (count === 0) {
    throw new Error('No shipping methods were rendered');
  }

  for (let i = 0; i < count; i++) {
    const row = rows.nth(i);
    const radio = row.locator('input[type="radio"]').first();
    try {
      await row.click({ force: true });
      await radio.check({ force: true });
      if (await radio.isChecked().catch(() => false)) {
        const labelText = (await row.innerText().catch(() => '')).trim().replace(/\s+/g, ' ');
        return labelText;
      }
    } catch (_) {
      // try next row
    }
  }

  throw new Error('Unable to select any shipping method');
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
    await page.waitForTimeout(500);
  }

  throw new Error('Checkout did not advance to payment');
}

async function selectInteracPaymentMethod(page) {
  const radio = page.locator('input#interac_etransfer, input[value="interac_etransfer"]').first();
  await radio.waitFor({ state: 'visible', timeout: 60000 });
  const paymentInfoResponse = page.waitForResponse(
    (response) => /\/rest\/[^/]+\/V1\/(guest-carts|carts)\/.+\/set-payment-information/i.test(response.url()),
    { timeout: 15000 }
  ).catch(() => null);
  await radio.check({ force: true });

  const activeMethod = page.locator('.payment-method._active').filter({ hasText: 'Interac e-Transfer' }).first();
  await activeMethod.waitFor({ state: 'visible', timeout: 30000 });

  const notice = activeMethod.locator('.interac-etransfer-checkout-note').first();
  await notice.waitFor({ state: 'visible', timeout: 30000 });
  await paymentInfoResponse;
  await page.locator('.loading-mask').waitFor({ state: 'hidden', timeout: 30000 }).catch(() => {});
  await page.waitForTimeout(1000);

  return (await notice.innerText().catch(() => '')).trim();
}

async function acceptVisibleAgreements(page) {
  const checkboxes = page.locator('.payment-method._active input[type="checkbox"]:visible');
  const count = await checkboxes.count();
  for (let i = 0; i < count; i++) {
    const checkbox = checkboxes.nth(i);
    if (!(await checkbox.isChecked().catch(() => false))) {
      await checkbox.check({ force: true }).catch(() => {});
    }
  }
}

async function getCheckoutDiagnostics(page) {
  return page.evaluate(() => new Promise((resolve) => {
    require([
      'Magento_Checkout/js/model/quote',
      'Magento_Checkout/js/model/payment/additional-validators'
    ], function (quote, additionalValidators) {
      const validators = typeof additionalValidators.getValidators === 'function'
        ? additionalValidators.getValidators()
        : [];
      const recaptchaNodes = Array.from(
        document.querySelectorAll('.recaptcha-checkout-place-order, .g-recaptcha, [id*="recaptcha"]')
      ).map((node) => ({
        id: node.id || '',
        className: node.className || '',
        visible: !!(node.offsetWidth || node.offsetHeight || node.getClientRects().length)
      }));
      const button = document.querySelector('.payment-method._active button.action.primary.checkout');

      resolve({
        selectedPaymentMethod: quote.paymentMethod(),
        hasBillingAddress: !!quote.billingAddress(),
        validatorCount: validators.length,
        validatorResults: validators.map((validator, index) => {
          try {
            return {
              index,
              keys: Object.keys(validator || {}),
              result: validator.validate(true)
            };
          } catch (error) {
            return {
              index,
              keys: Object.keys(validator || {}),
              error: error && error.message ? error.message : String(error)
            };
          }
        }),
        recaptchaNodes,
        buttonDisabled: button ? button.disabled : null
      });
    });
  }));
}

async function placeOrder(page) {
  await acceptVisibleAgreements(page);

  const diagnostics = await getCheckoutDiagnostics(page);
  logStage(`checkout diagnostics: ${JSON.stringify(diagnostics)}`);

  const button = page.locator('.payment-method._active button.action.primary.checkout').first();
  await button.waitFor({ state: 'visible', timeout: 60000 });
  await button.click({ force: true });

  await page.waitForURL(/\/checkout\/onepage\/success\/?$/i, { timeout: 120000 });
  await page.waitForSelector('.checkout-success', { timeout: 60000 });
  await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
}

async function fetchJson(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.headers || {})
    }
  });

  if (!response.ok) {
    throw new Error(`Request failed: ${response.status} ${response.statusText} (${url})`);
  }

  return response.json();
}

async function clearMailpit(mailpitUrl) {
  const response = await fetch(`${mailpitUrl}/api/v1/messages`, {
    method: 'DELETE'
  });

  if (!response.ok) {
    throw new Error(`Failed to clear Mailpit inbox: ${response.status} ${response.statusText}`);
  }
}

async function waitForOrderEmail(mailpitUrl, emailAddress, orderNumber) {
  const deadline = Date.now() + 90000;

  while (Date.now() < deadline) {
    const payload = await fetchJson(`${mailpitUrl}/api/v1/messages`);
    const messages = Array.isArray(payload.messages) ? payload.messages : [];
    const match = messages.find((message) => {
      const subject = String(message.Subject || '');
      const to = JSON.stringify(message.To || []);
      return subject.toLowerCase().includes('order confirmation')
        && to.toLowerCase().includes(emailAddress.toLowerCase())
        && (!orderNumber || subject.includes(orderNumber));
    }) || messages.find((message) => {
      const to = JSON.stringify(message.To || []);
      return to.toLowerCase().includes(emailAddress.toLowerCase());
    });

    if (match) {
      let details = null;
      try {
        details = await fetchJson(`${mailpitUrl}/api/v1/message/${match.ID}`);
      } catch (_) {
        // message detail shape varies by Mailpit version; summary is still enough to prove delivery
      }

      return { summary: match, details };
    }

    await new Promise((resolve) => setTimeout(resolve, 2000));
  }

  throw new Error('Timed out waiting for the order confirmation email in Mailpit');
}

async function extractOrderNumber(page) {
  const selectors = [
    '.checkout-success a.order-number',
    '.checkout-success p a',
    '.checkout-success p span'
  ];

  for (const selector of selectors) {
    const locator = page.locator(selector).first();
    if (await locator.isVisible().catch(() => false)) {
      const text = (await locator.innerText().catch(() => '')).trim();
      if (text) {
        return text.replace(/^#/, '');
      }
    }
  }

  return '';
}

async function main() {
  const config = getConfig();
  const ts = timestampLocal();
  const outDir = path.resolve(process.cwd(), '.playwright', 'artifacts', 'checkout-etransfer');
  fs.mkdirSync(outDir, { recursive: true });

  const browser = await chromium.launch({ headless: config.headless });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 2200 },
    ignoreHTTPSErrors: true
  });
  const page = await context.newPage();
  const emailAddress = `playwright+etransfer-${Date.now()}@example.com`;

  page.on('console', async (msg) => {
    const type = msg.type();
    if (type === 'error' || type === 'warning') {
      const values = [];
      for (const arg of msg.args()) {
        values.push(await arg.jsonValue().catch(() => null));
      }
      logStage(`browser ${type}: ${msg.text()} ${values.length ? JSON.stringify(values) : ''}`.trim());
    }
  });

  page.on('pageerror', (error) => {
    logStage(`page error: ${error.message}`);
  });

  page.on('requestfailed', (request) => {
    const failure = request.failure();
    logStage(`request failed: ${request.method()} ${request.url()} ${failure ? failure.errorText : ''}`.trim());
  });

  page.on('response', async (response) => {
    const url = response.url();
    if (!isCheckoutDiagnosticsUrl(url)) {
      return;
    }

    let bodySnippet = '';
    try {
      const text = await response.text();
      bodySnippet = text.replace(/\s+/g, ' ').slice(0, 600);
    } catch (_) {
      bodySnippet = '[unavailable]';
    }

    logStage(`response ${response.status()} ${response.request().method()} ${url} ${bodySnippet}`);
  });

  try {
    logStage('clearing Mailpit inbox');
    await clearMailpit(config.mailpitUrl);

    logStage('opening storefront home');
    await page.goto(new URL('/', config.baseUrl).toString(), { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
    await dismissNewsletterPopup(page);

    logStage(`opening product ${config.productPath}`);
    await openProduct(page, config.baseUrl, config.productPath);
    logStage(`selecting weight ${config.weightLabel}`);
    await selectKratomWeight(page, config.weightLabel);
    logStage('adding product to cart');
    await addToCart(page);
    logStage('navigating to checkout');
    await goToCheckout(page, config.baseUrl);
    await dismissNewsletterPopup(page);
    logStage(`filling shipping address for ${config.province}`);
    await fillShippingAddress(page, config.province);
    logStage('selecting first shipping method');
    const shippingMethod = await selectFirstShippingMethod(page);
    logStage(`selected shipping method: ${shippingMethod}`);
    logStage(`continuing as guest ${emailAddress}`);
    await proceedToPayment(page, emailAddress);
    logStage('selecting Interac e-Transfer payment method');
    const checkoutNotice = await selectInteracPaymentMethod(page);
    logStage('placing order');
    await placeOrder(page);

    logStage('capturing success page');
    const orderNumber = await extractOrderNumber(page);
    const successHeading = (await page.locator('.local-etransfer-success__title').first().innerText()).trim();
    const screenshotPath = path.join(outDir, `${ts}-success.png`);
    await page.screenshot({ path: screenshotPath, fullPage: true });

    logStage('waiting for confirmation email in Mailpit');
    const emailResult = await waitForOrderEmail(config.mailpitUrl, emailAddress, orderNumber);

    const summary = {
      timestamp: ts,
      emailAddress,
      orderNumber,
      shippingMethod,
      checkoutNotice,
      successHeading,
      screenshotPath,
      mailpitMessageId: emailResult.summary ? emailResult.summary.ID : null,
      mailpitSubject: emailResult.summary ? emailResult.summary.Subject : null,
      mailpitTo: emailResult.summary ? emailResult.summary.To : null
    };

    fs.writeFileSync(path.join(outDir, `${ts}.json`), JSON.stringify(summary, null, 2));
    fs.writeFileSync(path.join(outDir, 'latest.json'), JSON.stringify(summary, null, 2));
    fs.copyFileSync(screenshotPath, path.join(outDir, 'latest-success.png'));

    console.log(JSON.stringify(summary, null, 2));
  } catch (error) {
    const failureScreenshot = path.join(outDir, `${ts}-failure.png`);
    const visibleErrors = await page.locator('.message-error, [role=\"alert\"], .mage-error, .payment-method._active .message').allInnerTexts().catch(() => []);
    await page.screenshot({ path: failureScreenshot, fullPage: true }).catch(() => {});
    console.error(`[checkout-etransfer] failure screenshot: ${failureScreenshot}`);
    if (visibleErrors.length) {
      console.error(`[checkout-etransfer] visible errors: ${JSON.stringify(visibleErrors)}`);
    }
    throw error;
  } finally {
    await context.close().catch(() => {});
    await browser.close().catch(() => {});
  }
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
