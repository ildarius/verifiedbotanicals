# Rotating Special Deals

**Status:** Implemented in `app/code/Local/RotatingSpecialDeals/`. This note preserves the original implementation analysis and resulting feature shape.

## Goal

Implement a fully automated 14-day promotion cycle that:

- selects exactly 2 products every 14 days
- applies a 30% discount
- ensures the 2 selected products come from different groups
- prevents either product from repeating in the immediately previous cycle
- removes the previous cycle automatically
- updates the homepage SM deals countdown immediately

This note started as a pre-implementation analysis and remains useful as background for the live rotating-deals module.

## Existing Components We Can Reuse

### 1. Magento native special pricing already covers storefront price display

The SM theme templates call Magento price rendering via `getProductPrice($_product)`, so if a product has a valid `special_price`, Magento will render the regular price plus discounted price using the standard price renderer.

Relevant files:

- [app/design/frontend/Sm/market/Sm_FilterProducts/templates/thumb-deals.phtml](/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_FilterProducts/templates/thumb-deals.phtml:122)
- [app/design/frontend/Sm/market/Sm_FilterProducts/templates/grid-slider-deal2.phtml](/home/ildar/projects/magento/app/design/frontend/Sm/market/Sm_FilterProducts/templates/grid-slider-deal2.phtml:1)

Implication:

- We do not need custom frontend price markup just to show original and discounted prices.

### 2. The current homepage deal block is driven by `Sm_FilterProducts`, not a standalone local `Sm_Deals` module

The homepage demo content for `home-demo-37` embeds a `Sm\FilterProducts\Block\Widget\AddFilterProducts` widget with:

- `product_source="countdown_products"`
- `template="Sm_FilterProducts::grid-slider-deal2.phtml"`
- widget-level `date_to`

Relevant files:

- [app/code/Sm/Market/etc/import/pages.xml](/home/ildar/projects/magento/app/code/Sm/Market/etc/import/pages.xml:9235)
- [app/code/Sm/Market/etc/import/fresh1.xml](/home/ildar/projects/magento/app/code/Sm/Market/etc/import/fresh1.xml:6)

Implication:

- The countdown timer reset needs to update the homepage CMS widget directive or equivalent persisted widget config, not only product pricing.

### 3. `Sm_FilterProducts` already knows how to fetch products with special pricing and render countdowns

There are two relevant product sources:

- `special_products`: selects products with active `special_price`, `special_from_date <= now`, `special_to_date >= now`
- `countdown_products`: selects products with `special_price` and compares `special_to_date` to widget `date_to`

Relevant files:

- [app/code/Sm/FilterProducts/Block/FilterProducts.php](/home/ildar/projects/magento/app/code/Sm/FilterProducts/Block/FilterProducts.php:301)
- [app/code/Sm/FilterProducts/etc/widget.xml](/home/ildar/projects/magento/app/code/Sm/FilterProducts/etc/widget.xml:57)

Implication:

- Existing theme/widget plumbing is reusable.
- The automation layer should feed this component instead of replacing the homepage deals UI.

### 4. Cron infrastructure is already normal Magento cron

The project already contains custom module crons from other vendors, so adding a small custom cron job follows established conventions.

Relevant files:

- [app/code/Magefan/Blog/etc/crontab.xml](/home/ildar/projects/magento/app/code/Magefan/Blog/etc/crontab.xml:8)

Implication:

- A custom module with `etc/crontab.xml` is the right mechanism for the 14-day rotation.

## Gaps In Existing Components

### 1. No existing business logic for rotation, history, or group-aware selection

There is no current code that:

- stores a deal cycle
- remembers the previous pair
- enforces “different category/product group”
- schedules a replacement every 14 days

### 2. The current `countdown_products` query is not strict enough for active-cycle selection

`_countDownProducts()` filters with:

- `special_from_date <= now`
- `special_to_date <= widget date_to`

It does **not** require `special_to_date >= now`.

Relevant file:

- [app/code/Sm/FilterProducts/Block/FilterProducts.php](/home/ildar/projects/magento/app/code/Sm/FilterProducts/Block/FilterProducts.php:329)

Implication:

- The current homepage block can include products whose special already expired, as long as the expiry is before the widget `date_to`.
- For this feature, we should plan on correcting this behavior as part of implementation.

### 3. There is no existing “strain/group” attribute in the imported catalog

The importer currently creates only:

- `kratom_weight`

Relevant file:

- [import_products.php](/home/ildar/projects/magento/import_products.php:52)

The CSV does already categorize products into:

- `Red Vein Kratom`
- `Green Vein Kratom`

Relevant file:

- [data/products.csv](/home/ildar/projects/magento/data/products.csv:1)

Implication:

- We need to define what “different category or product group” means in code.
- Based on current data, category is the only existing reliable grouping signal.

### 4. Homepage countdown end date is embedded in CMS widget parameters

On `home-demo-37`, the countdown end date is hardcoded inside the widget directive in CMS page content.

Relevant file:

- [app/code/Sm/Market/etc/import/pages.xml](/home/ildar/projects/magento/app/code/Sm/Market/etc/import/pages.xml:9235)

Implication:

- Rotating deals must update persisted CMS/widget content or some equivalent runtime source used by that widget.

## Recommended Implementation Direction

Build a new custom module, likely `Local/RotatingSpecialDeals`, that owns:

- cycle state/history
- scheduled selection
- special price assignment/removal
- homepage countdown synchronization
- cache invalidation

Use Magento native product `special_price`, `special_from_date`, and `special_to_date` as the pricing mechanism.

## Why use native special pricing instead of catalog price rules

- The existing SM homepage deal widgets already depend on product-level `special_price`.
- Magento native price rendering will already show old price plus discounted price.
- Product-level dates map directly to the 14-day cycle requirement.

