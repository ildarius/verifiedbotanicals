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
- Keep `dev/plans/` limited to active or future work. When a feature is implemented or otherwise complete, move its write-up into `dev/notes/` and update any `AGENTS.md` or note links that referenced the old plan path.
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

## Import Notes (MSI / `inventory_stock_1`)

Magento’s storefront product collections and many theme widgets use MSI/stock joins for “salable” filtering. In this project we hit a failure mode where `inventory_stock_1` (the MSI legacy-stock view) was corrupted, which made native product widgets look “broken” even though products existed in the DB.

### What Can Go Wrong

- After running `import_products.php`, products exist in `catalog_product_entity`, but storefront collections/widgets return empty.
- Configurable parents can appear “not salable” even if all children are in stock, which can lead to missing widgets and `$0.00`/missing price index rows.
- Stock-related debugging can be confusing because direct SQL queries show products + categories, but frontend queries that rely on salability filters return zero results.

### How To Verify

From the DB container, confirm `inventory_stock_1` is a real view backed by `cataloginventory_stock_status` joined to `catalog_product_entity`:

```bash
docker exec ddev-magento-db mysql -u db -pdb -D db -e "SHOW CREATE VIEW inventory_stock_1\\G"
```

If it looks like a dummy view returning constants (or it’s missing), native storefront filtering can break.

### How To Fix (If Corrupted)

Recreate `inventory_stock_1` as the expected view:

```bash
docker exec ddev-magento-db mysql -u db -pdb -D db -e "
DROP TABLE IF EXISTS inventory_stock_1;
DROP VIEW IF EXISTS inventory_stock_1;
CREATE VIEW inventory_stock_1 AS
SELECT DISTINCT
  legacy_stock_status.product_id AS product_id,
  legacy_stock_status.website_id AS website_id,
  legacy_stock_status.stock_id AS stock_id,
  legacy_stock_status.qty AS quantity,
  legacy_stock_status.stock_status AS is_salable,
  product.sku AS sku
FROM cataloginventory_stock_status AS legacy_stock_status
JOIN catalog_product_entity AS product
  ON legacy_stock_status.product_id = product.entity_id;
"
```

Then reindex the stock + price indexers:

```bash
docker exec -u 1000 ddev-magento-web php bin/magento indexer:reindex cataloginventory_stock catalog_product_price
```

### Configurable Parent `stock_status` Stuck At 0

If configurable parents are still not salable (common symptom: widget sections render, but don’t include the parent products you expect), deleting the bad `cataloginventory_stock_status` rows for the parents and reindexing can force a correct rebuild:

```bash
docker exec ddev-magento-db mysql -u db -pdb -D db -e "
DELETE ss
FROM cataloginventory_stock_status ss
JOIN catalog_product_entity e ON e.entity_id = ss.product_id
WHERE e.sku IN ('RB','RMD','RH','GMD','GM','GH');
"

docker exec -u 1000 ddev-magento-web php bin/magento indexer:reindex cataloginventory_stock
```

After these fixes, rerunning `import_products.php` and normal theme widgets should behave consistently again.

## Harnesses

- `import_categories.php`: Programmatically imports categories from `data/products.csv`.
  - Run with: `docker exec -u 1000 ddev-magento-web php import_categories.php`
- `import_products.php`: Programmatically imports products from `data/products.csv`, creates the 'Kratom' attribute set and 'kratom_weight' attribute, and links variations as configurable products.
  - Run with: `docker exec -u 1000 ddev-magento-web php import_products.php`
  - Current preferred CSV shape is the newer `var/tmp/products.csv` format documented in `dev/notes/kratom-product-import-csv-format.md`.
  - Pricing behavior:
    - simple-product prices come from the CSV `price` column
    - configurable parent prices are set to the minimum child price
    - if a rotating-special cycle is currently active, the importer refreshes that cycle's `special_price` values so the configured discount remains correct against the newly imported base prices
