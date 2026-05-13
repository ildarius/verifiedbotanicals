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
docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade
```

Result:

- patch applied successfully
- caches flushed
- homepage now renders the moved assets from `static/.../Sm_Market/images/...`

### 7. Moved `home-dropdown` layout preview assets into tracked theme files

Copied into tracked theme assets:

- `app/design/frontend/Sm/market/web/images/shared/layout-demo/layout-*.jpg`

Source files were copied from:

- `pub/media/wysiwyg/layout-demo/*.jpg`

Added:

- `app/code/Local/HomepageAssets/Setup/Patch/Data/MigrateHomeDropdownAssets.php`

Patch behavior:

- rewrites CMS block `home-dropdown`
- converts `{{media url=wysiwyg/layout-demo/...}}` to `{{view url='Sm_Market::images/shared/layout-demo/...'}}`

### 8. Replaced generated homepage theme CSS with a tracked theme asset for `fresh1_en`

Added:

- `app/design/frontend/Sm/market/Sm_Themecore/templates/html/head.phtml`
- `app/design/frontend/Sm/market/web/css/settings/settings_fresh1_en.css`

Behavior:

- for store code `fresh1_en`, the head template now loads the tracked static asset
- other stores still fall back to the vendor helper URL under `pub/media/sm/configed_css/`

### 9. Re-ran production static deploy

Executed successfully:

```bash
docker exec -u 1000 ddev-magento-web php bin/magento setup:static-content:deploy -f --strategy standard --theme Sm/market en_US
```

Notes:

- `ddev exec` is not usable from the current root shell
- `setup:static-content:deploy -f --theme Sm/market en_US` with the default quick strategy failed in `frontend/Magento/blank`
- rerunning with `--strategy standard` completed successfully for `Magento/blank`, `Sm/market`, and `Sm/themecore`

## Verified Working

Verified in rendered homepage HTML:

- support header image
- mobile logo
- homepage mega-menu icon
- homepage category images
- homepage banner images
- homepage service icons
- homepage farmer images
- `home-dropdown` layout preview images
- tracked stylesheet `Sm_Market/css/settings/settings_fresh1_en.css`

These now resolve from:

- `https://magento.ddev.site/static/.../Sm_Market/images/...`
- `https://magento.ddev.site/static/.../Sm_Market/css/settings/settings_fresh1_en.css`

## Remaining Portability Gaps

### 1. Normal product media/cache paths still appear

Still present in rendered homepage:

- `media/catalog/product/cache/...`
- `media/lazyloading/blank.png`

These are expected runtime/media dependencies and were not addressed yet.

## Most Likely Next Steps

### A. Decide whether to also vendor the lazyload placeholder

Rendered homepage still uses:

- `media/lazyloading/blank.png`

This is not homepage-specific and is still referenced by vendor/product rendering, but if the goal expands to "no homepage HTML may reference `pub/media` except product images", this is the next obvious asset to port.

### B. Decide how broadly to replace generated theme CSS

Current behavior is intentionally narrow:

- `fresh1_en` uses tracked theme CSS
- other stores still fall back to vendor-generated CSS in `pub/media/sm/configed_css/`

If full multi-store portability is needed, add tracked CSS copies for the other active store codes and extend the store-code map in:

- `app/design/frontend/Sm/market/Sm_Themecore/templates/html/head.phtml`

## Current Relevant Files

- `app/code/Local/HomepageAssets/Setup/Patch/Data/MigrateHomeDemo37Assets.php`
- `app/code/Local/HomepageAssets/Setup/Patch/Data/MigrateHomeDropdownAssets.php`
- `app/design/frontend/Sm/market/Sm_Themecore/templates/html/head.phtml`
- `app/design/frontend/Sm/market/Sm_MegaMenu/templates/vertical.phtml`
- `app/design/frontend/Sm/market/Sm_Market/templates/html/header-mobile.phtml`
- `app/design/frontend/Sm/market/web/css/settings/settings_fresh1_en.css`
- `app/design/frontend/Sm/market/web/images/home-demo-37/`
- `app/design/frontend/Sm/market/web/images/shared/`

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
docker exec -u 1000 ddev-magento-web php bin/magento setup:upgrade
```

Rebuild production static assets for the active theme:

```bash
docker exec -u 1000 ddev-magento-web php bin/magento setup:static-content:deploy -f --strategy standard --theme Sm/market en_US
```
