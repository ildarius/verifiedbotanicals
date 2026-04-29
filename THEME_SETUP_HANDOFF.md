# Theme Setup Handoff

Date: 2026-04-28

## Scope

This handoff covers the recent SM Market / Fresh 1 theme setup work on this Magento 2.4.7 project, what was completed, the current site state, and what still needs to be done to fully match the vendor demo.

Target demo:

- `https://market.magentech.com/pub/fresh1_en/`

Local site:

- `https://magento.ddev.site/`

## Initial Findings

At the start of this work:

- The SM theme package was installed under `app/design/frontend/Sm/` and `app/code/Sm/`.
- The local homepage was assigned to `home-demo-37`.
- The active theme was `Sm/market` with theme ID `5`.
- The local site still looked very different from the vendor Fresh 1 demo because the installed layout config was still the default SM Market layout:
  - `header-1`
  - `footer-1`
  - `product-1`
- The local DB had no catalog data, so product and category-driven demo sections could not render.

## Work Completed

### 1. Confirmed theme and homepage assignment

Verified:

- `design/theme/theme_id = 5` at store scope.
- `web/default/cms_home_page = home-demo-37`.

### 2. Re-applied the vendor Fresh 1 demo config

Used the vendor importer from `Sm\Market\Model\Import\Demo` to import `fresh1` for store `1`.

That aligned the local store with the Fresh 1 layout profile from:

- [app/code/Sm/Market/etc/import/fresh1.xml](/home/ildar/projects/magento/app/code/Sm/Market/etc/import/fresh1.xml)

Expected Fresh 1 settings:

- `header-37`
- `footer-29`
- `product-21`

Result:

- The storefront shell switched to Fresh 1 classes and assets instead of the default SM Market layout.

### 3. Identified missing sample data as the main remaining gap

Confirmed that the theme-only install was not enough to reproduce the demo because:

- the local live DB had `0` products
- Fresh 1 widgets and carousels depend on demo categories, products, and media

### 4. Staged and inspected the vendor quickstart database

Inspected the quickstart archive:

- [sm_market_quickstart_pl_m2.4.8_v10.13.zip](/home/ildar/projects/magento/theme_files/Code_v10.13/magento2.4.x-new-framework/sm_market_quickstart_pl_m2.4.8_v10.13.zip)

Extracted and staged:

- `data_quickstart/sample_data.sql`

Imported it into a temporary DB for inspection and verified:

- the staged dataset contains `362` products
- the staged dataset contains the expected Fresh 1 categories and products
- the staged dataset includes the `fresh1_en` / `fresh1_ar` store views
- the staged dataset includes the correct Fresh 1 homepage assignment and layout config

### 5. Backed up the previous live DB

Created a backup inside the DB container before replacing the live DB:

- `/var/tmp/db-before-quickstart.sql`

Container:

- `ddev-magento-db`

### 6. Switched the live site to the quickstart dataset

Replaced the live Magento DB with the quickstart sample dataset and updated the local install to use it.

Also extracted matching demo media from the quickstart archive into:

- `pub/media`

This restored:

- demo products
- demo categories
- demo CMS media
- Fresh 1 slider/banner assets

### 7. Pointed the website default group to Fresh 1

Updated `store_website.default_group_id` to use the Fresh 1 store group:

- website `1` now points to group `41`
- group `41` default store is `111`
- store `111` is `fresh1_en`

### 8. Updated base URL and storefront routing

Set the local site to use:

- `web/unsecure/base_url = https://magento.ddev.site/`
- `web/secure/base_url = https://magento.ddev.site/`
- `web/url/use_store = 0`

This keeps the root storefront URL on `https://magento.ddev.site/` instead of exposing the store code in the path.

### 9. Recreated the known admin user

Recreated:

- `playwright-admin`

This was needed after replacing the live DB with quickstart data.

### 10. Rebuilt key indexes and flushed caches

Ran cache flushes and reindex operations.

Notes:

- category/product indexes were reset and rebuilt successfully
- catalog search indexing still reports a search backend error because the local search engine is not healthy

This does not block basic storefront rendering, but it is still an environment issue.

### 11. Corrected one concrete quickstart DB compatibility issue

The quickstart DB imported an invalid backend model for category attribute `default_sort_by`.

Broken value from quickstart:

- `Magento\Catalog\Model\Category\Attribute\Backend\DefaultSortby`

Correct value for this Magento version:

- `Magento\Catalog\Model\Category\Attribute\Backend\Sortby`

This was fixed directly in the live DB.

## Current Site State

