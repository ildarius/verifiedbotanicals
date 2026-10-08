# Security Hardening Tracker — October 2026

Follow-up to the [September 2026 incident](security-incident-2026-09-28.md). That tracker covers containment and credential rotation. This one covers the hardening and ongoing habits agreed on 2026-10-02.

## Where we left off

- **Current step:** Phase 1, item 5 (reCAPTCHA). It is live and blocks bots. **Waiting on the owner** for one real-browser check: log in, and place a test order (see item 5 results). Monitor email works (fixed 2026-10-04 via Gmail SMTP).
- **Queued:** the Magefan Blog CVE-2026-79323 fix and module updates (see "Queued tasks"). It is ready to run in its own session.
- **Next:** Phase 2, ongoing habits, worked through one at a time with the owner, starting with A.
- **Last updated:** 2026-10-08.

## Phase 1 — Quick fixes (Claude, owner approved 2026-10-02)

- [x] **1. Incident monitor alert fatigue.** Since 2026-10-01 the monitor has raised the same two alerts every 15 minutes:
  - The `env.php` and crontab hashes changed after the 10-01 session-lifetime work and were never re-baselined.
  - Claude scratchpad files in `/tmp` and `/var/tmp` are flagged as new scripts.

  The monitor also expires on 2026-10-05. Fix: review the changes, exclude the scratchpads, alert only when the alert set changes, email alerts, run permanently, and re-baseline.
- [x] **2. Remove `pub/php-probe.php`.** It is publicly reachable (HTTP 200) and leaks the PHP version and ini paths.
- [x] **3. Admin IP allowlist bypass.** The rule in `.htaccess` matches `/admin_y312l0` but not `/index.php/admin_y312l0`. Magento currently redirects the latter to the blocked path, but the edge rule should cover it too, like the REST token rule does.
- [x] **4. Production mode.** The site runs in `default` mode, so Magento generates static files on demand into the web-served `pub/static`. Switch to `production`. Known risk: `Sm/themecore` static-deploy issue (AGENTS.md "Run Findings"). After this, code changes need `setup:di:compile` and theme changes need `setup:static-content:deploy`.
- [~] **5. reCAPTCHA.** The modules are installed, but no form uses them. Enable for customer login, create account, forgot password, and place order (card testing). **Needs from owner:** Google reCAPTCHA site key and secret key.
- [x] **6. HSTS header.** Not sent. Start with a short `max-age` and raise it to 1 year once it has proven stable. Omit `includeSubDomains` until every subdomain is confirmed HTTPS-only.

### Phase 1 results (2026-10-02)

- **1. Monitor fixed.**
  - All alerts since the 09-30 baseline were reviewed. Each was legitimate:
    - Claude scratchpad scripts.
    - Headless Chrome/Puppeteer processes.
    - Committed code changes.
    - `env.php` gained only `session/save_path`, and the crontab gained only the session-cleanup line; both from commit `fee8e4b6`.
  - `monitor.sh` changes:
    - No expiry.
    - Claude scratchpads (`/tmp/claude-*`, `/var/tmp/claude-*`) are skipped.
    - Chrome, Puppeteer and at-spi processes are allowed.
    - Every alert is still written to `alerts.log`, but it prints, and so emails, only when the alert set changes, once a day while alerts persist, and once when they clear.
  - The crontab now has `MAILTO="ildarius@gmail.com"`.
  - Test email #1 (~08:12) **never arrived**. Cron's mail and that test went out as `verifiedbota@66-206-23-226.boulat.net`, the server hostname. That has no SPF or DKIM, so Gmail rejects it; Gmail bounced the same way in April 2026. The 2,125 cron mails in `~/mail/new` were the old monitor's output, delivered locally.
  - Fix (09:25):
    - `monitor.sh` now sends its own mail through `sendmail -f verifiedbota@verifiedbotanicals.com`. SPF `ip4:66.206.23.226` passes, and cPanel DKIM-signs for the domain.
    - Cron mail is turned off (`MAILTO=""`).
    - Test email #2 was sent the same way at 09:25. **It never arrived either.**
  - **2026-10-04: email delivery fixed for real.**
    - Tests #1–3, all sent through the server's own mail (exim, including as `@verifiedbotanicals.com`), never reached the Gmail account, not even Spam or Trash; checked through the Gmail connector. No bounces came back. Exim's log is root-only, so the cause is unknown.
    - New `~/incident-monitor/notify.php` sends through Magento's own authenticated SMTP account (Gmail SMTP as `verifiedbotanicals@gmail.com`), the route the store's order emails already use. The password is read from Magento config at send time and never stored or printed. Magento's config reader already decrypts encrypted fields, so the script decrypts only a still-encrypted value.
    - `monitor.sh` `notify()` uses it, falls back to sendmail, and logs any failure to `monitor.log`.
    - Test #4 (`notify.php`) and test #5 (the monitor's own `notify()`) both landed in the **inbox** on 2026-10-04 at 07:39.
    - Dependency: alerts now rely on the store's Gmail app password. If Google revokes it, store emails and monitor emails fail together, and the failure is logged in `monitor.log`. Phase 2's phone push will be the independent second channel.
    - Process alerts now include the parent PID. The 2026-10-04 00:45 alert for a bare `[php]` process was most likely a PHP process caught mid-exit (its command line already cleared); it happened once and no other check fired.
  - `~/.env`'s SMTP password for `verifiedbota@verifiedbotanicals.com` is stale (`535 Incorrect authentication data`), probably since the 09-29 mailbox password change. Whatever reads `~/.env` cannot send mail.
  - Pre-change copies are in `~/incident-monitor/state/` (`monitor.sh.pre-20261002`, `crontab.pre-20261002`).
  - The monitor script lives outside the repo, at `~/incident-monitor/`.
