# Local_PartnerProgram

Public page `/partner-program` that explains the Verified Botanicals partner (affiliate) program to smoke shop
and head shop owners. Every price and percentage on the page is read from live data at render time.

## Quickstart

**Change the contact, the pack sizes or the examples** (no code):
Admin > Content > Pages > "Partner Program for Smoke Shops" > Content. The page holds one widget directive:

```
{{widget type="Local\\PartnerProgram\\Block\\Widget\\PartnerPage" program_id="1" size_attribute="kratom_weight" pack_sizes="100g,250g,500g" example_sizes="100g,250g" example_regulars="25,50" contact_name="Ildar" contact_phone="(263) 366-8232"}}
```

Edit the values in quotes and save. Keep the doubled backslashes. Leave `example_sizes` empty to hide the
regulars example.

**Change the discount, commission or hold period**: change them where they live and the page follows:

| What the page shows | Where it comes from |
|---|---|
| Customer discount (20%) | Marketing > Cart Price Rules > "Affiliate coupons - 20% off" (the cart rule linked to program 1), Discount Amount |
| Commission, first order and later orders (20% / 20%) | Affiliate > Programs > "Verified Botanicals Partner Program" > tier for the default affiliate group |
| Hold period (30 days) | Stores > Configuration > Affiliate > Commission > Holding period |
| Prices per pack size | Each strain's 100g / 250g / 500g child product, regular **Price** (not Special Price) |

Saving any of these clears the page from the full page cache automatically.

**Change the wording**: edit `view/frontend/templates/widget/partner-page.phtml`, then

```bash
php -d memory_limit=-1 bin/magento cache:flush
```

(A `.phtml` change needs only a cache flush in production mode.)

## How it works

- `Block/Widget/PartnerPage.php`: the widget. Computes each table row and example from the live data:
  - first order, customer pays = regular price minus the discount (rounded to the cent like Magento does)
  - first order, shop earns = commission (first order rate) of what the customer pays
  - every later order, shop earns = commission (later order rate) of the regular price
  - first year example = regulars x (first order earning + 11 later order earnings)
  - if prices for a size differ between strains, the row shows "from" the lowest one
- `Model/PartnerProgramData.php`: reads the program, its cart rule, the default group tier, the hold period and
  the regular prices (enabled children of enabled, visible configurable products in the current store).
  If the program or rule is inactive, or the tier is not "percent of cart", the page drops all numbers, shows
  a short "contact us" version and logs a warning to `var/log/system.log` ("Partner program page: ...").
- Cache: the block tags the cached page with `vb_partner_program` and every product it read
  (`cat_p_<id>`), so a product save clears it. `Observer/CleanPartnerPageCache` (cart rule save, affiliate
  config save) and `Plugin/CleanPageCacheOnProgramSave` (program save) clear the custom tag.
- `Setup/Patch/Data/CreatePartnerProgramPage.php` creates the CMS page (skips if the URL key exists).
  `AddPartnerProgramFooterLink.php` adds "Partner Program" after "About Us" in the `footer-links-bottom` block.
- The page is built in the module, not the theme, so a theme change keeps it working. Styles are inline in
  the template and scoped under `.vbpp`, so no static content deploy is needed for them.

## French version (not built yet)

The site has one store view (English, store 111). Plan: a second CMS page `programme-partenaires` with the same
widget plus a `template` argument pointing at a French copy of the template, and links between the two pages.
