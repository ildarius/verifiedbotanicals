# Automation Checkout Hardening (2026-08-26)

## Scope
- Resume and harden the standalone automation repo at `/home/ildar/projects/magento/automated-tests`.
- Investigate intermittent checkout stalls around product open, weight selection, and cart/checkout navigation.
- Investigate and reduce storefront/frontend JS errors affecting automation stability.

## Repos / Local State

### Main Magento repo
- Path: `/home/ildar/projects/magento`
- Pre-existing local changes preserved:
  - `.gitignore` modified
  - `tools/playwright/README.md` untracked

### Standalone automation repo
- Path: `/home/ildar/projects/magento/automated-tests`
- This remains the source of truth for automation.
- Local uncommitted changes were already present before this session, including:
  - multiple modified docs/scripts/libs from the migration/refactor work
  - untracked `lib/admin.js`
  - untracked `lib/browser.js`
  - untracked `tmp/`
- `tmp/` was not touched or deleted.

## Automation Changes Applied

### Persist checkout progress during the run
Updated:
- `/home/ildar/projects/magento/automated-tests/scripts/run-checkout.js`

Changes:
- every `logStep()` now rewrites `summary.json` immediately
- every `logStep()` appends one JSON line to `steps.ndjson`
- `summary.steps` is preserved even if the process hangs before `finally`
- added finer-grained step logging for:
  - storefront home open
  - product open
  - weight selection
  - cart/checkout transition

Result:
- stalled runs now leave partial artifacts on disk under `output/artifacts/checkout-runs/...`

### Replace brittle `networkidle` dependencies with DOM readiness
Updated:
- `/home/ildar/projects/magento/automated-tests/lib/storefront.js`

Added/changed:
- `openStorefrontHome()`
- `waitForStorefrontPageReady()`
- `waitForProductPageReady()`
- `waitForCartPageReady()`
- `openProduct()` now uses DOM markers instead of treating `networkidle` as the success condition
- `goToCheckout()` now uses `domcontentloaded` plus cart/checkout DOM checks instead of `waitUntil: 'networkidle'`

Observed reason for the original flakiness:
- third-party requests frequently keep the page from reaching a clean idle state
- examples observed during runs:
  - Google Analytics `g/collect`
  - Google Tag Manager
  - Braintree assets on cart/checkout

### Fix popup helper wasting time when no popup exists
Updated:
- `/home/ildar/projects/magento/automated-tests/lib/storefront.js`

Change:
- `dismissNewsletterPopup()` now exits after a few idle passes when no visible popup candidate exists

Why this mattered:
- earlier runs were paying most or all of the 10-second popup timeout even when there was no popup to close

### Keep weight-selection diagnostics
Updated:
- `/home/ildar/projects/magento/automated-tests/lib/storefront.js`
- `/home/ildar/projects/magento/automated-tests/scripts/run-checkout.js`

Current instrumentation in `selectKratomWeight()` logs:
- before waiting for the swatch
- after the initial click
- retry clicks
- observed `class` and `aria-checked`
- timeout state if selection fails

### Make shared address helper compatible with both config and fixture shapes
Updated:
- `/home/ildar/projects/magento/automated-tests/lib/storefront.js`

Change:
- `fillShippingAddress()` now tolerates callers that only provide:
  - `region`
  - `city`
  - `postcode`
- defaulted:
  - `countryId = 'CA'`
  - `telephone = '0000000000'`

Why:
- the tax fixture does not include the normalized `config/store.js` shape

### Propagate the hardened shared flow into active scripts
Updated:
- `/home/ildar/projects/magento/automated-tests/scripts/run-checkout.js`
- `/home/ildar/projects/magento/automated-tests/scripts/storefront-shipping-scenarios.js`
- `/home/ildar/projects/magento/automated-tests/scripts/storefront-tax-scenarios.js`
- `/home/ildar/projects/magento/automated-tests/scripts/storefront-checkout-etransfer.js`

These scripts now rely on the shared storefront helpers instead of duplicating the older fragile waits.

