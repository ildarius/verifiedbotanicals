# WHM/cPanel Remote Migration Runbook

## Purpose

This document is the step-by-step migration runbook for moving this Magento Open Source `2.4.7` project from the current local DDEV environment to a remote server managed through WHM/cPanel with root access.

This is not a partial content move. The destination must receive the full Magento application and the full database-backed store state, including:

- products
- categories
- customers
- orders
- CMS pages
- CMS blocks
- configuration data
- media files
- custom modules and themes

## Audience

This plan is written for the developer or sysadmin who will build and operate the remote host directly.

They are expected to have:

- WHM/root access
- shell access to the server
- permission to install packages/services
- permission to configure Apache, PHP, cron, and databases

## Critical Project Facts

### Application facts

- Magento Open Source `2.4.7`
- Composer-based installation
- custom code under `app/code/`
- custom themes under `app/design/frontend/`
- current operating style is effectively `production`

### Important local behavior that must be preserved

- `app/etc/config.php` is tracked and should deploy as-is.
- `app/etc/env.php` is not tracked and must be recreated on the remote host from the current local values.
- The current admin frontName is `admin_y312l0` and must be preserved unless there is a deliberate post-migration change.
- The root [`.htaccess`](/home/ildar/projects/magento/.htaccess:1) contains an admin IP allowlist rule and must be preserved.
- Search in this project should use Magento’s OpenSearch adapter, not the old `elasticsearch7` setting. See [dev/notes/search-and-category-opensearch-fix-2026-05-20.md](/home/ildar/projects/magento/dev/notes/search-and-category-opensearch-fix-2026-05-20.md).
- The active storefront theme is `Sm/market`.
- This codebase has a known static deploy caveat. Prefer:

```bash
php bin/magento setup:static-content:deploy -f --strategy standard --theme Sm/market en_US
```

Reference: [dev/notes/homepage-asset-portability-handoff.md](/home/ildar/projects/magento/dev/notes/homepage-asset-portability-handoff.md)

### Cron-dependent project behavior

The remote host must run Magento cron every minute.

Known project-specific cron users include:

- `Local/RotatingSpecialDeals`
- `Local/InteracETransfer`
- `Magefan/AdminUserGuide`
- core Magento cron jobs for indexing, emails, and cleanup

## Migration Principle

Build the remote server first, stage the site there, restore the full database and `pub/media`, validate the site while it is still private, then do a short final sync and DNS cutover.

Do not try to “rebuild” catalog/config/content manually on the remote server. The authoritative store state is the database.

## Phase 0: Pre-Migration Decision

### 0.1 Confirm the target architecture

The preferred destination layout is:

- WHM/cPanel-managed Linux server
- Apache with Magento-compatible `.htaccess`
- PHP CLI and PHP-FPM or Apache PHP configured for Magento
- MariaDB/MySQL
- OpenSearch available either:
  - locally on the same server
  - on another private server you control
  - or as a managed external service

### 0.2 Do not proceed until these are confirmed

- the vhost can use Magento `pub/` as the document root
- the server can run Composer `2.x`
- the server can run Magento CLI commands
- the server can run cron every minute
- the server has enough RAM/CPU for `setup:di:compile` and static deploy
- the server has a working OpenSearch target for Magento

## Phase 1: Collect Source Environment Data

This phase happens on the current local machine.

### 1.1 Preserve the current `env.php`

The remote environment must preserve values from the current local `app/etc/env.php`, especially:

- `crypt/key`
- `backend/frontName`
- DB prefix if present
- any cache/session/search settings that are intentionally in use

If the `crypt/key` changes, encrypted config and credentials can break.

### 1.2 Create a source application backup set

Prepare these artifacts from the current project:

1. Full database dump
2. Full `pub/media/` archive or rsync source
3. Copy of current `app/etc/env.php`
4. Code snapshot from the repo at the intended deployment revision

### 1.3 Database dump requirements

The database dump must include the full store data, not just schema.

