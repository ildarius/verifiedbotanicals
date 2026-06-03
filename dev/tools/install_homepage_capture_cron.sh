#!/usr/bin/env bash
set -euo pipefail

CRON_FILE="/etc/cron.d/magento-homepage-capture"
REPO_DIR="/home/ildar/projects/magento"
RUN_USER="ildar"
LOG_FILE="$REPO_DIR/.playwright/artifacts/homepage-history/cron.log"

mkdir -p "$REPO_DIR/.playwright/artifacts/homepage-history"
touch "$REPO_DIR/.playwright/artifacts/storefront-homepage-latest.json" \
  "$REPO_DIR/.playwright/artifacts/storefront-homepage-latest.png" \
  "$LOG_FILE"
chown -R "$RUN_USER:$RUN_USER" "$REPO_DIR/.playwright/artifacts/homepage-history"
chown "$RUN_USER:$RUN_USER" \
  "$REPO_DIR/.playwright/artifacts/storefront-homepage-latest.json" \
  "$REPO_DIR/.playwright/artifacts/storefront-homepage-latest.png" \
  "$LOG_FILE"

cat > "$CRON_FILE" <<CRON
SHELL=/bin/bash
PATH=/usr/local/bin:/usr/bin:/bin

# Capture the Magento homepage every hour at minute 12.
12 * * * * $RUN_USER cd $REPO_DIR && $REPO_DIR/dev/tools/capture_homepage.sh >> $LOG_FILE 2>&1
CRON

chmod 0644 "$CRON_FILE"
echo "Installed cron entry at $CRON_FILE"