- **2. Probe removed.** Commit `a634140e`. `/php-probe.php` now returns 404.
- **3. Admin rule fixed.** Commit `dd86cdfe`.
  - Now 403 from non-allowlisted IPs: `/admin_y312l0/`, `/index.php/admin_y312l0/…`, `//index.php//admin_y312l0/`, and uppercase variants.
  - Storefront, login and GraphQL still return 200.
- **4. Production mode on**, 2026-10-02 08:18:42–08:18:56 (14 s maintenance).
  - DI compile and static deploy (`Sm/market` and `Magento/backend`, `en_US`, standard strategy) were built in a copy outside the web root, then swapped in. The `Sm/themecore` problem did not reproduce.
  - Result: 8,696 static files, 0 symlinks, static version `1790943411`.
  - Headless crawl before and after (home, product, 3 categories, cart, checkout, login, register, forgot password, search, contact): 0 failed requests, 0 console errors, all 338 requested static files present on disk.
  - Cron runs normally, and consumers are started on demand by `consumers_runner`.
  - New `dev/tools/deploy-production.sh` repeats this for future changes. Its build-only run was verified; its swap path is the same sequence that ran today, plus two fixes:
    - `pkill` is anchored to the PHP binary. The original `pkill` killed its own shell, which left maintenance on for a few extra seconds.
    - It copies `generated/.htaccess` into the build.
  - AGENTS.md updated: production-mode rules and the module-change sequence (`setup:upgrade --keep-generated`).
  - Correction to the 10-02 audit wording: `default` mode does not display exceptions to visitors either. The real gains are that `pub/static` is no longer written on demand from the web, plus performance.
- **5. reCAPTCHA live** (2026-10-02 ~09:28).
  - Invisible v2 keys (owner-created) are stored encrypted in `core_config_data`.
  - Enabled for: customer login, create account, forgot password, place order, contact, resend confirmation email.
  - Verified:
    - The widget renders with valid keys on all of those pages ("protected by reCAPTCHA", no key or domain error).
    - A headless browser gets Google's image challenge, as intended for bots.
    - A login POST without a token is rejected ("Can not resolve reCAPTCHA parameter").
    - REST `payment-information` (place order) without a token returns 400 "ReCaptcha validation failed".
  - **Newsletter left off on purpose.** The theme's footer form `newsletter-footer-validate-detail` renders no reCAPTCHA, so enforcing it would break every footer signup. Follow-up below.
  - **Wishlist pop-up login fixed.** `Sm_CartQuickPro`'s pop-up template looks for a layout block `mini-login-msp-recaptcha` from the old MSP module, which no longer exists, so it had no widget and would have failed. The new theme layout `app/design/frontend/Sm/market/Sm_CartQuickPro/layout/default.xml` defines it with Magento's reCAPTCHA. Separately, clicking the wishlist heart as a guest opens no pop-up, with or without this change; that is an existing theme issue.
  - Not yet proven: that the secret key validates a real, human-solved token. Owner to test a login and a checkout in a normal browser.
  - **Cache gotcha:** after adding the layout file, `cache:flush` left stale entries in `var/cache` (Symfony file cache, 1,198 of 1,237 files kept) and in `var/page_cache`. Emptying both directories fixed it. A later test showed flush and clean do invalidate new entries; the cause of the stale ones is unknown (possibly entries from around the production switch). **If a layout or config change doesn't show after `cache:flush`, run `rm -rf var/cache/* var/page_cache/*`.**
