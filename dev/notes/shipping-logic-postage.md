# Shipping Logic — Magento Store (Customer-Facing)

## Goal

Document the customer-facing shipping options shown at checkout (Regular vs Express), driven by **total order weight** (sum of all items in cart).

## Source (Required Attribution)

This content comes from the projects file: `Claude\All projects\Sunny Kratom\docs\postage.md`.

## Proposed Docs Deliverable

- This doc lives in `dev/notes/` as the current “source of truth” for shipping rules.
- Link it from a simple `dev/notes/README.md` index so it’s discoverable.

## Shipping Logic

This section defines the rules Magento uses to present shipping options at checkout. All logic is based on **total order weight** (sum of all items in cart). For the underlying carrier rates that inform these charges, see the linked sections below.

---

## Regular Shipping — Customer-Facing Prices

Regular shipping is only available for orders up to 125g (envelope tier). Orders above 125g default to Express — see note below.

| Total order weight | Packaging | Dimensions | Our cost (postage + packaging) | Charge to customer |
|---|---|---|---|---|
| 1g – 25g | Kraft envelope + 1 oversized stamp | 22.9 × 30.5 cm | $2.61 + $1.19 = **$3.80** | **$3.80** |
| 26g – 125g | Kraft envelope + 2 oversized stamps | 22.9 × 30.5 cm | $5.22 + $1.19 = **$6.41** | **$6.41** |
| 126g+ | — | — | Regular Parcel not offered — see Express | — |

> **Why no regular shipping for orders over 125g:** Canada Post Regular Parcel (rate code C06) costs $14.40 for 0.5 kg but does not include guaranteed tracking and pricing varies by destination. The Flat Rate Box used for Express costs $18.99–$32.99 but is a flat price anywhere in Canada with tracking included. Given the small price difference and significantly better customer experience with Express, we only offer Express for all box-tier orders.

---

## Express Shipping — Customer-Facing Prices

Carrier: Canada Post Xpresspost Prepaid Envelope. Delivery: 1–2 business days.

| Total order weight | Packaging | Dimensions | Our cost (envelope, postage included) | Charge to customer |
|---|---|---|---|---|
| 1g – 25g | Xpresspost Small prepaid envelope | 26 × 15.9 × 1.5 cm | $17.35 | **$17.35** |
| 26g – 125g | Xpresspost Medium prepaid envelope | 31.8 × 24.1 × 1.5 cm | $19.60 (or $17.90 in bulk) | **$19.60** |
| 126g – 1000g | Flat Rate Box small | 35.5 × 26.3 × 5.3 cm | $21.99 (flat rate) | **$21.99** |
| 1001g – 2000g | Flat Rate Box medium | 37.9 × 26 × 12 cm | $24.99 (flat rate) | **$24.99** |
| 2001g – 5000g | Flat Rate Box large | 40.6 × 30.4 × 19 cm | $32.99 (flat rate) | **$32.99** |

> **Note (Destination-based pricing):** Xpresspost prepaid envelopes have different price zones. The prices above assume the **Regional** envelope rate (Quebec/Ontario). For customers shipping **outside Quebec/Ontario**, show the **Canada** envelope rate instead (small **$26.80**, medium **$28.10**). This is customer-facing at checkout (not a fulfilment-time decision).

---

> **Important:** Once total weight exceeds 125g, all orders ship in a box — never in an envelope. The Kraft envelope is only used up to 125g.

---

## Rationale

**Why split envelope shipping into two tiers (≤25g vs. 26–125g)?**
A single oversized stamp ($2.61) covers up to 25g. Orders from 26g to 125g require 2 stamps ($5.22). Rather than charging the 2-stamp rate to a customer who only ordered 25g, we pass the actual cost through — $3.80 for the small tier vs. $6.41 for the larger tier.

**Why charge exactly what Canada Post charges?**
Shipping is a cost pass-through, not a profit centre. Customers pay the exact carrier cost. No markup, no handling fee added on top.

