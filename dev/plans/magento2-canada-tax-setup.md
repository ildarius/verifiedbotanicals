# Magento 2 — Canadian Tax Configuration

**Project:** Verified Botanicals (verifiedbotanicals.com)
**Platform:** Magento 2
**Market:** Canada (all provinces)
**Last updated:** 2026-05-25

---

## Overview

Magento 2 has a fully capable built-in tax engine. No third-party module is needed for Canada. This document covers everything required to configure GST, HST, QST, and PST correctly for a Canadian storefront shipping to Canadian customers.

---

## Background: Canadian Tax Structure

| Province / Territory | Tax Type | Rate | Notes |
|---|---|---|---|
| Alberta (AB) | GST only | 5% | No provincial tax |
| British Columbia (BC) | GST + PST | 5% + 7% = 12% | PST applied separately |
| Manitoba (MB) | GST + RST | 5% + 7% = 12% | RST applied separately |
| New Brunswick (NB) | HST | 15% | Combined federal+provincial |
| Newfoundland & Labrador (NL) | HST | 15% | Combined |
| Northwest Territories (NT) | GST only | 5% | No provincial tax |
| Nova Scotia (NS) | HST | 15% | Combined |
| Nunavut (NU) | GST only | 5% | No provincial tax |
| Ontario (ON) | HST | 13% | Combined |
| Prince Edward Island (PE) | HST | 15% | Combined |
| Quebec (QC) | GST + QST | 5% + 9.975% = 14.975% | Applied separately |
| Saskatchewan (SK) | GST + PST | 5% + 6% = 11% | PST applied separately |
| Yukon (YT) | GST only | 5% | No provincial tax |

> **Note:** Kratom products sold for research/botanical purposes are standard-rated. No special exemption categories apply.

---

## Step 1: Create Tax Zones (Tax Rate destinations)

Go to: **Stores → Taxes → Tax Zones and Rates → Add New Tax Rate**

Create one entry per province/territory. Use the following:

| Rate Name | Country | State/Province | Zip/Post Code | Rate |
|---|---|---|---|---|
| CA-AB-GST | Canada | Alberta | * | 5.0000 |
| CA-BC-GST | Canada | British Columbia | * | 5.0000 |
| CA-BC-PST | Canada | British Columbia | * | 7.0000 |
| CA-MB-GST | Canada | Manitoba | * | 5.0000 |
| CA-MB-RST | Canada | Manitoba | * | 7.0000 |
| CA-NB-HST | Canada | New Brunswick | * | 15.0000 |
| CA-NL-HST | Canada | Newfoundland and Labrador | * | 15.0000 |
| CA-NT-GST | Canada | Northwest Territories | * | 5.0000 |
| CA-NS-HST | Canada | Nova Scotia | * | 15.0000 |
| CA-NU-GST | Canada | Nunavut | * | 5.0000 |
| CA-ON-HST | Canada | Ontario | * | 13.0000 |
| CA-PE-HST | Canada | Prince Edward Island | * | 15.0000 |
| CA-QC-GST | Canada | Quebec | * | 5.0000 |
| CA-QC-QST | Canada | Quebec | * | 9.9750 |
| CA-SK-GST | Canada | Saskatchewan | * | 5.0000 |
| CA-SK-PST | Canada | Saskatchewan | * | 6.0000 |
| CA-YT-GST | Canada | Yukon | * | 5.0000 |

Set **Zip/Post Code** to `*` (wildcard) to match all postal codes in that province.

---

## Step 2: Create Tax Rules

Go to: **Stores → Taxes → Tax Rules → Add New Tax Rule**

Tax rules link together: a **Customer Tax Class** + a **Product Tax Class** + one or more **Tax Rates**.

### Customer Tax Class

Magento ships with "Retail Customer" by default. Keep it as-is unless you need a B2B exempt tier. For now, use **Retail Customer** for all rules.

### Product Tax Class

Create a product tax class called **Taxable Goods** (or use the default if it already exists):
- Go to **Stores → Taxes → Product Tax Classes → Add New**
- Name: `Taxable Goods`

### Rules to create

Create one rule per province/territory. Name them clearly:

