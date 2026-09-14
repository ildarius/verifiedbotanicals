# Rotating deals scheduler recovery — 2026-09-10

## Confirmed outcome

The homepage `Grab The Best Offer of This Week!` widget was empty because its
previously active deal cycle had expired and Magento cron had not run since
2026-06-02. The issue is resolved and verified on the local DDEV server.

At 16:00 local database time, the normal scheduled
`local_rotating_special_deals_rotate` job completed successfully. It rotated
cycle 4 and created active cycle 5, selecting `GM` (Green Malay) and `RMD`
(Red Maeng Da). The active window is 2026-09-10 20:00:57 through
2026-09-24 20:00:57 UTC. The homepage widget's `date_to` now matches the end
of that window.

## Diagnosis

- The actual Magento root is `/home/ildar/projects/magento`; the active
  homepage identifier is `home`.
- The last cycle (ID 4) was still marked `active` but had ended on
  2026-06-16. Its parent products had expired special-price dates, so the
  MySQL/EAV-backed `Sm_FilterProducts` countdown collection correctly found
  no eligible products.
- `cron_schedule` contained one stale pending rotation job from 2026-06-02.
  There was no running cron process in `ddev-magento-web`, and no user
  crontab. The most recent prior `var/log/cron.log` entries were also from
  2026-06-02.
- The three `catalog_product_entity` mview triggers were already defined by
  the active environment's database account (`db@%`), matching Magento's
  configured database user. Logs contained no `TRIGGER command denied`,
  `CouldNotSaveException`, or rotating-deals failure. Therefore the stale
  catalog-search trigger-definer repair used on another server was neither
  applicable nor run here.

Useful read-only checks:

```sql
SELECT c.cycle_id, c.status, c.started_at, c.ends_at, i.sku, i.group_key
FROM local_rotating_special_deal_cycle AS c
LEFT JOIN local_rotating_special_deal_item AS i ON i.cycle_id = c.cycle_id
ORDER BY c.cycle_id DESC, i.item_id;

SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE,
       ACTION_TIMING, DEFINER, CREATED
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND EVENT_OBJECT_TABLE = 'catalog_product_entity'
ORDER BY TRIGGER_NAME;
```

## Repair

Magento's generated user crontab alone could not work in this DDEV web
container: attempting to start its system cron daemon failed with
`seteuid: Operation not permitted`. The durable project-level fix is the
`web_extra_daemons.magento-cron` entry in `.ddev/config.yaml`. It runs:

```sh
php -d memory_limit=-1 bin/magento cron:run
```

once per minute through DDEV Supervisor. After changing the DDEV config, the
project was restarted as its owning host user. Supervisor then reported
`webextradaemons:magento-cron RUNNING` and Magento generated the 16:00
rotation schedule normally. No force-rotation command, direct SQL data edit,
or manual modification of cycles, prices, special-price dates, CMS pages, or
CMS blocks was used.

## Validation evidence

- `cron_schedule` job 581: `success`; scheduled at 16:00:00, executed at
  16:00:55, finished at 16:01:05 (local database time).
- `var/log/cron.log` records `local_rotating_special_deals_rotate` starting
  and completing successfully in about 10 seconds, without product-save or
  trigger errors.
- Cycle 5 is `active`, has exactly two items (`GM`, `RMD`), and cycle 4 is
  `rotated`.
- Both selected configurable parents and all child variants have the new
  special-price dates; their stock and MSI salability are `1`; and their
  price-index rows contain discounted `final_price` values.
- `curl` of the homepage rendered the heading, a countdown to
  `2026/09/24 20:00:57`, and exactly the two selected products. The empty
  selection text was absent.
- `npm run pw:homepage-check` completed and refreshed
  `.playwright/artifacts/storefront-homepage.png`; visual inspection confirmed
  the same two rendered deal cards and countdown.
- All Magento indexers are `Ready`. `catalogsearch_fulltext` is valid,
  scheduled, mview-enabled, idle, and has no backlog.

## OpenSearch and catalog-search distinction

The homepage countdown widget uses a Magento MySQL/EAV product collection;
it does not query OpenSearch directly. Catalog-search mview triggers can still
block product saves indirectly when their definers are stale, but that was not
the cause of this incident.

OpenSearch was reachable at the configured service endpoint. Cluster health
was yellow on its one-node setup because replica shards were unassigned; all
95 primary shards were active and there were no pending tasks. No
catalog-search or OpenSearch repair is currently required.
