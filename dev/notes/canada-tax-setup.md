# Canada Tax Setup (Magento 2)

This project uses Magento’s built-in tax engine for Canadian GST/HST/PST/QST. The configuration is applied via an idempotent data-patch module so it can be reproduced reliably across environments.

## What’s Implemented

Module: `Local_CanadaTaxSetup` (`app/code/Local/CanadaTaxSetup/`)

- Creates a product tax class: `Taxable Goods`
- Creates CA tax rates for all provinces/territories (GST/HST and province-specific PST/QST/RST)
- Creates tax rules for each province/territory
  - **BC/MB/SK/QC are split into separate rules per component tax** so Magento shows separate tax lines (e.g., GST + PST) consistently in checkout totals
- Sets recommended tax configuration defaults (destination-based on shipping address, display settings, shipping tax class)

This note is the permanent reference for the implemented tax setup in this repo.

## Apply / Re-Apply

Run inside the web container:

`$ docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade`

Then (if you changed storefront behavior and want a clean baseline):

`$ docker exec -u 1000 ddev-magento-web php bin/magento cache:flush`

## Notes / Gotchas

- The module is intentionally **idempotent** (safe to run multiple times).
- If you manually change tax rules/rates in Admin, re-running `setup:upgrade` will put them back to the expected baseline.
- Tax class for shipping is set to `Taxable Goods` so the same province rules apply to shipping charges.

## Verification (UI)

Use the automated tax checkout verifier:

- `dev/notes/tax-automated-tests.md`
