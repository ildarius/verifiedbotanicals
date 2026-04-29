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
    username: fromEnv('MAGENTO_ADMIN_USER', 'playwright-admin'),
    password: fromEnv('MAGENTO_ADMIN_PASSWORD', 'PWAdmin!23456'),
    storageStatePath: path.resolve(process.cwd(), '.playwright', 'admin-auth.json')
  };
}

async function main() {
  const config = getConfig();
  fs.mkdirSync(path.dirname(config.storageStatePath), { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    ignoreHTTPSErrors: true
  });
  const page = await context.newPage();

  await page.goto(new URL(config.adminPath, config.baseUrl).toString(), {
    waitUntil: 'domcontentloaded'
  });

  await page.locator('#username').fill(config.username);
  await page.locator('#login').fill(config.password);
  await page.getByRole('button', { name: 'Sign in' }).click();
  await page.waitForLoadState('networkidle');

  const loginError = page.locator('.message-error');
  if (await loginError.count()) {
    throw new Error(`Admin login failed: ${await loginError.first().innerText()}`);
  }

  await page.waitForURL(/admin/i);
  await context.storageState({ path: config.storageStatePath });
  console.log(`Saved admin session to ${config.storageStatePath}`);

  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
