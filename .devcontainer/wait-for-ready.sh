#!/bin/sh
# postStartCommand — runs inside the openemr container after start.
#
# /meta/health/readyz returns 200 with {"installed":false} during the
# development-easy bootstrap install. That's correct upstream behavior:
# setup.php is a legitimate first-run UI, and the app is HTTP-ready before
# install completes. But in a devcontainer the user expects EASY_DEV_MODE to
# have auto-installed by the time VS Code attaches, so we wait for
# installed=true specifically — agent-/devcontainer-flavored readiness, not
# generic HTTP readiness.
set -eu

deadline=$(( $(date +%s) + 600 ))
last=
while [ "$(date +%s)" -lt "$deadline" ]; do
    last=$(curl --insecure --silent --max-time 5 https://localhost/meta/health/readyz 2>/dev/null || true)
    case "$last" in
        *'"installed":true'*)
            echo "OpenEMR ready"
            exit 0
            ;;
        *)
            echo "Waiting for OpenEMR install to finish..."
            sleep 10
            ;;
    esac
done

echo "Timed out waiting for OpenEMR install" >&2
echo "Last readyz response: ${last:-<none>}" >&2
exit 1
