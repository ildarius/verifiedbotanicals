#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DEFAULTS_FILE="${ROOT_DIR}/var/tmp/remote-db.cnf"
CONTAINER_NAME="ddev-magento-web"

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

usage() {
    cat <<EOF
Usage:
  dev/tools/remote_db.sh
  dev/tools/remote_db.sh --sql "SELECT NOW();"
  dev/tools/remote_db.sh --file path/to/query.sql

Credentials:
  Write remote DB credentials to:
    ${DEFAULTS_FILE}

  Use MySQL option-file format:
    [client]
    host=example.com
    port=3306
    user=example_user
    password=example_password
    database=example_db

Notes:
  - The credentials file is ignored by git because it lives under var/tmp/.
  - Queries run through the ${CONTAINER_NAME} container, not the host mysql client.
  - Running with no arguments opens an interactive mysql session.
  - --sql runs one query and prints the result.
  - --file reads SQL from a file.
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    usage
    exit 0
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "docker not found in PATH" >&2
    exit 1
fi

if [[ ! -f "${DEFAULTS_FILE}" ]]; then
    echo "Missing credentials file: ${DEFAULTS_FILE}" >&2
    echo "Copy dev/tools/remote-db.cnf.example to var/tmp/remote-db.cnf and fill in the remote credentials." >&2
    exit 1
fi

if ! docker exec "${CONTAINER_NAME}" sh -lc 'command -v mysql >/dev/null 2>&1' >/dev/null 2>&1; then
    echo "Could not find mysql client in ${CONTAINER_NAME}. Make sure DDEV is running." >&2
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

MYSQL_ARGS=(
    "--no-defaults"
    "--protocol=TCP"
    "--host=${DB_HOST}"
    "--port=${DB_PORT}"
    "--user=${DB_USER}"
    "--default-character-set=utf8mb4"
    "${DB_NAME}"
)

case "${1:-}" in
    "")
        if [[ -t 0 && -t 1 ]]; then
            exec docker exec -it -e MYSQL_HISTFILE=/dev/null -e MYSQL_PWD="${DB_PASSWORD}" "${CONTAINER_NAME}" \
                mysql "${MYSQL_ARGS[@]}"
        fi
        exec docker exec -i -e MYSQL_HISTFILE=/dev/null -e MYSQL_PWD="${DB_PASSWORD}" "${CONTAINER_NAME}" \
            mysql "${MYSQL_ARGS[@]}"
        ;;
    --sql)
        if [[ $# -lt 2 ]]; then
            echo "--sql requires a query string" >&2
            usage >&2
            exit 1
        fi
        exec docker exec -i -e MYSQL_HISTFILE=/dev/null -e MYSQL_PWD="${DB_PASSWORD}" "${CONTAINER_NAME}" \
            mysql "${MYSQL_ARGS[@]}" -e "$2"
        ;;
    --file)
        if [[ $# -lt 2 ]]; then
            echo "--file requires a SQL file path" >&2
            usage >&2
            exit 1
        fi
        if [[ ! -f "$2" ]]; then
            echo "SQL file not found: $2" >&2
            exit 1
        fi
        exec docker exec -i -e MYSQL_HISTFILE=/dev/null -e MYSQL_PWD="${DB_PASSWORD}" "${CONTAINER_NAME}" \
            mysql "${MYSQL_ARGS[@]}" < "$2"
        ;;
    *)
        echo "Unknown argument: $1" >&2
        usage >&2
        exit 1
        ;;
esac
