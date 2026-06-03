#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/home/ildar/projects/magento"
export PATH="/usr/local/bin:/usr/bin:/bin:$PATH"

cd "$REPO_DIR"
npm run pw:homepage-capture
