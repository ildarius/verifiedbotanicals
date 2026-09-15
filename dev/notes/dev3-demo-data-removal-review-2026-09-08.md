# Dev3 demo-data removal review — 2026-09-08

## Cleanup applied — 2026-09-15

The reviewed cleanup inventory was applied with
`dev/tools/dev3_demo_data_cleanup.php --apply` after a successful guarded dry
run. Post-cleanup SQL verification found zero remaining target products,
reviews, orders, customers, CMS pages, attributes, store views, store groups,
and product URL rewrites.

Protected records were rechecked after the transaction: live store `111`,
product `470`, Kratom reviews `239`–`262`, CMS page `8`, category root `2`, and
all CMS blocks remain. The surviving website/group/store chain is valid:
website `1` uses group `41`, which uses store `111` and category root `2`.

Before cleanup, database and `pub/media` backups were written under `backups/`.
The database dump excludes only the derived `inventory_stock_1` view because
that view could not be read by `mysqldump`; its backing catalog/inventory tables
are present in the dump.

The cleanup initially exposed 21 stale trigger definers left by an obsolete
database account. Their exact definitions were backed up, then the triggers
were recreated with the active database account as definer. No trigger logic
was changed. The catalog and search indexers were rebuilt successfully; the
CLI required `-d memory_limit=-1` for the search reindex.

Original review scope: read-only reconciliation of `demo-data-audit.md` against the database serving `dev3.verifiedbotanicals.com`, except for one weekly-deals CMS-page test which was reverted in the same session. No cleanup deletion was performed during that review. **CMS blocks are explicitly out of scope.**

## Decision summary

The earlier audit is directionally useful, but is not safe to apply verbatim to production. Its counts have drifted and three important assumptions are now false:

1. The `Default` attribute set contains one current, explicitly protected product: ID `470` / SKU `vb-blog-media` / `VB Blog Media (do not delete) vb-blog-media`. Do not delete by attribute-set alone.
2. Only 9 of 33 reviews belong to demo products. Reviews `239`–`262` belong to live Kratom parents and must remain.
3. Every store group currently points at root category `2` (`Default Category`), including the live group. There are no separate demo roots to delete. Do not run the proposed category/root-category deletion phase.

## Confirmed removal candidates

These are the records which the current database evidence supports removing, subject to a full backup and a dry-run dependency count on the target database.

| Area | Exact primary targets | Count |
|---|---|---:|
| Demo products | IDs `42, 43, 46, 50–60, 140–187, 258, 280–283` | 67 |
| Demo product reviews | Review IDs `41, 43, 47, 50–54, 192` | 9 |
| Demo orders | `sales_order.entity_id` `1–4, 11–25` | 19 |
| Demo customers | `customer_entity.entity_id` `1–19` | 19 |
| Demo CMS pages | Page IDs `5` (`under-price`), `6` (`new-arrivals`), `7` (`daily-deals`) | 3 |
| Demo product attributes | `eav_attribute.attribute_id` `140` (`model`), `141` (`year`), `142` (`auto_manufacturer`) | 3 |
| Demo attribute set | `Auto` (resolve its ID by name in the target database) | 1 |
| Demo store groups | Group IDs `1, 5–40, 42–44` | 40 |
| Demo store views | Store IDs `1, 39–110, 112–118` | 80 |

The product list is deliberately explicit rather than `attribute_set IN ('Default','Auto')`; that predicate would incorrectly delete protected product ID `470`.

### Exact product boundary

All 67 listed products have the `Default` attribute set and are Magentech demo merchandise or Magento sample product types. Product ID `470` also has `Default`, but is **not** a candidate:

| Keep | Reason |
|---|---|
| `470` / `vb-blog-media` / `VB Blog Media (do not delete) vb-blog-media` | Explicit protected live-support product. |

## Do not remove