## Magento / Frontend Fixes Applied

### Fixed one real theme `jQuery` error
Updated:
- `/home/ildar/projects/magento/app/design/frontend/Sm/themecore/web/js/main.js`

Fix:
- replaced direct global `jQuery` calls in the newsletter popup block with the AMD-injected `$`

This removed the earlier storefront/product-page `jQuery is not defined` error caused by that theme file.

### Fixed one checkout-specific global-jQuery error source
Updated:
- `/home/ildar/projects/magento/app/code/Sm/BundleImage/view/frontend/web/js/printThis.js`

Fixes applied:
- avoid `this instanceof jQuery`
- use `$.each(...)` instead of `jQuery.each(...)`
- close the wrapper with `})(window.jQuery || window.$);`

This removed the earlier checkout-route `jQuery is not defined` error from `Sm_BundleImage/js/printThis.js`.

### Resumed follow-up: converted `printThis` into a RequireJS-safe AMD module
Updated:
- `/home/ildar/projects/magento/app/code/Sm/BundleImage/view/frontend/web/js/printThis.js`

Fix:
- replaced the self-executing global wrapper `}(window.jQuery || window.$)` with `define(['jquery'], function ($) { ... })`
- returned `$.fn.printThis` from the module so `bundle-image.js` can keep depending on `printThis` through RequireJS

Why:
- the remaining checkout error `Cannot read properties of undefined (reading 'fn')` was caused by the plugin executing before a global `$` existed
- this module was already wired through `view/frontend/requirejs-config.js`, so the robust fix was to make the file itself a proper AMD module instead of depending on globals

### Reduced checkout CSP noise from custom/theme inline scripts to one remaining core/runtime hit
Updated:
- `/home/ildar/projects/magento/app/code/Magefan/Blog/view/frontend/templates/lazyload-js.phtml`
- `/home/ildar/projects/magento/app/code/Magefan/Blog/view/frontend/web/js/lazyload-init.js`
- `/home/ildar/projects/magento/app/code/Sm/AutoCompleteSearch/view/frontend/templates/autocomplete.phtml`
- `/home/ildar/projects/magento/app/code/Sm/CartQuickPro/view/frontend/templates/popup-login/login-mini-form.phtml`
- `/home/ildar/projects/magento/app/code/Sm/CartQuickPro/view/frontend/web/js/popup-login.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Checkout/templates/cart/minicart.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Checkout/templates/onepage.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/layout/default.xml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/account/customer.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/js/customer-data.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/js/customer-data/invalidation-rules.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/js/section-config.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_GoogleGtag/templates/ga.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageBuilder/templates/googlemaps.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageBuilder/templates/widget_initializer.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageBuilder/web/js/require-config.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageCache/templates/form_key_provider.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageCache/templates/javascript.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageCache/web/js/page-cache-init.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Theme/templates/js/cookie.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Theme/templates/js/cookie_status.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Theme/web/js/require-config.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Ui/templates/wysiwyg/active_editor.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Wishlist/templates/wishlist-header.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_CartQuickPro/templates/quickview.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_CartQuickPro/web/js/quickview-init.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_MegaMenu/templates/horizontal.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_MegaMenu/web/js/horizontal-init.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/web/css/source/pages/checkout/_styles.less`

What changed:
- converted multiple inline `require(...)`, `require.config(...)`, and `text/x-magento-init` emitters to `data-mage-init` or external AMD modules
- moved the horizontal mega menu and CartQuickPro quick-view bootstrap code into theme JS files
- moved Google Gtag and Page Cache form-key initialization into hidden `data-mage-init` containers
- replaced the checkout loader `renderStyleAsTag(...)` helper in the theme `onepage.phtml` override with a class-based CSS rule

Observed result:
- the direct checkout console probe on Thursday, August 27, 2026 dropped from five blocked inline-script CSP violations to one remaining unstable inline-script violation
- that last CSP hash still rotates per run, so it is not yet reduced to a single static template hash whitelist candidate

## Magento Commands Run

Ran inside `ddev-magento-web`:

