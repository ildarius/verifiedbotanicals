# Dev3 weekly-deals repair hand-off

You have shell access to the Magento installation serving `https://dev3.verifiedbotanicals.com`. Diagnose and fix the empty homepage **“Grab The Best Offer of This Week!”** section. Do not change or delete CMS blocks, products, categories, customers, orders, or any demo-cleanup data as part of this task.

Observed state: production renders the deal; dev3 renders “We can't find products matching the selection.” The dev3 homepage (`cms_page.identifier = home`) has a `Sm\\FilterProducts\\Block\\Widget\\AddFilterProducts` widget using `grid-slider-deal2.phtml`, `product_source="countdown_products"`, categories `377,378,379`, limit `2`, and end date `09/10/2026 19:00:10`.

Database evidence already verified on dev3: active cycle `10` selects `GH` / Green Hulu (product `451`) and `WH` / White Hulu (product `469`). Both are enabled, catalog-visible, salable, website-assigned, category-linked, price-indexed, and have a special price active through `2026-09-10 19:00:10`. This is an application collection/runtime failure, not missing deal data.

Work from the Magento root:

```bash
php bin/magento cache:clean block_html full_page
php bin/magento indexer:status
php bin/magento indexer:reindex catalog_product_price cataloginventory_stock inventory catalog_category_product
php bin/magento cache:flush
```

If the widget is still empty, inspect `app/code/Sm/FilterProducts/Block/FilterProducts.php`, especially `_countDownProducts()`. Compare it with the known-good implementation in the project repository. The collection must:

- filter active specials with `special_from_date <= now` and `special_to_date >= now`;
- retain the widget end-date upper bound;
- use the actual `catalog_category_product` relations for categories `377,378,379` rather than relying on a missing/stale category index;
- filter storefront-visible, salable configurable products;
- return both active parents `GH` and `WH`.

Deploy the corrected PHP file, run the smallest required compile/deploy commands for production mode, and flush `block_html` and `full_page`. Do not alter the homepage CMS widget directive unless code inspection proves it differs from the values above.

Validate with:

```bash
curl -k -L -sS https://dev3.verifiedbotanicals.com/ | rg -n -C 3 'Grab The Best Offer|We can.t find products matching|Green Hulu|White Hulu'
```

Success means the homepage HTML contains the deal heading, countdown markup, and both deal products; it must not contain the empty-selection message. Report the changed file(s), Magento commands run, and validation output.