That includes:

- catalog tables
- customer tables
- sales tables
- CMS tables
- `core_config_data`
- index tables if present
- custom module tables

Use a full SQL dump, compressed if needed.

### 1.4 Record current live-sensitive settings

Before migration, record:

- current base URLs
- current admin frontName: `admin_y312l0`
- current search-engine settings
- current theme assignment
- any current cron or email notes the host operator must know

## Phase 2: Prepare The Remote WHM/cPanel Server

This phase happens on the destination server.

### 2.1 Create the cPanel account and domain mapping

In WHM:

1. Create or identify the cPanel account that will own the Magento files.
2. Create the domain/subdomain for staging or production.
3. Make sure SSH is enabled for that account.

### 2.2 Set the web root correctly

Magento should be served from the project `pub/` directory.

Target layout example:

```text
/home/<cpanel-user>/magento
/home/<cpanel-user>/magento/pub
```

The Apache vhost or cPanel document root must point to:

```text
/home/<cpanel-user>/magento/pub
```

Do not serve the site from the repository root.

### 2.3 Install required server software

Install or verify:

- PHP version compatible with Magento `2.4.7`
- required PHP extensions for Magento
- Composer `2.x`
- MariaDB/MySQL compatible with Magento `2.4.7`
- OpenSearch
- `unzip`
- `git`
- `rsync`

### 2.4 PHP settings

Raise PHP limits high enough for Magento operations.

At minimum review:

- `memory_limit`
- `max_execution_time`
- `max_input_vars`
- `upload_max_filesize`
- `post_max_size`
- OPcache settings

The server must be able to complete:

- `composer install --no-dev`
- `php bin/magento setup:upgrade`
- `php bin/magento setup:di:compile`
- `php bin/magento setup:static-content:deploy`

### 2.5 Apache requirements

Verify:

- `.htaccess` is honored
- rewrite support is active
- the vhost allows overrides needed by Magento
- the site can use SSL

This matters because the project already relies on `.htaccess`, including the admin IP restriction.

## Phase 3: Provision Destination Services

### 3.1 Database

Create:

- destination database
- destination database user
- destination database password

Grant the user full privileges on the Magento database.

### 3.2 OpenSearch

Install and start OpenSearch, or configure the external endpoint now.

The destination Magento config should use the native OpenSearch adapter.

Do not leave this until after cutover. Search/category indexing is a known failure point in this project if the search engine is misconfigured.

Reference: [dev/notes/search-and-category-opensearch-fix-2026-05-20.md](/home/ildar/projects/magento/dev/notes/search-and-category-opensearch-fix-2026-05-20.md)

### 3.3 SSL

Provision SSL for the final domain or at least the staging hostname before validation.

## Phase 4: Deploy The Codebase

### 4.1 Create the application directory

Example:

```bash
mkdir -p /home/<cpanel-user>/magento
cd /home/<cpanel-user>/magento
```

### 4.2 Upload the code

Use one of:

- `git clone`
- `rsync`
- tarball upload and extract

The deployed code must include:

- `app/code/`
- `app/design/frontend/`
- `vendor/` only if you are shipping a prebuilt artifact
- all Magento root files including `.htaccess`

### 4.3 Composer install

From the Magento root:

```bash
composer install --no-dev
```

If the host will install dependencies itself, it must have valid `repo.magento.com` credentials available to Composer.

If that is not practical, prepare a deployment artifact elsewhere and transfer the installed application tree.

## Phase 5: Build Remote `app/etc/env.php`

Create `app/etc/env.php` on the destination using the local file as the source of truth.

### 5.1 Preserve these values from source

- `crypt/key`
- `backend/frontName`
- any intentionally configured cache/session options

### 5.2 Replace these values for destination

- DB host
- DB name
- DB username
- DB password
- search hostname/port if stored here
- any environment-specific filesystem paths

### 5.3 Do not randomize the admin path

Keep the existing admin frontName:

```text
admin_y312l0
```