- **6. HSTS live.** Commit `dd86cdfe`. `Strict-Transport-Security: max-age=86400` on pages, 403s and 404s. HTTP already redirects to HTTPS.

### Follow-ups from Phase 1

- [ ] **Item 5:** owner creates reCAPTCHA keys at https://www.google.com/recaptcha/admin/create. Choose **Challenge (v2) → Invisible reCAPTCHA badge**, domain `verifiedbotanicals.com`. Then Claude configures them and tests login, registration and checkout, including the custom InteracETransfer payment method.
- [ ] **On or after 2026-10-09:** raise HSTS to `max-age=31536000`, if there were no certificate problems.
- [ ] **On or after 2026-10-05:** delete `~/build-prod-20261002/rollback` (the pre-production `generated/` and symlinked `pub/static`).
- [x] Deleted `~/env.php.bak-20261001173951` (owner approved 2026-10-02).
- [ ] Add reCAPTCHA to the theme's footer newsletter form, then enable `recaptcha_frontend/type_for/newsletter`.
- [ ] Update or retire the stale SMTP password in `~/.env`. First find what uses it.
- [ ] Wishlist heart does nothing for guests (existing theme bug, not security).
- [ ] **Webmail login alert, 2026-10-08.** `webmaild - serving 182.8.195.240`, 14 alerts from 03:45 onward.
  - Investigated: it is the first-ever webmail login of `web@verifiedbotanicals.com` (the mailbox shared with the Indonesian SEO contractor), at 03:55 EDT / 14:55 WIB.
  - The IP is in Semarang, Central Java, on Telkomsel mobile (AS23693). One session.
  - Activity: only the default-folder setup; nothing sent, read or deleted. No forwarders, filters or identity changes.
  - Looks legitimate. **Owner to confirm with the employee.**
  - Then add a webmail rule: report each new (mailbox, mobile network) pair once, from the Roundcube session tables, instead of alerting on the `webmaild` process. An IP allowlist won't work, because mobile IPs rotate.
  - Bug fixed: alert signatures included PIDs, so one ongoing condition re-emailed every 15 minutes. They are now ignored in the signature.
- [ ] **Outage, 2026-10-08, 02:31:40 to about 07:27 EDT (about 5 h, whole server).**
  - Cause: the nightly ModSecurity vendor update installed a Comodo WAF rule that the server's regex engine rejects: `comodo_apache/30_Apps_OtherApps.conf` line 6035, "invalid range in character class". The nightly restart stopped Apache, and it could not start again.
  - cPanel's service monitor could not recover it, because the config was invalid.
  - Fixed by the owner as root:
    - `whmapi1 modsec_make_config_inactive config=modsec_vendor_configs/comodo_apache/30_Apps_OtherApps.conf`
    - `apachectl -t` → Syntax OK
    - `restartsrv_httpd`
  - Verified afterwards: storefront, product, cart, login, GraphQL and static files all return 200; admin returns 403 from outside the allowlist.
  - Follow-ups:
    - Sysadmin: re-enable the file once Comodo ships a fixed rule set, and stop a bad vendor update from taking Apache down. Emailed 2026-10-08.
    - [x] **Uptime and content check added** (2026-10-08): `~/incident-monitor/uptime.sh`, run from cron every 5 minutes.
      - A page counts as healthy only with HTTP 200 **and** the expected content:
        - Homepage: at least 3 product links and 3 prices, plus the store name.
        - The versioned `require.js` loads and is real JS.
        - The first homepage product page has an add-to-cart button and a price.
        - Category `red-vein-kratom.html` has products and the toolbar.
        - Cart shows "Shopping Cart"; the login form is present.
        - Every page has a complete `</html>` and the footer, and takes 15 s or less.
      - Emails via `notify.php`: after 2 consecutive failures (5 to 10 minutes), every 60 minutes while still down, with time since the first failure, and once on recovery with the total downtime. Log: `~/incident-monitor/uptime.log`.
      - Tested: a page returning 200 but missing store content was flagged. The `[TEST] STORE DOWN` and `[TEST] RECOVERED` emails reached the inbox. Test overrides: `UPTIME_BASE`, `UPTIME_STATE`, `UPTIME_LOG`, `UPTIME_TEST=1`.
      - Limit: it alerts through Magento's Gmail SMTP login. If the database is also down, sending may fail (logged). Phase 2's phone push is the independent channel.
