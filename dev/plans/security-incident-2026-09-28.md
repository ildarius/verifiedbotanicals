# Production Security Incident Tracker — 2026-09-28

Status: **application containment complete; account and host remediation active**

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

## Active remediation tasks

### Application containment and repair — Codex

- [x] Create a timestamped preservation bundle of the live implant, relevant logs, and relevant Magento reports before modifying runtime files.
- [x] Put a temporary web-server-level block in place for the observed malicious request patterns while remediation is in progress.
- [x] Remove the malicious preamble from `app/etc/env.php`, preserving only the legitimate Magento configuration array.
- [x] Apply Adobe's official `VULN-39341_249.patch` for CVE-2026-75650 and record its hash and verification result.
- [x] Verify that no executable files or PHP markers remain in public media after the repair.
- [x] Restart the two Magento long-running consumers so no worker retains the old bootstrap state.
- [ ] Obtain hosting-provider confirmation that the web PHP handler has been reloaded (or that this account uses a non-persistent handler). The account shell cannot access the cPanel PHP-FPM restart API and no account PHP-FPM pool is visible.
- [ ] Build and verify a clean vendor tree outside the live document root, then deploy it atomically in a maintenance window. Do not use the contaminated backup as a source; run `dev/tools/apply-vuln-39341-hotfix.sh` after Composer populates the clean vendor tree.
- [x] Run narrow Magento/configuration validation and update this tracker with results.

### Hosting and account actions — owner / hosting provider

- [ ] Restrict origin access during remediation and add WAF/web-server detection or blocks for StyleSmuggler request patterns; retain the current logs.
- [ ] Inspect system cron, cPanel scheduled tasks, systemd timers, startup files, and outbound connections for unknown persistence. This needs host-level visibility beyond the account shell.
- [ ] Rotate cPanel, SSH, SFTP/FTP, deployment, Git, mail, and backup credentials; remove unknown access and keys.
- [ ] Confirm an off-host, immutable backup exists before further cleanup. Do not treat the September 24 `env.php` backup as clean.

### Magento and service credentials — owner

- [ ] Rotate the Magento encryption key through Magento's supported command during maintenance, after the patch is applied.
- [ ] Rotate the database password/user and update the legitimate `env.php` configuration.
- [ ] Rotate payment, shipping, tax, SMTP, newsletter, reCAPTCHA, analytics, search, cloud-storage, Composer/Marketplace, and other external-service credentials at their source.
- [ ] Reset Magento Admin passwords, require MFA, invalidate sessions, and remove unknown Admin users.
- [ ] Deactivate/recreate Magento integrations; revoke REST/SOAP/GraphQL tokens and rotate OAuth client secrets.
- [ ] Review CMS pages, blocks, email templates, HTML header/footer configuration, and `core_config_data` for injected scripts, remote URLs, or obfuscated content.

## Required validation and monitoring

- [ ] Confirm an authenticated Admin login works after repair; storefront availability returned HTTP 200. Do not run checkout until payment credentials and integration state are reviewed.
- [ ] Confirm normal product-image upload works and malicious/polyglot upload probes are rejected in staging.
- [ ] Monitor for at least 72 hours: new executable files under writable web paths, `env.php` changes, new cron entries, `Local_PolyShellGuard` warnings, and StyleSmuggler-shaped requests.
- [ ] Retain access logs and PHP error logs covering 2026-09-08 through the conclusion of monitoring.

## References

- Adobe's [APSB26-146 remediation guidance](https://experienceleague.adobe.com/en/docs/commerce-knowledge-base/kb/announcements/commerce-apsb26-146) identifies CVE-2026-75650, the `VULN-39341` hotfix, and post-patch credential rotation requirements.
- The existing [2.4.9 upgrade spike note](/home/verifiedbota/public_html/dev/notes/magento-2.4.9-upgrade-spike-2026-09-24.md:28) is useful historical context but should not be treated as the complete incident report.
- The [September 24 investigation transcript](/home/verifiedbota/.codex/sessions/2026/09/24/rollout-2026-09-24T12-55-19-01a0d457-d04e-7d93-b086-4b1bd7773e37.jsonl:263) records the previous quarantine and credential-rotation handoff.
