# Partner program landing page: progress

Public page that explains the partner (affiliate) program to Montreal smoke shop and head shop owners. It is the
web version of the printed Psychonaut partner mailer, made generic (no shop name, code or link).
Module: `app/code/Local/PartnerProgram` (README has the Quickstart). Live: https://verifiedbotanicals.com/partner-program

## Where we left off (2026-11-12)

English page is live and verified. Open: French page; the guest loophole in the one-use-per-customer limit
(see Open points); OpenSearch is down on prod.

## Checklist

- [x] Read the program setup on prod: Mirasvit Affiliate program 1, cart rule 1, commission config
- [x] Pack sizes: configurable products with `kratom_weight` (25g, 50g, 100g, 250g, 500g); all 9 strains share one price per size
- [x] Ildar: table uses the **regular** price, not the rotating special price (2026-11-12)
- [x] Cart rule 1 "Uses per Customer" changed 0 -> 1, on Ildar's decision (2026-11-12)
- [x] Module `Local_PartnerProgram`: widget renders the whole page; prices, discount, commission and hold period are live
- [x] Page cache clears on product save, cart rule save, program save and affiliate config save (rule and program tested)
- [x] CMS page `/partner-program` (page 49, all store views) and footer link "Partner Program" (`footer-links-bottom`)
- [x] Contact: call or text Ildar, (263) 366-8232 (Ildar, 2026-11-12)
- [x] Verified in Playwright: desktop 1366 px and phone 390 px, no console errors, numbers match the catalog
- [ ] Ildar reviews the page copy
- [ ] French page `/programme-partenaires` (English first, French next: Ildar, 2026-11-12)
- [ ] Decide on the guest loophole below
- [ ] Copy this tracker to `Sunny Kratom/site/affiliate_setup/partner-landing-page-progress.md` on the PC and link it from `kb/sunny-kratom.md` (the session ran on the server and could not reach those files)

## Numbers on 2026-11-12 (for checking only, the page computes them)

| Pack | Regular | First order, customer pays | First order, shop earns | Later orders, shop earns |
|---|---|---|---|---|
| 100 g | $27.75 | $22.20 | $4.44 | $5.55 |
| 250 g | $55.50 | $44.40 | $8.88 | $11.10 |
| 500 g | $101.75 | $81.40 | $16.28 | $20.35 |

The printed mailer's 250 g "first year" examples ($3,276.00 / $6,552.00) are slightly off; the correct values are
$3,274.50 / $6,549.00 (25 x ($8.88 + 11 x $11.10)).

## Open points

- **Guests can reuse the code.** Magento only enforces "Uses per Customer" for logged-in customers
  (`Magento\SalesRule\Model\Utility::canProcessRule` checks the quote's customer ID). Most orders here are guest
  checkouts (Local_GuestToCustomer turns them into accounts after the order), so a returning customer who checks
  out as a guest can use the code again. Fix option: a small plugin that rejects the affiliate rule for a guest
  whose email already has an order with that rule.
- **The QR link alone does not apply the discount.** It links the customer to the shop (cookie, 365 days), but
  the 20% off needs the code entered at checkout. The page says so in step 2.
- **OpenSearch is not running on prod** (nothing on localhost:9200). `setup:upgrade` refuses to run, so this
  module's two data patches were applied directly with `PatchApplier::applyDataPatch('Local_PartnerProgram')`.
  Catalog search is probably affected too.
