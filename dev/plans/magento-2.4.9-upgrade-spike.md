# Magento 2.4.9 Upgrade Spike (keep SM Market v10.14)

Status: **in progress**. Branch: `spike/2.4.9` (created from master `a16d85b7`). Owner: next agent session.
Started: 2026-09-24.

This file tracks the whole 2.4.9 effort. Update the checklist and log as you go; when the work is
finished or abandoned, move this file to `dev/notes/` per `AGENTS.md`.

## Why

- Production was compromised on 2026-09-24 via **PolyShell (APSB25-94)**. Adobe fixed it only in
  2.4.9; it is not backported to any 2.4.7-pN release.
- Interim mitigation is in place locally (not yet committed or deployed at time of writing):
  `app/code/Local/PolyShellGuard/`, `.htaccess` denies in `.htaccess`, `pub/media/.htaccess`,
  `pub/media/custom_options/.htaccess`, probe harness `dev/tools/polyshell_probe.sh`.
  Evidence: `var/tmp/incident-20260924-media-executable-files/`.
- The codebase is on **base 2.4.7 with no -pN security releases** (`composer.lock`), so it also
  lacks CosmicSting (fixed 2.4.7-p1) and SessionReaper CVE-2025-54236 (fixed 2.4.7-p8). This is
  tracked as a separate, more urgent item below.

## Goal of the spike

Find out, with real results instead of estimates, whether the store runs on Magento 2.4.9 with
SM Market v10.14 and how much fixing that takes. Then restore DDEV to 2.4.7.

The spike is **local DDEV only**. Nothing here goes to production.

## Decisions so far

- Chosen route for the spike: **backup + git branch on the existing DDEV project** (not a separate
  DDEV project).
- Alternatives still open (researched separately, not part of this spike):
  - Hyvä theme (admin is researching licensing/compatibility)
  - Luma child theme styled like the current site
- The 2.4.7-pN security update should happen regardless of the outcome here.

## Key facts from the v10.13 → v10.14 comparison (2026-09-24)

Packages:
- v10.14: `var/tmp/Code_v10.14/magento2.4.x/sm-market-theme-m2.4.9_v10.14.zip`
  (quickstart zip alongside it is not needed)
- v10.13 (pristine baseline of what we installed):
  `/mnt/c/Users/ildar/Downloads/Mainfile_SM_Market_Ver10/Code_v10.13/magento2.4.x-new-framework/sm_market_theme_m2.4.6-2.4.8_v10.13.zip`

Real changes in v10.14 (everything else is identical):
- `composer.json` in every Sm module/theme: machine-rewritten metadata (ignored for `app/code`).
- `app/code/Sm/Themecore/Helper/Data.php`: `implode($hexCode)` → `implode('', $hexCode)`.
- `app/code/Sm/Themecore/Helper/MobileDetect.php`, `app/code/Sm/Market/Helper/MobileDetect.php`,
  `app/code/Sm/MegaMenu/view/frontend/templates/MobileDetect.php`: null-safe `$this->userAgent`.
  The MegaMenu copy is already locally modified (August prod fix); merge by hand.
- New in `app/design/frontend/Sm/market/web/css/source/`: `core_styles/`, `jquery.fancybox/`,
  `owlcarousel/`, `swiper/`. These are identical copies of the same folders in `Sm/themecore`;
  `market/web/css/styles-m.less` imports them. Possibly SM's fix for our known
  `setup:static-content:deploy` failure in `Sm/themecore`. Unverified.
- New demo import `app/code/Sm/Market/etc/import/fashion11.xml` (not needed).
- None of the SM classes that replace core classes changed (`Sm\Themecore\Block\Cms\Page|Block`,
  `Sm\Market\Controller\Cms\Index`, `Sm\AttributesSearch` search result/controller,
  `Sm\ShopBy\Block\FilterRenderer`, `Sm\BundleImage` option blocks, `Sm\MegaMenu` admin chooser and
  Wysiwyg images helper, `Sm\CartQuickPro` Result\Page plugin). These are the main 2.4.9 risk.

**Do NOT copy from the v10.14 zip** (packaging leftovers from a Windows dev install):
- `app/code/Local/WindowsFix/` (disables Magento validators on Windows; clashes with our `Local` namespace)
- `pub/static/`, `pub/errors/`, `pub/opt/`
- any `pub/media/**/.htaccess` (would revert the PolyShell fix to `custom_options/.htaccess`)
- `install-theme-windows.ps1`
- `pub/media/**` demo images in general