**Why switch to Express-only above 125g instead of offering Regular Parcel?**
Canada Post Regular Parcel (rate code C06) costs $14.40 for 0.5 kg but does not include guaranteed tracking and pricing varies by destination. The Flat Rate Box used for Express costs $18.99–$32.99 but is a flat price anywhere in Canada with tracking included. Given the small price difference and significantly better customer experience, we only offer Express for all box-tier orders.

**Why switch to a box above 125g instead of stacking envelopes?**
At 126g+, lettermail postage climbs steeply and envelope thickness limits become a problem. Flat Rate Boxes handle up to 5 kg and avoid the need to calculate and stack stamps for heavier orders.

**Why are there two Express box tiers (501g–1000g and 1001g+)?**
Canada Post sells two relevant box sizes: small ($21.99, max ~1 kg practical) and medium ($24.99). Orders over 2 kg may require the large box ($32.99). Each tier passes through the exact box cost.

---

## Possible Order Combinations and How They Route

| Example cart | Total weight | Regular | Express |
|---|---|---|---|
| 1× 25g | 25g | $3.80 — 1 stamp, Kraft envelope | $17.35 — Xpresspost Small |
| 1× 50g | 50g | $6.41 — 2 stamps, Kraft envelope | $19.60 — Xpresspost Medium |
| 2× 25g | 50g | $6.41 — 2 stamps, Kraft envelope | $19.60 — Xpresspost Medium |
| 1× 100g | 100g | $6.41 — 2 stamps, Kraft envelope | $19.60 — Xpresspost Medium |
| 1× 25g + 1× 100g | 125g | $6.41 — 2 stamps, Kraft envelope | $19.60 — Xpresspost Medium |
| 1× 250g | 250g | Express only | $21.99 — Flat Rate Box small |
| 2× 250g | 500g | Express only | $21.99 — Flat Rate Box small |
| 1× 500g | 500g | Express only | $21.99 — Flat Rate Box small |
| 2× 500g | 1000g | Express only | $21.99 — Flat Rate Box small |

---

## Magento Configuration Notes

- Configure two shipping methods: **"Regular Shipping"** and **"Express Shipping"**
- Use weight-based table rates (Table Rates method in Magento 2)
- **Regular Shipping tiers** (envelope orders only — ≤125g):
  - `0–0.025 kg` → $3.80 (1-stamp Kraft envelope)
  - `0.026–0.125 kg` → $6.41 (2-stamp Kraft envelope)
  - `0.126 kg+` → Regular Shipping not available (hide/disable above 125g)
- **Express Shipping tiers:**
  - `0–0.025 kg` → $17.35 (Xpresspost Small prepaid envelope)
  - `0.026–0.125 kg` → $19.60 (Xpresspost Medium prepaid envelope)
  - `0.126–1.000 kg` → $21.99 (Flat Rate Box small)
  - `1.001–2.000 kg` → $24.99 (Flat Rate Box medium)
  - `2.001–5.000 kg` → $32.99 (Flat Rate Box large)
- Product weights in catalog are already set in kg (e.g., 25g = 0.025, 100g = 0.100)
- Packaging size is selected internally at fulfilment time — not exposed to the customer
- For Express envelope tiers (`≤125g`), configure destination-based rates so Quebec/Ontario customers see the **Regional** prices and other provinces/territories see the **Canada** prices.

---

## Rate Shortcut

`https://www.canadapost.ca/cpotools/apps/far/business/findARate?execution=e1s1`

## Repo Implementation Notes (How We’ll Build This)

### What exists today (core Magento)

- `Magento_OfflineShipping` (built-in) provides `tablerate`, `flatrate`, `freeshipping` carriers.
- Built-in **Table Rates** can vary by destination (Country/Region/Zip) and weight, but it exposes only a single shipping method under one carrier code.

### What this shipping flow requires