Catalog price rules would add extra indexing complexity and would not automatically satisfy the current SM widget selection logic.

## Proposed Architecture

### 1. New module

Create `app/code/Local/RotatingSpecialDeals/` with:

- `registration.php`
- `etc/module.xml`
- `etc/crontab.xml`
- `etc/db_schema.xml`
- service classes for selection, cycle rotation, homepage sync, and cleanup

### 2. Persistent cycle tracking table

Add a small custom table to record each cycle, for example:

- `cycle_id`
- `started_at`
- `ends_at`
- `status`
- `product_ids` or child relation table
- `group_keys`
- `homepage_target`
- audit timestamps

Recommended shape:

- one cycle header table
- one cycle item table with one row per selected product

Why:

- avoids trying to infer the “previous pair” from current catalog prices
- makes cron idempotent and debuggable
- gives a clean source of truth for admin/manual inspection later

### 3. Product selection rules

Selection service should:

- choose 2 enabled, saleable, frontend-visible products
- require distinct group keys
- exclude product IDs from the immediately previous cycle
- randomize fairly within the eligible pool

Initial recommended group key:

- primary assigned top-level strain category, or simpler:
- category ID from a configured allowlist such as `Red Vein Kratom` and `Green Vein Kratom`

Because the current catalog only clearly exposes strain via category, category-based grouping is the lowest-risk first implementation.

### 4. Deal application strategy

For each new cycle:

1. load previous active cycle
2. remove previous special pricing from its selected products
3. select new eligible pair
4. set:
   - `special_price = regular price * 0.70`
   - `special_from_date = cycle start`
   - `special_to_date = cycle end`
5. save cycle state
6. update homepage countdown end date
7. flush the narrowest relevant caches and reindex product price if required

### 5. Homepage countdown synchronization

The homepage block currently uses widget parameter `date_to` on a CMS page widget directive.

Planned sync behavior:

- detect the active homepage deal widget on `home-demo-37`
- rewrite its `date_to` parameter to the new cycle end
- persist the updated CMS page content

Alternative fallback if the homepage implementation differs in live data:

- update the relevant `widget_instance` record if the site uses a widget instance rather than inline CMS directive
- otherwise update store config only if the homepage block is reading config defaults instead of inline parameters

This part needs one short implementation-time verification against the live local database because imported sample XML is only a template, not guaranteed current persisted content.

### 6. Cache and index refresh

After a successful rotation:

- clean at least `block_html`, `full_page`, and relevant catalog cache tags
- run the narrowest necessary reindex, expected to include `catalog_product_price`

Reason:

- price changes need to be visible immediately
- homepage countdown block is heavily cache-sensitive

### 7. Fix or bypass the current `countdown_products` gap

Recommended options, in order:

1. add a targeted preference/plugin so countdown products require `special_to_date >= now`
2. if that proves too invasive, switch the homepage block to a custom product source backed by our cycle table

Preferred approach is option 1 because it preserves existing SM widget rendering with minimal frontend change.

## Implementation Phases

### Phase 1. Verification spike

- confirm how the current homepage deal block is persisted locally:
  - CMS page content directive
  - widget instance
  - store config
- confirm whether the visible featured products are configurable parents, simple products, or mixed
- confirm native `special_price` on those visible products produces correct frontend pricing

This is the most important pre-code check because configurable pricing can behave differently depending on how the catalog was imported.

### Phase 2. Module skeleton and storage

- create module
- add cycle tables
- add repository/service classes

### Phase 3. Rotation engine

- implement cycle finder
- implement eligible product pool builder
- implement group-aware random selector
- implement previous-cycle exclusion
- implement deal apply/remove logic

### Phase 4. Homepage timer sync

- update homepage widget `date_to`
- fix or extend `countdown_products` behavior if needed
- invalidate caches

### Phase 5. Validation

- manual cron execution
- verify two products change
- verify both are from different groups
- verify previous pair is not repeated
- verify old cycle products lose their special price
- verify homepage countdown resets to exactly 14 days from the new start
- verify storefront shows full price and discounted price

## Validation Plan

Magento-side validation after implementation:

- `php bin/magento setup:upgrade`
- `php bin/magento setup:di:compile`
- `php bin/magento cache:clean`
- targeted reindex if the implementation requires it

DDEV execution:

- run Magento CLI inside `ddev-magento-web`

Functional checks:

- trigger cron job manually
- inspect selected products in admin/database
- open homepage and product pages
- confirm timer end matches cycle end
- confirm the homepage widget shows only the current pair

## Risks And Decisions To Confirm Before Coding

### 1. Definition of “different group”

Current recommendation:

- use product category as the grouping axis
- for your current catalog, treat `Red Vein Kratom` and `Green Vein Kratom` as distinct groups

If you want broader future-proofing, we should add a dedicated product attribute such as `deal_rotation_group`.

### 2. Visible product type

If storefront-visible products are configurable parents with derived pricing, we need to verify whether parent-level `special_price` is sufficient. If not, the implementation may need:

- a custom selected-product source for the homepage
- and/or child-product price updates with parent display adjustments

### 3. Single homepage target

The current plan assumes one homepage deal block on `home-demo-37`. If multiple pages or store views should rotate together, the module should store a configurable list of targets.

## Recommendation

Proceed with a custom module using:

- Magento native special prices for the discount
- a custom cycle/history table for rotation state
- category-based grouping for the first version
- homepage CMS/widget `date_to` synchronization
- a small correction to `Sm_FilterProducts` countdown selection so only active-cycle deals appear

This gives the highest reuse of the current stack while keeping the custom code focused on the missing business logic rather than replacing the vendor theme components.
