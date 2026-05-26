# Shipping Automated Tests

This document covers automated validation for customer-facing shipping options (Regular vs Express) as documented in `dev/notes/shipping-logic-postage.md`.

## Quick Start (Playwright Screenshots)

This test will generate checkout UI screenshots for all scenarios (SKUs × provinces × methods) and write them to `.playwright/artifacts/shipping-scenarios/`:
`$ npm run pw:shipping-screenshots`

This test will generate checkout UI screenshots for RB25 only:
`$ PW_ONLY_SKUS=RB25 npm run pw:shipping-screenshots`

This test will generate checkout UI screenshots for QC only (Regular Shipping only):
`$ PW_ONLY_PROVINCES=QC PW_ONLY_METHODS="Regular Shipping" npm run pw:shipping-screenshots`

## Playwright Screenshot Harness (Checkout UI)

This Playwright harness runs a real (guest) checkout flow and captures what the customer sees:

- Selects `Red Bali` configurable weight to purchase the target SKU (`RB25`, `RB50`, `RB100`, `RB250`, `RB500`)
- Proceeds through checkout for each province (`QC`, `ON`, `BC`)
- Selects the target shipping method (`Regular Shipping` / `Express Shipping`) where applicable
- Captures **full-page screenshots** on:
  - Shipping step (after selecting the shipping method)
  - Payment step (shows product + shipping totals)

Screenshots are written to (gitignored): `.playwright/artifacts/shipping-scenarios/`

### Run All Scenarios

This test will generate screenshots for **all** SKU/province/method combinations:
`$ npm run pw:shipping-screenshots`

### Run One SKU

This test will generate screenshots for **RB25 only** (QC/ON/BC, methods as applicable):
`$ PW_ONLY_SKUS=RB25 npm run pw:shipping-screenshots`

### Run One Province

This test will generate screenshots for **Ontario only** (all SKUs, methods as applicable):
`$ PW_ONLY_PROVINCES=ON npm run pw:shipping-screenshots`

### Run One Shipping Method

This test will generate screenshots for **Regular Shipping only** (skips Express scenarios):
`$ PW_ONLY_METHODS="Regular Shipping" npm run pw:shipping-screenshots`

### Run a Narrow Subset

This test will generate screenshots for RB25/RB50 in QC/ON using Regular Shipping:
`$ PW_ONLY_SKUS=RB25,RB50 PW_ONLY_PROVINCES=QC,ON PW_ONLY_METHODS="Regular Shipping" npm run pw:shipping-screenshots`

## PHP Regression (No Browser)

Fast checks that validate the shipping logic without rendering checkout UI:

- Direct carrier rate request checks: `docker exec -u 1000 ddev-magento-web php dev/tools/test_matrixrate_shipping.php`
- Quote/cart checks with real products: `docker exec -u 1000 ddev-magento-web php dev/tools/test_matrixrate_checkout_quotes.php`
