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

Important scope detail:

- the global default homepage config is still `web/default/cms_home_page = home-demo-01`
- Fresh 1 is active at store scope for `fresh1_en` / `fresh1_ar`
- the root URL resolves to Fresh 1 because `store_website.website_id = 1` now uses `default_group_id = 41`, whose default store is `fresh1_en`

## Sanity Check Against Vendor Guide

Compared with the bundled vendor instructions in [theme_files/Guide/Guie2.4.x_newfw/index.html](/home/ildar/projects/magento/theme_files/Guide/Guie2.4.x_newfw/index.html):

- The local result does cover the major vendor setup milestones for showing Fresh 1 on the storefront:
  - theme files are installed
  - `Sm/market` is active for the Fresh 1 store views
  - static blocks and pages were imported
  - the Fresh 1 demo homepage is assigned
  - the site renders with `header-37`, `product-21`, and `footer-29`
- The local approach does not match the vendor quickstart process exactly. The vendor quickstart guide expects a fresh Magento install from the quickstart package and then a Magento `setup:install`; this project instead overlaid the vendor quickstart database and media onto an existing Magento 2.4.7 codebase and DDEV environment.
- The vendor manual-install note about missing products due to wrong category IDs does not look like the primary missed step here. The live DB now has the vendor sample catalog (`362` products) and the rendered Fresh 1 homepage still outputs raw widget directives, which points to a widget/rendering failure rather than just empty category mappings.
- One vendor-required installation step is still not clean in this environment: `php bin/magento setup:static-content:deploy -f` has a known compatibility issue in `Sm/themecore`. The storefront can render in the current mode, but this is still an incomplete part of a vendor-equivalent setup.
- The vendor guide also assumes a healthy Elasticsearch service for Magento 2.4.x. That is still not true locally, and the environment currently reports `Could not ping search engine: No alive nodes found in your cluster`.
- The vendor guide does contain one note that looks related at first glance:
  - if homepage product sections do not display, edit the homepage CMS content and change category IDs used by `SM Filter Products` / `SM Listing Tabs`
  - that recommendation is aimed at "widget rendered but no products shown" cases
  - current evidence does not match that exact failure mode, because the live storefront still returns raw `{{widget ...}}` directives in the final HTML instead of empty rendered product sections

Bottom line:

- There is no strong evidence that a basic vendor setup step like theme assignment, blocks/pages import, demo import, or media restore was forgotten.
- The remaining gap is more likely a compatibility/runtime problem in the imported SM widget stack than a missed checklist item from the vendor documentation.

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

Concrete evidence from the current environment:

- the rendered storefront still contains raw widget markup for Fresh 1 product sections
- example live response snippets:
  - `{{widget type="Sm\FilterProducts\Block\Widget\AddFilterProducts" template="Sm_FilterProducts::grid-slider-deal2.phtml" ...}}`
  - `{{widget type="Sm\FilterProducts\Block\Widget\AddFilterProducts" template="Sm_FilterProducts::grid-slider.phtml" ...}}`
- the homepage CMS record is `cms_page.page_id = 45`, `identifier = home-demo-37`
- current homepage content length is `18918`, so this is the imported vendor CMS page, not a placeholder

## Debugging Already Attempted

The following was tested during investigation:

- direct Fresh 1 demo config import
- full quickstart DB restore
- full quickstart media restore
- category/product index rebuild
- cache flushes
- PageBuilder/plugin-based workaround attempts
- module-side `Sm_FilterProducts` template path repair
- full-page second-pass CMS filter retry in `Sm\Themecore`
- targeted fallback plugin retrying leftover PageBuilder `{{widget ...}}` directives
- targeted PageBuilder HTML pre-decode in `Sm\Themecore\Block\Cms\Page`
- isolated direct renders of `Sm\Themecore\Block\Cms\Page::_toHtml()` in the `fresh1_en` store context
- live-route instrumentation inside `Sm\Themecore\Block\Cms\Page`
- cloned per-render CMS filters in `Sm\Themecore\Block\Cms\Page` and `Sm\Themecore\Block\Cms\Block`
- narrow leftover-widget retry inside `Sm\Themecore\Block\Cms\Page`
- two targeted `Sm\MegaMenu` constructor/dependency fixes

The local workaround module used during testing was removed and is not part of the final repo state.

