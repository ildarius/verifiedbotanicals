# Production Security Incident Tracker — 2026-09-28

Status: **application containment complete; edge rules hardened; DB password rotated; vendor tree verified; 72-hour monitoring running. Owner/hosting actions remain: encryption-key rotation (highest priority), remaining credentials, off-host backup copy, host-level checks.**

This is the active tracker for the September 2026 Magento production incident. It deliberately omits credentials, request payloads, and other sensitive data. Preserve the evidence referenced below; do not restore files from the contaminated backup.

## Executive finding

The production codebase was compromised. At the start of this investigation on 2026-09-28, `app/etc/env.php` contained a request-triggered, obfuscated PHP backdoor before the normal Magento configuration array. The implant was created on 2026-09-10 and was executed by an attacker. It was preserved in a sealed evidence bundle and removed on 2026-09-28. The former live file is byte-identical to the `env.php` captured in the 2026-09-24 upgrade backup, indicating that the upgrade restored an already-infected configuration file rather than establishing a newly written implant.

The evidence most directly attributes the initial compromise to the **StyleSmuggler** chain, CVE-2026-75650 / APSB26-146. The prior local note characterized the event as PolyShell; the quarantined `custom_options` files remain evidence of separate upload/polyglot activity, but the access-log and Magento-report evidence ties the `env.php` implant to StyleSmuggler.

Magento Open Source is at `2.4.9`, but the required Adobe `VULN-39341_249.patch` hotfix is not applied: an official patch forward dry-run succeeds against the current source. Version 2.4.9 alone is not sufficient for CVE-2026-75650.

## Evidence and indicators

| Item | Finding |
|---|---|
| Live implant | [app/etc/env.php](/home/verifiedbota/public_html/app/etc/env.php:2), lines 2–23; normal configuration begins at line 25. SHA-256: `0f30bd9ab7365bf572986ad7a3cd1975856157b7dbe615ab9d7340d7760c423d`. |
| Contaminated backup | [backups/dev3-pre-2.4.9-20260924_190143/app/etc/env.php](/home/verifiedbota/public_html/backups/dev3-pre-2.4.9-20260924_190143/app/etc/env.php:2) is byte-identical to the live implant. Do not use it as a restore source. |
| Execution evidence | [PHP error log](/home/verifiedbota/logs/verifiedbotanicals_com.php.error.log:297) records attacker code executing through `env.php(23): eval()` on 2026-09-10. |
| Archive | [var/tmp/incident-20260924-media-executable-files.tar.gz](/home/verifiedbota/public_html/var/tmp/incident-20260924-media-executable-files.tar.gz), 216 unique quarantined paths, SHA-256 `3d569a1eec3800dd97384a9b8c03a4ee99323d2c4988b59bf1315279d92b0a72`. |
| Initial attack | On 2026-09-10, access logs show an attacker using `/customer/address_file/upload` and `/graphql?styles[...]`, then the implant timestamp appears at 20:50 EDT. |
| Persistence | Prior investigation recorded a rogue `/var/tmp/.sysd-verifi/sync.sh` cron. It is absent now; the current user crontab contains only Magento cron. |
| Renewed attempts | 43 StyleSmuggler-shaped GraphQL requests reached the site on 2026-09-26–27 from multiple sources. No second persistence file has been confirmed. |
| Current media scan | `pub/media` currently has zero script-like extensions and zero embedded PHP markers. |

## Scope and confidence

- **Confirmed:** the `env.php` backdoor is currently present and was executed on 2026-09-10.
- **Confirmed:** hostile exploit attempts continued after the 2026-09-24 cleanup.
- **Likely:** the current implant was restored from the contaminated September 24 backup, not written anew after cleanup.
- **Not established:** successful new persistence after the 2026-09-24 cleanup. The missing CVE-2026-75650 hotfix means it cannot be ruled out.

## Completed checks

