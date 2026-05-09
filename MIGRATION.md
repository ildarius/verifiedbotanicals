# Magento Project Migration Guide (Git Workflow)

This guide describes how to migrate this DDEV-managed Magento 2.4.7 project to another machine by pulling from the Git repository.

## Prerequisites on the New Machine

1.  **Docker Desktop** (or Colima/OrbStack on macOS).
2.  **DDEV** installed.
3.  **Git** installed and configured with access to this repository.
4.  **Magento Marketplace Credentials:** Ensure you have your `auth.json` or keys ready.

## Step 1: Prepare the Source Data (Source Machine)

Since you are pulling the code via Git, you only need to manually transfer files that are **not** tracked by Git.

1.  **Export the latest database:**
    ```bash
    ddev export-db --file=backups/migration-fresh.sql.gz
    ```

2.  **Identify non-git files to transfer:**
    You must manually copy these files/directories from the source machine:
    - `app/etc/env.php` (Critical: Contains the encryption key and DB settings)
    - `app/etc/config.php` (Contains module enablement status)
    - `pub/media/` (Contains product images, etc. Large folder, transfer separately)
    - `auth.json` (Required for Composer to pull Magento packages)
    - `backups/migration-fresh.sql.gz` (The DB dump)

## Step 2: Clone and Setup (New Machine)

1.  **Clone the repository:**
    ```bash
    git clone <repository_url> magento-project
    cd magento-project
    ```

2.  **Restore non-git files:**
    Place the files you collected in Step 1 into their respective locations in the `magento-project` folder.

3.  **Initialize DDEV:**
    ```bash
    ddev start
    ```

## Step 3: Handle Dependencies (The `vendor` folder)

The `vendor/` folder is not tracked by Git. You must rebuild it:

1.  **Run Composer Install:**
    DDEV will use the PHP version defined in `.ddev/config.yaml` (8.2).
    ```bash
    ddev composer install
    ```
    *Note: This requires your `auth.json` to be present in the project root or your global composer home.*

## Step 4: Import the Database

```bash
ddev import-db --file=backups/migration-fresh.sql.gz
```

## Step 5: Final Magento Setup

Run these commands to ensure the environment is fully synchronized:

```bash
ddev exec php bin/magento setup:upgrade
ddev exec php bin/magento setup:di:compile
ddev exec php bin/magento setup:static-content:deploy -f
ddev exec php bin/magento cache:flush
```

## Step 6: Verify

1.  **Site URL:** `https://magento.ddev.site/`
2.  **Admin:** `https://magento.ddev.site/admin`

---

## Summary of Ignored Files (Manual Transfer Required)

According to `.gitignore`, these items are **not** in Git and must be moved manually:
- `backups/`
- `app/etc/config.php`
- `app/etc/env.php`
- `auth.json`
- `pub/media/` (mostly)
- `node_modules/` (if using Grunt/Gulp, run `npm install` on the new machine)