| Area | Verified keep decision |
|---|---|
| CMS blocks | Keep every block. No `cms_block` or `cms_block_store` row belongs in the patch. |
| Live store | Group `41` / `fresh1`, store `111` / `fresh1_en`; this is the live storefront. |
| Live catalog | Kratom products and categories `377`, `378`, `379`. |
| Reviews | IDs `239`–`262` are live Kratom reviews; retain all 24. |
| Product `470` | See protected-product note above. |
| Theme attributes | Retain all `sm_*` attributes. |
| Category root | Retain root category `2`; it is shared by every current store group. |
| CMS page 8 | `about-us` is linked by the live footer; retain until its content/link is deliberately replaced. |

## Requires business confirmation — exclude from the first patch

The following four customer records are not proven vendor demo data by the current database. They were not included in the removal list:

| ID | Email | Created |
|---:|---|---|
| 20 | `hoa21@gmail.com` | 2025-11-13 |
| 21 | `test12345@gmail.com` | 2025-11-13 |
| 22 | `hoa34@gmail.com` | 2025-11-14 |
| 23 | `ildarius@gmail.com` | 2026-08-13 |

Also leave search terms, newsletter subscribers, widget instances, URL rewrites, and category rows out of patch v1. They need a separate reference analysis after store deletion; none is a safe blanket delete target.

## Patch shape for dev3, then production

Use a versioned Magento maintenance script/data patch which accepts only these explicit IDs, starts with a backup/dry-run report, deletes dependent sales and review rows before primary rows, and finishes with reindex/cache commands. Do not use a broad `DELETE` by attribute set, date, store name, or email domain.

Order:

1. Back up DB and media; run the script in dry-run mode and compare every count above.
2. Delete the 19 demo orders and all dependent invoice/shipment/credit-memo, payment, item, grid, and sales-link rows identified by the dry run.
3. Delete reviews `41,43,47,50–54,192` and their rating/detail/summary rows.
4. Delete customers `1–19` and their address/entity attribute rows.
5. Delete only the 67 enumerated products through Magento services (or a dependency-aware maintenance script), preserving `470`.
6. Remove store views, then groups, using the exact IDs above. Reconcile scoped configuration, URL rewrites, CMS page/store assignments, and widget/store assignments; do not delete CMS blocks.
7. Remove `Auto`, then the three enumerated attributes only after verifying no remaining product values/references.
8. Delete CMS pages `5–7`; keep page `8`.
9. Reindex, flush cache, and run storefront/admin smoke tests.

Before production, run the same inventory queries against production and require exact or consciously-approved differences. The production patch must refuse to run if protected product `470`, live store `111`, or Kratom review IDs are selected.

## Weekly-deals diagnostic (not cleanup scope)

Dev3 currently renders the weekly-deals widget as “We can't find products matching the selection.” Production renders it correctly. The dev3 database proves this is not missing deal data: active cycle `10` contains configurable parents `GH` (ID `451`) and `WH` (ID `469`); both are enabled, visible, salable, website-assigned, price-indexed, category-linked, and have specials active through `2026-09-10 19:00:10`.

Removing the widget category filter from the homepage CMS page did not change the empty result, and that test was immediately reverted. The remaining failure is in the remote application’s `Sm_FilterProducts` collection/runtime path. It requires remote application-shell access to inspect/deploy code and run Magento cache/index commands; it cannot be safely fixed by database cleanup SQL.

## Evidence timestamp

Database queried at 2026-09-08 08:56 server time. This report is for review only; it authorizes no deletion.

## Readable removal inventory

This section replaces the ID-only shorthand above. The original `demo-data-audit.md` has been consolidated into this report; where its claims conflict with live data, the decisions in this report win.

### Products — remove (67)

