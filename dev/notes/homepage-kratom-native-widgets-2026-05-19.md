# Homepage Kratom Product Widgets (2026-05-19)

## Goal
Replace the SM Market demo homepage products on `home-demo-37` with the imported kratom catalog, using the theme’s native widget blocks (not static CMS HTML), so product cards render like the vendor demo (image, name, price box, review summary, add-to-cart UI, etc.).

## Symptoms
- The homepage product areas were “hotfixed” into static HTML in `cms_page.identifier = home-demo-37`.
- Static HTML only showed image + name (no native Magento price box / review summary / add-to-cart markup).
- Attempts to restore the original `{{widget ... Sm\FilterProducts ...}}` directives previously rendered empty collections.

## Root Cause
There were two separate issues:

1. `inventory_stock_1` was corrupted.
   - It existed as a VIEW returning constants (not real rows).
   - Many Magento product collections (and MSI stock-status filters) join `inventory_stock_1` by SKU to determine “salable” products.
   - With a broken view, those joins can collapse collections to empty, which makes theme widgets appear to have “no products”.

2. The kratom configurable parents (`RB`, `RMD`, `RH`, `GMD`, `GM`, `GH`) were marked not salable.
   - Their rows in `cataloginventory_stock_status` were stuck at `stock_status = 0` even though all children were in stock.
   - This prevented them from being returned by widget collections and broke price indexing for the parents.

## Fix Applied

### 1) Recreate `inventory_stock_1` as the proper legacy-stock MSI view
Run inside the DB container:

```sql
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
```

### 2) Rebuild stock status for the kratom configurable parents
Delete the bad parent stock-status rows and reindex stock:

```sql
DELETE ss
FROM cataloginventory_stock_status ss
JOIN catalog_product_entity e ON e.entity_id = ss.product_id
WHERE e.sku IN ('RB','RMD','RH','GMD','GM','GH');
```

Then:

```bash
docker exec -u 1000 ddev-magento-web php bin/magento indexer:reindex cataloginventory_stock
```

After this, the parents become salable and `catalog_product_index_price` starts generating rows for them.

### 3) Remove the CMS hotfix and restore the native widget directives on `home-demo-37`
The CMS page content for `home-demo-37` had static HTML sections for:
- Deals of the day
- New Arrivals

These were replaced back to the theme-native widgets:
- `Sm_FilterProducts::grid-slider-deal2.phtml`
- `Sm_FilterProducts::grid-slider.phtml`

This was done using the one-off harness script:
- `var/tmp/restore_homepage_native_widgets.php`

After running it, clean caches:

```bash
docker exec -u 1000 ddev-magento-web php bin/magento cache:clean block_html full_page
```

### 4) Ensure review summary renders even when there are no reviews yet
The vendor demo shows rating stars on product cards. Our kratom products have no reviews, so Magento will render nothing unless explicitly requested.

Updated these theme overrides to always display the “empty” review summary block:
- `app/design/frontend/Sm/market/Sm_FilterProducts/templates/grid-slider.phtml`
- `app/design/frontend/Sm/market/Sm_FilterProducts/templates/grid-slider-deal2.phtml`

Change:
```php
$block->getReviewsSummaryHtml($_product, $templateType);
```
to:
```php
$block->getReviewsSummaryHtml($_product, $templateType, true);
```

## Verification
- `curl -k -L -s https://magento.ddev.site/` no longer contains raw `{{widget ...}}` directives.
- The homepage contains native product-card markup including:
  - `div.price-box ...`
  - `div.product-reviews-summary ...`
  - add-to-cart form markup.

## Notes / Follow-ups
- The kratom CSV has no `Regular price` values, so prices currently index/render as `$0.00`. If real prices are required, add them to `data/products.csv` (or extend the import to apply a weight-based price map).
- `indexer:reindex` currently triggers an unrelated OpenSearch mapping error in this environment; it does not block the homepage widget rendering, but should be addressed separately for clean production-mode reindexing.