- [x] Preserved existing upload evidence archive and verified its SHA-256.
- [x] Identified and documented the live `env.php` backdoor without disclosing secrets.
- [x] Verified the live and September 24 backup `env.php` files are byte-identical.
- [x] Reviewed current user cron, PHP auto-prepend/append settings, owned temporary paths, and running processes for the known persistence.
- [x] Checked public media for executable extensions and embedded PHP markers: zero found.
- [x] Confirmed `Local_PolyShellGuard` is enabled and media deny rules match Git.
- [x] Confirmed the exact backdoor signature has no second copy in the current application tree.
- [x] Confirmed that `VULN-39341_249.patch` has not been applied by an official forward dry-run.
- [x] Identified archived post-cleanup StyleSmuggler attempts on 2026-09-26–27.

## 2026-09-28 remediation record

- Created [the preservation bundle](/home/verifiedbota/public_html/var/tmp/incident-20260928-preservation.tar.gz), SHA-256 `bb41851dfaf295922e4a5d9114d882645d73c040f5a82a735a2dfb647e171d61`; its containing directory is mode 0700 and contents are mode 0600.
- Removed the malicious `env.php` preamble, retained the legitimate configuration array, linted it successfully, and restricted the file to mode 0600.
- Applied and reverse-verified Adobe's official `VULN-39341_249.patch`, SHA-256 `8df23110e1909e2d5f0c2f0ecbe7f803670dfb0c9c3d15a527371c04b7825bf7`, across all nine affected paths. The tracked [hotfix](/home/verifiedbota/public_html/m2-hotfixes/VULN-39341_249.patch) and [verification/apply script](/home/verifiedbota/public_html/dev/tools/apply-vuln-39341-hotfix.sh) must be used after every Composer vendor rebuild.
- Installed temporary root and `pub` request blocks for the observed StyleSmuggler query pattern. A harmless external probe returned HTTP 403; the storefront returned HTTP 200.
- Restarted the two long-running Magento consumers as detached account-owned processes with a 1 GB CLI memory limit. The account has no visible PHP-FPM pool; the cPanel PHP-FPM restart API requires hosting/root privileges.
- Re-scanned `pub/media`: zero script-like files and zero embedded PHP markers. The known backdoor marker has zero remaining matches outside preserved evidence and contaminated backups.
- Magento CLI reports version 2.4.9 and maintenance mode disabled. Composer manifest validation completed with only pre-existing schema warnings (version field, root PSR-0, and an exact Magento version constraint).

## 2026-09-28 afternoon remediation record — Claude

- **PHP handler / OPcache.** The site does run under PHP-FPM (ea-php83 pool `verifiedbotanicals_com`, on-demand workers). The earlier "no visible pool" note was wrong. The FPM master process started on 2026-09-26, before the fix. `opcache.validate_timestamps=1` and `revalidate_freq=2`, with no auto-prepend or auto-append. However, the shared OPcache held a stale `app/etc/env.php` entry with timestamp 0; OPcache never revalidates such entries. That entry was force-invalidated at about 17:03 EDT. Three other timestamp-0 entries and one stale entry for the patched `vendor/magento/framework/Webapi/ErrorProcessor.php` remain. Bulk invalidation from the account was not permitted, so an `opcache_reset()` or an ea-php83 FPM reload is still needed from hosting/root.
- **Edge rules made durable and hardened** in both `.htaccess` and `pub/.htaccess`. The earlier rules had two gaps:
  - Blocked requests were handed via ErrorDocument back into Magento, which rendered a full page with a 403 status. Blocks now return a static 9-byte `Forbidden`.
  - Bypasses found and closed: `&+styles[..]` (PHP strips the leading space), `styles%00[..]`, plain `styles=`, and the upload controller reached as `/customer/address_file/upload/index/…`, `/index.php/…` or a store-code prefix. It is now blocked for all methods.
  - Added a block for template directives in query strings (`{{block …}}`, `{{styles …}}`, etc.), which appear in the observed attack traffic. A benign probe matrix returns 403 for every variant. Home, login, search, GraphQL `storeConfig` and nested-filter queries, and static assets return 200.
  - Limitation: mod_rewrite cannot inspect POST bodies. ModSecurity is not exposed to this account.