Final repo state from this work does not keep that temporary module.

Latest concrete findings from the follow-up fix attempt:

- Magento was logging `Invalid template file: 'Sm_FilterProducts::grid-slider.phtml' in module: 'Sm_FilterProducts'`
- the failing homepage widgets reference:
  - `Sm_FilterProducts::grid-slider.phtml`
  - `Sm_FilterProducts::grid-slider-deal2.phtml`
- those templates existed in the theme override path but not in the module path, which matters when Magento renders widget blocks by module alias
- the repo now includes module-side copies at:
  - [app/code/Sm/FilterProducts/view/frontend/templates/grid-slider.phtml](/home/ildar/projects/magento/app/code/Sm/FilterProducts/view/frontend/templates/grid-slider.phtml)
  - [app/code/Sm/FilterProducts/view/frontend/templates/grid-slider-deal2.phtml](/home/ildar/projects/magento/app/code/Sm/FilterProducts/view/frontend/templates/grid-slider-deal2.phtml)
- the repo now also includes the missing widget option for `grid-slider-deal2` in [app/code/Sm/FilterProducts/etc/widget.xml](/home/ildar/projects/magento/app/code/Sm/FilterProducts/etc/widget.xml)
- after that template-path repair, the storefront still emitted raw `{{widget ...}}` directives, so the template-file problem was real but not the only blocker

Latest concrete findings from the 2026-04-29 follow-up run:

- the imported `home-demo-37` content is PageBuilder HTML content with escaped inner markup
- isolated direct renders of [app/code/Sm/Themecore/Block/Cms/Page.php](/home/ildar/projects/magento/app/code/Sm/Themecore/Block/Cms/Page.php) can produce real rendered product HTML for the same page in store `fresh1_en`
- the standard live storefront route still returns raw widget directives for the two `Sm\FilterProducts` sections even after the same CMS page block can render them in isolation
- two real constructor/runtime compatibility issues were found and fixed in `Sm\MegaMenu`:
  - [app/code/Sm/MegaMenu/Model/MenuItems.php](/home/ildar/projects/magento/app/code/Sm/MegaMenu/Model/MenuItems.php) no longer injects `Magento\Framework\App\Action\Context` just to get a message manager
  - [app/code/Sm/MegaMenu/Block/MegaMenu/View.php](/home/ildar/projects/magento/app/code/Sm/MegaMenu/Block/MegaMenu/View.php) no longer injects `Magento\Framework\View\Context` through the failing path and now uses the block request directly
- those `Sm\MegaMenu` fixes remove real `Missing required argument $routerList of Magento\Framework\App\RouterList` failures that previously occurred while rendering decoded homepage content
- after the `Sm\MegaMenu` fixes, the visible storefront symptom still remained: the live homepage response still exposed raw `{{widget ...}}` directives
- the CMS page block now also clones a fresh page filter per render, and the CMS block class clones a fresh block filter per render:
  - [app/code/Sm/Themecore/Block/Cms/Page.php](/home/ildar/projects/magento/app/code/Sm/Themecore/Block/Cms/Page.php)
  - [app/code/Sm/Themecore/Block/Cms/Block.php](/home/ildar/projects/magento/app/code/Sm/Themecore/Block/Cms/Block.php)
- that fresh-filter change was kept because it is safe and avoids shared filter-instance state, but by itself it did not resolve the visible homepage symptom
- isolated direct rendering of the exact failing widget directive through `Magento\Cms\Model\Template\FilterProvider::getPageFilter()` works in store `fresh1_en`
  - the `Sm\FilterProducts\Block\Widget\AddFilterProducts` directive for `grid-slider.phtml` expands to real product HTML in isolation
  - that means the widget block itself is not completely broken
- isolated `toHtml()` on the CMS page block also renders the failing homepage section correctly
  - both `_toHtml()` and `toHtml()` on `Magento\Cms\Block\Page` / `Sm\Themecore\Block\Cms\Page` can produce rendered `block-filterproducts` HTML for the `block-home-37 block-deal-full-37` section
- despite that, the real HTTP response at `https://magento.ddev.site/` still contains the raw `Sm\FilterProducts` widget directives after cache clean and php-fpm restart
- because of that, the strongest current evidence is:
  - the failing directives are renderable
  - the CMS page block can render them
  - the live storefront response still diverges from that working render path

Important observed divergence from this run:

