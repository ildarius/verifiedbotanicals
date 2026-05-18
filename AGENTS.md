# AGENTS

## Project

- Magento Open Source `2.4.7`
- Composer-based install rooted at this directory
- Custom PHP code belongs in `app/code/`
- Custom themes belong in `app/design/frontend/`
- **Current Mode:** `production` (requires `setup:di:compile` and `setup:static-content:deploy` for most changes).
- **Custom Modules:**
  - `Coduzion/Lookbook`: Lookbook functionality.
  - `Magefan/*`: Blog, Admin User Guide, Wysiwyg Advanced, etc.
  - `Sm/*`: Core and Market theme-specific modules.
  - `Local/PageBuilderDirectiveFix`: Currently an empty shell.
- **Custom Themes:**
  - `Sm/market`: Primary theme (active, ID 5).
  - `Sm/market_2`, `Sm/market_3`, `Sm/market_4`: Available theme variants (inactive).
  - `Sm/themecore`: Parent theme for Sm Market (ID 4).
  - `Sm/smtheme_mobile`: Mobile-specific theme.

## Working Rules

- Prefer minimal, targeted changes that fit Magento conventions.
- **Configuration:** `app/etc/config.php` (enabled modules) and `app/etc/env.php` (database/env settings) are gitignored but are the source of truth for the local environment.
- Do not edit `vendor/` unless the user explicitly asks for it.
- Preserve existing local changes; this worktree may be dirty.
- Use `rg` for search and inspect existing module/theme structure before adding files.
- When the user mentions `tmp` or `/tmp` for project assets, treat that as `var/tmp/` unless they explicitly say otherwise.
- **Harness Preservation:** Do not delete harnesses, scripts, or temporary tools created during tasks without explicit user approval. Only propose deletion for items with extremely low re-use potential.

## Product Creation Process

The programmatic product import (`import_products.php`) follows these steps:
1.  **Attribute Set:** Creates/verifies the `Kratom` attribute set, inheriting from `Default`.
2.  **Attributes:** Creates/verifies the `kratom_weight` (select) attribute and ensures it is assigned to the `Kratom` attribute set.
3.  **Options:** Populates `kratom_weight` with options (25g, 50g, etc.) derived from the CSV.
4.  **Simple Products (Variations):**
    *   Creates simple products with `Visibility: Not Visible Individually`.
    *   Maps weight options from the name/SKU to the `kratom_weight` attribute.
    *   Sets stock status and quantity.
5.  **Configurable Products (Variables):**
    *   Creates configurable products with `Visibility: Catalog, Search`.
    *   Uses `ExtensionAttributes` to link simple products and define the configurable attribute (`kratom_weight`).
    *   Assigns categories as defined in the CSV.

## Harnesses

- `import_categories.php`: Programmatically imports categories from `data/products.csv`.
  - Run with: `docker exec -u 1000 ddev-magento-web php import_categories.php`
- `import_products.php`: Programmatically imports products from `data/products.csv`, creates the 'Kratom' attribute set and 'kratom_weight' attribute, and links variations as configurable products.
  - Run with: `docker exec -u 1000 ddev-magento-web php import_products.php`

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

## Database Management

- **Backup (DDEV/Docker):** `docker exec ddev-magento-db mysqldump -u db -pdb db | gzip > backups/filename.sql.gz`
- **Restore (DDEV/Docker):** `zcat backups/filename.sql.gz | docker exec -i ddev-magento-db mysql -u db -pdb db`

## Validation

- After structural Magento changes, run only the narrowest relevant validation first.
- For theme changes, at minimum validate registration, clear caches, and confirm static assets can deploy if needed.
- For module changes, validate syntax and run the relevant Magento CLI command before broader testing.

## Notes For Agents

- If a task depends on archived assets in `theme_files/`, inspect package compatibility with the current Magento version before installation.
- Record any required post-install commands in the final handoff.
- Rotating special deals discovery and implementation planning is documented in [dev/plans/rotating-special-deals.md](/home/ildar/projects/magento/dev/plans/rotating-special-deals.md).

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
- The homepage left vertical menu on `home-demo-37` is not driven by standard category navigation; it is embedded in CMS content via `{{block class="Sm\MegaMenu\Block\MegaMenu\View" template="Sm_MegaMenu::vertical.phtml" theme="2" group_id="5" title="Shop by Categories" limit="7"}}`.
- To change that left menu without editing the CMS page record directly, override `app/design/frontend/Sm/market/Sm_MegaMenu/templates/vertical.phtml` and branch specifically for `cms_index_index` with MegaMenu `group_id = 5`.
- The current customization swaps that one homepage menu instance from imported category items to a frontend-visible product collection, keeps the same 7-item limit, and relabels the block to `Shop by Products`.
- The product rows intentionally reuse the existing `Vegetables` leaf icon from `media/wysiwyg/mega-menu/icon/icon-1.png`.
- After editing that template, clear at least `block_html` and `full_page` caches in `ddev-magento-web` so the homepage picks up the change.
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