- **Traffic scale.** September logs contain 3,924 `styles[...]` requests, mostly 2026-09-10 → 2026-09-23. Many returned 200 while the hotfix was missing. No attack-shaped request reached the site after the 08:01 repair (all Sep 28 hits are our own probes).
- **Account-level persistence review (clean):**
  - User crontab: only the Magento cron plus the new monitor.
  - `/etc/cron.d` and `/etc/cron.daily`: stock cPanel/OS entries.
  - systemd timers and units: stock cPanel/OS. The `initialize_*_plugin` timers are cPanel's own.
  - No user systemd units. `.bashrc` and `.profile` are stock. `~/.local/bin` holds only the Claude CLI link. `php-cli.d` has only memory and time settings.
  - One SSH key (`ildar@windows`, file modified 2026-09-28 09:33 — owner to confirm).
  - No cPanel API tokens, no extra FTP or WebDAV accounts, no domain forwarders. The only mail forwarder and filter send `robert@` to the owner's Gmail addresses.
  - Account processes: only PHP-FPM, Magento consumers, sshd and tooling.
  - Outbound sockets cannot be attributed to processes without root.
- **Magento data review (clean):**
  - One Admin user (`playwright-admin`, Administrators). All Admin traffic in September came from the allowlisted 24.157.155.x address, plus 4 localhost curl checks during the 09-24 upgrade.
  - Only the stock "Magento Analytics user" integration and its single access token. No Admin or customer OAuth tokens.
  - CMS: 5 pages and 93 blocks. The only script-like hits are a Google Maps iframe and an escaped `javascript:void(0)`. No custom email/newsletter templates, widgets or layout updates.
  - `design/head/includes` and the absolute footer are empty. Base URLs are unchanged since June.
  - The 2026-09-20 payment config change was saved from the owner's IP. The 2026-09-24 affiliate settings match the owner's affiliate setup work.
  - The `Magento_TwoFactorAuth` tables are absent, so Admin MFA is **not** enforced.
- **Credential finding.** The Magento crypt key signs Admin/customer JWTs. Anyone holding the old key can forge Admin API tokens, so encryption-key rotation is the most urgent remaining credential item.
- **Database password rotated** in place for `verifiedbota_magento2`. The user is kept because it is the DEFINER of 57 triggers and 1 view. The new secret was generated on the host and never displayed. `env.php` was updated atomically (mode 0600), verified by a live connection, and the storefront returned 200 on all checks. The two long-running consumers were **not** restarted (the restart was not permitted from this session). Restart them so a reconnect does not use the old password (see tasks).
- **Clean vendor tree built and verified** at `~/vendor-rebuild-20260928b`, outside the document root:
  - Built from `composer.lock` with the official Composer 2.10.2 phar (SHA-256 matches getcomposer.org; `var/tmp/composer.phar` is byte-identical to it) and an empty cache.
  - After applying the hotfix, live `vendor/` matches the clean build exactly, except Composer autoloader hash suffixes and entry ordering (same entries). The magento-base files (`lib/`, `setup/`, `bin/`, `pub/*.php`, `pub/errors/`, `app/etc/di.xml`, bootstrap) also match.
  - All non-vendor PHP is tracked in Git and clean, except `env.php`.
- **Bug fixed in `dev/tools/apply-vuln-39341-hotfix.sh`.** Its `--batch -R --dry-run` check succeeded on an unpatched tree (`--batch` assumes reversed patches), so after a rebuild it would have reported "already applied" and skipped the hotfix. It now uses `--force` and re-verifies after applying. Verified on live (patched) and on staging (unpatched → applied).
- **Local backup bundle** at `~/incident-backup-20260928/`, outside the document root, directory mode 0700, files 0400:
  - DB dump: 875 tables, dump completed.
  - Code, media and config tar: excludes `vendor/`, `generated/`, `pub/static`, caches and the contaminated `backups/`.
  - Logs/evidence tar, plus `composer.json` and `composer.lock`.
  - `MANIFEST.sha256`. The DB dump was taken *before* the DB password rotation.
  - It is not yet off-host or immutable.
