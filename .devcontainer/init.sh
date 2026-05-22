#!/usr/bin/env bash
# initializeCommand — runs on the host before the container is built.
#
# Writes docker/development-easy/.env (the directory of the first -f file,
# where compose auto-discovers .env for top-level interpolation):
#   - COMPOSE_PROJECT_NAME: openemr-<workspace> so parallel devcontainer
#     instances of different worktrees get isolated containers, volumes,
#     and networks.
#   - WT_HTTPS_PORT: deterministic per workspace so the pinned HTTPS port
#     stays stable across rebuilds (OAuth-relevant).
#   - OPENEMR_SETTING_site_addr_oath: Codespaces-forwarded URL when
#     running in Codespaces; omitted otherwise so the base compose default
#     (https://localhost:${WT_HTTPS_PORT}) takes over.
#
# Always rewrites the file so stale values from a previous workspace or
# codespace don't leak.
set -euo pipefail

cd "$(dirname "$0")/.."
workspace="$(basename "$PWD")"
https_port=$(printf '%s' "$workspace" | cksum | awk '{printf "%d\n", 30000 + ($1 % 20000)}')

env_file="docker/development-easy/.env"
{
    printf 'COMPOSE_PROJECT_NAME=openemr-%s\n' "$workspace"
    printf 'WT_HTTPS_PORT=%s\n' "$https_port"
    if [[ -n "${CODESPACE_NAME:-}" ]]; then
        domain="${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
        printf 'OPENEMR_SETTING_site_addr_oath=https://%s-443.%s\n' "$CODESPACE_NAME" "$domain"
    fi
} > "$env_file"