- Two customer-facing methods at checkout: **Regular Shipping** and **Express Shipping**.
- Weight-based tiers with an envelope cutoff at **125g / 0.125 kg**.
- Destination-based pricing for Express envelope tiers (QC/ON “Regional” vs rest-of-Canada “Canada” envelope pricing).

### Recommended implementation options

**Option A (No paid extensions): implement a custom carrier with 2 methods (recommended for flexibility)**

- Create a custom module under `app/code/Local/Shipping/` (name TBD) that registers one carrier (e.g., `local_shipping`) and returns two methods:
  - `regular` (available only when total weight `<= 0.125`)
  - `express` (available for all weights)
- Implement rate logic in `collectRates()` using:
  - package weight (`$request->getPackageWeight()`)
  - destination country/region/postcode (`$request->getDestCountryId()`, `$request->getDestRegionId()`, `$request->getDestPostcode()`)
- Use destination rules:
  - If destination is QC/ON, use “Regional” envelope prices for Express envelope tiers.
  - Otherwise, use “Canada” envelope prices (small `26.80`, medium `28.10`) for Express envelope tiers.
- Add admin configuration in `etc/adminhtml/system.xml` for:
  - enabling/disabling the carrier
  - display titles (“Regular Shipping”, “Express Shipping”)
  - (optional) a switch to treat QC/ON as “Regional” vs a configurable list of region IDs
- Deploy steps (production mode): `setup:upgrade`, `setup:di:compile`, `setup:static-content:deploy`, `cache:flush`.

**Option B (Low-code): install an extension that supports multiple table-rate methods**

- Install a “multi-table-rate / matrix-rate” shipping extension that supports:
  - multiple methods under one carrier (Regular + Express)
  - conditions by weight and destination region
- Then configure the tiers in the extension’s UI rather than custom code.

---

## Current Repo Implementation (MatrixRate)

We implement **Option B** using the open-source **WebShopApps MatrixRate** module:

- Module: `app/code/WebShopApps/MatrixRate/` (`WebShopApps_MatrixRate`)
- Rates CSV (source of truth): `dev/notes/shipping-matrixrate-ca-weight-rates.csv`
- Import helper: `dev/tools/import_matrixrate_rates.php`
- Regression tests:
  - Pure weight/address request: `dev/tools/test_matrixrate_shipping.php`
  - Quote/cart with real products: `dev/tools/test_matrixrate_checkout_quotes.php`

### Local setup commands (DDEV)

```bash
docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade
docker exec -u 1000 ddev-magento-web php bin/magento setup:di:compile

docker exec -u 1000 ddev-magento-web php bin/magento config:set carriers/matrixrate/active 1
docker exec -u 1000 ddev-magento-web php bin/magento config:set carriers/matrixrate/title "Postage"
docker exec -u 1000 ddev-magento-web php bin/magento config:set carriers/matrixrate/condition_name package_weight
docker exec -u 1000 ddev-magento-web php bin/magento config:set carriers/matrixrate/sallowspecific 1
docker exec -u 1000 ddev-magento-web php bin/magento config:set carriers/matrixrate/specificcountry CA

docker exec -u 1000 ddev-magento-web php dev/tools/import_matrixrate_rates.php
docker exec -u 1000 ddev-magento-web php bin/magento cache:clean config
```

### Concrete build steps (Option A)

1) Create module skeleton: `registration.php`, `etc/module.xml`.
2) Add carrier class (extends `Magento\Shipping\Model\Carrier\AbstractCarrier`), implement:
   - `collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)`
   - `getAllowedMethods()`
3) In `collectRates()`, compute and append:
   - Regular method rate if `weight <= 0.125`
   - Express method rate based on `(weight, destination zone)`
4) Add `etc/config.xml` defaults and `etc/adminhtml/system.xml` config fields (titles, active flag).
5) Validate in checkout with:
   - a `0.025 kg` cart, `0.125 kg` cart, and `0.126 kg` cart
   - QC/ON shipping address vs non-QC/ON address
