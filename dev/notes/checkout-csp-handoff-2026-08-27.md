# Checkout CSP Hand-off (2026-08-27)

## Purpose
- Use this note as the resume point for the current checkout hardening work.
- Replace `automated-tests/tmp/hand-off-doc.txt` for future session context loading.

## Current State
- The shared checkout automation flow is stable again and consistently reaches `https://magento.ddev.site/checkout/#shipping`.
- The residual checkout inline-script CSP violation is fixed.
- The earlier checkout `pageerror` from `Sm_BundleImage/js/printThis.js` is fixed.
- Expected third-party noise still appears during checkout runs:
  - Google Analytics / Google Tag Manager request failures
  - PayPal Braintree console noise such as `paypalCheckout error r`
- This state was reverified on `2026-08-28` with a fresh browser-backed checkout CSP probe.

## Latest Verified Run
- Command:
  - `node automated-tests/scripts/checkout-csp-probe.js --product red_bali --weight 25g`
- Latest probe artifact:
  - `/home/ildar/projects/magento/output/artifacts/checkout-csp-probe/20260828-092617__red-bali__25g`
- Latest probe summary:
  - `/home/ildar/projects/magento/output/artifacts/checkout-csp-probe/20260828-092617__red-bali__25g/probe.json`
- Latest raw checkout response:
  - `/home/ildar/projects/magento/output/artifacts/checkout-csp-probe/20260828-092617__red-bali__25g/checkout-response.html`
- Prior clean probe artifact:
  - `/home/ildar/projects/magento/output/artifacts/checkout-csp-probe/20260827-132253__red-bali__25g`
- Prior passing checkout-run artifact:
  - `/home/ildar/projects/magento/automated-tests/output/artifacts/checkout-runs/20260827-083053__red-bali__25g__QC__shipping-step`
- Observed result:
  - checkout reached the shipping step
  - no `pageerror` entries were emitted
  - no checkout CSP inline-script violations remained
  - the fresh probe still reported `blockedHashes: []`
  - the fresh probe still reported `pageErrors: []`
  - only expected third-party noise remained (`paypalCheckout error r` and analytics request failures)

## Automation Repo Changes

### Hardened flow and diagnostics
- `automated-tests/lib/storefront.js`
  - replaced brittle `networkidle` assumptions with DOM readiness checks
  - hardened product, cart, and checkout navigation helpers
  - shortened newsletter-popup dismissal when no popup exists
  - kept detailed kratom weight-selection diagnostics
  - made shipping address filling tolerate both normalized config and tax-fixture shapes
- `automated-tests/scripts/run-checkout.js`
  - writes `summary.json` on every `logStep()`
  - appends step records to `steps.ndjson`
  - preserves partial step history even when a run stalls
- `automated-tests/scripts/storefront-shipping-scenarios.js`
- `automated-tests/scripts/storefront-tax-scenarios.js`
- `automated-tests/scripts/storefront-checkout-etransfer.js`
  - switched to the hardened shared storefront helpers
- `automated-tests/scripts/checkout-csp-probe.js`
  - captures the live `/checkout/` document response from a real add-to-cart browser session
  - records console errors, failed requests, inline-script hashes, and matched blocked hashes
  - writes `probe.json` and `checkout-response.html` artifacts for future CSP debugging

## Magento Repo Changes

### Fixed non-CSP runtime errors
- `app/design/frontend/Sm/themecore/web/js/main.js`
  - replaced direct global `jQuery` use in the newsletter popup path with the injected AMD `$`
- `app/code/Sm/BundleImage/view/frontend/web/js/printThis.js`
  - converted the plugin from a global self-executing wrapper to a RequireJS-safe AMD module
  - this removed the old checkout `pageerror` tied to missing global `$`

### Reduced custom/theme checkout CSP noise
- Converted inline `require(...)`, `require.config(...)`, or inline bootstrap blocks into `data-mage-init` or external AMD modules where touched.
- Key resumed-pass files:
  - `app/design/frontend/Sm/market/Sm_CartQuickPro/templates/quickview.phtml`
  - `app/design/frontend/Sm/market/Sm_CartQuickPro/web/js/quickview-init.js`
  - `app/design/frontend/Sm/market/Sm_MegaMenu/templates/horizontal.phtml`
  - `app/design/frontend/Sm/market/Sm_MegaMenu/web/js/horizontal-init.js`
  - `app/design/frontend/Sm/market/Magento_GoogleGtag/templates/ga.phtml`
  - `app/design/frontend/Sm/market/Magento_Checkout/templates/onepage.phtml`
  - `app/design/frontend/Sm/market/web/css/source/pages/checkout/_styles.less`