v10.14 no longer bundles Magefan or Coduzion; keep ours and find 2.4.9-compatible versions separately.

Our local drift from pristine v10.13: 40 modified SM files and 33 locally added paths. Notable ones
to protect: `Sm/FilterProducts/Block/FilterProducts.php`, `Sm/Themecore/Block/Cms/Page.php`,
`market/Sm_MegaMenu/templates/vertical.phtml`, both `grid-slider-deal2.phtml` copies, `part-37` LESS,
Magefan blog templates and CSS, `market/Magento_Checkout/templates/onepage.phtml`,
`market/Magento_Theme/templates/root.phtml`, `market/Magento_Ui`, `market/Magento_PageBuilder`.
The theme's own copies of core templates must be checked against 2.4.9 by hand.

## Environment facts

- DDEV: `nginx-fpm`, PHP **8.2** (`.ddev/config.yaml`). 2.4.9 needs PHP 8.3+; production runs ea-php83.
- `vendor/` (~760 MB) is **not tracked in git**; back it up as a directory.
- DDEV runs nginx, so `.htaccess` behaviour can't be verified in DDEV. Use a throwaway `httpd:2.4`
  container if needed.
- A past DDEV restart hung in `/pre-start.sh`; see `AGENTS.md` Run Findings for the workaround.
- Earlier failed upgrade leftovers: `vendor.broken-20260513/`, `generated.broken-20260513/`. Leave them alone.

## Checklist

### 0. Prerequisites
- [x] Confirm with the user how to handle uncommitted PolyShell work on `master`: committed to master as `a16d85b7`
- [x] DB backup: `backups/pre-2.4.9-spike-20260924_163116.sql.gz` (865 tables, dump completed marker verified)
- [x] Back up `vendor/`, `composer.json`, `composer.lock`, `app/etc/config.php`, `app/etc/env.php`, `.ddev/config.yaml`
      to `backups/pre-2.4.9-spike-20260924_163116/` (vendor copy: 103209 entries, `diff -rq` clean)
- [x] Baseline homepage capture: `.playwright/artifacts/homepage-history/20260924-163206-storefront-homepage.png`
- [x] Create branch `spike/2.4.9`

### 1. Platform
- [x] Check Adobe's 2.4.9 system requirements and record them here
      - Magento 2.4.9 root `composer.json` (GitHub tag `2.4.9`): `php ~8.3.0||~8.4.0||~8.5.0` (8.2 dropped; prod ea-php83 is OK)
      - Adobe system-requirements page, 2.4.9 column: Composer 2.10, MySQL 8.4, MariaDB 12.3 (recommended) / 11.8,
        OpenSearch 3, Valkey 9 (Redis not listed), Varnish 8, RabbitMQ 4.3, nginx 1.30
      - `opensearch-project/opensearch-php ^2.3`, `symfony/console ^7.4`, `monolog ^3.6`, `web-token/jwt-framework ^4.0`
      - DDEV today: MariaDB 10.6, OpenSearch 2.19.5 (both below the official 2.4.9 matrix; tried as-is first)
- [x] Switch DDEV to PHP 8.3 (match production), restart, and confirm the site still loads on 2.4.7
      (`php_version: "8.3"`, `ddev restart` clean, PHP 8.3.30, homepage 200, Playwright capture: no console/page errors)

### 2. Composer
- [x] Update to 2.4.9 (inside the web container). What worked:
      - plain `composer require magento/product-community-edition=2.4.9` is refused ("use 'require-commerce' instead")
        and 2.4.7's `require-dev` (`allure-phpunit ^2`, MFTF `^4.7`, PHPUnit 9) conflicts with 2.4.9
      - `composer require-commerce magento/product-community-edition 2.4.9 --no-update --force-root-updates --no-interaction`
        rewrote the root to the 2.4.9 template (adds `magento/composer`, PHPUnit 12, MFTF 6, symfony/finder 7.4)
      - `composer update -W --no-interaction`: exit 0, 1008 upgraded / 28 installed / 49 removed, no advisories
      - repo.magento.com needs Marketplace keys: put in project-root `auth.json` (gitignored); none existed before