- `42` — `MK-587031` — Top Handle Handbags Shoulder
- `43` — `MK-967016` — Stainless Steel Diving Watch
- `46` — `MK-010201` — Refresh Womens Wynne06 Ribbed
- `50`–`60` — `MK-S-14523`, `MK-CF-4525-Blue-15cm`, `MK-CF-4525-Blue-20cm`, `MK-CF-4525-Blue-25cm`, `MK-CF-4525-Red-15cm`, `MK-CF-4525-Red-20cm`, `MK-CF-4525-Red-25cm`, `MK-CF-4525`, `MK-G-42963`, `MK-B-58246`, `MK-DL-01036` — Magento sample simple/configurable/grouped/bundle/downloadable products
- `140`–`144` — `B-4582641`, `B-752641`, `B-8501396`, `B-485021`, `MK-854126` — demo computer cases
- `145`–`154` — `B-7596012`, `B-8574960`, `B-1452692`, `B-5462258`, `B-5862012`, `B-1523689`, `B-8590125`, `B-5860124`, `B-5890124`, `B-8574901` — demo CPUs and storage
- `155`–`164` — `B-45821`, `B-8597013`, `B-521740`, `B-854701`, `B-9801235`, `B-582136`, `B-5214012`, `B-52149023`, `B-4589314`, `B-5801297` — demo keyboards and motherboards
- `165`–`174` — `B-1529854`, `B-1501367`, `B-4580123`, `B-012030`, `B-0125987`, `B-010203`, `B-012597`, `B-0147896`, `B-0236598`, `B-5824607` — demo mice and power supplies
- `175`–`187` — `B-0196750`, `B-01236985`, `B-8901347`, `B-780136`, `B-0159703`, `B-1023954`, `B-0120169`, `B-145823`, `B-5894102`, `B-0297530`, `B-4560123`, `B-4157412`, `B-963698` — demo RAM, GPUs, and Windows software
- `258`, `280`–`283` — `MK-F-005`, `MK-F-005-Blue`, `MK-F-005-Green`, `MK-F-005-Purple`, `MK-F-005-Gray` — Adidas Alliance II Sackpack and its demo variants

### Orders — remove (19)

| IDs | Store / customer | Meaning |
|---|---|---|
| `1–4`, `11–13`, `15–16`, `24–25` | `Main Website Store` / vendor-demo or guest addresses | 2021–2023 demo orders |
| `14` | `Shop1` / `mohammadaliawada961@gmail.com` | canceled demo order |
| `17` | `Technology1` / `teste.munha@gmail.com` | canceled demo order |
| `18–23` | `Shop11` / `cuongtd@ytcvn.com` or `admin98@gmail.com` | 2023 demo orders |

### Customers — remove (19)

`cuongtd@ytcvn.com`, `admin678@gmail.com`, `admin876@gmail.com`, `admin45@gmail.com`, `haitd@ytcvn.com`, `hoant@ytcvn.com`, `parisem@mailinator.com`, `churchwongrass@gmail.com`, `kitiro3453@cosaxu.com`, `admin98@gmail.com`, `ngan@gmail.com`, `test123@gmail.com`, `admin982@gmail.com`, `admin4@gmail.com`, `test@gmail.com`, `Test90@gmail.com`, `admin5@gmail.com`, `testngan@gmail.com`, `hoa9@gmail.com`.

### Store structure — remove (40 groups / 80 views)

Remove the unused `Main Website Store` / `default` group and all views, plus these demo store groups and both of each group’s English/Arabic views: `Shop1`–`Shop11`, `Technology1`–`Technology3`, `Fashion1`–`Fashion11`, `Sport1`–`Sport4`, `Furniture1`, `Furniture 2`, `Jewelry`, `Cosmetics`, `Kids Store`, `AutoParts1`, `AutoParts2`, `Fresh`, `Medicine`, and `Petshop`. Also remove `Fresh 1 / Arabic` (`fresh1_ar`) only; retain `Fresh 1 / English` (`fresh1_en`) as the live store.

### Retained findings from the original audit

The original audit correctly identified the theme demo package, the live `Fresh 1 / English` storefront, Kratom catalog, three demo CMS pages, three Auto attributes, unnecessary store views, and the need for dependency-aware deletion/reindexing. Its CMS-block recommendation is retained unchanged: leave all CMS blocks and their assignments untouched.