- `dev/tools/add_kratom_reviews.php`: Programmatically adds approved storefront reviews to the six kratom configurable parent products (`RB`, `RMD`, `RH`, `GMD`, `GM`, `GH`).
  - Uses mixed US-based male/female reviewer names.
  - Inserts separate reviews per supplied review text and applies product ratings.
  - Idempotent for the current seeded review text set: reruns skip exact existing review bodies instead of duplicating them.
  - Run with: `docker exec -u 1000 ddev-magento-web php dev/tools/add_kratom_reviews.php`
- `dev/tools/remote_db.sh`: Remote MySQL harness for read/query access to the non-DDEV store database.
  - Credentials file path: `var/tmp/remote-db.cnf`
  - Template: `dev/tools/remote-db.cnf.example`
  - Execution path: runs `mysql` inside the `ddev-magento-web` container, so DDEV must be running.
  - File format is MySQL option-file format:
    ```ini
    [client]
    host=your-remote-db-host
    port=3306
    user=your-remote-db-user
    password=your-remote-db-password
    database=your-remote-db-name
    ```
  - `var/tmp/` is gitignored, so credentials stay local.
  - Interactive session: `dev/tools/remote_db.sh`
  - One query: `dev/tools/remote_db.sh --sql "SELECT NOW();"`
  - SQL file: `dev/tools/remote_db.sh --file var/tmp/query.sql`
  - The harness sets `MYSQL_HISTFILE=/dev/null` to avoid writing query history locally.

## Magento Theme Work

- Place frontend themes under `app/design/frontend/<Vendor>/<theme>`.
- A Magento theme should include at least `registration.php` and `theme.xml`.
- Check parent theme inheritance before overriding templates or layout XML.
- Prefer theme-level overrides only when configuration or modules cannot solve the requirement cleanly.
- **Theme/Demo Icons:** many homepage/demo icons come from Magento’s WYSIWYG media library under `pub/media/wysiwyg/` (for demo icons specifically: `pub/media/wysiwyg/icon-image/`). Some demos also copy a subset into the theme for convenience (e.g. `app/design/frontend/Sm/market/web/images/home-demo-37/icon-image/`), but that theme folder may contain only *some* of the available icons. If you’re looking for more icons in the same “family”, search `pub/media/wysiwyg/icon-image/` first, then confirm usage via CMS content or theme templates.

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
- When a change is visible in the storefront/admin UI, run the Playwright harness to confirm it renders as expected (and iterate/fix if it doesn’t). Example: `npm run pw:homepage-check`.
- When asked to run Magento CLI commands, prefer running them directly inside the DDEV containers (e.g. `docker exec -u 1000 ddev-magento-web php bin/magento ...`) and record any required commands in hand-off notes.
- **Visual change workflow (strict):** for requests that change visible storefront/admin UI output (copy/text tweaks, swapping icons/images, CMS section edits, etc.), first analyze whether the change can be applied with a direct SQL update executed in the DB container (e.g. `docker exec ddev-magento-db mysql -u db -pdb -D db -e "<SQL>"`). Only build a Magento data patch under `app/code/.../Setup/Patch/Data/*.php` (like `UpdateHomeDemo37WhyChooseUs.php`) if the change cannot be done safely/cleanly as a simple SQL query execution.
  - For large `cms_page.content` / `cms_block.content` updates, prefer a “DB harness + base64” workflow to avoid painful SQL escaping.
    - Important: don’t `SELECT content ... > file` directly via the `mysql` CLI; it often prints escaped sequences like `\\n` instead of real newlines, and writing that back will literally put `\\n` into the CMS content.
    - Dump (newline-safe): `docker exec ddev-magento-db mysql -u db -pdb -D db -N -e "SELECT TO_BASE64(content) FROM cms_page WHERE identifier='home-demo-37';" > var/tmp/home-demo-37-content.b64.raw`
    - Decode: `python3 -c "import re,base64,pathlib; raw=pathlib.Path('var/tmp/home-demo-37-content.b64.raw').read_text(); b64=re.sub(r'\\s+','',raw.replace('\\\\n','')); pathlib.Path('var/tmp/home-demo-37-content.html').write_bytes(base64.b64decode(b64))"`
    - Edit `var/tmp/home-demo-37-content.html`, then update: `CONTENT_B64=$(base64 -w0 var/tmp/home-demo-37-content.html) && docker exec ddev-magento-db mysql -u db -pdb -D db -e "UPDATE cms_page SET content = FROM_BASE64('${CONTENT_B64}') WHERE identifier='home-demo-37';"`
    - Clear caches: `docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page`

