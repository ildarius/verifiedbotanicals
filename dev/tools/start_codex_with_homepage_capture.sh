#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="/home/ildar/projects/magento"
CODEX_BIN="${CODEX_BIN:-codex}"

cd "$REPO_DIR"

if command -v npm >/dev/null 2>&1; then
  npm run pw:homepage-capture || true
fi

exec "$CODEX_BIN" "$@"
