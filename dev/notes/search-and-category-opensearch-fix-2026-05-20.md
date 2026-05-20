# Search + Category Empty Pages Fix (2026-05-20)

## Symptoms Reported
- Storefront search returned no results for queries like `Gree Malay` / `Green Malay`.
- Clicking a homepage category link such as `Green Vein Kratom` opened a category page that rendered empty (no product list).
- Reindexing often emitted OpenSearch errors.

## What Was Actually Broken

### 1) Magento Search Engine Was Misconfigured For OpenSearch 2.x
Magento was configured with:
- `catalog/search/engine = elasticsearch7`
- host/port pointed at the DDEV OpenSearch container (`opensearch:9200`)

In this setup, reindexing `catalogsearch_fulltext` attempted to PUT a *typed* mapping to an endpoint like:
- `PUT /market247_product_111_v*/document/_mapping`

OpenSearch 2.x does not support that typed mapping endpoint, so indexing failed with:
- `no handler found for uri [/.../document/_mapping] and method [PUT]`

Net effect: product search indices existed but had `docs.count = 0`, so storefront search always returned empty.

### 2) Category-Product Index Was Stale For The Active Store View
The active store view in this DB is `store_id = 111` (`fresh1_en`).

For category `377` (`Green Vein Kratom`):
- `catalog_category_product` had rows (products were assigned)
- but `catalog_category_product_index_store111` had **0** rows for `category_id = 377`

So the category product list rendered empty even though product-category relations existed.

## Fix Applied

### 1) Switch Magento Search Engine To The Native OpenSearch Adapter
Run in `ddev-magento-web`:

```bash
php bin/magento config:set catalog/search/engine opensearch
php bin/magento config:set catalog/search/opensearch_server_hostname opensearch
php bin/magento config:set catalog/search/opensearch_server_port 9200
php bin/magento config:set catalog/search/opensearch_index_prefix market247
php bin/magento config:set catalog/search/opensearch_enable_auth 0
php bin/magento config:set catalog/search/opensearch_server_timeout 15
php bin/magento cache:clean config
```

Then reindex search:

```bash
php bin/magento indexer:reindex catalogsearch_fulltext
```

After this, OpenSearch indices started populating (non-zero `docs.count`) and storefront search returned products again.

### 2) Rebuild Category/Product Relation Indexes
Run in `ddev-magento-web`:

```bash
php bin/magento indexer:reindex catalog_category_product catalog_product_category
```

After this, `catalog_category_product_index_store111` began including rows for `377` and `378`, and the category pages were no longer empty.

## Verification

### Storefront (curl)
- `https://magento.ddev.site/catalogsearch/result/?q=Green+Malay` shows product cards.
- `https://magento.ddev.site/green-vein-kratom.html` shows the 3 green kratom configurables.

### Playwright Harness
Added a storefront Playwright check script:
- `npm run pw:search-check`

It:
- submits a search for `Gree Malay` and asserts results exist
- visits `/green-vein-kratom.html` and asserts the main product list is non-empty
- writes screenshots to:
  - `.playwright/artifacts/storefront-search-results.png`
  - `.playwright/artifacts/storefront-category-green-vein.png`