- **72-hour monitor** installed: `~/incident-monitor/monitor.sh`, run every 15 minutes from the user crontab until 2026-10-05. It is read-only and prints only on ALERT; alerts go to `~/incident-monitor/alerts.log`. It checks:
  - `env.php`, both `.htaccess` files, `pub/media/.htaccess`, crontab, SSH keys and shell rc hashes.
  - New script-like files in writable paths and `/tmp`.
  - PHP markers in new media files.
  - Tracked-code drift, and whether VULN-39341 is still applied.
  - Unblocked StyleSmuggler, template-directive or upload-endpoint requests.
  - PolyShellGuard/security events in `system.log` and `exception.log`.
  - Unrecognised account processes.

  Re-run `monitor.sh --baseline` after any intentional change (e.g. committing the `.htaccess` edits or rotating the crypt key).
- **Side observation.** Magento SMTP authentication to Gmail has failed with BadCredentials since 2026-09-25, so transactional email is not being delivered. The app password was probably rotated at Google without updating Magento.

## 2026-09-29 remediation record — owner with Claude

- Consumers restarted (15:19 EDT). All 7,579 OPcache entries for the site invalidated; the re-check showed 0 stale entries.
- Backup verified on the owner's PC, encrypted (7-Zip AES-256) and stored in a Google-Drive-synced folder. The server copy and the unencrypted PC copy were deleted.
- `autotest_ro` and `autotest_cleanup` DB passwords rotated; the hand-off file was deleted.
- **Maintenance window, about 15:55–16:39 EDT (store 503 to the public; owner IP exempt).** Encryption key rotated and old keys retired. All Admin and customer JWTs revoked and Admin sessions ended. Admin email set to the owner's Gmail. SMTP fixed with a new Gmail app password (Magento and `~/.env`), and a test email was delivered. The rollback `env.php` copy was deleted from the server and the owner's PC.
- **New edge rule** (both `.htaccess` files): the REST admin-token endpoint (all store-code, async, bulk and `index.php` forms) and the SOAP `integrationAdminTokenService` are allowed only from 24.157.155.x. Probed from outside: 403. This covers the gap left by not enabling 2FA.
- After the window, the storefront, login, search, GraphQL, static files and customer REST all behave normally. The admin UI, admin-token API, `styles`, template-directive and upload probes all return 403.
- Monitor: all alerts since the 09-28 baseline were reviewed. Each was an owner action or planned change (nano sessions, owner webmail, `env.php` key rotation, `.bashrc`). They are archived in `~/incident-monitor/alerts-2026-09-28_29-reviewed.log`. Webmail from the owner's IP is now ignored. Re-baselined 16:39 EDT.

- **2026-09-30: Mirasvit Affiliate reinstalled.** It had been dropped unintentionally by the 2.4.9 upgrade commit `9bc447ea` (2026-09-24), so the extension was absent from the live site from about 19:00 that day. Its data survived: 1 program, 1 account, 1 coupon, 0 transactions ever, and 0 orders were placed in the gap. The cart rule "Affiliate coupons - 20% off" was active throughout the gap, so the coupon worked without tracking. `/affiliate/account/` now responds again (302 to login).

## Active remediation tasks

### Application containment and repair — Codex