## Notes For Agents

- If a task depends on archived assets in `theme_files/`, inspect package compatibility with the current Magento version before installation.
- Record any required post-install commands in the final handoff.
- Rotating special deals discovery and implementation notes are documented in [dev/notes/rotating-special-deals.md](/home/ildar/projects/magento/dev/notes/rotating-special-deals.md).

## Rotating Special Deals

- Implemented in `app/code/Local/RotatingSpecialDeals/`.
- Purpose: automatically run a 14-day homepage deal cycle for exactly 2 products with a 30% discount.
- Storage:
  - cycle header table: `local_rotating_special_deal_cycle`
  - cycle item table: `local_rotating_special_deal_item`
- Scheduler:
  - cron job: `local_rotating_special_deals_rotate`
  - schedule: hourly in `etc/crontab.xml`, but it only rotates when no active cycle remains or when forced manually.
- Manual trigger:
  - `docker exec -u 1000 ddev-magento-web php bin/magento local:rotating-special-deals:rotate`
  - force immediate rotation: `docker exec -u 1000 ddev-magento-web php bin/magento local:rotating-special-deals:rotate --force`
- Selection rules:
  - uses configurable parent products only
  - selects exactly 2 products
  - products must come from different kratom group categories
  - current group categories are `377` = `Green Vein Kratom`, `378` = `Red Vein Kratom`, `379` = `White Vein Kratom`
  - excludes products from the immediately previous cycle
- Pricing behavior:
  - uses native product `special_price`, `special_from_date`, and `special_to_date`
  - current discount factor is `30%` off (`special_price = regular price * 0.70`)
  - because the imported kratom catalog had `0` prices, the module also seeds reasonable base prices for missing kratom product prices before rotating deals
  - seeded defaults currently map weights to: `25g=12.99`, `50g=21.99`, `100g=39.99`, `250g=84.99`, `500g=149.99`
- Homepage integration:
  - targets CMS page `home-demo-37`
  - rewrites the homepage `Sm\FilterProducts` deal widget directive to:
    - `product_source="countdown_products"`
    - `select_category="377,378,379"`
    - `product_limitation="2"`
    - `date_to="<cycle end>"`
- Important `Sm_FilterProducts` fix:
  - `app/code/Sm/FilterProducts/Block/FilterProducts.php`
  - `_countDownProducts()` was tightened to require `special_to_date >= now`, not just `<= widget date_to`
- Important pricing-display caveat:
  - Magento’s configurable price renderer in this project does not naturally show the rotated parent special as an old/new price pair even when the indexed `final_price` is discounted
  - to make the homepage deals block visibly show `Regular Price` and `Special Price`, `grid-slider-deal2.phtml` was patched in both:
    - `app/design/frontend/Sm/market/Sm_FilterProducts/templates/grid-slider-deal2.phtml`
    - `app/code/Sm/FilterProducts/view/frontend/templates/grid-slider-deal2.phtml`
  - this patch is intentionally narrow to the rotating-deals template path
