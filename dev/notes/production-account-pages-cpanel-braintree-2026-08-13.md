# Production account-page incident, 2026-08-13

Context: verifiedbotanicals.com runs Magento Open Source 2.4.7 under cPanel/WHM with ea-php83 PHP-FPM. The store is not intended to use PayPal, Braintree, or other online payment collection. Payments are expected to be collected offline; do not document or add real payment credentials for this site.

## Symptoms

- Browser-like customer POSTs to `/customer/account/createpost/` and `/customer/account/loginPost/` returned Magento CMS 404 responses.
- GET account pages rendered mostly normal HTML but returned HTTP 500:
  - `/customer/account/create/`
  - `/customer/account/login/`
- Earlier account-page requests logged 128M PHP memory fatals.
- After the memory fix, fresh Magento logs showed:
  - `Braintree\Configuration::merchantId needs to be set (or accessToken needs to be passed to Braintree\Gateway).`
- Apache also logged:
  - `/home/verifiedbota/public_html/customer/.htaccess: Invalid command '<IfVersion'`

## Fixes Applied

- `app/code/Local/KratomSearchTweaks/Plugin/FrontController/NormalizeCustomerPostPathPlugin.php`
  - Normalizes Magento's request method to `POST` only for:
    - `customer/account/createpost`
    - `customer/account/loginpost`
  - It only changes the method when the request object says non-POST but server/request evidence shows a POST payload.

- `app/code/Local/KratomSearchTweaks/etc/frontend/di.xml`
  - Disables PayPal Braintree's frontend minicart plugin `addPayLaterMessageConfig`.
  - Adds a local guarded replacement plugin for `Magento\Checkout\Block\Cart\Sidebar::getConfig()`.

- `app/code/Local/KratomSearchTweaks/Plugin/Checkout/Cart/GuardedPayLaterMessageConfigPlugin.php`
  - Checks Pay Later cart messaging config before requesting a Braintree client token.
  - With PayPal/Braintree inactive, it leaves minicart config unchanged and does not touch Braintree credentials.

- `app/code/Local/KratomSearchTweaks/etc/module.xml`
  - Adds a sequence dependency on `PayPal_Braintree` so the frontend DI override order is explicit.

- `pub/.user.ini`
  - Changed `memory_limit` from `756M` to `1500M`.
  - Runtime probe showed ea-php83 FPM using `/home/verifiedbota/public_html/pub/.user.ini`.

- `app/code/Sm/MegaMenu/view/frontend/templates/MobileDetect.php`
  - Patched null user-agent handling for PHP 8.3 deprecations in `preg_match()` / `stripos()`.

- `customer/.htaccess`
  - Replaced unsupported `<IfVersion>` deny rules with `Options -Indexes`.
  - This avoids Apache 500s on Magento's `/customer/...` route while still preventing directory indexing for the empty physical `customer/` directory.

## Commands Run

```bash
runuser -u verifiedbota -- bash -lc 'cd /home/verifiedbota/public_html && php -d memory_limit=-1 bin/magento setup:di:compile'
runuser -u verifiedbota -- bash -lc 'cd /home/verifiedbota/public_html && php -d memory_limit=-1 bin/magento cache:flush'
systemctl reload ea-php83-php-fpm
```

## Validation

- `GET https://verifiedbotanicals.com/customer/account/create/` returns HTTP 200.
- `GET https://verifiedbotanicals.com/customer/account/login/` returns HTTP 200.
- Invalid POST with valid `form_key` to `/customer/account/createpost/` returns 302 back to the create page, not CMS 404.
- Invalid POST with valid `form_key` to `/customer/account/loginPost/` returns 302 back to the login page, not CMS 404.
- Clean isolated GET/POST checks did not add fresh Braintree merchant-id errors.
- Fresh log checks showed no new 128M memory fatal, no new MobileDetect null user-agent exception, and no new `customer/.htaccess` `<IfVersion>` error.
