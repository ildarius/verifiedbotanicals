# Magento 2.4.9 Upgrade Spike (keep SM Market v10.14)

Status: **not started**. Branch: `spike/2.4.9` (to be created). Owner: next agent session.
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
- [ ] Confirm with the user how to handle uncommitted PolyShell work on `master` (commit first is recommended)
- [ ] DB backup: `docker exec ddev-magento-db mysqldump -u db -pdb db | gzip > backups/pre-2.4.9-spike-<timestamp>.sql.gz`
- [ ] Back up `vendor/`, `composer.json`, `composer.lock`, `app/etc/config.php`, `app/etc/env.php`, `.ddev/config.yaml`
      to `backups/pre-2.4.9-spike-<timestamp>/` (not into git)
- [ ] Baseline homepage capture: `npm run pw:homepage-capture`
- [ ] Create branch `spike/2.4.9`

### 1. Platform
- [ ] Check Adobe's 2.4.9 system requirements (PHP, OpenSearch, MariaDB/MySQL, Redis/Valkey, Composer) and record them here
- [ ] Switch DDEV to PHP 8.3 (match production), restart, and confirm the site still loads on 2.4.7

### 2. Composer
- [ ] `composer require magento/product-community-edition=2.4.9 --no-update`, then `composer update` (inside the web container)
- [ ] Resolve conflicts (`mirasvit/module-affiliate`, `magento/composer-*` plugins); record each here

### 3. SM v10.14 merge
- [ ] Apply the real v10.14 changes listed above (hand-merge MegaMenu `MobileDetect.php`)
- [ ] Copy the four LESS folders into `market/web/css/source/`
- [ ] Nothing from the "do NOT copy" list

### 4. Build
- [ ] `bin/magento setup:upgrade`
- [ ] `bin/magento setup:di:compile`: record every error (expect SM preference/plugin signature issues)
- [ ] `bin/magento setup:static-content:deploy -f en_US`: record whether the `Sm/themecore` failure is gone
- [ ] `bin/magento indexer:reindex` + `cache:flush`; re-check `inventory_stock_1` view per `AGENTS.md`

### 5. Verification
- [ ] Homepage: `npm run pw:homepage-capture`, compare with the baseline (deals countdown, new arrivals, Shop by Products menu)
- [ ] Category page with layered nav (SM ShopBy), search results (SM AttributesSearch)
- [ ] Product page: configurable weight selector, reviews
- [ ] Cart + checkout: MatrixRate shipping, Canada tax, Interac e-Transfer payment, order placement
- [ ] Blog (Magefan), Lookbook, affiliate pages
- [ ] Admin: CMS page edit (Page Builder), SM MegaMenu admin, product edit
- [ ] `local:rotating-special-deals:rotate --force` still works
- [ ] PolyShell: `dev/tools/polyshell_probe.sh` rejected by native 2.4.9 code (also test with `Local_PolyShellGuard` disabled)

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

## Findings

_(fill in during the spike)_

## Recommendation

_(fill in at the end: keep SM + 2.4.9 / Hyvä / Luma child theme, with effort estimate)_