- Post-change validation commands:
  - `docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade`
  - `docker exec -u 1000 ddev-magento-web php bin/magento setup:di:compile`
  - `docker exec -u 1000 ddev-magento-web php bin/magento setup:static-content:deploy -f en_US`
  - `docker exec -u 1000 ddev-magento-web php bin/magento cache:flush`

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
- Homepage history capture:
  - Timestamped homepage capture command: `npm run pw:homepage-capture`
  - Saves timestamped screenshots and JSON summaries under `.playwright/artifacts/homepage-history/`
  - Also refreshes convenience copies:
    - `.playwright/artifacts/storefront-homepage-latest.png`
    - `.playwright/artifacts/storefront-homepage-latest.json`
  - Host-side launcher for scheduled/manual capture: `dev/tools/capture_homepage.sh`
  - Codex session-start wrapper: `dev/tools/start_codex_with_homepage_capture.sh`
  - Cron installer: `dev/tools/install_homepage_capture_cron.sh`
  - Installed schedule target: `/etc/cron.d/magento-homepage-capture`
  - Current intended cadence: hourly at minute `12`
  - For homepage/UI work, prefer this flow:
    - run `npm run pw:homepage-capture` before changes when you need a baseline
    - run `npm run pw:homepage-capture` after visible homepage changes
    - compare the latest capture with prior images in `.playwright/artifacts/homepage-history/`
  - `AGENTS.md` does not auto-run on session start by itself. To capture on each new Codex session, launch Codex through `dev/tools/start_codex_with_homepage_capture.sh`.

## Run Findings

- This project runs under DDEV, not plain `docker compose`.
- Full database backup created on `2026-04-28` at `backups/magento-db-20260428-214114.sql` from the `ddev-magento-db` container (`db` database).
- The main app URL is `https://magento.ddev.site/`.
- The Magento admin URI is `/admin`, but authenticated admin URLs use Magento secret keys, so direct guessed admin paths may redirect back to the dashboard.
- For browser automation, prefer navigating from authenticated admin pages or use the signed URLs already captured in `.playwright/local.env`.
- A dedicated browser automation admin user exists: `playwright-admin`.
- If `php bin/magento admin:user:unlock <username>` reports that the user was not locked but login still fails, check `admin_user.is_active` next. In one remote-server incident on `2026-07-01`, the account was not lock-blocked; it was disabled (`is_active = 0`), which surfaces as `DISABLED` in the query below:

```sql
SELECT
    user_id,
    username,
    is_active,
    failures_num,
    first_failure,
    lock_expires,
    CASE
      WHEN is_active = 0 THEN 'DISABLED'
      WHEN lock_expires IS NOT NULL AND lock_expires > UTC_TIMESTAMP() THEN 'LOCKED'
      ELSE 'UNLOCKED'
    END AS status
  FROM admin_user
  WHERE username = 'playwright-admin';
```
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
- The two product areas under the hero were first changed from `Sm\FilterProducts` widget directives to inline static HTML product-card markup.
- During the follow-up run later on `2026-05-18`, those sections were reworked again because the user reported two issues:
- the demo countdown widget had disappeared
- the homepage products were rendering as cropped thin image slices without visible labels/details
- Current rendered product cards on the homepage are:
- deals section: `Green Maeng Da`, `Red Maeng Da`, `Red Bali`
- new arrivals section: `Green Hulu / Green Kapuas`, `Green Malay`, `Green Maeng Da`, `Red Hulu / Red Kapuas`, `Red Maeng Da`, `Red Bali`
- The current homepage HTML after the last hotfix confirms the restored countdown section text and full product names around lines `1293-1323` in the fetched storefront HTML.

- Files added or changed during this run:
- `app/code/Local/HomepageAssets/Setup/Patch/Data/ReplaceHomepageDemoProductsWithKratom.php`
- `app/code/Local/HomepageAssets/Setup/Patch/Data/SwapHomepageDealsWidgetToLatestKratom.php`
- `app/code/Local/HomepageAssets/Setup/Patch/Data/ReplaceHomepageProductWidgetsWithKratomBlocks.php`
- `app/code/Local/HomepageAssets/Setup/Patch/Data/RestoreHomepageKratomWidgets.php`
- `app/code/Local/HomepageAssets/Block/HomepageKratomProducts.php`
- `app/code/Local/HomepageAssets/view/frontend/templates/homepage/kratom-products.phtml`
- `app/code/Sm/FilterProducts/Block/FilterProducts.php`
- `app/design/frontend/Sm/market/Sm_FilterProducts/templates/grid-slider-deal2.phtml`
- `import_products.php`
- `var/tmp/debug_homepage_widgets.php`
- `var/tmp/fix_homepage_sections.php`

