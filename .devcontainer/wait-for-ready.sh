#!/bin/sh
# postStartCommand — runs inside the openemr container after start.
#
# Wait for the app to be functionally usable for an attached terminal:
# database connectivity and filesystem writability. We deliberately do NOT
# gate on /readyz "installed":true — that flag reflects $config in
# sqlconf.php, and PHP opcache caches the initial $config=0 copy of the
# file even after the dev-easy bootstrap flips it to $config=1. Result:
# "installed":false is reported indefinitely on first boot. The container
# is still functional; the user can navigate to setup.php or restart php
# to refresh opcache when ready.
set -eu

deadline=$(( $(date +%s) + 300 ))
last=
while [ "$(date +%s)" -lt "$deadline" ]; do
    last=$(curl --insecure --silent --max-time 5 https://localhost/meta/health/readyz 2>/dev/null || true)
    case "$last" in
        *'"database":true'*'"filesystem":true'*)
            echo "OpenEMR functional (database + filesystem ready)"
            echo "Readyz: $last"
            exit 0
            ;;
        *)
            echo "Waiting for OpenEMR DB + filesystem..."
            sleep 5
            ;;
    esac
done

echo "Timed out waiting for OpenEMR DB + filesystem" >&2
echo "Last readyz response: ${last:-<none>}" >&2
exit 1