- [x] Resolve conflicts; record each here
      - `mirasvit/module-affiliate`: its repo `afl2.packages.mirasvit.com` returns HTTP 401 (no license on this machine);
        it was already absent from `vendor/`. Removed the require + repository on the spike branch.
      - `magento/composer-*` plugins: no conflict.
      - **Composer's `magento-force: override` overwrites deployment files.** It reverted: root `.htaccess` (admin IP
        allowlist, PolyShell deny, cPanel `ea-php83` handler), `pub/.htaccess` and `pub/media/custom_options/.htaccess`
        (`04a8aa4b` no-`mod_version` fix -> back to `<IfVersion>`), `pub/media/.htaccess` (PolyShell denies), root and
        `pub/.user.ini` (cPanel 1500M memory, sessions path). All restored from HEAD; kept 2.4.9's narrowed media
        `get.php` rewrite (asset extensions only) merged into our `pub/media/.htaccess`, and took 2.4.9's
        `pub/static/.htaccess` (cache header only). No other locally-customised file was overwritten (checked
        every changed file for local git history).

### 3. SM v10.14 merge
- [x] Apply the real v10.14 changes listed above: `Themecore/Helper/Data.php`, `Themecore/Helper/MobileDetect.php`,
      `Market/Helper/MobileDetect.php` copied. MegaMenu `MobileDetect.php`: kept ours unchanged (our August fix is a
      superset; v10.14's copy still calls `stripos($this->userAgent, ...)` with a possible null)
- [x] Copy the four LESS folders into `market/web/css/source/` (verified identical to `Sm/themecore` copies)
- [x] Nothing from the "do NOT copy" list

### 4. Build
- [x] `bin/magento setup:upgrade`: exit 0 on MariaDB 10.6 (no RDBMS version check tripped), "Upgrade completed successfully"
- [x] `bin/magento setup:di:compile`: exit 0, **no errors** (32 s). Runtime class-load sweep done separately (see Findings)
- [x] `bin/magento setup:static-content:deploy -f en_US`: exit 0 for all 9 themes; **`Sm/themecore` failure is gone**.
      A/B (market deploy with the four v10.14 LESS folders removed): `styles-m.css` still builds, identical size (598106 B),
      so the fix comes from 2.4.9 itself, not the v10.14 LESS copies. Note: SCD quick strategy skips existing output;
      delete `pub/static/frontend/<theme>` first when re-testing.
- [x] `bin/magento indexer:reindex` + `cache:flush`: all indexers OK incl. Catalog Search on OpenSearch 2.19.5;
      `inventory_stock_1` still the correct view, all 6 kratom parents `is_salable=1`, 216 price-index rows

### 5. Verification
- [x] Homepage: `npm run pw:homepage-capture`, compare with the baseline
      - first 2.4.9 capture (`20260924-165222`): **completely unstyled**, 11x `require is not a function`. Cause: 2.4.9 split
        `<head>` output into `$headContent` + `$headCritical` + `$headAssets`; our
        `market/Magento_Theme/templates/root.phtml` override printed only `$headContent`. Fixed by adding
        `$headCritical ?? ''` and `$headAssets ?? ''` (the `??` keeps it valid on 2.4.7).
      - after fix (`20260924-165407`): matches the baseline visually (same 1440x5409), 0 console/page errors
      - after the rotating-deals fix below (`20260924-171518`): "Deals Of The Day" countdown + 2 discounted products back
- [x] Category page with layered nav (SM ShopBy), search results (SM AttributesSearch): all 200; `automated-tests`
      `search:check` passes (results + green-vein category). Found and fixed 1 new 2.4.9 error:
      `Undefined array key 451` in core `ConfigurableProduct\Pricing\Render\FinalPriceBox::hasSpecialPrice()`
      (2.4.9 `ListProduct` puts its collection's `special_price_map` on the shared `product.price.render.default` block;
      the SM FilterProducts widget on the category page renders other products through it). Fix: override
      `getProductPriceHtml()` in `Sm/FilterProducts/Block/FilterProducts.php` to detach map + flag while the widget renders.
- [x] Product page: configurable weight selector, reviews: pages 200 with reviews; weight selection exercised by the checkout run
- [x] Cart + checkout (all `automated-tests`, `--base-url ddev`):
      - `tax:scenarios`: 13/13 provinces, totals within tolerance. SK $2.58 vs expected $2.57 is pre-existing
        (identical in the 2026-09-14 2.4.7 manifest).
      - `shipping:scenarios`: 24/24, 0 errors
      - `checkout:etransfer`: order `111000000011` placed (Red Bali 25g, QC, $3.80 Regular Shipping, Interac e-Transfer),
        0 console/page errors. Browser warning "Fallback to JQueryUI Compat activated" (code also in 2.4.7; recheck on restore).
- [x] Blog (Magefan), Lookbook, affiliate pages: blog index + post render correctly. `/lookbook` 404 is by design
      (module has no frontend index controller; not placed in any CMS page/block/widget). Affiliate: Mirasvit was
      never installed in this codebase, so there are no affiliate pages to test.
- [x] Admin (harness `var/tmp/spike_admin_check.js`, `var/tmp/spike_admin_pagebuilder_check.js`; needs
      `admin/security/use_form_key=0`, set on the spike DB only): dashboard, CMS page/block grids, CMS `home` edit with
      **Page Builder stage initialised**, product grid + RB configurable edit, order grid + order view, SM MegaMenu
      groups/group edit/new item, Magefan posts, Lookbook admin, SM Market config, cache: all 200, 0 JS errors.
      Footer shows "Magento ver. 2.4.9".
- [x] `local:rotating-special-deals:rotate`: failed with "The store that was requested wasn't found." **Pre-existing,
      not a 2.4.9 regression**: `EligibleProductProvider::getPools()` hard-coded `setStoreId(1)`, but this DB's only
      storefront store is `111` (`fresh1_en`). The cron job catches the exception, so cycle 5 (ended 2026-09-24 16:00)
      was never rotated, which is why the homepage deals block was already empty in the 2.4.7 baseline. Fixed with
      `StoreManagerInterface::getDefaultStoreView()`; normal rotation then created cycle 6 (GMD, WM). **Also needed on
      master/prod** if prod's store id is not 1. Trace harness: `var/tmp/spike_rotate_trace.php`.
- [x] PolyShell (see Findings for detail). The probe needs a real file-type option: added `OPTION_ID` to
      `dev/tools/polyshell_probe.sh`; fixture product `POLYSHELL-TEST` (option 25) from `var/tmp/spike_polyshell_fixture.php`.
      - **2.4.9 bypasses `Local_PolyShellGuard`**: REST `file_info` now goes through the new
        `Catalog\Model\Product\Option\Type\File\ImageContentProcessor` (via `CustomOptionProcessor`), not the hooked
        `Webapi\...\File\Processor`. With the guard enabled, `probe.php.png` was written to `pub/media/custom_options/quote/`.
        Fixed on the spike branch: new `BlockImageContentProcessorPlugin` (+ `di.xml` entry; the class doesn't exist in
        2.4.7, so the entry is 2.4.9-only). After the fix: .php/.phtml/.php.png/.png all rejected, nothing written.
      - Native 2.4.9 (guard disabled): rejects `.php`/`.phtml`/`.phar` and non-image bytes, but **accepts `x.php.png`,
        `x.phtml.jpg`, and a valid-PNG polyglot containing `<?php`**, saved under the client's filename.

### 6. Restore DDEV to 2.4.7
- [ ] Restore DB, `vendor/`, `composer.*`, `app/etc/*`, `.ddev/config.yaml`; check out `master`
- [ ] `setup:upgrade` / `di:compile` / static deploy / `cache:flush`; homepage capture matches the baseline

### 7. Report
- [ ] Fill in "Findings" and "Recommendation" below

## Separate, higher-priority item: 2.4.7 security release

- [ ] Confirm prod version: `composer show magento/product-community-edition` on prod (was it hotfixed?)
- [ ] Upgrade DDEV to the latest 2.4.7-pN, regression-test, deploy to prod
- [ ] Deploy `Local_PolyShellGuard` + `.htaccess` changes to prod (prod dev prompt was handed off 2026-09-24)

## Log

- 2026-09-24: PolyShell incident analysed; local mitigation built and tested. v10.14 package reviewed
  (no malicious code; packaging leftovers listed above). Spike approach chosen: backup + branch.
- 2026-09-24 16:3x: Spike started. PolyShell work committed to master (`a16d85b7`); backups taken; baseline
  capture done; branch `spike/2.4.9` created. Starting state: PHP 8.2.30, Composer 2.10.3, Magento CLI 2.4.7,
  MariaDB 10.6, OpenSearch 2.19.5, deploy mode `default` (not `production` as `AGENTS.md` claims).
  Baseline pre-existing issues (NOT caused by the spike): homepage deals block under "Featured Categories"
  renders "We can't find products matching the selection."; `composer.json` requires
  `mirasvit/module-affiliate` but it is not installed in `vendor/` and its modules were already dropped from
  `config.php`.

## Findings

_(fill in during the spike)_

## Recommendation

_(fill in at the end: keep SM + 2.4.9 / Hyvä / Luma child theme, with effort estimate)_
