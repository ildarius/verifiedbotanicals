# GA4 GTM Tracking Plan

## Goal

Implement a durable storefront analytics layer for Magento that:

- uses Google Tag Manager as the single browser-side tag entry point
- sends GA4 ecommerce events with Magento-accurate payloads
- respects Magento cookie consent behavior
- avoids duplicate purchase firing
- is extensible later for Google Ads, Meta Pixel, and other marketing tags

This plan is for the future implementation path. As of 2026-06-07, the current live configuration uses Magento's built-in `Magento_GoogleGtag` module with GA4 measurement ID `G-CFX106ZF1K`.

## Current State

### 1. Magento native GA4 support exists, but it is narrow

The codebase already includes and enables:

- `Magento_GoogleAnalytics`
- `Magento_GoogleGtag`

Relevant file:

- [app/etc/config.php](/home/ildar/projects/magento/app/etc/config.php:97)

The native `Magento_GoogleGtag` integration:

- loads `gtag.js`
- initializes the configured GA4 measurement ID
- sends a `purchase` event on the order success page

Relevant files:

- [vendor/magento/module-google-gtag/view/frontend/web/js/google-analytics.js](/home/ildar/projects/magento/vendor/magento/module-google-gtag/view/frontend/web/js/google-analytics.js:36)
- [vendor/magento/module-google-gtag/Block/Ga.php](/home/ildar/projects/magento/vendor/magento/module-google-gtag/Block/Ga.php:133)

Implication:

- native Magento config is acceptable as a temporary baseline
- it does not cover most important ecommerce interactions such as `add_to_cart`, `begin_checkout`, `view_item`, or checkout step events

### 2. No custom storefront event layer exists yet

No local module currently exposes a normalized `window.dataLayer` event stream for storefront actions.

Implication:

- future tracking work should start with a dedicated local module rather than scattered theme snippets or CMS script inserts

## Recommended Future Architecture

Build a custom module, likely `Local/Ga4Tracking`, that owns:

- GTM container loading
- dataLayer event payload generation
- consent-aware firing rules
- Magento-specific ecommerce data normalization
- protection against duplicate `purchase` events

Use Google Tag Manager as the single tag runtime in the browser, and configure the GA4 event tags inside GTM.

## Why GTM Instead Of Direct Gtag Long Term

- one place to manage GA4 and future marketing tags
- no code deploy required for routine tag configuration changes
- easier QA in GTM Preview mode
- cleaner separation between Magento event generation and analytics vendor configuration
- safer future expansion to Ads remarketing, Meta Pixel, Hotjar, or consent-mode updates

## Implementation Principles

### 1. Keep Magento responsible for event data quality

Magento should prepare accurate commerce payloads:

- SKU
- product name
- currency
- price
- quantity
- coupon
- shipping
- tax
- transaction ID

GTM should be responsible for dispatching those payloads to GA4.

### 2. Prefer one normalized event contract

All frontend tracking should push into `window.dataLayer` using a consistent structure, for example:

```js
window.dataLayer.push({
  event: 'add_to_cart',
  ecommerce: {
    currency: 'USD',
    value: 21.99,
    items: [
      {
        item_id: 'GMD50',
        item_name: 'Green Maeng Da 50g',
        item_brand: 'Kratom',
        item_category: 'Green Vein Kratom',
        price: 21.99,
        quantity: 1
      }
    ]
  }
});
```

### 3. Do not double-fire purchase events

When GTM-based purchase tracking is implemented, disable or replace the native Magento GA4 purchase output so the same order does not generate duplicate GA4 conversions.

## Phase 1 Event Scope

Implement these first:

- `view_item`
- `add_to_cart`
- `remove_from_cart`
- `view_cart`
- `begin_checkout`
- `add_shipping_info`
- `add_payment_info`
- `purchase`

These cover the core ecommerce funnel and are the highest-value baseline for revenue attribution and checkout drop-off analysis.

