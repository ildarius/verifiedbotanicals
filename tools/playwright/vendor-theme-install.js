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
    adminPath: fromEnv('MAGENTO_ADMIN_PATH', '/admin'),
    storageStatePath: path.resolve(process.cwd(), '.playwright', 'admin-auth.json'),
    screenshotDir: path.resolve(process.cwd(), '.playwright', 'artifacts'),
    demoButtonLabel: fromEnv('MAGENTO_THEME_DEMO_BUTTON', 'Shop 1'),
    demoButtonId: fromEnv('MAGENTO_THEME_DEMO_BUTTON_ID', 'market_theme_install_import_shop1'),
    themeLabel: fromEnv('MAGENTO_THEME_LABEL', 'Sm Market'),
    designConfigUrl: fromEnv(
      'MAGENTO_DESIGN_CONFIG_URL',
      'https://magento.ddev.site/admin/theme/design_config/index/key/c3c0d26484dc4f5f3bb52a34c1bc1681a1b2a2af520c87d825ba3feb12598a1a/'
    ),
    systemConfigUrl: fromEnv(
      'MAGENTO_SYSTEM_CONFIG_URL',
      'https://magento.ddev.site/admin/admin/system_config/index/key/a9c80163ca3196125d0808551960eb25617b80d9d9d3b5b2b25f19af5b2c4970/'
    )
  };
}

async function clickFirstVisible(page, selectors) {
  for (const selector of selectors) {
    const locator = page.locator(selector).first();
    if (await locator.count()) {
      await locator.click();
      return true;
    }
  }
  return false;
}

async function clickButtonByText(page, text) {
  const xpath = `//button[contains(@title, "${text}") or .//span[normalize-space()="${text}"] or normalize-space()="${text}"]`;
  const locator = page.locator(`xpath=${xpath}`).first();
  await locator.waitFor({ state: 'visible', timeout: 30000 });
  await locator.click();
}

async function clickButtonById(page, id) {
  const locator = page.locator(`#${id}`).first();
  await locator.waitFor({ state: 'attached', timeout: 30000 });
  await page.evaluate((buttonId) => {
    const button = document.getElementById(buttonId);
    if (!button) {
      throw new Error(`Button not found: ${buttonId}`);
    }
    button.click();
  }, id);
}

async function findHref(page, predicateSource) {
  const href = await page.locator('a').evaluateAll((els, source) => {
    const predicate = new Function('entry', source);
    const match = els
      .map((a) => ({
        text: (a.textContent || '').trim(),
        href: a.href
      }))
      .find((entry) => predicate(entry));
    return match ? match.href : null;
  }, predicateSource);

  if (!href) {
    throw new Error(`Unable to find href with predicate: ${predicateSource}`);
  }

  return href;
}

async function main() {
  const config = getConfig();
  fs.mkdirSync(config.screenshotDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    storageState: config.storageStatePath
  });
  const page = await context.newPage();

  await page.goto(new URL(`${config.adminPath}/admin/dashboard/index/`, config.baseUrl).toString(), {
    waitUntil: 'domcontentloaded'
  });
  await page.waitForLoadState('networkidle');
  await clickFirstVisible(page, [
    'button[data-role="action"][title="Accept"]',
    'button.action-secondary'
  ]);

  const designConfigHref = await findHref(
    page,
    "entry => entry.href.includes('theme/design_config/index/')"
  ).catch(() => config.designConfigUrl);
  await page.goto(designConfigHref, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle');

  await page
    .locator("xpath=//tr[td[contains(normalize-space(), 'Default Store View')]]//a[contains(normalize-space(), 'Edit')]")
    .first()
    .click();
  await page.waitForLoadState('networkidle');

  await page.locator("select[name='theme_theme_id']").selectOption({ label: config.themeLabel });
  await clickButtonByText(page, 'Save Configuration');
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: path.join(config.screenshotDir, 'design-config-sm-market.png'), fullPage: true });

  await page.goto(new URL(`${config.adminPath}/admin/dashboard/index/`, config.baseUrl).toString(), {
    waitUntil: 'domcontentloaded'
  });
  await page.waitForLoadState('networkidle');

  const systemConfigHref = await findHref(
    page,
    "entry => entry.href.includes('/admin/system_config/index/') && entry.text === 'Configuration'"
  ).catch(() => config.systemConfigUrl);
  await page.goto(systemConfigHref, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle');

  await page.locator("xpath=//span[normalize-space()='SM Market' or normalize-space()='SM Market']/ancestor::a[1]").first().click();
  await page.waitForLoadState('networkidle');

  await page.screenshot({ path: path.join(config.screenshotDir, 'sm-market-config.png'), fullPage: true });

  await clickButtonById(page, 'market_theme_install_import_blocks');
  await page.waitForLoadState('networkidle');

  await clickButtonById(page, 'market_theme_install_import_pages');
  await page.waitForLoadState('networkidle');

  await clickButtonById(page, config.demoButtonId);
  await page.waitForLoadState('networkidle');

  await clickButtonByText(page, 'Save Config');
  await page.waitForLoadState('networkidle');

  await page.screenshot({ path: path.join(config.screenshotDir, 'sm-market-config-after-import.png'), fullPage: true });
  console.log('Completed SM Market admin import flow');

  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
