#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BACKUP_DIR="${ROOT_DIR}/backups"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LABEL="${1:-manual}"
OUTPUT_FILE="${BACKUP_DIR}/magento-db-${LABEL}-${TIMESTAMP}.sql.gz"
HOMEPAGE_CAPTURE_SCRIPT="${ROOT_DIR}/dev/tools/capture_homepage.sh"
ENV_FILE="${ROOT_DIR}/app/etc/env.php"

mkdir -p "${BACKUP_DIR}"

run_ddev_backup() {
    echo "Creating DDEV database backup at ${OUTPUT_FILE}"
    docker exec ddev-magento-db mysqldump -u db -pdb db | gzip > "${OUTPUT_FILE}"
    echo "Backup complete: ${OUTPUT_FILE}"

    echo "Capturing homepage screenshot via ${HOMEPAGE_CAPTURE_SCRIPT}"
    "${HOMEPAGE_CAPTURE_SCRIPT}"
    echo "Homepage capture complete"
}

load_remote_db_config() {
    if [[ ! -f "${ENV_FILE}" ]]; then
        echo "Missing Magento env file: ${ENV_FILE}" >&2
        exit 1
    fi

    mapfile -t DB_CONFIG < <(
        php -r '
        $env = include $argv[1];
        $db = $env["db"]["connection"]["default"] ?? null;
        if (!$db) {
            fwrite(STDERR, "Could not find db.connection.default in app/etc/env.php\n");
            exit(1);
        }
        foreach (["host", "dbname", "username", "password", "port"] as $key) {
            echo (string)($db[$key] ?? ""), PHP_EOL;
        }
        ' "${ENV_FILE}"
    )

    DB_HOST="${DB_CONFIG[0]}"
    DB_NAME="${DB_CONFIG[1]}"
    DB_USER="${DB_CONFIG[2]}"
    DB_PASSWORD="${DB_CONFIG[3]}"
    DB_PORT="${DB_CONFIG[4]:-}"

    if [[ -z "${DB_HOST}" || -z "${DB_NAME}" || -z "${DB_USER}" ]]; then
        echo "Incomplete DB config in ${ENV_FILE}" >&2
        exit 1
    fi
}

run_remote_backup() {
    if ! command -v mysqldump >/dev/null 2>&1; then
        echo "mysqldump not found in PATH" >&2
        exit 1
    fi

    load_remote_db_config

    local defaults_file
    defaults_file="$(mktemp)"
    trap 'rm -f "${defaults_file}"' RETURN

    cat > "${defaults_file}" <<EOF
[client]
host=${DB_HOST}
user=${DB_USER}
password=${DB_PASSWORD}
port=${DB_PORT:-3306}
EOF

    echo "Creating host database backup at ${OUTPUT_FILE}"
    mysqldump \
        --defaults-extra-file="${defaults_file}" \
        --single-transaction \
        --routines \
        --triggers \
        "${DB_NAME}" | gzip > "${OUTPUT_FILE}"
    echo "Backup complete: ${OUTPUT_FILE}"
    echo "Skipping homepage capture outside DDEV"
}

if command -v docker >/dev/null 2>&1 && docker exec ddev-magento-db true >/dev/null 2>&1; then
    run_ddev_backup
else
    run_remote_backup
fi
