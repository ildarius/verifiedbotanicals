# Security Hardening Tracker — October 2026

Follow-up to the [September 2026 incident](security-incident-2026-09-28.md). That tracker covers containment and credential rotation. This one covers the hardening and ongoing habits agreed on 2026-10-02.

## Where we left off

- **Current step:** Phase 1, item 5 (reCAPTCHA). **Waiting on the owner** for Google reCAPTCHA keys (see item 5). Items 1–4 and 6 are done.
- **Queued:** the Magefan Blog CVE-2026-79323 fix and module updates (see "Queued tasks"). It is ready to run in its own session.
- **Next:** Phase 2, ongoing habits, worked through one at a time with the owner, starting with A.
- **Last updated:** 2026-10-02.

## Phase 1 — Quick fixes (Claude, owner approved 2026-10-02)

- [x] **1. Incident monitor alert fatigue.** Since 2026-10-01 the monitor has raised the same two alerts every 15 minutes:
  - The `env.php` and crontab hashes changed after the 10-01 session-lifetime work and were never re-baselined.
  - Claude scratchpad files in `/tmp` and `/var/tmp` are flagged as new scripts.

  The monitor also expires on 2026-10-05. Fix: review the changes, exclude the scratchpads, alert only when the alert set changes, email alerts, run permanently, and re-baseline.
- [x] **2. Remove `pub/php-probe.php`.** It is publicly reachable (HTTP 200) and leaks the PHP version and ini paths.
- [x] **3. Admin IP allowlist bypass.** The rule in `.htaccess` matches `/admin_y312l0` but not `/index.php/admin_y312l0`. Magento currently redirects the latter to the blocked path, but the edge rule should cover it too, like the REST token rule does.
- [x] **4. Production mode.** The site runs in `default` mode, so Magento generates static files on demand into the web-served `pub/static`. Switch to `production`. Known risk: `Sm/themecore` static-deploy issue (AGENTS.md "Run Findings"). After this, code changes need `setup:di:compile` and theme changes need `setup:static-content:deploy`.
- [ ] **5. reCAPTCHA.** The modules are installed, but no form uses them. Enable for customer login, create account, forgot password, and place order (card testing). **Needs from owner:** Google reCAPTCHA site key and secret key.
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
  - A test email was sent on 2026-10-02 ~08:12. *Owner: confirm it arrived and isn't in spam.*
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
- **6. HSTS live.** Commit `dd86cdfe`. `Strict-Transport-Security: max-age=86400` on pages, 403s and 404s. HTTP already redirects to HTTPS.

### Follow-ups from Phase 1

- [ ] **Item 5:** owner creates reCAPTCHA keys at https://www.google.com/recaptcha/admin/create. Choose **Challenge (v2) → Invisible reCAPTCHA badge**, domain `verifiedbotanicals.com`. Then Claude configures them and tests login, registration and checkout, including the custom InteracETransfer payment method.
- [ ] **On or after 2026-10-09:** raise HSTS to `max-age=31536000`, if there were no certificate problems.
- [ ] **On or after 2026-10-05:** delete `~/build-prod-20261002/rollback` (the pre-production `generated/` and symlinked `pub/static`).
- [ ] **Owner decision:** delete `~/env.php.bak-20261001173951`. It is a full copy of the current secrets and differs only by the session path.
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

- 2026-10-02 — Patch-alert design decided: sources Adobe + Magefan + Mirasvit, phone push (Pushover recommended), "major" and "patched" rules set by Claude. Not built yet.
- 2026-10-02 — Owner requested unmissable, repeating patch alerts; added under Phase 2 A (to plan later).
- 2026-10-02 — Phase 1 items 1–4 and 6 done (details above). Magefan task queued. Item 5 waiting on reCAPTCHA keys.
- 2026-10-02 — Tracker created. Live audit: Magento 2.4.9 + VULN-39341 hotfix, PHP 8.3, admin 2FA (Google) forced with 1 user enrolled, admin IP-restricted, `env.php` 0600, no scripts in media/static, `.git`/`env.php`/logs not web-reachable, DB clean of injected scripts (core_config_data, CMS blocks/pages, status labels).