The current `.htaccess` rule is already aligned to that path.

## Phase 6: Import Full Database And Media

This phase is mandatory for a real migration.

### 6.1 Import the full database

Import the SQL dump into the destination database.

This import must bring over all store data, including products and the rest of the live site content:

- products
- categories
- attributes
- customers
- orders
- CMS content
- configuration
- custom module data

This is the main source of truth for the store. Do not try to reconstruct this manually.

### 6.2 Copy `pub/media/`

Transfer the full `pub/media/` tree from source to destination.

This must include:

- product images
- WYSIWYG images
- theme/demo images referenced by CMS content

### 6.3 Set correct ownership and permissions

Ensure the cPanel account user owns the Magento files and that Magento can write to required directories such as:

- `var/`
- `generated/`
- `pub/static/`
- `pub/media/`
- `app/etc/`

## Phase 7: Update Environment-Specific Magento Config

After the database import, update only the settings that should differ by environment.

### 7.1 Base URLs

Set:

- unsecure base URL
- secure base URL

Use the destination hostname and HTTPS.

### 7.2 Search settings

Set Magento to use OpenSearch if the imported DB still points at the old local values.

Use the pattern documented in [dev/notes/search-and-category-opensearch-fix-2026-05-20.md](/home/ildar/projects/magento/dev/notes/search-and-category-opensearch-fix-2026-05-20.md):

```bash
php bin/magento config:set catalog/search/engine opensearch
php bin/magento config:set catalog/search/opensearch_server_hostname <opensearch-host>
php bin/magento config:set catalog/search/opensearch_server_port <opensearch-port>
php bin/magento config:set catalog/search/opensearch_index_prefix market247
php bin/magento config:set catalog/search/opensearch_enable_auth 0
php bin/magento config:set catalog/search/opensearch_server_timeout 15
php bin/magento cache:clean config
```

Adjust auth settings if the destination OpenSearch requires authentication.

### 7.3 Any destination-only settings

Review and update only what should be environment-specific, for example:

- base URLs
- cookie settings if needed
- offloader/proxy headers if needed
- SMTP settings if they differ

## Phase 8: Run Magento Build And Upgrade Steps

Run these from the Magento root on the destination server.

### 8.1 Enable maintenance mode

```bash
php bin/magento maintenance:enable
```

### 8.2 Upgrade and compile

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

### 8.3 Deploy static content

Use the safer project-specific command:

```bash
php bin/magento setup:static-content:deploy -f --strategy standard --theme Sm/market en_US
```

If more locales are active, include them explicitly.

### 8.4 Reindex

Start with:

```bash
php bin/magento indexer:reindex
```

If reindexing fails due to search-engine issues, fix OpenSearch config before continuing.

### 8.5 Flush caches

```bash
php bin/magento cache:flush
```

### 8.6 Disable maintenance mode

```bash
php bin/magento maintenance:disable
```

## Phase 9: Configure Magento Cron

Cron is mandatory.

### 9.1 Install Magento cron

Run as the Magento file owner:

```bash
php bin/magento cron:install
```

If WHM/cPanel cron is being managed manually, create the equivalent minute-level cron under the correct account user.

### 9.2 Validate cron

Verify:

- `cron_schedule` gets new rows
- jobs progress from `pending` to `success`
- custom cron tasks are not stalled

Pay attention to:

- rotating special deals
- e-transfer cleanup
- core email/index/indexer jobs

## Phase 10: Validate Admin Access Controls

### 10.1 Preserve `.htaccess`

The root [`.htaccess`](/home/ildar/projects/magento/.htaccess:1) already contains an admin IP restriction.

That rule must remain in place on the destination.

### 10.2 Validate the custom admin URL

Admin should still use:

```text
/admin_y312l0
```

### 10.3 Test both allowed and denied access

Verify:

- an allowed IP in `24.157.155.*` can reach the admin path
- a non-allowed IP gets denied

### 10.4 Proxy/CDN caveat