- the live request path and the isolated direct CMS page render do not behave the same even when pointed at the same `home-demo-37` page in store `fresh1_en`
- direct invocation of `Sm\Themecore\Block\Cms\Page::_toHtml()` can return fully rendered `Sm\FilterProducts` product markup
- `curl -k https://magento.ddev.site/` still returns raw `{{widget ...}}` directives for those same sections
- because of that, the remaining blocker is now specifically a live request-path or filter-chain divergence, not a missing sample-data step and not the earlier missing-template issue

Latest concrete findings from the 2026-04-29 later follow-up:

- restarting `php-fpm` inside `ddev-magento-web` did not change the visible storefront symptom
  - this was checked specifically to rule out stale php-fpm/opcache state
- a narrow frontend plugin attempt was created and then removed again because it did not change the real homepage output
  - first attempt: plugin on `Magento\Framework\View\Result\Page::renderResult()`
  - second attempt: plugin on `Magento\Framework\App\Response\Http::sendResponse()`
  - goal of both attempts was intentionally narrow:
    - only target leftover raw `Sm\FilterProducts\Block\Widget\AddFilterProducts` directives
    - only on the Fresh 1 homepage response body
    - do not re-filter the entire CMS page
  - both attempts were removed again after live verification because `curl -k https://magento.ddev.site/` still returned the same raw directives
- runtime checks showed that:
  - the response plugin was present in Magento's frontend plugin list
  - however, mutating the response at that layer still did not change the observed storefront HTML
  - because of that, those response-layer plugin approaches should be treated as already tested and not kept in the repo

Do not retry these approaches as-is:

- Do not reintroduce a broad second-pass `filter()` call across the whole CMS page content in `Sm\Themecore\Block\Cms\Page` or `Sm\Themecore\Block\Cms\Block`.
  - That caused storefront regressions and, in live testing, could collapse rendering into failures or 502s.
- Do not reintroduce the temporary `Local_PageBuilderDirectiveFix` module in its prior form.
  - It was created, registered, tested, and then fully removed.
  - The module entry was also removed again from `app/etc/config.php`.
- Do not retry the PageBuilder plugin approach that regex-matches every leftover `{{widget ...}}` directive in the whole page and calls the widget filter on each one during the frontend request.
  - That version made homepage requests hang long enough to be unusable.
- Do not retry the temporary `cms_page_render` observer or temporary PageBuilder plugin that were created during the 2026-04-29 follow-up run and then removed again.
  - Those variants did not resolve the live storefront response.
  - One of those temporary observer attempts also caused a stale-config `Class "Sm\Themecore\Observer\RenderPageBuilderWidgets" does not exist` failure until Magento config/compiled caches were cleaned again.
- Do not keep temporary debug endpoints or temporary homepage debug logging from this run.
  - They were used only to compare the isolated render path against the live route and were removed again.
- Do not assume that because `Sm\Themecore\Block\Cms\Page::_toHtml()` can render the widgets in isolation, the storefront route is fixed.
  - That was explicitly tested and is false in the current environment.
- Do not assume a plain `docker restart ddev-magento-web` is safe validation on this project.
  - In this environment the container repeatedly came back stuck in `/pre-start.sh` without launching nginx/php-fpm.
  - Recovery required manually running `/start.sh` inside `ddev-magento-web`.
- Do not re-add the narrow response-layer plugin attempts from the later 2026-04-29 run unless there is a new reason to believe the final response body can actually be mutated there.
  - tested targets:
    - `Magento\Framework\View\Result\Page::renderResult()`
    - `Magento\Framework\App\Response\Http::sendResponse()`
  - both were verified against the real homepage response and then removed again because they did not change the live output

## Most Likely Remaining Root Cause

The remaining problem appears to be in one of these areas:

1. SM widget rendering compatibility on Magento 2.4.7
2. interaction between PageBuilder-encoded CMS content and widget directives
3. a remaining quickstart DB compatibility issue in EAV/module config beyond the `default_sort_by` fix
4. an exception path inside SM widget blocks or dependent modules that causes directive expansion to fail silently in the storefront response
5. stale generated/config state from the removed local workaround module
6. PageBuilder HTML-content decoding is still leaving widget directives behind in this specific CMS render path, even after the `Sm_FilterProducts` template alias issue was repaired
7. shared or stateful use of Magento template-filter instances across multiple CMS renders during the same live request
8. a live request-path divergence between the standard homepage route and the isolated direct `Sm\Themecore\Block\Cms\Page` render that was used for debugging

