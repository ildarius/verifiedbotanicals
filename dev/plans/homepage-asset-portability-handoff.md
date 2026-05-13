# Homepage Asset Portability Handoff

## Goal

Make the homepage portable from Git by removing dependencies on untracked `pub/media/...`, generated theme CSS, and DB-only asset references.

## What Was Done

### 1. Restored Magento after accidental deletion of ignored files

Root cause:

- `vendor/` was missing
- `app/etc/config.php` was missing
- partial restore with `rsync --ignore-existing` left `vendor/` and `generated/` inconsistent

What fixed it:

- restored `app/etc/config.php`
- restored `vendor/` and `generated/` as full directories from `magento-backup-before-filter`

### 2. Started tracking shared Magento config

Committed:

- `app/etc/config.php`

Reason:

- it is safe to commit in this repo
- it only contains the `modules` map
- it does not contain secrets, DB credentials, hostnames, or tokens

### 3. Moved key homepage assets into tracked theme files

Copied into tracked theme assets:

- `app/design/frontend/Sm/market/web/images/home-demo-37/category/*`
- `app/design/frontend/Sm/market/web/images/home-demo-37/banner/*`
- `app/design/frontend/Sm/market/web/images/home-demo-37/icon-image/*`
- `app/design/frontend/Sm/market/web/images/home-demo-37/clients/*`
- `app/design/frontend/Sm/market/web/images/shared/support.png`
- `app/design/frontend/Sm/market/web/images/shared/logo-mobile_1.png`
- `app/design/frontend/Sm/market/web/images/shared/mega-menu/icon-1.png`

Source files were copied from:

- `pub/media/wysiwyg/...`
- `pub/media/logomobile/default/logo-mobile_1.png`

### 4. Added a local module + data patch to rewrite CMS asset references

Added:

- `app/code/Local/HomepageAssets/registration.php`
- `app/code/Local/HomepageAssets/etc/module.xml`
- `app/code/Local/HomepageAssets/Setup/Patch/Data/MigrateHomeDemo37Assets.php`

Enabled in:

- `app/etc/config.php`

Patch behavior:

- rewrites CMS page `home-demo-37`
- rewrites CMS block `support-header-37`
- converts `{{media url=...}}` to `{{view url='Sm_Market::images/...'}}` for the moved homepage assets

### 5. Updated theme templates to stop using `pub/media` for two homepage-adjacent assets

Changed:

- `app/design/frontend/Sm/market/Sm_MegaMenu/templates/vertical.phtml`
- `app/design/frontend/Sm/market/Sm_Market/templates/html/header-mobile.phtml`

Behavior:

- homepage mega-menu leaf icon now comes from tracked theme asset
- mobile header logo now uses tracked theme asset when config value is `default/logo-mobile_1.png`

### 6. Ran Magento upgrade

Executed:

```bash
ddev exec php bin/magento setup:upgrade
```

Result:

- patch applied successfully
- caches flushed
- homepage now renders the moved assets from `static/.../Sm_Market/images/...`

## Verified Working

Verified in rendered homepage HTML:

- support header image
- mobile logo
- homepage mega-menu icon
- homepage category images
- homepage banner images
- homepage service icons
- homepage farmer images

These now resolve from:

- `https://magento.ddev.site/static/.../Sm_Market/images/...`

## Remaining Portability Gaps

### 1. Homepage still references media layout preview images

Rendered homepage still uses:

- `media/wysiwyg/layout-demo/layout-*.jpg`

These come from the “home-dropdown” content/menu preview area.

### 2. Homepage still depends on generated theme CSS

Rendered homepage still loads:

- `media/sm/configed_css/settings_fresh1_en.css`

This is generated and not tracked.

This may also be where the modified homepage background image is coming from, depending on how it was changed.

### 3. Normal product media/cache paths still appear

Still present in rendered homepage:

- `media/catalog/product/cache/...`
- `media/lazyloading/blank.png`

These are expected runtime/media dependencies and were not addressed yet.

## Most Likely Next Steps

### A. Remove `layout-demo` media dependency

Find the source for the homepage “demo layout preview” assets and switch those references to tracked theme assets.

Likely sources:

- CMS block/page content
- imported theme content
- template code rendering `data-src-prev`

Useful command:

```bash
curl -ks https://magento.ddev.site/ | rg 'layout-demo'
```

### B. Remove generated theme CSS dependency

Investigate:

- which settings generate `media/sm/configed_css/settings_fresh1_en.css`
- whether the changed homepage background image is coming from those settings

Likely code paths:

- `app/code/Sm/Themecore/...`
- generated CSS helpers/templates

Goal:

- move critical homepage styling, especially the modified background image, into tracked theme files
- avoid relying on generated CSS for homepage-critical presentation

Useful command:

```bash
curl -ks https://magento.ddev.site/media/sm/configed_css/settings_fresh1_en.css | rg 'background|media/'
```

## Current Relevant Files

- `app/code/Local/HomepageAssets/Setup/Patch/Data/MigrateHomeDemo37Assets.php`
- `app/design/frontend/Sm/market/Sm_MegaMenu/templates/vertical.phtml`
- `app/design/frontend/Sm/market/Sm_Market/templates/html/header-mobile.phtml`
- `app/design/frontend/Sm/market/web/images/home-demo-37/`
- `app/design/frontend/Sm/market/web/images/shared/`

## Current Git Status Notes

Relevant uncommitted changes:

- modified: `app/design/frontend/Sm/market/Sm_Market/templates/html/header-mobile.phtml`
- modified: `app/design/frontend/Sm/market/Sm_MegaMenu/templates/vertical.phtml`
- modified: `app/etc/config.php`
- untracked: `app/code/Local/`
- untracked: `app/design/frontend/Sm/market/web/images/home-demo-37/`
- untracked: `app/design/frontend/Sm/market/web/images/shared/`

Extra untracked directories at repo root that look accidental from restore activity:

- `cache/`
- `captcha/`
- `customer/`
- `downloadable/`
- `import/`
- `theme_customization/`
- `tmp/`

These likely do not belong at project root and should be reviewed before any broad `git add`.

Temporary restore backups still present:

- `generated.broken-20260513/`
- `vendor.broken-20260513/`

## Resume Commands

Check current status:

```bash
cd /home/ildar/projects/magento
git status --short
```

Verify homepage asset sources:

```bash
curl -ks https://magento.ddev.site/ | rg -o 'https://magento\\.ddev\\.site/(static|media)/[^\" ]+' | sort -u
```

Re-apply DB patch logic if needed:

```bash
ddev exec php bin/magento setup:upgrade
```
