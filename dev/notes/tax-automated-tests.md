# Tax Automated Tests (Canada Checkout)

This document covers automated verification for Canadian tax calculation during checkout (GST/HST/PST/QST/RST by destination province/territory).

Baseline expectations are stored as JSON fixtures and compared against the checkout tax summary.

## Quick Start (Playwright Screenshots + Assertions)

Run **all provinces/territories**:
`$ npm run pw:tax-screenshots`

Run **one province** (example: Quebec):
`$ PW_ONLY_PROVINCES=QC npm run pw:tax-screenshots`

Run **a subset** (example: QC + ON + BC):
`$ PW_ONLY_PROVINCES=QC,ON,BC npm run pw:tax-screenshots`

Run in headed mode (debug):
`$ PW_HEADLESS=0 npm run pw:tax-screenshots`

## What The Test Does

Script: `tools/playwright/storefront-tax-scenarios.js`

- Opens a single product (fixture default: `Red Bali` on `/red-bali.html`)
- Selects the configured weight (fixture default: `50g`)
- Proceeds as a guest through checkout
- Enters a province-specific Canadian shipping address (fixture)
- Selects the configured shipping method (fixture default: `Regular Shipping`)
- Asserts:
  - Subtotal equals fixture baseline (when provided)
  - Shipping amount equals fixture baseline
  - Tax detail lines match expected titles + percentages (e.g., `GST (5%)`, `QST (9.975%)`)
  - Total tax equals computed expected tax (sum over all tax components for product + shipping)
- Captures full-page screenshots on:
  - Shipping step
  - Payment step (with totals visible)

Artifacts (gitignored):

- Screenshots: `.playwright/artifacts/tax-scenarios/`
- Manifest: `.playwright/artifacts/tax-scenarios/manifest.json`

At the end of the run, the script prints a JSON object including the screenshot list so you can quickly locate all images produced.

## Baseline Fixtures

Fixture file:

- `tools/playwright/fixtures/canada-tax-baseline.json`

Edit this file if:

- Shipping prices change (update `expectedShippingAmount`)
- Product price changes (update `expectedSubtotalAmount` if present)
- Tax rates change (update per-province `taxes` percents)