- [x] Create a timestamped preservation bundle of the live implant, relevant logs, and relevant Magento reports before modifying runtime files.
- [x] Put a temporary web-server-level block in place for the observed malicious request patterns while remediation is in progress.
- [x] Remove the malicious preamble from `app/etc/env.php`, preserving only the legitimate Magento configuration array.
- [x] Apply Adobe's official `VULN-39341_249.patch` for CVE-2026-75650 and record its hash and verification result.
- [x] Verify that no executable files or PHP markers remain in public media after the repair.
- [x] Restart the two Magento long-running consumers so no worker retains the old bootstrap state.
- [x] Clear stale OPcache state (in place of a hosting-side PHP handler reload). *(Done 2026-09-29: owner invalidated all 7,579 site entries; re-check showed 0 stale and 0 timestamp-0 entries, env.php current.)* Original item: obtain hosting-provider confirmation that the web PHP handler has been reloaded. *(Partly done 09-28: FPM is in use; `env.php` OPcache entry force-invalidated; timestamp validation is on. Still needed: root `opcache_reset()` or ea-php83 FPM reload, because 3 timestamp-0 entries and a stale `ErrorProcessor.php` entry remain.)*
- [x] Build and verify a clean vendor tree outside the live document root. *(Done 09-28 at `~/vendor-rebuild-20260928b`, hotfix applied with the fixed script; live `vendor/` verified identical apart from autoloader hashes/order.)*
- [x] Atomic vendor swap. *(Done 2026-09-30 12:18–12:19:43 EDT, in a 1m45s maintenance window, together with the Mirasvit reinstall. Live `vendor/` is now the verified-clean Composer build (`~/vendor-rebuild-20260928b`) plus 5 new packages: `mirasvit/module-affiliate` 1.8.23, `mirasvit/module-core` 1.7.22, `geoip2/geoip2`, `maxmind-db/reader`, `maxmind/web-service-common`. The dry run and the real install showed 0 updates and 0 removals of existing packages, and the new Mirasvit code was scanned (no `eval`/shell). Hotfix script: "already applied". `setup:upgrade` and `setup:di:compile` succeeded, both `.htaccess` rule sets are intact, and there have been no new errors in `exception.log`/`system.log`. Probes after the swap: storefront 200, all edge blocks 403. The previous tree is kept as `vendor.pre-mirasvit`: remove it after 72 hours without issues (≥ 2026-10-03). Backups of the prior `composer.json`, `composer.lock` and `config.php` are in `~/pre-mirasvit-backup/`. Consumers were restarted at 12:20:54 and the monitor re-baselined at 12:21.)*
- [x] Run narrow Magento/configuration validation and update this tracker with results.
- [x] Fix `apply-vuln-39341-hotfix.sh` so its "already applied" check cannot pass on an unpatched tree.
- [x] Restart the two long-running consumers after the 09-28 DB password rotation. *(Done 2026-09-29 15:19 EDT; logs clean.)* They still hold the pre-rotation config and will fail on reconnect. Kill PIDs 197271 and 197273, then start each consumer again with the same arguments (`queue:consumers:start <name> --single-thread --max-messages=10000`, PHP 8.3, `-d memory_limit=1G`). Alternatively, let Magento cron's `consumers_runner` restart them.

### Hosting and account actions — owner / hosting provider

