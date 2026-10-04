// Headless check of the success-page account notice (Local_GuestToCustomer) on the live store.
//
// Places a guest order server-side (place-test-order.php), seeds this browser's checkout session the way
// checkout would (seed-checkout-session.php), then opens /checkout/onepage/success/ and optionally sets the
// password from the inline form. The real place-order call is behind invisible reCAPTCHA, hence the seeding.
//
// usage (from the Magento root):
//   node dev/tools/guest-to-customer/success-page-check.js guest2customer+$(date +%s)@example.com 'Some-Pass-2026!'
//   MARK=0 node ...   seed without the "new account" marker (expect the "Sign in" version)
//   ATTACK=1 node ... also POST to the set-password endpoint directly (must be refused unless eligible)
//
// Screenshots go to var/tmp/guest-to-customer-*.png. Cancel the test orders and delete the test
// customers afterwards. Always use an @example.com email.
const path = require('path');
const { execSync } = require('child_process');
const puppeteer = require(path.join(process.env.HOME, 'node_modules/puppeteer'));

const ROOT = path.resolve(__dirname, '../../..');
const BASE = process.env.MAGENTO_BASE_URL || 'https://verifiedbotanicals.com';
const PHP = `PHP_INI_SCAN_DIR=/opt/cpanel/ea-php83/root/etc/php.d:${process.env.HOME}/php-cli.d `
  + '/opt/cpanel/ea-php83/root/usr/bin/php -d memory_limit=-1';
const shot = (name) => path.join(ROOT, 'var/tmp', `guest-to-customer-${name}.png`);
const [email, password] = process.argv.slice(2);

(async () => {
  if (!email || !email.endsWith('@example.com')) {
    throw new Error('Pass an @example.com email.');
  }
  const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
  const page = await browser.newPage();
  await page.setViewport({ width: 1400, height: 1000 });
  page.on('console', (m) => m.type() === 'error' && console.log('console error:', m.text()));

  await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
  const sid = (await page.cookies()).find((c) => c.name === 'PHPSESSID').value;
  const orderId = execSync(`${PHP} dev/tools/guest-to-customer/place-test-order.php '${email}' 2>/dev/null`, { cwd: ROOT })
    .toString().trim().split('\n').pop();
  console.log('order:', execSync(
    `php dev/tools/guest-to-customer/seed-checkout-session.php ${sid} ${orderId} ${process.env.MARK === '0' ? 0 : 1}`,
    { cwd: ROOT }
  ).toString().trim());

  await page.goto(BASE + '/checkout/onepage/success/', { waitUntil: 'networkidle2' });
  console.log('url:', page.url());
  await page.screenshot({ path: shot('1-success') });
  console.log('notice:', await page.evaluate(() => (document.querySelector('.local-g2c')?.innerText || 'NONE').replace(/\s+/g, ' ')));

  if (process.env.ATTACK) {
    console.log('direct POST:', await page.evaluate(async () => {
      const body = new URLSearchParams({
        form_key: document.cookie.match(/form_key=([^;]+)/)[1],
        password: 'Takeover-2026!x',
        password_confirmation: 'Takeover-2026!x',
      });
      const res = await fetch('/guesttocustomer/account/setPassword', { method: 'POST', body, credentials: 'include' });
      return `${res.status} ${await res.text()}`;
    }));
  }

  if (password && await page.$('#g2c-password')) {
    await page.type('#g2c-password', password);
    await page.type('#g2c-password-confirmation', password);
    const [resp] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('/guesttocustomer/account/setPassword')),
      page.click('.local-g2c button[type=submit]'),
    ]);
    console.log('setPassword:', resp.status(), await resp.text());
    await new Promise((r) => setTimeout(r, 2000));
    await page.screenshot({ path: shot('2-done') });
    await page.goto(BASE + '/sales/order/history/', { waitUntil: 'domcontentloaded' });
    console.log('signed in, order history:', page.url().includes('/sales/order/history'));
    await page.screenshot({ path: shot('3-history') });
  }
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