Additional lead from logs:

- `var/log/exception.log` contains `Magento\Widget\Model\Template\Filter` / legacy directive processing stack traces while filtering homepage content
- the same log also contains historical `InvalidArgumentException` entries for missing plugin classes from the removed test module:
  - `Local\PageBuilderDirectiveFix\Plugin\PageBuilderTemplateFilterPlugin`
  - `Local\PageBuilderDirectiveFix\Plugin\FrameworkTemplateFilterPlugin`
- `app/etc/config.php` does not currently register `Local_PageBuilderDirectiveFix`, and `php bin/magento module:status Local_PageBuilderDirectiveFix` reports `Module does not exist`
- that means the module is gone, but old generated state or cached interception metadata may still be worth ruling out during cleanup
- during the 2026-04-29 run, one stale-config error also appeared after a temporary observer had already been removed:
  - `Class "Sm\Themecore\Observer\RenderPageBuilderWidgets" does not exist`
  - cleaning `config`, `compiled_config`, `layout`, `block_html`, and `full_page` caches removed that temporary stale-reference problem
- the `Sm\MegaMenu` constructor issue was real and is now fixed in code, but it was not the final visible blocker
- no new decisive live-route exception replaced the raw widget directives after the `Sm\MegaMenu` fixes were applied

This is now a targeted debugging task, not a general theme-install task.

## Resume Point

State at end of 2026-04-29:

- keep the current `Sm\MegaMenu` constructor fixes
- keep the `Sm\Themecore` fresh-filter cloning and PageBuilder pre-decode changes
- do not re-add the removed temporary observer/plugin/debug-endpoint experiments
- do not re-add the removed response-layer plugin experiments from the later 2026-04-29 run
- the homepage is still broken specifically because the live storefront route leaves raw `Sm\FilterProducts` widget directives in the final HTML
- the same `home-demo-37` page can render correctly through isolated direct `Sm\Themecore\Block\Cms\Page::_toHtml()` invocation in store `fresh1_en`
- the exact failing `Sm\FilterProducts` widget directive also renders correctly in isolation through `Magento\Widget\Model\Template\Filter\Interceptor`
- that means the next session should start by comparing the live storefront request path against the isolated working render path, with focus on what bypasses or replaces that otherwise-working filter output during the real homepage request

## What Still Needs To Be Done

### Required next work

1. Trace the raw homepage widget directives through Magento filter/rendering.
2. Identify which specific widget block or dependency is failing during homepage expansion.
3. Fix the underlying compatibility problem so Fresh 1 homepage widgets render product HTML instead of raw directives.
4. Re-verify the homepage against the vendor Fresh 1 demo after that fix.

### Recommended debugging path

1. Start from `home-demo-37` CMS content and isolate each `{{widget ...}}` directive.
   Current page:
   - `cms_page.page_id = 45`
   - `identifier = home-demo-37`
2. Focus first on the widget types that are visibly failing in the live HTML:
   - `Sm\FilterProducts\Block\Widget\AddFilterProducts`
   - template options declared in [app/code/Sm/FilterProducts/etc/widget.xml](/home/ildar/projects/magento/app/code/Sm/FilterProducts/etc/widget.xml)
   - widget block class in [app/code/Sm/FilterProducts/Block/Widget/AddFilterProducts.php](/home/ildar/projects/magento/app/code/Sm/FilterProducts/Block/Widget/AddFilterProducts.php)
3. Check the other homepage-driven SM blocks that still participate in `home-demo-37` rendering:
   - [app/code/Sm/ListingTabs/Block/ListingTabs.php](/home/ildar/projects/magento/app/code/Sm/ListingTabs/Block/ListingTabs.php)
   - [app/code/Sm/Categories/Block/Widget/AddCategories.php](/home/ildar/projects/magento/app/code/Sm/Categories/Block/Widget/AddCategories.php)
   - `Sm\MegaMenu\Block\MegaMenu\View` directives embedded in the homepage CMS content
4. Reproduce the storefront failure while tailing logs:
   - `docker exec ddev-magento-web sh -lc 'tail -f var/log/exception.log var/log/system.log'`
   - in another shell: `curl -k https://magento.ddev.site/ | rg "\{\{widget|Sm\\\\FilterProducts|Sm\\\\ListingTabs"`