- Important: the current homepage behavior is still not coming from a healthy Magento product-widget/catalog path.
- What was attempted in the follow-up run:
- `grid-slider-deal2.phtml` was adjusted so the countdown heading/timer can render whenever `date_to` is configured, even if `product_source` is not `countdown_products`
- a new patch `RestoreHomepageKratomWidgets` was added to try to switch the live CMS page back to widget directives using kratom categories `377,378,379`
- `Sm/FilterProducts/Block/FilterProducts.php` was given another narrow fallback path for `lastest_products`
- direct Magento debugging scripts were added under `var/tmp/` to inspect storefront collection behavior from inside the web container
- Result of that debugging:
- the live CMS page could be rewritten back to widget directives, but the widget collections still rendered empty
- even a plain Magento product collection for SKUs `RB`, `RMD`, `RH`, `GMD`, `GM`, `GH` returned `count=0` inside the storefront/app context used by the debug script, despite raw SQL against the same DB showing those rows exist
- `cataloginventory_stock_status` for the configurable parents was `0`
- `inventory_stock_1` had no rows for the kratom SKUs that were being tested
- `catalog_product_index_price` also had no rows for the configurable parents
- because of that deeper catalog/index/storefront inconsistency, the widget restoration path was abandoned for this session

- `import_products.php` was modified to stop skipping existing simple products and to write MSI default-source rows for simple kratom SKUs when rerun.
- `docker exec -u 1000 ddev-magento-web php import_products.php` was rerun successfully after those changes.
- This created `inventory_source_item` rows for simple kratom SKUs like `GMD25`, `GMD50`, `RB25`, `RB50`, `RH25`, `GM25`.
- The visible configurable parents (`RB`, `RMD`, `RH`, `GMD`, `GM`, `GH`) still showed odd storefront collection behavior and were not reliably retrievable through normal product collection attribute/category filters in this environment.

- `Sm/FilterProducts` was debugged and modified because its category filtering was suspicious:
- multi-category parsing was normalized
- category filtering was later switched to resolve product IDs from `catalog_category_product`
- Even after that, the homepage widget path still failed in this catalog.
- Final workaround at end of this session:
- the two homepage sections were rewritten again directly in the CMS record using richer static HTML, not widget output
- the countdown area was restored as a hardcoded visual section with day/hour/minute/second boxes
- the product cards were rewritten with explicit `width`, `max-width`, `aspect-ratio:1/1`, and `object-fit:contain` on the images to stop the cropped-slice rendering problem
- this final CMS hotfix is what the storefront is now using

- Commands run during this attempt:
- `docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade`
- `docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page`
- `docker exec -u 1000 ddev-magento-web php import_products.php`
- `docker exec -u 1000 ddev-magento-web php var/tmp/debug_homepage_widgets.php`
- `docker exec -u 1000 ddev-magento-web php var/tmp/fix_homepage_sections.php`
- `docker exec -u 1000 ddev-magento-web php bin/magento indexer:reindex catalog_product_price cataloginventory_stock`
- `docker exec -u 1000 ddev-magento-web php bin/magento indexer:reindex inventory`
- targeted DB inspection via `docker exec ddev-magento-db mysql -u db -pdb db ...`
- storefront verification via `curl -k -L -s https://magento.ddev.site/`
- Playwright storefront validation via `npm run pw:homepage-check`

