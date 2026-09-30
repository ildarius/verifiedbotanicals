#!/usr/bin/env bash
# Apply Adobe's VULN-39341 hotfix after any Composer vendor rebuild.
set -euo pipefail

project_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
patch_file="$project_root/m2-hotfixes/VULN-39341_249.patch"
expected_sha256='8df23110e1909e2d5f0c2f0ecbe7f803670dfb0c9c3d15a527371c04b7825bf7'

if [[ ! -f "$patch_file" ]]; then
    echo "Missing hotfix: $patch_file" >&2
    exit 1
fi

actual_sha256=$(sha256sum "$patch_file" | awk '{print $1}')
if [[ "$actual_sha256" != "$expected_sha256" ]]; then
    echo "Hotfix checksum mismatch; refusing to apply it." >&2
    exit 1
fi

cd "$project_root"
# --force (never --batch): --batch makes patch "assume reversed", so a -R dry-run
# would succeed on an unpatched tree and the hotfix would be silently skipped.
is_applied() { patch --dry-run --force -R -p1 < "$patch_file" >/dev/null 2>&1; }

if is_applied; then
    echo 'VULN-39341 is already applied.'
    exit 0
fi

patch --dry-run --forward --force -p1 < "$patch_file" >/dev/null
patch --forward --force -p1 < "$patch_file"
if ! is_applied; then
    echo 'VULN-39341 verification failed after applying.' >&2
    exit 1
fi
echo 'VULN-39341 applied and verified.'