```bash
docker exec -u 1000 ddev-magento-web php bin/magento setup:static-content:deploy -f en_US
docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page
```

Static content deploy was rerun multiple times during the August 26-27, 2026 checkout-CSP cleanup as each batch of theme/template changes landed.

## Verification Performed

### Standalone automation
Command rerun several times:

```bash
cd /home/ildar/projects/magento/automated-tests
npm run checkout:run -- --product red_bali --weight 25g --province QC --stage shipping-step
```

Latest successful artifact directory:
- `/home/ildar/projects/magento/automated-tests/output/artifacts/checkout-runs/20260826-194857__red-bali__25g__QC__shipping-step`

Follow-up verification after the AMD fix:

```bash
cd /home/ildar/projects/magento/automated-tests
npm run checkout:run -- --product red_bali --weight 25g --province QC --stage shipping-step
```

Latest successful artifact directory from the resumed pass:
- `/home/ildar/projects/magento/automated-tests/output/artifacts/checkout-runs/20260826-200602__red-bali__25g__QC__shipping-step`

Latest successful artifact directory after the August 27, 2026 CSP follow-up:
- `/home/ildar/projects/magento/automated-tests/output/artifacts/checkout-runs/20260827-083053__red-bali__25g__QC__shipping-step`

Observed result:
- checkout reached `https://magento.ddev.site/checkout/#shipping`
- the run still showed expected third-party `requestfailed` noise
- no `pageerror` entry was emitted for `Sm_BundleImage/js/printThis.js`

Additional successful checks:

```bash
cd /home/ildar/projects/magento/automated-tests
PW_ONLY_SKUS=RB25 PW_ONLY_PROVINCES=QC PW_ONLY_METHODS='Regular Shipping' npm run shipping:scenarios

cd /home/ildar/projects/magento/automated-tests
PW_ONLY_PROVINCES=QC npm run tax:scenarios
```

Latest tax manifest:
- `/home/ildar/projects/magento/automated-tests/output/artifacts/tax-scenarios/manifest.json`

### Browser probes
Direct Playwright/Node probes were used to inspect `pageerror`, `consoleErrors`, and failed requests on:
- storefront home
- `/red-bali.html`
- `/checkout/`

Confirmed fixed during this session:
- the earlier `jQuery is not defined` error from `app/design/frontend/Sm/themecore/web/js/main.js`
- the earlier checkout `jQuery is not defined` error from `Sm_BundleImage/js/printThis.js`

Confirmed improved during the August 27, 2026 follow-up:
- the checkout console no longer shows the earlier custom/theme blocked inline scripts from wishlist header, popup login, quick-view, mega menu, customer-data, PageBuilder widget init, or the Google Gtag/Page Cache init blocks that were previously still inline
- the checkout shipping-step path still completes after the CSP cleanup, with the latest passing artifact at `/home/ildar/projects/magento/automated-tests/output/artifacts/checkout-runs/20260827-083053__red-bali__25g__QC__shipping-step`

Latest direct checkout probe on Thursday, August 27, 2026:
- reached `https://magento.ddev.site/checkout/#shipping`
- `pageErrors`: none
- `consoleErrors`: one remaining inline-script CSP violation, one `paypalCheckout error r` error from `PayPal_Braintree/js/paypal/button.js`, and intermittent third-party script load failures such as Google Tag Manager / Google Analytics
- `failedRequests`: still includes expected external analytics/tag-manager noise

Probe caveat:
- using `curl` directly against `https://magento.ddev.site/checkout/` without a browser session or cart item lands on the empty-cart/cart route, so it is not a reliable source for mapping the live browser checkout DOM that the Playwright probe sees after add-to-cart

## Remaining Issues

### 1) One checkout CSP inline-script violation still remains
Checkout still logs one blocked inline-script CSP violation on the live browser checkout route after add-to-cart.

Current status:
- this did not block the tested shipping-step automation
- the remaining hash changes across runs, which suggests the final emitter is still coming from a runtime-generated inline script path rather than a single fixed template block
- the theme `Magento_Checkout/templates/onepage.phtml` override did not fully eliminate this last residual console error