- [ ] Owner: is **71.14.241.66** yours? It logged into cPanel on 2026-04-17 and 2026-04-29 (`~/.lastlogin`), before the incident; every other login is 24.157.155.108.
- [ ] Server mail (exim) apparently doesn't reach Gmail. Anything else relying on it, such as PHP `mail()` or cPanel notices, is probably silent too. Low priority; Magento uses Gmail SMTP.
- [ ] Not security, noted while auditing:
  - `var/log/cron.log` is 805 MB and has no rotation.
  - Braintree is enabled but unconfigured, which logs "merchantId needs to be set" about 30 times a day.
  - The crontab's `#~ MAGENTO START/END` markers are in reverse order. That could confuse `cron:install`/`cron:remove`.

## Queued tasks

- [ ] **Magefan Blog CVE-2026-79323 + Magefan module updates.** Added by the owner 2026-10-02. Full brief: [magefan-blog-cve-2026-79323.md](magefan-blog-cve-2026-79323.md); read its "Corrections" section first, because the site is now in production mode.
  - [ ] Step 1: disable `Magefan_BlogGraphQl`. Approved. Anonymous `blogComments` currently exposes commenter emails; there are 0 comments today.
  - [ ] Step 2: update Blog, BlogGraphQl, Community, WysiwygAdvanced and AdminUserGuide to the latest free versions. Approved.
  - [ ] Step 3: turn off native blog comments. **Ask the owner first.**

## Phase 2 — Ongoing habits (one at a time, with the owner)

Each habit gets a short discussion and decision, then setup, then a recorded result.