| Rule Name | Customer Tax Class | Product Tax Class | Tax Rates to include |
|---|---|---|---|
| CA - Alberta | Retail Customer | Taxable Goods | CA-AB-GST |
| CA - British Columbia | Retail Customer | Taxable Goods | CA-BC-GST, CA-BC-PST |
| CA - Manitoba | Retail Customer | Taxable Goods | CA-MB-GST, CA-MB-RST |
| CA - New Brunswick | Retail Customer | Taxable Goods | CA-NB-HST |
| CA - Newfoundland | Retail Customer | Taxable Goods | CA-NL-HST |
| CA - Northwest Territories | Retail Customer | Taxable Goods | CA-NT-GST |
| CA - Nova Scotia | Retail Customer | Taxable Goods | CA-NS-HST |
| CA - Nunavut | Retail Customer | Taxable Goods | CA-NU-GST |
| CA - Ontario | Retail Customer | Taxable Goods | CA-ON-HST |
| CA - PEI | Retail Customer | Taxable Goods | CA-PE-HST |
| CA - Quebec | Retail Customer | Taxable Goods | CA-QC-GST, CA-QC-QST |
| CA - Saskatchewan | Retail Customer | Taxable Goods | CA-SK-GST, CA-SK-PST |
| CA - Yukon | Retail Customer | Taxable Goods | CA-YT-GST |

For provinces with two separate rates (BC, MB, SK, QC), both tax rates should be selected within the **same rule** so they stack correctly on a single line item.

---

## Step 3: Assign Product Tax Class to Products

Go to each product → **Taxes** field → set to **Taxable Goods**.

To do this in bulk:
1. Go to **Catalog → Products**
2. Select all products
3. **Actions → Update Attributes**
4. Set **Tax Class** → `Taxable Goods`
5. Save

---

## Step 4: Configure Tax Display Settings

Go to: **Stores → Configuration → Sales → Tax**

Recommended settings for this storefront:

| Setting | Value | Reason |
|---|---|---|
| Tax Calculation Method Based On | Unit Price | Standard for product-level tax |
| Tax Calculation Based On | Shipping Address | Canadian standard — tax applies at destination |
| Default Tax Destination Calculation → Country | Canada | Fallback for guests with no address yet |
| Default Tax Destination Calculation → State | Quebec | Our primary market |
| Display Product Prices in Catalog | Excluding Tax | Shows clean price; tax added at checkout |
| Display Shipping Prices | Excluding Tax | Same |
| Display Subtotal | Excluding Tax | Cleaner cart summary |
| Display Order Totals | Including Tax | Full amount shown at confirmation |
| Display Full Tax Summary | Yes | Breaks down GST/QST separately — important for Quebec compliance |
| Enable Cross Border Trade | No | Not needed; we're Canada-only |

---

## Step 5: Shipping Tax

If shipping charges are taxable (they are in most Canadian provinces), configure this:

Go to: **Stores → Configuration → Sales → Tax → Calculation Settings**

- **Tax Class for Shipping** → set to `Taxable Goods` (or create a dedicated `Shipping` tax class if preferred)

This ensures the same provincial tax rules apply to shipping charges automatically.

---

## Step 6: Verify with a Test Order

After setup, place a test order as a guest or logged-in customer:

1. Add a product to the cart
2. Enter a shipping address for Quebec → confirm you see 5% GST + 9.975% QST
3. Enter a BC address → confirm 5% GST + 7% PST
4. Enter an Ontario address → confirm 13% HST (single line)
5. Enter an Alberta address → confirm 5% GST only

Check that:
- Tax amounts update dynamically as the address is changed in checkout
- Order confirmation email shows the tax breakdown
- The admin order view shows tax lines correctly

---

## Notes for the Developer

- All configuration is in the Magento admin — no code changes required.
- Tax rates are static (manually maintained). If Canadian tax rates change, update them in **Stores → Tax Zones and Rates**.
- Quebec requires both GST and QST to be shown as separate line items on receipts. The "Display Full Tax Summary" setting above handles this.
- No third-party tax module is needed for this market. Avalara/Vertex would only become relevant if expanding to the US.
- This setup is compatible with Magento 2.4.x. Verified on standard Magento 2 CE and EE.