### 2) External request and payment-widget noise remains expected
Observed during runs:
- Google Analytics request failures
- Google Tag Manager timeouts
- PayPal Braintree asset failures/timeouts/resets
- `paypalCheckout error r` from `PayPal_Braintree/js/paypal/button.js:187`
- occasional font request failures

Current status:
- these are still noisy in diagnostics
- the hardened automation no longer treats them as readiness blockers

## Best Next Starting Point

If resuming this work next session, start with:

1. Re-run the passing shipping-step harness first and use its artifact as the baseline:

```bash
cd /home/ildar/projects/magento/automated-tests
npm run checkout:run -- --product red_bali --weight 25g --province QC --stage shipping-step
```

2. Then run the direct probe pattern again to capture exact browser-side errors on the live checkout route after add-to-cart:
- open storefront home
- open `/red-bali.html`
- add to cart
- navigate to checkout
- dump `pageErrors`, `consoleErrors`, `failedRequests`

3. Focus on the one remaining rotating-hash CSP error first:
- do not trust `curl https://magento.ddev.site/checkout/` by itself for mapping this; it hits the empty-cart path without the browser/cart session state
- instead, inspect the live Playwright page DOM after checkout navigation and look for runtime-generated inline scripts added after Magento bootstrap
- the most suspicious remaining area is still early checkout/bootstrap code rather than the already-fixed custom modules

## Files Changed This Session

### Magento repo
- `/home/ildar/projects/magento/app/design/frontend/Sm/themecore/web/js/main.js`
- `/home/ildar/projects/magento/app/code/Sm/BundleImage/view/frontend/web/js/printThis.js`
- `/home/ildar/projects/magento/app/code/Magefan/Blog/view/frontend/templates/lazyload-js.phtml`
- `/home/ildar/projects/magento/app/code/Magefan/Blog/view/frontend/web/js/lazyload-init.js`
- `/home/ildar/projects/magento/app/code/Sm/AutoCompleteSearch/view/frontend/templates/autocomplete.phtml`
- `/home/ildar/projects/magento/app/code/Sm/CartQuickPro/view/frontend/templates/popup-login/login-mini-form.phtml`
- `/home/ildar/projects/magento/app/code/Sm/CartQuickPro/view/frontend/web/js/popup-login.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Checkout/templates/cart/minicart.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Checkout/templates/onepage.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/layout/default.xml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/account/customer.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/js/customer-data.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/js/customer-data/invalidation-rules.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Customer/templates/js/section-config.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_GoogleGtag/templates/ga.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageBuilder/templates/googlemaps.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageBuilder/templates/widget_initializer.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageBuilder/web/js/require-config.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageCache/templates/form_key_provider.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageCache/templates/javascript.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_PageCache/web/js/page-cache-init.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Theme/templates/js/cookie.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Theme/templates/js/cookie_status.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Theme/web/js/require-config.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Ui/templates/wysiwyg/active_editor.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Magento_Wishlist/templates/wishlist-header.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_CartQuickPro/templates/quickview.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_CartQuickPro/web/js/quickview-init.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_MegaMenu/templates/horizontal.phtml`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_MegaMenu/web/js/horizontal-init.js`
- `/home/ildar/projects/magento/app/design/frontend/Sm/market/web/css/source/pages/checkout/_styles.less`
- `/home/ildar/projects/magento/dev/notes/automation-checkout-hardening-2026-08-26.md`

### Standalone automation repo
- `/home/ildar/projects/magento/automated-tests/lib/storefront.js`
- `/home/ildar/projects/magento/automated-tests/scripts/run-checkout.js`
- `/home/ildar/projects/magento/automated-tests/scripts/storefront-shipping-scenarios.js`
- `/home/ildar/projects/magento/automated-tests/scripts/storefront-tax-scenarios.js`
- `/home/ildar/projects/magento/automated-tests/scripts/storefront-checkout-etransfer.js`
