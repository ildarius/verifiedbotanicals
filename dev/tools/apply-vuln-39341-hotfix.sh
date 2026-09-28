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
if patch --dry-run --batch -R -p1 < "$patch_file" >/dev/null; then
    echo 'VULN-39341 is already applied.'
    exit 0
fi

patch --batch -p1 < "$patch_file"
patch --dry-run --batch -R -p1 < "$patch_file" >/dev/null
echo 'VULN-39341 applied and verified.'