## Hand-off

### 2026-05-18

- Goal attempted: replace homepage product sections such as `New Arrivals` on `home-demo-37` with kratom products.
- The live homepage CMS page is `cms_page.identifier = home-demo-37`.
- The left vertical homepage menu customization from the earlier run is still in play via `app/design/frontend/Sm/market/Sm_MegaMenu/templates/vertical.phtml`.

- Homepage CMS content was changed directly in the database for `home-demo-37`.
- The two product areas under the hero were changed from `Sm\FilterProducts` widget directives to inline static HTML product-card markup.
- Current rendered product cards on the homepage are:
- deals section: `Green Maeng Da`, `Red Maeng Da`, `Red Bali`
- new arrivals section: `Green Hulu / Green Kapuas`, `Green Malay`, `Green Maeng Da`, `Red Hulu / Red Kapuas`, `Red Maeng Da`, `Red Bali`
- The current homepage HTML confirms these product names are rendered around lines `1291-1312` in the fetched storefront HTML.

- Files added or changed during this run:
- `app/code/Local/HomepageAssets/Setup/Patch/Data/ReplaceHomepageDemoProductsWithKratom.php`
- `app/code/Local/HomepageAssets/Setup/Patch/Data/SwapHomepageDealsWidgetToLatestKratom.php`
- `app/code/Local/HomepageAssets/Setup/Patch/Data/ReplaceHomepageProductWidgetsWithKratomBlocks.php`
- `app/code/Local/HomepageAssets/Block/HomepageKratomProducts.php`
- `app/code/Local/HomepageAssets/view/frontend/templates/homepage/kratom-products.phtml`
- `app/code/Sm/FilterProducts/Block/FilterProducts.php`
- `import_products.php`

- Important: the final homepage behavior is not coming from the custom block/template files above.
- Reason: this theme/CMS path rendered `{{widget ...}}` directives inside the encoded PageBuilder HTML, but did not successfully render the replacement `{{block ...}}` directives in that same context.
- As a workaround, the homepage CMS record was then updated directly to inline static HTML for those two product sections.
- Result: some of the PHP/module changes above may now be unused or only partially relevant.

- `import_products.php` was modified to stop skipping existing simple products and to write MSI default-source rows for simple kratom SKUs when rerun.
- `docker exec -u 1000 ddev-magento-web php import_products.php` was rerun successfully after those changes.
- This created `inventory_source_item` rows for simple kratom SKUs like `GMD25`, `GMD50`, `RB25`, `RB50`, `RH25`, `GM25`.
- The visible configurable parents (`RB`, `RMD`, `RH`, `GMD`, `GM`, `GH`) still showed odd storefront collection behavior and were not reliably retrievable through normal product collection attribute/category filters in this environment.

- `Sm/FilterProducts` was debugged and modified because its category filtering was suspicious:
- multi-category parsing was normalized
- category filtering was later switched to resolve product IDs from `catalog_category_product`
- Even after that, the homepage widget path still failed in this catalog, which is why the final workaround was static CMS HTML.

- Commands run during this attempt:
- `docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade`
- `docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page`
- `docker exec -u 1000 ddev-magento-web php import_products.php`
- targeted DB inspection via `docker exec ddev-magento-db mysql -u db -pdb db ...`
- storefront verification via `curl -k -L -s https://magento.ddev.site/`

- Known issues introduced or still unresolved after this run:
- the homepage has multiple broken things according to the user; only the product-section replacement work was attempted here
- the homepage product sections currently use inline CMS HTML, not Magento product widgets or a stable custom block path
- the direct CMS rewrite should be treated as a temporary workaround, not a clean final architecture
- `app/code/Local/HomepageAssets/...` additions from this run may be dead code unless the next agent decides to reactivate that path
- `app/code/Sm/FilterProducts/Block/FilterProducts.php` and `import_products.php` were changed during debugging and should be reviewed before keeping
- `php bin/magento indexer:reindex` can still spill into an unrelated OpenSearch/catalog search mapping error on `/market247_product_111_v2/document/_mapping`

- Suggested next debugging starting points:
- inspect the current raw `cms_page.content` for `home-demo-37` first, because the live homepage product sections are now embedded there as static encoded HTML
- decide whether to keep the static CMS workaround or back it out and implement a proper Magento block/widget/module path
- review whether the new files under `app/code/Local/HomepageAssets/` should remain, be wired up properly, or be removed later
- review diffs in `import_products.php` and `app/code/Sm/FilterProducts/Block/FilterProducts.php` before further catalog/debug work