- [ ] **A. Patching cadence.**
  - Subscribe to Adobe security bulletins (APSB) and the Magento release notes.
  - Agree a rule: critical isolated patches within 72 hours, full patch releases within 2 weeks.
  - Write the upgrade and patch runbook, including re-applying `m2-hotfixes/` after any Composer rebuild.
  - [ ] **Unmissable patch alerts** (owner request 2026-10-02; design decided 2026-10-02, not built yet). When a new security patch is discovered for anything this site runs, send the owner an alert that keeps repeating until the patch is applied:
    - Normal patches: at least once a day, every day, until applied.
    - Major or critical vulnerabilities: every 6 hours.
    - Every alert states how much time has passed since the first alert for that patch ("first alerted 2 days 6 hours ago").
    - It should be impossible to ignore. Stopping it requires applying the patch, or the owner explicitly acknowledging it.

    **Decisions (owner, 2026-10-02):**
    - **Sources: all three.**
      - Adobe security bulletins (APSB, Magento / Adobe Commerce section), plus Adobe's isolated-patch / hotfix KB announcements.
      - Magefan release notes: GitHub releases and tags for the installed `magefan/module-*` repos.
      - Mirasvit changelogs for the installed modules (`module-affiliate`, `module-core`).
      - Supplementary, at Claude's discretion: `composer audit` (Packagist/GitHub advisory DB) for everything in `vendor/`. It is cheap and also covers libraries.
    - **Delivery: phone push notification** (owner loves the idea), plus email as the written record.
      - Recommended service: **Pushover** (one-time ~US$5 per platform). Its "emergency" priority repeats on the phone every 30+ seconds until tapped, bypasses quiet hours, and has an API to confirm acknowledgement. That fits "can't ignore" better than SMS.
      - Free alternative: ntfy.sh with priority 5 ("max/urgent"). It is louder than normal notifications but does not repeat until acknowledged.
      - Owner to choose when we build it.
    - **What counts as "major"** (Claude's discretion). An advisory counts as major (alert every 6 hours, emergency push) if it affects a version we run and meets any of these:
      - CVSS ≥ 9.0, or Adobe priority 1.
      - Known to be exploited in the wild: stated by Adobe, listed in CISA KEV, or reported by Sansec.
      - Unauthenticated remote code execution, SQL injection, arbitrary file upload or write, or account/admin takeover. Today's incident (StyleSmuggler) was this class.
      - Everything else that affects us is normal (daily alert, high-priority push).
      - Advisories that don't affect our installed versions get one informational email and no repeats.
    - **How "patched" is detected** (Claude's discretion). Checked automatically on every run; the alert stops by itself once the check passes:
      - Magento core: the installed version from `composer.lock` is at or above the fixed version in the bulletin. For isolated patches, the patch file in `m2-hotfixes/` reverse-applies cleanly (`patch --dry-run -R`, the same check the security monitor uses for VULN-39341).
      - Magefan/Mirasvit modules: the installed version (`app/code/Magefan/*/composer.json`, `composer.lock` for Mirasvit) is at or above the fixed version, compared with `version_compare`.
      - `composer audit` findings: the advisory no longer appears.
      - Manual acknowledgement for cases automation can't judge (not applicable, or mitigated by a WAF rule): an `ack <id> "<reason>"` command. The reason is logged. For a major advisory, an ack only snoozes it for 7 days unless the reason marks it "not applicable".
    - Alert state (first-seen time, last-sent time, ack) lives in a small state file next to the security monitor (`~/incident-monitor/`), outside the web root.
    - Watcher runs from cron on this server. Run frequency and the exact feed URLs are settled at build time.
    - Known risk: Mirasvit changelogs are web pages, not feeds, so scraping them may break. The watcher must alert when a source can't be fetched or parsed. Failing silently would defeat the purpose.
- [ ] **B. WAF / virtual patching.**
  - Choose between Cloudflare (free or Pro, with managed rules) and Sansec Shield.
  - Put it in front of the site and restrict the origin to it.
  - Overlaps open incident items "host-level WAF coverage" and "restrict origin access".
- [ ] **C. Weekly malware scanning.**
  - Adobe Security Scan Tool (free; account.magento.com) on a weekly schedule.
  - Optionally Sansec eComscan, which targets Magecart skimmers.
  - Decide who gets the reports.
- [ ] **D. Off-server backups.**
  - Automated scheduled DB and media backups that leave the server.
  - At least one immutable or offline copy (S3/B2 with Object Lock, or a USB drive kept unplugged).
  - Test a restore.
  - Overlaps the open incident item "make the backup immutable".
- [ ] **E. Remaining post-breach credentials.** Rotate payment, shipping, tax, newsletter, reCAPTCHA, analytics and other third-party API keys at their source; this is the open incident item. Then decide whether customers need a forced password reset.

## Log

- 2026-10-08 — Monitor false positive at 08:45 (it caught uptime.sh and its curl mid-run); both are now allowlisted in the process check, verified with 10 samples and 0 leaks.
- 2026-10-08 — Uptime and content check live, every 5 minutes with email alerts.
- 2026-10-08 — Server-wide outage 02:31–07:27 caused by a broken Comodo WAF rule update; the rule file was disabled and Apache restarted. An uptime check is to be added to the monitor.
- 2026-10-08 — Webmail login alert investigated (web@, Telkomsel Indonesia, looks legitimate, awaiting confirmation). Fixed alert re-sending caused by changing PIDs.
- 2026-10-04 — Monitor email fixed: it now sends through Magento's Gmail SMTP; tests #4 and #5 confirmed in the inbox. Process alerts include the parent PID.
- 2026-10-02 — reCAPTCHA live (item 5, owner check pending). Monitor email fixed (sender domain). Old env.php backup deleted. Found the attacker's 09-10 mail() probe from 139.28.18.122; added to the incident tracker.
- 2026-10-02 — Patch-alert design decided: sources Adobe + Magefan + Mirasvit, phone push (Pushover recommended), "major" and "patched" rules set by Claude. Not built yet.
- 2026-10-02 — Owner requested unmissable, repeating patch alerts; added under Phase 2 A (to plan later).
- 2026-10-02 — Phase 1 items 1–4 and 6 done (details above). Magefan task queued. Item 5 waiting on reCAPTCHA keys.
- 2026-10-02 — Tracker created. Live audit: Magento 2.4.9 + VULN-39341 hotfix, PHP 8.3, admin 2FA (Google) forced with 1 user enrolled, admin IP-restricted, `env.php` 0600, no scripts in media/static, `.git`/`env.php`/logs not web-reachable, DB clean of injected scripts (core_config_data, CMS blocks/pages, status labels).
