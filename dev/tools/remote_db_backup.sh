#!/usr/bin/env bash
set -euo pipefail

# One-off production DB backup helper, mirroring remote_db.sh's credential
# handling (reads .local/remote-db.cnf [client] section, never echoes the
# password) but runs mysqldump instead of an interactive/one-shot query.
#
# Usage:
#   dev/tools/remote_db_backup.sh path/to/output.sql.gz [table-list-file]
#
# table-list-file (optional): a file with one table name per line. Dumps only
# those tables instead of the whole database. Useful for chunking a dump into
# short-lived connections when the network path can't sustain one long-running
# TLS session (observed here: WSL2's outbound NAT appears to kill idle/long
# connections after roughly two minutes, truncating a full whole-DB dump).

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DEFAULTS_FILE="${ROOT_DIR}/.local/remote-db.cnf"
CONTAINER_NAME="ddev-magento-web"

if [[ $# -lt 1 || $# -gt 2 ]]; then
    echo "Usage: $0 path/to/output.sql.gz [table-list-file]" >&2
    exit 1
fi
OUT_FILE="$1"
TABLE_LIST_FILE="${2:-}"
TABLES=()
if [[ -n "${TABLE_LIST_FILE}" ]]; then
    if [[ ! -f "${TABLE_LIST_FILE}" ]]; then
        echo "Table list file not found: ${TABLE_LIST_FILE}" >&2
        exit 1
    fi
    mapfile -t TABLES < "${TABLE_LIST_FILE}"
fi

read_cnf_value() {
    local key="$1"
    awk -F= -v wanted="$key" '
        /^\[client\]$/ { in_client=1; next }
        /^\[/ { in_client=0 }
        in_client && $1 ~ "^[[:space:]]*" wanted "[[:space:]]*$" {
            val=$0
            sub(/^[^=]*=/, "", val)
            gsub(/^[[:space:]]+|[[:space:]]+$/, "", val)
            print val
            exit
        }
    ' "${DEFAULTS_FILE}"
}

if [[ ! -f "${DEFAULTS_FILE}" ]]; then
    echo "Missing credentials file: ${DEFAULTS_FILE}" >&2
    exit 1
fi

DB_HOST="$(read_cnf_value host)"
DB_PORT="$(read_cnf_value port)"
DB_USER="$(read_cnf_value user)"
DB_PASSWORD="$(read_cnf_value password)"
DB_NAME="$(read_cnf_value database)"

if [[ -z "${DB_HOST}" || -z "${DB_PORT}" || -z "${DB_USER}" || -z "${DB_PASSWORD}" || -z "${DB_NAME}" ]]; then
    echo "Missing one or more required [client] values in ${DEFAULTS_FILE}" >&2
    exit 1
fi

echo "Dumping ${DB_NAME}@${DB_HOST}:${DB_PORT} -> ${OUT_FILE} (${#TABLES[@]} explicit tables)" >&2

docker exec -e MYSQL_PWD="${DB_PASSWORD}" "${CONTAINER_NAME}" \
    mysqldump --no-defaults --protocol=TCP --host="${DB_HOST}" --port="${DB_PORT}" --user="${DB_USER}" \
    --single-transaction --quick --routines --triggers --no-tablespaces \
    "${DB_NAME}" "${TABLES[@]}" | gzip > "${OUT_FILE}"

echo "Backup written: ${OUT_FILE} ($(du -h "${OUT_FILE}" | cut -f1))" >&2