The local site is materially closer to the vendor demo than before.

Confirmed current state:

- the root storefront is now the Fresh 1 store view
- the page shell is Fresh 1
- the body classes match Fresh 1:
  - `header-37-style`
  - `product-21-style`
  - `footer-29-style`
  - `cms-home-demo-37`
- the quickstart catalog is present locally
- the Fresh 1 media assets are present locally

Examples confirmed in rendered HTML:

- `media/wysiwyg/slidershow/home-37/item-1.jpg`
- `media/wysiwyg/slidershow/home-37/item-2.jpg`
- `media/wysiwyg/slidershow/home-37/item-3.jpg`

## Remaining Blocker

The site is not fully matching the vendor Fresh 1 demo yet.

The remaining issue is:

- parts of the Fresh 1 homepage still output raw `{{widget ...}}` directives instead of rendered widget HTML

This affects the product/deal sections on `home-demo-37`, for example:

- `Sm\FilterProducts\Block\Widget\AddFilterProducts`

Symptoms:

- static shell, banners, slider, category-select data, and other non-widget content render
- product/deal widget sections do not render as real product markup
- raw widget directives still appear in the final page HTML

This means the site now has:

- the right theme
- the right store view
- the right homepage
- the right demo media
- the right sample catalog

But it still has a widget rendering problem in the homepage content path.

## Debugging Already Attempted

The following was tested during investigation:

- direct Fresh 1 demo config import
- full quickstart DB restore
- full quickstart media restore
- category/product index rebuild
- cache flushes
- PageBuilder/plugin-based workaround attempts

The local workaround module used during testing was removed and is not part of the final repo state.

Final repo state from this work does not keep that temporary module.

## Most Likely Remaining Root Cause

The remaining problem appears to be in one of these areas:

1. SM widget rendering compatibility on Magento 2.4.7
2. interaction between PageBuilder-encoded CMS content and widget directives
3. a remaining quickstart DB compatibility issue in EAV/module config beyond the `default_sort_by` fix
4. an exception path inside SM widget blocks or dependent modules that causes directive expansion to fail silently in the storefront response

This is now a targeted debugging task, not a general theme-install task.

## What Still Needs To Be Done

### Required next work

1. Trace the raw homepage widget directives through Magento filter/rendering.
2. Identify which specific widget block or dependency is failing during homepage expansion.
3. Fix the underlying compatibility problem so Fresh 1 homepage widgets render product HTML instead of raw directives.
4. Re-verify the homepage against the vendor Fresh 1 demo after that fix.

### Recommended debugging path

1. Start from `home-demo-37` CMS content and isolate each `{{widget ...}}` directive.
2. Render the widget content directly through Magento in the `fresh1_en` store context.
3. Watch `var/log/exception.log` and `var/log/system.log` while rendering the page.
4. Check `Sm_FilterProducts`, `Sm_Categories`, `Sm_ListingTabs`, `Sm_MegaMenu`, and any category/product attribute dependencies.
5. Audit the quickstart DB for any additional stale backend/source model class references imported from a different Magento patch level.

### Environment follow-up

The local search service is still unhealthy.

Observed issue:

- `Could not ping search engine: No alive nodes found in your cluster`

This should be fixed separately because it affects search indexing, though it is not the main blocker for the current homepage shell.

## Important Paths

Quickstart archive:

- [sm_market_quickstart_pl_m2.4.8_v10.13.zip](/home/ildar/projects/magento/theme_files/Code_v10.13/magento2.4.x-new-framework/sm_market_quickstart_pl_m2.4.8_v10.13.zip)

Theme archive:

- [sm_market_theme_m2.4.6-2.4.8_v10.13.zip](/home/ildar/projects/magento/theme_files/Code_v10.13/magento2.4.x-new-framework/sm_market_theme_m2.4.6-2.4.8_v10.13.zip)

Vendor guide:

- [index.html](/home/ildar/projects/magento/theme_files/Guide/Guie2.4.x_newfw/index.html)

Fresh 1 import config:

- [fresh1.xml](/home/ildar/projects/magento/app/code/Sm/Market/etc/import/fresh1.xml)

Magento module config:

- [config.php](/home/ildar/projects/magento/app/etc/config.php)

## Summary

Theme installation is no longer the main issue.

The site has already been moved onto the Fresh 1 quickstart dataset and now uses the correct Fresh 1 shell, store view, homepage, media, and sample catalog.

The remaining work is to fix homepage widget rendering so the demo’s dynamic product sections render properly and the storefront visually matches the vendor Fresh 1 demo end-to-end.