## Phase 2 Event Scope

Add these after Phase 1 is stable:

- `view_item_list`
- `select_item`
- `search`
- `login`
- `sign_up`
- `generate_lead`
- `view_promotion`
- `select_promotion`

Project-specific likely uses:

- homepage rotating-deals banners
- newsletter signup
- blog or CMS CTA clicks if marketing asks for them later

## Magento Integration Strategy

### 1. New module

Create `app/code/Local/Ga4Tracking/` with at least:

- `registration.php`
- `etc/module.xml`
- `etc/frontend/di.xml` if needed
- `view/frontend/layout/*.xml`
- `view/frontend/templates/*.phtml`
- `view/frontend/web/js/*.js`
- RequireJS mixins where Magento JS actions need interception

### 2. Server-rendered events

Generate event payloads server-side where Magento already has clean data:

- `view_item`
- `view_cart`
- `purchase`

Why:

- product and order data are authoritative on the server
- less fragile than scraping DOM state from a heavily customized theme

### 3. JS/AJAX-driven events

Use frontend JS hooks or RequireJS mixins for actions driven asynchronously:

- add to cart
- remove from mini-cart or cart
- checkout progression

Likely integration points include Magento checkout JS components and cart submission flows used by the SM theme.

### 4. Consent gating

Respect Magento cookie restriction mode and only initialize GTM or push non-essential analytics events when consent allows it.

The future module should mirror the native cookie-awareness pattern already used by `Magento_GoogleGtag`.

## Data Mapping Recommendations

### Item identity

Recommended default:

- `item_id` = simple product SKU actually sold

Optional extra fields:

- parent configurable SKU as a custom parameter
- Magento product ID as a custom parameter if needed for debugging

Reason:

- revenue is ultimately tied to purchasable simple SKUs
- configurable parent context can still be preserved without sacrificing SKU accuracy

### Categories

Send the best available storefront category context, for example:

- `item_category` = top-level merchandising category
- `item_category2` = child category when useful

For the kratom catalog, the strain group categories are likely the most useful reporting dimension:

- `Green Vein Kratom`
- `Red Vein Kratom`
- `White Vein Kratom`

### Monetary fields

Always populate when available:

- `currency`
- `value`
- `price`
- `quantity`
- `tax`
- `shipping`
- `coupon`

## Checkout Event Notes

For Magento checkout, treat these as distinct milestones:

- `begin_checkout`: cart page to checkout entry
- `add_shipping_info`: shipping step completed
- `add_payment_info`: payment method selected or payment step completed
- `purchase`: success page only, once per order

The exact checkout JS hooks should be verified against the live one-page checkout implementation before coding.

## Validation Plan

### During implementation

- verify `window.dataLayer` pushes in browser devtools
- use GTM Preview mode before publishing tags
- confirm one and only one `purchase` event per order
- confirm payload item counts, prices, currency, and transaction IDs match Magento order data

### Storefront checks

- product page view fires `view_item`
- add-to-cart button fires `add_to_cart`
- cart page loads `view_cart`
- checkout start fires `begin_checkout`
- order success page fires `purchase`

### Regression checks

- ensure no duplicate events from native `Magento_GoogleGtag` plus GTM
- ensure consent-disabled visitors do not receive analytics tags when restriction mode applies
- ensure configurable-product orders report the expected child SKU and values

## Cutover Plan

When the GTM-based module is ready:

1. keep native GA4 enabled only during parallel QA if necessary
2. verify whether both native and GTM paths are firing `purchase`
3. disable the native GA4 output before production rollout if duplication is observed
4. publish the GTM container after storefront QA passes

## Future Nice-To-Haves

- add custom dimensions for Magento customer group, store view, or product type
- track promotion impressions and clicks for rotating homepage deals
- track newsletter signup as `generate_lead`
- add a small debug mode that logs dataLayer payloads for local QA only