- Known issues introduced or still unresolved after this run:
- the homepage has multiple broken things according to the user; only the product-section replacement work was attempted here
- the homepage product sections currently use inline CMS HTML, not Magento product widgets or a stable custom block path
- the direct CMS rewrite should be treated as a temporary workaround, not a clean final architecture
- the widget/catalog path is still broken underneath; the next agent should assume the storefront product collection/index problem is unresolved
- `app/code/Local/HomepageAssets/...` additions from this run may be dead code unless the next agent decides to reactivate that path
- `app/code/Sm/FilterProducts/Block/FilterProducts.php` and `import_products.php` were changed during debugging and should be reviewed before keeping
- `php bin/magento indexer:reindex` can still spill into an unrelated OpenSearch/catalog search mapping error; during this session it hit `/market247_product_111_v4/document/_mapping`
- `setup:upgrade` cleared generated/static frontend artifacts because the app is effectively in production-style mode for these changes; keep that in mind if the storefront starts serving missing static assets again
- the newsletter popup is still present in the Playwright screenshot and partially obscures the upper homepage during validation
- the latest Playwright validation artifact for this session is `.playwright/artifacts/storefront-homepage.png`

- Suggested next debugging starting points:
- inspect the current raw `cms_page.content` for `home-demo-37` first, because the live homepage product sections are again embedded there as static encoded HTML
- decide whether to keep the static CMS workaround temporarily or back it out and implement a proper Magento block/widget/module path
- investigate why Magento app-context collections are returning `count=0` for known SKUs even though direct SQL from the same database finds the products
- investigate why `inventory_stock_1` has no rows for the kratom products and why `catalog_product_index_price` has no rows for the configurable parents
- investigate why `cataloginventory_stock_status.stock_status` is `0` for visible kratom configurables even though simple children have `inventory_source_item` rows
- review whether the new files under `app/code/Local/HomepageAssets/` should remain, be wired up properly, or be removed later
- review diffs in `import_products.php` and `app/code/Sm/FilterProducts/Block/FilterProducts.php` before further catalog/debug work
- review whether the temporary scripts under `var/tmp/` should be preserved for continued debugging or migrated into a cleaner harness

### 2026-05-19

- Removed the temporary homepage static-HTML “hotfix” for `home-demo-37` and restored the native SM Market widgets (`Sm\FilterProducts`) for the Deals + New Arrivals sections.
- Fixed the underlying catalog/stock visibility issue: `inventory_stock_1` was corrupted and did not reflect real stock/salability. Recreated it as the expected legacy-stock MSI view backed by `cataloginventory_stock_status` joined to `catalog_product_entity` by `product_id`.
- Fixed kratom configurable parent salability: the kratom configurable parents (`RB`, `RMD`, `RH`, `GMD`, `GM`, `GH`) had `cataloginventory_stock_status.stock_status = 0` stuck. Deleting those rows and reindexing `cataloginventory_stock` rebuilt correct `stock_status = 1`, which restored widget rendering and parent price-index rows.
- Disabled the `Local_HomepageAssets` module and then removed it from the codebase.
- Session notes: [dev/notes/homepage-kratom-native-widgets-2026-05-19.md](/home/ildar/projects/magento/dev/notes/homepage-kratom-native-widgets-2026-05-19.md).

### 2026-06-02

- Newsletter signup is temporarily disabled in two live data locations:
- popup signup:
- controlled by `core_config_data.path = themecore/advanced/newsletter_group/show_newsletter_popup`
- currently set to `0` for the default scope and the existing store-scope overrides in the local DB
- re-enable by setting those rows back to `1`, then run `docker exec -u 1000 ddev-magento-web php bin/magento cache:clean config block_html full_page`
- footer signup:
- active footer style is `footer-29`, and the visible signup form comes from `cms_block.identifier = footer-29-content`
- the newsletter column was not deleted; it was hidden by changing its wrapper from `&lt;div class="col-lg-3"&gt;` to `&lt;div class="col-lg-3 newsletter-disabled-temp" style="display:none"&gt;`
- re-enable by removing ` newsletter-disabled-temp` and ` style="display:none"` from that wrapper in `footer-29-content`, then run `docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page`
- scratch copy of the edited CMS block content from this change is preserved at `var/tmp/footer-29-content-current.html`
