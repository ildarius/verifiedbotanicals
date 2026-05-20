# Kratom Product Import CSV Format

This repo's kratom product import should use `var/tmp/products.csv` as the working CSV path when present.

The current example format in `var/tmp/products.csv` is the format to follow for future imports.

## Expected Row Types

- `product_type=configurable` rows define the parent products such as `RB`, `RMD`, `RH`, `GMD`, `GM`, `GH`.
- `product_type=simple` rows define the purchasable variations such as `RB25`, `RB50`, `RB100`, etc.

## Important Columns

- `sku`
- `product_type`
- `categories`
- `name`
- `description`
- `short_description`
- `price`
- `qty`
- `is_in_stock`
- `weight`
- `_internal_size_label`
- `_internal_parent_sku`

## Pricing Rules

- Simple-product pricing comes directly from the CSV `price` column.
- Configurable parent pricing is set by the importer to the minimum child price for that parent.
- Do not rely on the old rotating-special fallback seed prices when the CSV contains real prices.

## Configurable Attribute Rules

- Kratom variations are linked by the custom configurable attribute `kratom_weight`.
- `_internal_size_label` should match the attribute options used by the importer, currently:
  - `25g`
  - `50g`
  - `100g`
  - `250g`
  - `500g`

## Rotating Specials Interaction

- The rotating specials module uses native Magento `special_price`, `special_from_date`, and `special_to_date`.
- After an import, if there is an active rotating-special cycle, the importer refreshes the active cycle items so they remain at the configured discount factor against the newly imported base prices.
- Current discount factor: `30%` off, implemented as `special_price = base price * 0.70`.

## Import Command

Run:

```bash
docker exec -u 1000 ddev-magento-web php import_products.php
```

`import_products.php` now supports both:

- the new CSV format described above
- the older legacy import CSV shape, as a compatibility fallback