If the site will sit behind Cloudflare or another proxy, verify Apache is receiving the real client IP. Otherwise the allowlist may evaluate against the proxy IP instead of the visitor IP.

## Phase 11: Functional Validation Before Cutover

The destination must be validated before DNS is switched.

### 11.1 Storefront validation

Check:

- homepage loads
- CSS and JS assets load
- category pages show products
- search returns products
- product pages show images and prices
- cart works
- checkout loads

### 11.2 Admin validation

Check:

- admin login works
- config pages open
- CMS pages and blocks exist
- media browser works

### 11.3 Data validation

Confirm that the migrated database content is actually present:

- products exist
- categories exist
- customers exist
- orders exist
- CMS pages exist
- CMS blocks exist

### 11.4 Indexing and search validation

Check:

- `php bin/magento indexer:status`
- search results in storefront
- category product lists in storefront

### 11.5 Cron validation

Check:

- cron jobs are succeeding
- no obvious queue of failed jobs is building up

## Phase 12: Final Cutover Procedure

This is the actual go-live phase.

### 12.1 Lower DNS TTL in advance

Lower TTL well before cutover so rollback remains practical.

### 12.2 Freeze the source during final sync

At cutover time:

1. put the source site into maintenance mode if needed
2. take a final database dump from source
3. sync final `pub/media/` changes

### 12.3 Restore the final DB snapshot to destination

Import the final source dump over the staged destination DB so the remote site has the latest:

- products
- customers
- orders
- CMS/config changes

### 12.4 Re-run post-import Magento tasks

At minimum:

```bash
php bin/magento maintenance:enable
php bin/magento cache:flush
php bin/magento indexer:reindex
php bin/magento maintenance:disable
```

If the final import changed module/schema state, rerun `setup:upgrade` as well.

### 12.5 Switch DNS

Point the live domain to the destination server.

### 12.6 Post-cutover smoke test

Immediately verify:

- storefront homepage
- a category page
- a product page
- admin login
- search
- cart

## Phase 13: Rollback Plan

Do not cut over without rollback prepared.

### 13.1 Keep these available

- original local/DDEV environment
- pre-cutover destination backup
- latest source DB dump
- latest `pub/media/` backup

### 13.2 Rollback trigger conditions

Rollback if any of these are broken after cutover and cannot be corrected quickly:

- storefront unavailable
- admin unavailable
- search/category pages empty due to indexing/search issues
- checkout blocked
- critical database content missing

### 13.3 Rollback action

Point DNS back to the prior environment and restore the previous state as needed.

## Execution Checklist

1. Confirm WHM/root host supports Magento `2.4.7`, Composer, cron, and OpenSearch.
2. Configure Apache/vhost to serve Magento from `pub/`.
3. Create destination DB and OpenSearch service.
4. Collect source `env.php`, full DB dump, and full `pub/media/`.
5. Deploy code to `/home/<cpanel-user>/magento`.
6. Run `composer install --no-dev`.
7. Recreate `app/etc/env.php` with preserved `crypt/key` and `backend/frontName`.
8. Import the full database.
9. Copy the full `pub/media/` tree.
10. Set destination base URLs and OpenSearch config.
11. Run `setup:upgrade`, `setup:di:compile`, static deploy, reindex, cache flush.
12. Install/validate Magento cron.
13. Validate storefront, admin, data presence, search, and cron.
14. Perform final DB/media sync during maintenance window.
15. Reindex/cache flush again after final import.
16. Switch DNS.
17. Run post-cutover smoke tests.

## Definition Of Done

The migration is complete only when all of the following are true:

- the remote server serves the site from `pub/`
- the full database has been migrated, including products and all other store data
- `pub/media/` has been migrated completely
- admin works at `/admin_y312l0`
- the admin IP restriction works
- search/category pages work against destination OpenSearch
- cron is running every minute
- Magento compile/static deploy succeed on the remote server
- storefront and admin smoke tests pass after DNS cutover
