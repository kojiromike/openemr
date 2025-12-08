#!/usr/bin/env bash
# Simple helper to run the full OpenEMR build + automatic installer.
# Place this at contrib/util/installScripts/auto_install.sh and make executable:
#   chmod +x contrib/util/installScripts/auto_install.sh
#
# This script:
# - ensures OPENEMR_ENABLE_INSTALLER_AUTO is set for the run
# - runs composer install (if composer.json exists)
# - runs npm ci / npm run build (if package.json exists)
# - invokes InstallerAuto.php with values from environment variables (or defaults)
#
# Important: This script will pass credentials on the php command line.
# Treat the environment carefully and avoid sharing logs with secrets.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"
cd "$REPO_ROOT"

# Ensure installer auto is enabled for this run
export OPENEMR_ENABLE_INSTALLER_AUTO="${OPENEMR_ENABLE_INSTALLER_AUTO:-1}"

# Defaults (override with env vars)
: "${MYSQL_HOST:=localhost}"
: "${MYSQL_PORT:=3306}"
: "${MYSQL_ROOT_USER:=root}"
: "${MYSQL_ROOT_PASS:=}"          # leave empty for no root password
: "${MYSQL_USER:=openemr}"
: "${MYSQL_PASS:=openemr}"
: "${MYSQL_DB:=openemr}"
: "${SITE:=default}"
: "${IUSER:=admin}"
: "${IUNAME:=Administrator}"
: "${IUSERPASS:=pass}"
: "${IGROUP:=Default}"
: "${SOURCE_SITE_ID:=}"
: "${CLONE_DATABASE:=}"
: "${NO_ROOT_DB_ACCESS:=}"
: "${DEVELOPMENT_TRANSLATIONS:=}"
: "${LOGINHOST:=localhost}"

echo "Working in repo: $REPO_ROOT"
echo "OPENEMR_ENABLE_INSTALLER_AUTO=$OPENEMR_ENABLE_INSTALLER_AUTO"
echo "Target site: $SITE"
echo "MySQL host: $MYSQL_HOST:$MYSQL_PORT"
echo "MySQL database: $MYSQL_DB"
echo "Installer will run as DB user: $MYSQL_USER"
if [ -n "$MYSQL_ROOT_PASS" ]; then
  echo "MySQL root password: (set)"
else
  echo "MySQL root password: (not set)"
fi

# 1) Composer install (if present)
if [ -f composer.json ]; then
  echo "Running composer install..."
  composer install --no-interaction --prefer-dist
else
  echo "No composer.json found; skipping composer install."
fi

# 2) NPM install & build (if present)
if [ -f package.json ]; then
  if command -v npm >/dev/null 2>&1; then
    echo "Running npm ci..."
    if ! npm ci --no-audit --no-fund 2>&1; then
      echo "npm ci failed; falling back to npm install..."
      npm install --no-audit --no-fund
    fi
    # Check for build script - use node if jq not available
    if command -v jq >/dev/null 2>&1; then
      if jq -e '.scripts.build' package.json >/dev/null 2>&1; then
        echo "Running npm run build..."
        npm run build
      else
        echo "No npm build script found; skipping build."
      fi
    elif grep -q '"build"' package.json; then
      echo "Running npm run build..."
      npm run build
    else
      echo "No npm build script found; skipping build."
    fi
  else
    echo "npm not installed; skipping npm steps."
  fi
else
  echo "No package.json found; skipping npm steps."
fi

# 3) Build php command with installer args
INSTALLER_PHP="$SCRIPT_DIR/InstallerAuto.php"
if [ ! -f "$INSTALLER_PHP" ]; then
  echo "ERROR: InstallerAuto.php not found at $INSTALLER_PHP"
  exit 1
fi

declare -A php_args=(
  [server]="$MYSQL_HOST"
  [port]="$MYSQL_PORT"
  [root]="$MYSQL_ROOT_USER"
  [rootpass]="$MYSQL_ROOT_PASS"
  [login]="$MYSQL_USER"
  [pass]="$MYSQL_PASS"
  [dbname]="$MYSQL_DB"
  [site]="$SITE"
  [iuser]="$IUSER"
  [iuname]="$IUNAME"
  [iuserpass]="$IUSERPASS"
  [igroup]="$IGROUP"
  [source_site_id]="$SOURCE_SITE_ID"
  [clone_database]="$CLONE_DATABASE"
  [no_root_db_access]="$NO_ROOT_DB_ACCESS"
  [development_translations]="$DEVELOPMENT_TRANSLATIONS"
  [loginhost]="$LOGINHOST"
)

echo "Running automatic installer..."
# Build argument array from associative array
args=()
for key in "${!php_args[@]}"; do
  args+=("${key}=${php_args[$key]}")
done
php -d memory_limit=1024M -f "$INSTALLER_PHP" "${args[@]}"

echo "Installer finished."
