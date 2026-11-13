#!/usr/bin/env bash
# Install and configure CrowdSec on the verifiedbotanicals server.
# Ubuntu 22.04 + cPanel/WHM, ea-nginx in front of Apache, CSF/LFD firewall.
#
# Run as ROOT (WHM > Terminal, or ssh as root):
#   bash /home/verifiedbota/public_html/dev/tools/crowdsec/install-crowdsec.sh
# Optional: link the server to your CrowdSec Console account (app.crowdsec.net > Security Engines > Enroll):
#   CROWDSEC_ENROLL_KEY=<key> bash /home/verifiedbota/public_html/dev/tools/crowdsec/install-crowdsec.sh
#
# Safe to re-run. What it does:
#   1. Installs the crowdsec engine and the iptables firewall bouncer (packagecloud repo from install.crowdsec.net).
#   2. Detection: nginx access/error logs (every request, including proxy-cache hits Apache never sees), SSH,
#      cPanel/WHM logins, LFD, exim and dovecot. Generic HTTP scans/probes, known CVE exploits, brute force.
#   3. Never bans the server itself (66.206.23.226, used by our own Playwright/monitor checks) or the owner's IP.
#      Known good bots (Google, Bing, CDNs) are whitelisted by crowdsecurity/whitelist-good-actors.
#   4. crowdsecurity/http-crawl-non_statics starts in SIMULATION (logged, not enforced): Magento AJAX can look like
#      crawling. Review it in the status file after a few days, then: cscli simulation disable crowdsecurity/http-crawl-non_statics
#   5. CSF integration: CSF flushes iptables on every restart, so csfpost.sh restarts the bouncer to put its rules back;
#      LFD is told to ignore the crowdsec processes.
#   6. Writes a status report every 10 minutes to /var/lib/crowdsec-status/status.txt, readable by verifiedbota, so
#      the site's non-root tooling can check that CrowdSec is alive and see what it blocks.
set -euo pipefail

SERVER_IP="66.206.23.226"
OWNER_IPS=("24.157.155.108")
SITE_USER="verifiedbota"
STATUS_DIR="/var/lib/crowdsec-status"
STAMP="$(date +%Y%m%d-%H%M%S)"

say()  { printf '\n== %s\n' "$*"; }
warn() { printf '!! %s\n' "$*" >&2; }
die()  { warn "$*"; exit 1; }

[[ $EUID -eq 0 ]] || die "Run this as root."
grep -q 'Ubuntu' /etc/os-release || die "Expected Ubuntu."
command -v csf >/dev/null || die "CSF not found; this script is written for the CSF firewall."

# --- 1. Packages ------------------------------------------------------------------------------------------
say "Installing packages"
if ! dpkg -s crowdsec >/dev/null 2>&1; then
    if ss -ltn | awk '{print $4}' | grep -qE '(^|:)8080$'; then
        die "Port 8080 is already in use; CrowdSec's local API needs it. Stop here and tell Claude."
    fi
    if [[ ! -f /etc/apt/sources.list.d/crowdsec_crowdsec.list ]]; then
        curl -fsSL https://install.crowdsec.net | sh
    fi
    DEBIAN_FRONTEND=noninteractive apt-get install -y crowdsec
fi
# Installed after the engine, so the package registers its own API key with the local API.
DEBIAN_FRONTEND=noninteractive apt-get install -y crowdsec-firewall-bouncer-iptables
cscli version 2>/dev/null | head -3 || true

# --- 2. Detection content ---------------------------------------------------------------------------------
say "Installing detection collections"
cscli hub update >/dev/null
for c in crowdsecurity/linux crowdsecurity/sshd crowdsecurity/nginx crowdsecurity/base-http-scenarios \
         crowdsecurity/http-cve crowdsecurity/whitelist-good-actors crowdsecurity/cpanel crowdsecurity/exim \
         crowdsecurity/dovecot; do
    cscli collections install "$c" >/dev/null 2>&1 && echo "   ok  $c" || warn "could not install $c (continuing)"