5. Rule out stale generated/interception state left behind by the removed workaround module:
   - confirm `Local_PageBuilderDirectiveFix` is absent from `app/etc/config.php`
   - confirm `php bin/magento module:status Local_PageBuilderDirectiveFix` reports `Module does not exist`
   - if needed, clean generated artifacts and caches before retesting widget rendering
6. Audit the widget config dependencies loaded by `Sm\FilterProducts\Block\Widget\AddFilterProducts::_getCfg()`:
   - it reads store config under `filterproducts/*`
   - verify the expected config exists for store `fresh1_en`
   - verify required category IDs referenced by homepage directives still exist in the quickstart dataset
7. Treat the `Sm_FilterProducts` template-path issue as already checked:
   - module-side templates now exist for `grid-slider.phtml` and `grid-slider-deal2.phtml`
   - `grid-slider-deal2` is now present in `widget.xml`
   - if raw directives still appear, move on to the page/render pipeline instead of redoing that same template-copy step
8. Treat the following as already checked and not sufficient by themselves:
   - targeted PageBuilder HTML pre-decode in `Sm\Themecore\Block\Cms\Page`
   - fresh cloned page/block filters in `Sm\Themecore`
   - narrow leftover-widget retry inside the CMS page block
   - those experiments were useful for narrowing the problem, but they did not fix the live storefront response
9. Compare the live storefront request against the isolated direct block render at the filter-chain level, not just at the page-content level:
   - the same `home-demo-37` page in store `fresh1_en` can render correctly through direct `_toHtml()` invocation
   - the standard storefront route still leaves raw directives in the final HTML
   - focus on where `Magento\Widget\Model\Template\Filter\Interceptor` behaves differently between those two paths
10. If widgets still fail after cleanup, render individual directives in isolation in the `fresh1_en` store context and compare the output path against Magento’s widget filter stack.
11. If another fallback is attempted, keep it narrower than the previous failed approaches:
   - do not re-filter the entire CMS page
   - do not regex-reprocess every widget directive in the entire final page HTML during the live request
   - prefer targeting only the specific leftover widget nodes in the real `home-demo-37` render path
   - if a fallback is attempted again, remove it if it does not change the real `curl -k https://magento.ddev.site/` output

## Online References

Useful references collected during this run:

- bundled SM Market vendor guide:
  - [theme_files/Guide/Guie2.4.x_newfw/index.html](/home/ildar/projects/magento/theme_files/Guide/Guie2.4.x_newfw/index.html)
- online SM Market docs:
  - `https://documentation.magentech.com/sm-market-24new/`
- online SM Filter Products docs:
  - `https://www.flytheme.net/documentation-sub/sm-filter-products.html`
- Magento Stack Exchange discussion about raw PageBuilder/frontend widget output:
  - `https://magento.stackexchange.com/questions/356789/magento2-custom-page-builder-doesnt-render-widgets-on-frontend`
- Magento Stack Exchange discussion about raw widget directives on Magento 2.4 homepage/frontend:
  - `https://magento.stackexchange.com/questions/356172/magento-2-4-product-list-widget-not-rendering`

How to interpret those references:

- the vendor docs are still useful for confirming intended architecture and data expectations
- the vendor category-ID advice is worth keeping in mind, but current evidence says it is not sufficient by itself for this case
- the Magento Q&A references are useful because they reinforce the same debugging model now supported by local evidence:
  - raw `{{widget ...}}` in final frontend HTML usually means the content is not being filtered at the right stage, or the filtered output is being bypassed/replaced later
  - they do not provide a drop-in SM Market / Magento 2.4.7 fix for this exact environment

Suggested search terms for future research:

- `Magento 2.4 PageBuilder raw {{widget}} frontend`
- `Magento cms page raw widget directives final html`
- `Sm FilterProducts raw widget homepage Magento`
- `Magentech SM Market homepage raw widget directives`
- `Magento PageBuilder html content type widget not rendering frontend`
- `Sm\\FilterProducts\\Block\\Widget\\AddFilterProducts raw widget`

## Planning Notes

Do not resume troubleshooting from the broad install/import side.

Best next-step options for the next session:

1. Trace where the live storefront response is assembled after the CMS page block has already produced correct HTML.
2. Compare the live homepage route against a minimal controller/block render path in the same frontend store context.
3. Inspect whether another layout/template path on the real request prints decoded CMS HTML directly instead of using the final filtered block output.
4. Only after that, decide whether the fix belongs in:
   - SM CMS page rendering
   - a later layout/template output path
   - the homepage controller/request flow

### Useful commands

1. Confirm live page still exposes raw widget directives:
   - `curl -k https://magento.ddev.site/ | rg -n "\{\{widget|Sm\\\\FilterProducts|Sm\\\\ListingTabs|home-page-37|header-37|footer-29"`
2. Confirm current store/theme/homepage routing:
   - `docker exec ddev-magento-db mysql -udb -pdb -N -e "SELECT website_id,default_group_id FROM db.store_website; SELECT store_id,code FROM db.store WHERE store_id IN (1,111,112); SELECT scope,scope_id,path,value FROM db.core_config_data WHERE scope='stores' AND scope_id IN (1,111,112) AND path IN ('design/theme/theme_id','web/default/cms_home_page');"`
3. Inspect the homepage CMS record directly:
   - `docker exec ddev-magento-db mysql -udb -pdb -N -e "SELECT page_id,identifier,title FROM db.cms_page WHERE identifier='home-demo-37'; SELECT SUBSTRING(content,1,2500) FROM db.cms_page WHERE identifier='home-demo-37';"`
4. Search logs for widget filter failures:
   - `rg -n "AddFilterProducts|FilterProducts|ListingTabs|widget|Directive|PageBuilder|home-demo-37" var/log/exception.log var/log/system.log var/log/debug.log`
5. Confirm the module-side `Sm_FilterProducts` templates now exist:
   - `find app/code/Sm/FilterProducts/view/frontend/templates -maxdepth 1 -type f | sort`
6. Confirm `grid-slider-deal2` is present in widget config:
   - `sed -n '1,120p' app/code/Sm/FilterProducts/etc/widget.xml`
7. Confirm the real live symptom, not just isolated render behavior:
   - `curl -k https://magento.ddev.site/ | rg -n "\{\{widget|Sm\\\\FilterProducts|block-filterproducts|filter-products-"`
8. Compare isolated direct CMS page render vs live route:
   - direct render probe used during this run:
   - `docker exec ddev-magento-web php -r 'require "app/bootstrap.php"; $bootstrap=\Magento\Framework\App\Bootstrap::create(BP, $_SERVER); $om=$bootstrap->getObjectManager(); $state=$om->get(\Magento\Framework\App\State::class); try{$state->setAreaCode("frontend");}catch(\Exception $e){} $pageModel=$om->create(\Magento\Cms\Model\Page::class)->setStoreId(111)->load("home-demo-37","identifier"); $block=$om->create(\Magento\Cms\Block\Page::class); $block->setData("page", $pageModel); $ref=new ReflectionMethod(get_class($block), "_toHtml"); $ref->setAccessible(true); $result=$ref->invoke($block); echo (strpos($result, "{{widget")!==false?"WIDGET_LEFT\n":"NO_WIDGET_LEFT\n"); echo substr($result, strpos($result, "block-home-37 block-deal-full-37")-120, 1400);'`
9. Check the actual filter class resolved in this environment:
   - `docker exec ddev-magento-web php -r 'require "app/bootstrap.php"; $bootstrap=\Magento\Framework\App\Bootstrap::create(BP, $_SERVER); $om=$bootstrap->getObjectManager(); $state=$om->get(\Magento\Framework\App\State::class); try{$state->setAreaCode("frontend");}catch(\Exception $e){} $filter=$om->get(\Magento\Cms\Model\Template\FilterProvider::class)->getPageFilter(); echo get_class($filter), "\n";'`

### Environment follow-up

The local search service is still unhealthy.

Observed issue:

- `Could not ping search engine: No alive nodes found in your cluster`

This should be fixed separately because it affects search indexing, though it is not the main blocker for the current homepage shell.

Separate environment trap discovered during this work:

- `docker restart ddev-magento-web` can leave the container stuck in `/pre-start.sh`
- when that happens, the storefront returns `502 Bad Gateway`
- recovery path used during this run:
  - `docker exec ddev-magento-web sh -lc '/start.sh'`

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
