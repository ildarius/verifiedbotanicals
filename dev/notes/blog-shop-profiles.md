# Blog "Shop profiles": live posts that no listing shows (2026-11-24)

- Posts in the Magefan blog category **Shop profiles** (ID 10, `shop-profiles`, inactive, not in menu or sidebar tree) stay
  live and indexable at their URLs but are left out of every storefront listing, next/previous and related links, search and RSS.
- Category ID: `local_blogapi/shop_profiles/category_id`, default in `app/code/Local/BlogApi/etc/config.xml`.
- Mechanism: `Local\BlogApi\Plugin\ExcludeShopProfilesFromListings` on `Magefan\Blog\Model\ResourceModel\Post\Collection::addActiveFilter()`,
  registered for the frontend area only (admin grid, cron/sitemap and REST unaffected).
- New profile: send `category_ids` = `10` to the upsert endpoint on **every** call that sends `category_ids`; the list replaces the
  post's categories, and an empty value removes them all (which makes the post visible in listings again). Send no `tag_titles`.
- Not covered: the anonymous REST route `GET /rest/V1/blog/post/list/...` still lists these posts, and the sitemap lists them by design.
- Sitemap: Magento Site Map row 1 (store 111), `/sitemap.xml`, daily 03:30 store time by cron (`sitemap_generate`), file `pub/sitemap.xml` (gitignored).
  robots.txt `Sitemap:` line comes from `design/search_engine_robots/custom_instructions`.
- Check listings read-only: `php -d memory_limit=-1 dev/tools/blog_listing_probe.php frontend 10` (and `crontab 10`).
- Backup of the state before the change: `~/backup-blog-shopprofiles-20261124` (outside the repo).
