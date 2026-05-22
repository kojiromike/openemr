#!/usr/bin/env bash
# initializeCommand — runs on the host before the container is built.
# Writes .devcontainer/.env with:
#   - WT_HTTPS_PORT: deterministic per workspace so parallel devcontainer
#     instances of different worktrees don't collide on the HTTPS port.
#     Pinned (not auto-assigned) because OAuth client redirect URIs and
#     OPENEMR_SETTING_site_addr_oath have to be known before container
#     start. Other ports are auto-assigned in compose.devcontainer.yml.
#   - OPENEMR_SETTING_site_addr_oath: set to the Codespaces-forwarded
#     HTTPS URL when running in Codespaces; omitted otherwise so the base
#     compose default (https://localhost:${WT_HTTPS_PORT}) takes over.
# Always rewrites the file so stale values from a previous workspace or
# codespace don't leak.
set -euo pipefail

cd "$(dirname "$0")"
workspace="$(basename "$(cd .. && pwd)")"
https_port=$(printf '%s' "$workspace" | cksum | awk '{printf "%d\n", 30000 + ($1 % 20000)}')

{
    printf 'WT_HTTPS_PORT=%s\n' "$https_port"
    if [[ -n "${CODESPACE_NAME:-}" ]]; then
        domain="${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
        printf 'OPENEMR_SETTING_site_addr_oath=https://%s-443.%s\n' "$CODESPACE_NAME" "$domain"
    fi
} > .env
