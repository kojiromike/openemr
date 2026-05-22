#!/usr/bin/env bash
# initializeCommand — runs on the host before the container is built.
# Writes .devcontainer/.env with Codespaces-aware overrides when applicable.
# Always rewrites the file so stale values from a previous codespace don't leak.
set -euo pipefail

cd "$(dirname "$0")"

if [[ -n "${CODESPACE_NAME:-}" ]]; then
    domain="${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
    cat > .env <<EOF
OPENEMR_SETTING_site_addr_oath=https://${CODESPACE_NAME}-443.${domain}
EOF
else
    : > .env
fi