done
cscli parsers install crowdsecurity/whitelists >/dev/null 2>&1 || true
cscli hub upgrade >/dev/null 2>&1 || true

say "Writing log acquisition (cPanel paths)"
mkdir -p "/etc/crowdsec/backup-$STAMP"
[[ -f /etc/crowdsec/acquis.yaml ]] && cp -p /etc/crowdsec/acquis.yaml "/etc/crowdsec/backup-$STAMP/"
# The package's auto-detection may have written its own sources; move them aside so no log is read twice.
if compgen -G "/etc/crowdsec/acquis.d/*.yaml" >/dev/null; then
    mv /etc/crowdsec/acquis.d/*.yaml "/etc/crowdsec/backup-$STAMP/"
fi
{
    echo "# Managed by public_html/dev/tools/crowdsec/install-crowdsec.sh ($STAMP). Previous version in backup-$STAMP/."
    add() { # add <type> <file>...
        local type=$1; shift; local files=()
        for f in "$@"; do [[ -e $f ]] && files+=("$f"); done
        [[ ${#files[@]} -gt 0 ]] || { warn "no log file found for type $type: $*"; return; }
        echo "---"; echo "filenames:"; for f in "${files[@]}"; do echo "  - $f"; done
        echo "labels:"; echo "  type: $type"
    }
    # ea-nginx is the front door: its global access log has every request, with the real client IP.
    add nginx  /var/log/nginx/access.log /var/log/nginx/error.log
    # syslog-format files: sshd (auth.log), dovecot (mail.log), LFD (lfd.log); the syslog parser sets the program.
    add syslog /var/log/auth.log /var/log/mail.log /var/log/lfd.log
    add cpanel /usr/local/cpanel/logs/login_log
    add exim   /var/log/exim_mainlog
} > /etc/crowdsec/acquis.yaml
grep -E 'type:|  - ' /etc/crowdsec/acquis.yaml | sed 's/^/   /' || true

say "Whitelisting the server and the owner"
{
    echo "name: local/verifiedbotanicals-whitelist"
    echo "description: \"Never ban the server itself or the store owner\""
    echo "whitelist:"
    echo "  reason: \"server ($SERVER_IP) and owner\""
    echo "  ip:"
    for ip in 127.0.0.1 ::1 "$SERVER_IP" "${OWNER_IPS[@]}"; do echo "    - \"$ip\""; done
} > /etc/crowdsec/parsers/s02-enrich/zz-verifiedbotanicals-whitelist.yaml

say "Putting http-crawl-non_statics in simulation for now"
cscli simulation enable crowdsecurity/http-crawl-non_statics >/dev/null 2>&1 || warn "simulation enable failed"

# --- 3. Start ---------------------------------------------------------------------------------------------
say "Starting the engine"
crowdsec -t -c /etc/crowdsec/config.yaml >/dev/null 2>&1 || { crowdsec -t -c /etc/crowdsec/config.yaml; die "crowdsec config test failed"; }
systemctl enable --now crowdsec >/dev/null 2>&1
systemctl restart crowdsec
if [[ -n "${CROWDSEC_ENROLL_KEY:-}" ]]; then
    cscli console enroll -e context "$CROWDSEC_ENROLL_KEY" --name verifiedbotanicals || warn "console enroll failed"
    systemctl restart crowdsec
fi
sleep 5
cscli bouncers list -o raw 2>/dev/null | grep -qi firewall || {
    warn "firewall bouncer has no API key; registering one"
    key=$(cscli bouncers add "cs-firewall-bouncer-$STAMP" -o raw)
    sed -i "s|^api_key:.*|api_key: ${key}|" /etc/crowdsec/bouncers/crowdsec-firewall-bouncer.yaml
}
systemctl enable --now crowdsec-firewall-bouncer >/dev/null 2>&1
systemctl restart crowdsec-firewall-bouncer

# --- 4. CSF / LFD integration ------------------------------------------------------------------------------
say "Hooking into CSF"
POST=/usr/local/csf/bin/csfpost.sh
if [[ ! -f $POST ]]; then printf '#!/bin/sh\n' > "$POST"; chmod 700 "$POST"; fi
if ! grep -q 'BEGIN crowdsec' "$POST"; then
    cat >> "$POST" <<'EOF'

# BEGIN crowdsec (added by public_html/dev/tools/crowdsec/install-crowdsec.sh)
# CSF flushes iptables on every start/restart; restart the CrowdSec bouncer so its DROP rules come back.
if systemctl is-enabled --quiet crowdsec-firewall-bouncer 2>/dev/null; then
    systemctl restart --no-block crowdsec-firewall-bouncer
fi
# END crowdsec
EOF
fi
for exe in /usr/bin/crowdsec /usr/bin/crowdsec-firewall-bouncer; do
    grep -qxF "exe:$exe" /etc/csf/csf.pignore || echo "exe:$exe" >> /etc/csf/csf.pignore
done
echo "   restarting CSF + LFD to prove the hook (the firewall reloads in a few seconds)"
csf -ra >/dev/null 2>&1 || warn "csf -ra returned an error"
sleep 15
if iptables -S 2>/dev/null | grep -qi crowdsec; then echo "   ok  CrowdSec iptables rules present after CSF restart"
else warn "CrowdSec iptables rules NOT present after CSF restart"; fi

# --- 5. Status report for the non-root site tooling ---------------------------------------------------------
say "Installing the 10-minute status report"
install -d -m 0750 -o root -g "$SITE_USER" "$STATUS_DIR"
cat > /usr/local/sbin/crowdsec-status.sh <<EOF
#!/bin/sh
# Writes CrowdSec health and recent activity for $SITE_USER to read (it has no root to run cscli).
out=$STATUS_DIR/status.txt; tmp=\$(mktemp $STATUS_DIR/.status.XXXXXX)
{
  echo "generated: \$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "crowdsec: \$(systemctl is-active crowdsec)  bouncer: \$(systemctl is-active crowdsec-firewall-bouncer)"
  echo "iptables_rule: \$(iptables -S 2>/dev/null | grep -ci crowdsec)"
  echo "active_decisions_local: \$(cscli decisions list -o raw 2>/dev/null | tail -n +2 | wc -l)"
  echo "active_decisions_incl_community: \$(cscli decisions list -a -o raw 2>/dev/null | tail -n +2 | wc -l)"
  echo; echo "### cscli capi status"; cscli capi status 2>&1 | tail -n 3
  echo; echo "### acquisition / parsers (lines read, parsed, unparsed)"; cscli metrics show acquisition parsers 2>/dev/null || cscli metrics 2>/dev/null
  echo; echo "### last 40 alerts (includes simulated)"; cscli alerts list --limit 40 2>/dev/null
  echo; echo "### active local decisions"; cscli decisions list 2>/dev/null | head -n 60
} > "\$tmp" 2>&1
chown root:$SITE_USER "\$tmp"; chmod 0640 "\$tmp"; mv -f "\$tmp" "\$out"
EOF
chmod 0755 /usr/local/sbin/crowdsec-status.sh
echo "*/10 * * * * root /usr/local/sbin/crowdsec-status.sh" > /etc/cron.d/crowdsec-status
chmod 0644 /etc/cron.d/crowdsec-status

# --- 6. Summary -------------------------------------------------------------------------------------------
say "Waiting 60 seconds for logs to flow, then summary"
sleep 60
/usr/local/sbin/crowdsec-status.sh
head -n 40 "$STATUS_DIR/status.txt"
say "Done. Tell Claude it ran; it will read $STATUS_DIR/status.txt to verify."
