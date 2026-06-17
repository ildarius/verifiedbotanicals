# DB Dump Definer Stripper

## Purpose

This project now includes a local helper script for preparing Magento database dumps for import into a remote host where MySQL may reject `DEFINER` and `SQL SECURITY DEFINER` fragments.

Primary use case:

- migrate the local Magento database to the remote WHM/cPanel host without import failures related to missing `SUPER` or `SET_USER_ID` privileges

Script path:

- [backups/strip_dump_definers.py](/home/ildar/projects/magento/backups/strip_dump_definers.py)

## What Problem It Solves

Some MySQL dumps include trigger/view fragments such as:

- `/*!50017 DEFINER=\`user\`@\`host\`*/`
- `/*!50013 DEFINER=\`user\`@\`host\` SQL SECURITY DEFINER */`
- `SQL SECURITY DEFINER`

These can break import on a remote host when the destination MySQL user does not have the required elevated privileges.

This helper strips only those definer/security fragments and leaves the rest of the dump content unchanged.

## Supported Input

The script accepts:

- plain `.sql`
- gzipped `.sql.gz`

It prompts interactively for the input filename when run from CLI.

Relative filenames are resolved inside `backups/`, but an absolute path can also be entered.

## Output Behavior

The script writes a new `.sql` file next to the source dump.

Output naming:

- input `magento-db-20260601.sql` -> output `DEFINER_STRIPPED_magento-db-20260601.sql`
- input `magento-db-20260601.sql.gz` -> output `DEFINER_STRIPPED_magento-db-20260601.sql`

The original dump is not modified.

## What It Removes

### 1. Definer-only conditional comments

Example:

```sql
/*!50017 DEFINER=`db`@`%`*/
```

### 2. Combined definer + SQL security conditional comments

Example:

```sql
/*!50013 DEFINER=`db`@`localhost` SQL SECURITY DEFINER */
```

### 3. Remaining `SQL SECURITY DEFINER` phrases

As a cleanup pass, the script removes any remaining literal `SQL SECURITY DEFINER`.

## What It Does Not Change

The script does not attempt to parse SQL structurally.

It does not intentionally alter:

- trigger bodies
- view query bodies
- delimiters
- line order
- unrelated SQL content

## Usage

From the project root:

```bash
python3 backups/strip_dump_definers.py
```

When prompted, enter a dump filename such as:

```text
magento-db-20260601-083033.sql.gz
```

or:

```text
magento-db-20260428-214114.sql
```

## Validation Behavior

The script reports:

- count of removed definer-only comments
- count of removed definer+sql-security comments
- count of removed leftover `SQL SECURITY DEFINER` phrases

It also:

- warns if zero matches were removed
- rescans the output for `DEFINER=` and `SQL SECURITY DEFINER`
- reports remaining line numbers if any are found

Exit behavior:

- `0` when stripping succeeds and the output rescans clean
- `2` when output is written but post-scan still finds remaining fragments
- `1` for input/usage errors

## Expected Success Checks

After running the script, these should return no matches:

```bash
grep 'DEFINER=' backups/DEFINER_STRIPPED_<dump-name>.sql
grep 'SQL SECURITY DEFINER' backups/DEFINER_STRIPPED_<dump-name>.sql
```

If those return nothing, the dump is ready for the remote import step.

## Recommended Remote-Migration Workflow

1. Create a full local DB dump.
2. Run [backups/strip_dump_definers.py](/home/ildar/projects/magento/backups/strip_dump_definers.py) against that dump.
3. Verify the output has no remaining `DEFINER=` or `SQL SECURITY DEFINER`.
4. Copy the stripped output file to the remote host.
5. Import the stripped file into the destination MySQL database.

This helper is part of the remote-host migration workflow documented in [dev/plans/cpanel-remote-migration-plan.md](/home/ildar/projects/magento/dev/plans/cpanel-remote-migration-plan.md).