- [x] Add durable web-server blocks for StyleSmuggler request patterns. *(Hardened 09-28 in both `.htaccess` files: bypasses closed, template-directive rule added, static 403 body. Monitor alerts on any non-403 attack-shaped request.)*
- [ ] Add host-level WAF coverage (ModSecurity / hosting WAF rule for `styles` query arrays, `{{…}}` directives in URLs or bodies, and `customer/address_file/upload`). mod_rewrite cannot see POST bodies, and ModSecurity is not exposed to the account.
- [ ] Restrict origin access during remediation. No CDN or proxy is in front (DNS points straight at 66.206.23.226), so the choices are: maintenance mode with an IP allowlist (takes the store offline), or putting a CDN/WAF in front and firewalling the origin to it. **This is the owner's decision.**
- [ ] Inspect host-level persistence. *(Account-visible scope done 09-28: user crontab, `/etc/cron.d`, `/etc/cron.daily`, systemd timers and units, user systemd, shell rc files, SSH keys, cPanel API tokens, FTP/WebDAV/sub-accounts, mail forwarders and filters. All clean. Still needs root: other users' crontabs and `/var/spool/cron`, at jobs, process-attributed outbound connections (`ss -tupn` as root), `/etc/rc.local` and `/etc/profile.d`, and the FPM pool config for per-pool `php_admin_value` overrides.)*
- [ ] Rotate cPanel, SSH, SFTP/FTP, deployment, Git, mail, and backup credentials; remove unknown access and keys. Confirm the single SSH key `ildar@windows` (authorized_keys changed 2026-09-28 09:33) is yours.
  - [x] cPanel account password (also covers SSH password, FTP and the default mailbox): changed by the owner on 2026-09-28.
  - [x] Mailbox passwords for `robert@` and `verifiedbota@`: changed on 2026-09-29; the `~/etc/verifiedbotanicals.com/shadow` entries show today's date.
  - [x] `~/.bash_history` scrubbed on 2026-09-29. One line held a plaintext SMTP app password (the old, revoked Gmail app password), and it was removed. No other secret-like lines were found.
  - [x] SSH: the only authorized key (`SHA256:NFDJPgMQ…XhQk`, `ildar@windows`) matches the owner's PC key. The server's orphan `id_ed25519.pub` (`SNXhKmZW…`, private key absent) is not registered on GitHub.
  - [x] GitHub (2026-09-29): 6 account SSH keys reviewed. The two added during the attack window (`dev3.verifiedbotanicals.com linux`, Sep 8; `seo.bizousoft.com linux box`, Sep 20) were confirmed by the owner. No personal access tokens, no OAuth apps. The owner had already deleted some keys after the hack.
  - [x] Claude login on the server: first installed 2026-09-28 09:18, after the backdoor was removed. Never exposed.
  - [~] Codex (ChatGPT login) in `~/.codex/auth.json` was present throughout the attack window. `codex logout` was run on 2026-09-29, but it only deletes the local file, so a copied refresh token would not be revoked. **Owner decision:** do not use "Log out of all devices". ChatGPT Settings → Security shows no foreign sessions. Watch Codex/ChatGPT usage, and use "Log out of all devices" if anything unexpected appears.
- [x] Copy `~/incident-backup-20260928/` off-host. *(Done 2026-09-29. All 5 files verified against `MANIFEST.sha256` on the owner's PC; archived as `incident-backup-20260928.7z` (7-Zip AES-256, encrypted file names, 1,225,865,794 bytes). Its password is kept only in the owner's password manager, never in any file. Stored in a Google-Drive-synced folder on the owner's PC (`…\Sunny Kratom\site\misc\backup`).)*
- [ ] Make the backup immutable, or keep an offline copy. Google Drive is off-host but **not** immutable: a deletion syncs, and trash and version history last only 30 days. Copy the `.7z` to a USB drive kept unplugged, or to S3/B2 with Object Lock. Do not treat the September 24 `env.php` backup as clean.

### Magento and service credentials — owner

- [x] **Highest priority:** rotate the Magento encryption key. *(Done 2026-09-29 in maintenance mode. `encryption:key:change` was run twice: a fragment of the first new key was displayed during verification, so a second clean key was generated. Stored values were re-encrypted to key position 2. Key positions 0 (compromised) and 1 were overwritten with random values, keeping the line positions. The SMTP password decrypts. `payment/amazon_payments/simplepath/privatekey` was already undecryptable before the rotation (encrypted under a key from an earlier install) and Amazon Pay is inactive, so clear or re-enter it if Amazon Pay is ever used.)*
- [x] Rotate the database password and update `env.php`. *(Done 09-28 in place for `verifiedbota_magento2`. Pending: restart consumers, see above.)*
- [x] Rotate the passwords of the other DB users on this database (`verifiedbota_autotest_ro`, `verifiedbota_autotest_cleanup`). *(Done 2026-09-29 08:40 EDT; both verified. Their credentials are not stored on the host, and remote MySQL is allowed from 24.157.155.%, so the tools using them run off-host. New values were handed over via a temporary mode-0600 file; the owner moved them to secure storage, and the file was deleted from the server on 2026-09-29. Any off-host tools using these users need the new passwords.)*
- [ ] Rotate payment, shipping, tax, SMTP, newsletter, reCAPTCHA, analytics, search, cloud-storage, Composer/Marketplace (`auth.json`: **Adobe repo.magento.com keys rotated 2026-09-29, new pair verified (HTTP 200); Mirasvit package key not rotated — owner accepted the risk, since the license is bound to the domain in Mirasvit's account UI, which the attacker cannot reach**), Duo application key (`twofactorauth/duo/application_key` — its value was also displayed during the 09-28 session), and other external-service credentials at their source. SMTP: new Gmail app password set in Magento and `~/.env` on 2026-09-29; test email delivered.
- [x] Reset Magento Admin passwords, invalidate sessions. *(Admin password changed 2026-09-28 16:08 EDT, after the backdoor was removed. Admin email changed from the `example.com` placeholder to the owner's Gmail on 2026-09-29. All Admin sessions ended and all Admin and customer JWTs revoked on 2026-09-29.)* **MFA: owner decision (2026-09-29) not to enable `Magento_TwoFactorAuth`; Admin UI access is restricted to 24.157.155.x instead.** Note that the REST Admin token endpoint (`/rest/*/V1/integration/admin/token`) is not covered by that restriction.
- [ ] Recreate the Magento integration token and rotate OAuth secrets after the key rotation. *(Reviewed 09-28: only the stock Magento Analytics integration and one access token; no Admin or customer OAuth tokens.)*
- [x] Review CMS pages, blocks, email templates, HTML header/footer configuration, and `core_config_data` for injected scripts, remote URLs, or obfuscated content. *(Clean 09-28; details in the afternoon record.)*

- [x] Removed unused credentials from `core_config_data` on 2026-09-30: `twofactorauth/duo/application_key` (value displayed during the review) and Amazon Pay's `access_key`, `secret_key`, `client_secret`, `simplepath/privatekey` and `simplepath/publickey`. The owner uses neither Duo nor Amazon Pay, and Amazon Pay was inactive. Config cache cleaned; storefront 200.

## Required validation and monitoring

- [x] Confirm an authenticated Admin login works after repair. *(09-28 16:06–16:08 EDT: `playwright-admin` logged in from the allowlisted IP; dashboard and sales grid returned 200. Storefront 200.)* Do not run checkout until the encryption key and payment credentials are rotated and integration state is reviewed.
- [ ] Confirm normal product-image upload works and malicious/polyglot upload probes are rejected in staging.
- [ ] Monitor for at least 72 hours. *(Running since 2026-09-28 17:12 EDT, every 15 minutes until 2026-10-05; check `~/incident-monitor/alerts.log` and `monitor.log`. Close this item no earlier than 2026-10-01 17:15 EDT with a clean alerts log.)*
- [ ] Retain access logs and PHP error logs covering 2026-09-08 through the conclusion of monitoring. *(A snapshot to date is in `~/incident-backup-20260928/logs-evidence.tar.gz`.)*

## References

- Adobe's [APSB26-146 remediation guidance](https://experienceleague.adobe.com/en/docs/commerce-knowledge-base/kb/announcements/commerce-apsb26-146) identifies CVE-2026-75650, the `VULN-39341` hotfix, and post-patch credential rotation requirements.
- The existing [2.4.9 upgrade spike note](/home/verifiedbota/public_html/dev/notes/magento-2.4.9-upgrade-spike-2026-09-24.md:28) is useful historical context but should not be treated as the complete incident report.
- The [September 24 investigation transcript](/home/verifiedbota/.codex/sessions/2026/09/24/rollout-2026-09-24T12-55-19-01a0d457-d04e-7d93-b086-4b1bd7773e37.jsonl:263) records the previous quarantine and credential-rotation handoff.
