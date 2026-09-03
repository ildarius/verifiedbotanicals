# Transitional Playwright Location

Date: August 26, 2026

`tools/playwright/` is no longer intended to be the long-term home of the storefront/admin automation suite.

The new source-of-truth repository is:

- `/home/ildar/projects/magento-automated-tests`
- Git remote: `git@github.com:ildarius/verifiedbotanicals_auto_tests.git`

## Current Status

These scripts are still present here so existing local commands and notes do not break during migration.

Equivalent standalone-repo commands now live in the new repository:

- `pw:admin-login` -> `npm run admin:login`
- `pw:homepage-capture` -> `npm run homepage:capture`
- `pw:homepage-check` -> `npm run homepage:check`
- `pw:search-check` -> `npm run search:check`
- `pw:shipping-screenshots` -> `npm run shipping:scenarios`
- `pw:tax-screenshots` -> `npm run tax:scenarios`
- `pw:checkout-etransfer` -> `npm run checkout:etransfer`

## Removal Condition

After the standalone repo fully absorbs the remaining useful logic and any local cron/manual workflows are moved over, this folder and the Magento root `pw:*` scripts can be deleted.

