#!/usr/bin/env bash
# Build compiled DI code and static content in a copy of the codebase outside the web root, then swap
# them into the live site in a ~15 second maintenance window. Production mode since 2026-10-02.
#
# Usage: dev/tools/deploy-production.sh [--build-only]
#   --build-only   build and verify in ~/build-prod-<stamp>, but do not touch the live site
#
# When it is needed:
#   - JS, CSS/LESS, knockout .html templates, requirejs-config, theme images/fonts  -> yes (static content)
#   - new/changed di.xml, plugins, preferences, constructor arguments, new modules  -> yes (DI compile)
#   - only PHP method bodies or .phtml templates changed                            -> no; bin/magento cache:flush
# Rollback: the previous generated/ and pub/static/ are kept in <build>/rollback (see the end of the output).
set -uo pipefail
umask 002
L=/home/verifiedbota/public_html
B=/home/verifiedbota/build-prod-$(date +%Y%m%d-%H%M%S)
PHP="env PHP_INI_SCAN_DIR=/opt/cpanel/ea-php83/root/etc/php.d:/home/verifiedbota/php-cli.d /opt/cpanel/ea-php83/root/usr/bin/php -d memory_limit=-1"
EXCLUDES=(--exclude=/pub/media/ --exclude=/var/ --exclude=/generated/ --exclude=/.git/ --exclude=/backups/ --exclude='/vendor.pre-*/' --exclude='/pub/static/')
die() { echo "!! $*" >&2; exit 1; }

echo "== Building in $B"
mkdir -m 700 "$B" || die "cannot create $B"
rsync -a "${EXCLUDES[@]}" "$L/" "$B/" || die "copy failed"
mkdir -p "$B/var" "$B/generated" "$B/pub/media" "$B/pub/static" && cp -p "$L/pub/static/.htaccess" "$B/pub/static/" && cp -p "$L/generated/.htaccess" "$B/generated/"
( cd "$B" && $PHP bin/magento setup:di:compile >/dev/null ) || die "setup:di:compile failed (build left in $B)"
( cd "$B" && $PHP bin/magento setup:static-content:deploy -f --strategy standard -a frontend -t Sm/market -l en_US -j 4 >/dev/null ) || die "frontend static deploy failed"
( cd "$B" && $PHP bin/magento setup:static-content:deploy -f --strategy standard -a adminhtml -t Magento/backend -l en_US -j 4 >/dev/null ) || die "adminhtml static deploy failed"
[[ -z $(grep -rl "$B" "$B/generated" "$B/pub/static" | head -1) ]] || die "build path leaked into generated/static output"
[[ -z $(find "$B/pub/static" -type l | head -1) ]] || die "static output contains symlinks"
echo "   ok: $(find "$B/pub/static" -type f | wc -l) static files, $(ls "$B/generated/metadata" | wc -l) metadata files"

if [[ "${1:-}" == --build-only ]]; then
    rm -f "$B/app/etc/env.php"
    echo "== Build only; live site untouched. Remove $B when done."
    exit 0
fi

changed=$(rsync -an --delete --itemize-changes "${EXCLUDES[@]}" "$L/" "$B/" | grep -v '^\.d' | head -5)
[[ -z "$changed" ]] || die "live code changed during the build; re-run. First differences: $changed"

echo "== Swapping into live (maintenance on)"
M="$PHP $L/bin/magento"
mkdir -p "$B/rollback"
$M maintenance:enable >/dev/null || die "maintenance:enable failed"
trap '$M maintenance:disable >/dev/null; echo "   maintenance off"' EXIT
mv "$L/generated" "$B/rollback/generated" && mv "$B/generated" "$L/generated" || die "generated/ swap failed; restore from $B/rollback"
mv "$L/pub/static" "$B/rollback/static" && mv "$B/pub/static" "$L/pub/static" || die "pub/static swap failed; restore from $B/rollback"
rm -rf "$L/var/view_preprocessed" "$L/var/cache" "$L/var/page_cache"
[[ $($M deploy:mode:show) == *production* ]] || $M deploy:mode:set production --skip-compilation >/dev/null
$M cache:flush >/dev/null
# Consumers hold the old code; Magento cron restarts them when messages arrive. Anchor on the PHP binary so
# this never matches a shell whose command line merely contains the text.
pkill -u "$(id -u)" -f '^/opt/cpanel/ea-php[0-9]+/root/usr/bin/php .*bin/magento queue:consumers:start' || true
trap - EXIT
$M maintenance:disable >/dev/null && echo "   maintenance off"

for u in / /customer/account/login/ /checkout/cart/; do
    echo "   $(curl -s -o /dev/null -w '%{http_code}' -m 30 "https://verifiedbotanicals.com$u") $u"
done
find "$B" -mindepth 1 -maxdepth 1 ! -name rollback -exec rm -rf {} +
echo "== Done. Static version $(cat "$L/pub/static/deployed_version.txt")."
echo "   Rollback: maintenance:enable; swap $B/rollback/{generated,static} back into generated/ and pub/static/; cache:flush; maintenance:disable."
echo "   Then re-run ~/incident-monitor/monitor.sh --baseline if it alerts on the change."
