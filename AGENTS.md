# AGENTS

## Project

- Magento Open Source `2.4.7`
- Composer-based install rooted at this directory
- Custom PHP code belongs in `app/code/`
- Custom themes belong in `app/design/frontend/`

## Working Rules

- Prefer minimal, targeted changes that fit Magento conventions.
- Do not edit `vendor/` unless the user explicitly asks for it.
- Preserve existing local changes; this worktree may be dirty.
- Use `rg` for search and inspect existing module/theme structure before adding files.

## Magento Theme Work

- Place frontend themes under `app/design/frontend/<Vendor>/<theme>`.
- A Magento theme should include at least `registration.php` and `theme.xml`.
- Check parent theme inheritance before overriding templates or layout XML.
- Prefer theme-level overrides only when configuration or modules cannot solve the requirement cleanly.

## Magento Module Work

- Register custom modules in `app/code/<Vendor>/<Module>/`.
- Typical required files are `registration.php` and `etc/module.xml`.
- Keep dependency scope narrow and avoid unnecessary preferences or global rewrites.

## Common Commands

- Dependency injection compile: `php bin/magento setup:di:compile`
- Static content deploy: `php bin/magento setup:static-content:deploy -f`
- Cache clean: `php bin/magento cache:clean`
- Cache flush: `php bin/magento cache:flush`
- Upgrade setup scripts/schema: `php bin/magento setup:upgrade`
- List modules: `php bin/magento module:status`

## Validation

- After structural Magento changes, run only the narrowest relevant validation first.
- For theme changes, at minimum validate registration, clear caches, and confirm static assets can deploy if needed.
- For module changes, validate syntax and run the relevant Magento CLI command before broader testing.

## Notes For Agents

- If a task depends on archived assets in `theme_files/`, inspect package compatibility with the current Magento version before installation.
- Record any required post-install commands in the final handoff.

## Playwright Harness

- This project includes a local Playwright admin harness in `tools/playwright/`.
- Install dependencies with `npm install` from the project root.
- Install the browser with `npx playwright install chromium`.
- Local harness settings live in `.playwright/local.env`.
- The harness uses the dedicated Magento admin account `playwright-admin` unless the env file is changed.
- `npm run pw:admin-login` signs into Magento Admin at `https://magento.ddev.site/admin` and saves session state to `.playwright/admin-auth.json`.
- `npm run pw:theme-install` resumes the vendor theme install flow inside Admin by opening `Stores > Configuration > MAGENTECH.COM > SM Market > Theme Installation` and clicking `Static Blocks`, `Pages`, the configured demo button, and `Save Config`.
- Harness artifacts and screenshots are written under `.playwright/`.
- Keep Playwright automation pointed at the DDEV site and run Magento CLI commands inside `ddev-magento-web`, not with the host PHP binary.

## Run Findings

- This project runs under DDEV, not plain `docker compose`.
- Full database backup created on `2026-04-28` at `backups/magento-db-20260428-214114.sql` from the `ddev-magento-db` container (`db` database).
- The main app URL is `https://magento.ddev.site/`.
- The Magento admin URI is `/admin`, but authenticated admin URLs use Magento secret keys, so direct guessed admin paths may redirect back to the dashboard.
- For browser automation, prefer navigating from authenticated admin pages or use the signed URLs already captured in `.playwright/local.env`.
- A dedicated browser automation admin user exists: `playwright-admin`.
- The theme archive for Magento `2.4.7` is `theme_files/Code_v10.13/magento2.4.x-new-framework/sm_market_theme_m2.4.6-2.4.8_v10.13.zip`.
- That archive installs both themes under `app/design/frontend/Sm/` and required modules under `app/code/`.
- Registered Magento theme IDs discovered during this run include:
- `5` = `Sm/market`
- `4` = `Sm/themecore`
- Vendor install flow confirmed for this project:
- extract archive into Magento root
- run `php bin/magento setup:upgrade` inside `ddev-magento-web`
- set `Sm Market` as the theme for `Default Store View`
- import `Static Blocks`
- import `Pages`
- import a demo from `Stores > Configuration > MAGENTECH.COM > SM Market`
- flush cache
- The imported CMS demo pages include `home-demo-37`, which corresponds to `Fresh 1`.
- The final homepage setting from this run is `web/default/cms_home_page = home-demo-37`.
- The final theme setting from this run is `design/theme/theme_id = 5`.
- The vendor admin screen labels the first demo set by business names like `Shop 1`, `Fresh 1`, etc., not `Demo 1`, even though the documentation uses generic `Demo X` wording.
- The `SM Market` import controls are rendered as hidden buttons inside the `Theme Installation` accordion. Their live IDs were verified:
- `market_theme_install_import_blocks`
- `market_theme_install_import_pages`
- `market_theme_install_import_shop1`
- `market_theme_install_import_fresh1`
- If Playwright clicks fail on those controls because they are hidden, invoking the page's own button handlers by DOM ID is a workable fallback.
- `php bin/magento setup:static-content:deploy -f` still has a vendor theme compatibility issue in `Sm/themecore`; the storefront can still serve in current mode, but production-mode hardening is still unfinished.
- A DDEV restart during this run left `ddev-magento-web` stuck in `/pre-start.sh` without launching `/start.sh`. Manually starting `/start.sh` inside the container restored nginx/php-fpm and brought the site back.
