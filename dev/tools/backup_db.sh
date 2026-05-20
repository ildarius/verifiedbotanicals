#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BACKUP_DIR="${ROOT_DIR}/backups"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LABEL="${1:-manual}"
OUTPUT_FILE="${BACKUP_DIR}/magento-db-${LABEL}-${TIMESTAMP}.sql.gz"

mkdir -p "${BACKUP_DIR}"

echo "Creating database backup at ${OUTPUT_FILE}"
docker exec ddev-magento-db mysqldump -u db -pdb db | gzip > "${OUTPUT_FILE}"
echo "Backup complete: ${OUTPUT_FILE}"
