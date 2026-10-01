# Admin Session Lifetime (48h)

Set 2026-10-01 so an admin stays logged in (after password + 2FA code) for 48 hours of inactivity.

## What was changed

- `admin/security/session_lifetime` = `172800` (DB config, default scope). Every admin request
  re-issues the `admin` cookie with this duration (`Magento\Backend\Model\Auth\Session::prolong()`),
  so it is a 48h idle timeout, not an absolute one.
- `app/etc/env.php` (gitignored): `session.save_path` = `/home/verifiedbota/public_html/var/session`.
- User crontab: hourly `find .../var/session -name 'sess_*' -mmin +4320 -delete` (72h).

## Why sessions moved out of the cPanel session dir

`.user.ini` / `php.ini` point `session.save_path` at `/var/cpanel/php/sessions/ea-php83`, and cPanel's
root-owned cleaner deletes files there after `session.gc_maxlifetime` = 1440s (24 min). PHP-FPM locks
`gc_maxlifetime` and `gc_probability` (`php_admin_value`), so they can't be raised from Magento, but
`save_path` can be overridden. In `var/session` cPanel never touches the files and PHP GC is off
(`gc_probability=0`), so the cron above is what cleans them up. Keep its threshold above the admin lifetime.

Moving the path logged out every session that existed before the change, including any guest carts.

## CLI note

Plain `php` lacks the sodium extension and fatals when it decrypts config. For scripts that bootstrap
Magento, use the same PHP the cron uses:
`PHP_INI_SCAN_DIR=/opt/cpanel/ea-php83/root/etc/php.d:/home/verifiedbota/php-cli.d /opt/cpanel/ea-php83/root/usr/bin/php`.