- Additional touched CSP-cleanup areas:
  - `app/code/Magefan/Blog/view/frontend/templates/lazyload-js.phtml`
  - `app/code/Magefan/Blog/view/frontend/web/js/lazyload-init.js`
  - `app/code/Sm/AutoCompleteSearch/view/frontend/templates/autocomplete.phtml`
  - `app/code/Sm/CartQuickPro/view/frontend/templates/popup-login/login-mini-form.phtml`
  - `app/code/Sm/CartQuickPro/view/frontend/web/js/popup-login.js`
  - `app/design/frontend/Sm/market/Magento_Customer/...`
  - `app/design/frontend/Sm/market/Magento_PageBuilder/...`
  - `app/design/frontend/Sm/market/Magento_PageCache/...`
  - `app/design/frontend/Sm/market/Magento_Theme/...`
  - `app/design/frontend/Sm/market/Magento_Ui/templates/wysiwyg/active_editor.phtml`
  - `app/design/frontend/Sm/market/Magento_Wishlist/templates/wishlist-header.phtml`
  - `app/design/frontend/Sm/market/Magento_Checkout/templates/cart/minicart.phtml`

## Final CSP Fix
- The last blocked inline script was the theme override in `app/design/frontend/Sm/market/Magento_Checkout/templates/cart/minicart.phtml`.
- It emitted a bare inline `window.checkout = {...};` block without Magento CSP nonce handling.
- The fix switched that bootstrap to `SecureHtmlRenderer->renderTag('script', ...)`, which caused the checkout response to emit the expected nonce-bearing script tag.
- Before the fix, the probe matched the blocked hash to the minicart `window.checkout` inline script:
  - blocked hash: `sha256-29GVuyb8l1WXjpnPx/fZHye/P4fcZiMaLU7weYH9DIk=`
  - pre-fix artifact: `/home/ildar/projects/magento/output/artifacts/checkout-csp-probe/20260827-132109__red-bali__25g`
- After deploy and cache clean, the follow-up probe showed:
  - `blockedHashes: []`
  - no CSP inline-script console errors
  - post-fix artifact: `/home/ildar/projects/magento/output/artifacts/checkout-csp-probe/20260827-132253__red-bali__25g`

## Deployment and Validation Commands
- Magento deploy/flush after template changes:
  - `docker exec -u 1000 ddev-magento-web php bin/magento setup:static-content:deploy -f en_US`
  - `docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page`
- Additional automation checks that passed during this hardening run:
  - `PW_ONLY_SKUS=RB25 PW_ONLY_PROVINCES=QC PW_ONLY_METHODS='Regular Shipping' npm run shipping:scenarios`
  - `PW_ONLY_PROVINCES=QC npm run tax:scenarios`
- Latest tax manifest:
  - `/home/ildar/projects/magento/automated-tests/output/artifacts/tax-scenarios/manifest.json`

## Remaining Issue
- No checkout inline-script CSP violations remain in the latest verified probe from `2026-08-28`.
- Remaining checkout console/request noise appears to be third-party:
  - PayPal Braintree console error `paypalCheckout error r`
  - Google Analytics / Google Tag Manager request failures in local/headless runs

## Important Debugging Constraint
- Do not use plain `curl https://magento.ddev.site/checkout/` by itself for checkout DOM or CSP debugging.
- Without the browser/cart session, that request falls back to the cart or empty-cart path and does not represent the live checkout DOM that Playwright reaches after add-to-cart.

## Recommended Next Steps
1. Treat checkout CSP cleanup as complete unless a fresh browser artifact shows a new violation.
2. If needed later, use `node automated-tests/scripts/checkout-csp-probe.js --product red_bali --weight 25g` instead of ad hoc probes so the raw checkout response and script hashes are saved.
3. Ignore the already-resolved `printThis`, theme-global-`jQuery`, and minicart `window.checkout` issues unless they reappear in a fresh artifact.
4. Investigate PayPal Braintree console noise separately only if it starts breaking checkout behavior rather than remaining non-blocking noise.
