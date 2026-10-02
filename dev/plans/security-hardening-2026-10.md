# Security Hardening Tracker — October 2026

Follow-up to the [September 2026 incident](security-incident-2026-09-28.md). That tracker covers containment and credential rotation. This one covers the hardening and ongoing habits agreed on 2026-10-02.

## Where we left off

- **Current step:** Phase 1 — quick fixes 1–6.
- **Next:** Phase 2 — ongoing habits, worked through one at a time with the owner.
- **Last updated:** 2026-10-02.

## Phase 1 — Quick fixes (Claude, owner approved 2026-10-02)

- [ ] **1. Incident monitor alert fatigue.** Since 2026-10-01 the monitor has raised the same two alerts every 15 minutes:
  - The `env.php` and crontab hashes changed after the 10-01 session-lifetime work and were never re-baselined.
  - Claude scratchpad files in `/tmp` and `/var/tmp` are flagged as new scripts.

  The monitor also expires on 2026-10-05. Fix: review the changes, exclude the scratchpads, alert only when the alert set changes, email alerts, run permanently, and re-baseline.
- [ ] **2. Remove `pub/php-probe.php`.** It is publicly reachable (HTTP 200) and leaks the PHP version and ini paths.
- [ ] **3. Admin IP allowlist bypass.** The rule in `.htaccess` matches `/admin_y312l0` but not `/index.php/admin_y312l0`. Magento currently redirects the latter to the blocked path, but the edge rule should cover it too, like the REST token rule does.
- [ ] **4. Production mode.** The site runs in `default` mode, so Magento generates static files on demand into the web-served `pub/static`. Switch to `production`. Known risk: `Sm/themecore` static-deploy issue (AGENTS.md "Run Findings"). After this, code changes need `setup:di:compile` and theme changes need `setup:static-content:deploy`.
- [ ] **5. reCAPTCHA.** The modules are installed, but no form uses them. Enable for customer login, create account, forgot password, and place order (card testing). **Needs from owner:** Google reCAPTCHA site key and secret key.
- [ ] **6. HSTS header.** Not sent. Start with a short `max-age` and raise it to 1 year once it has proven stable. Omit `includeSubDomains` until every subdomain is confirmed HTTPS-only.

## Phase 2 — Ongoing habits (one at a time, with the owner)

Each habit gets a short discussion and decision, then setup, then a recorded result.

- [ ] **A. Patching cadence.**
  - Subscribe to Adobe security bulletins (APSB) and the Magento release notes.
  - Agree a rule: critical isolated patches within 72 hours, full patch releases within 2 weeks.
  - Write the upgrade and patch runbook, including re-applying `m2-hotfixes/` after any Composer rebuild.
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

- 2026-10-02 — Tracker created. Live audit: Magento 2.4.9 + VULN-39341 hotfix, PHP 8.3, admin 2FA (Google) forced with 1 user enrolled, admin IP-restricted, `env.php` 0600, no scripts in media/static, `.git`/`env.php`/logs not web-reachable, DB clean of injected scripts (core_config_data, CMS blocks/pages, status labels).
