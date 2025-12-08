# Automatic install helper (auto_install.sh)

This helper script runs the repo build steps (composer / npm) and then calls
contrib/util/installScripts/InstallerAuto.php with environment-driven settings.

## Why use it

- Single convenient command to prepare the repo and run the automatic installer.
- Makes it easy for Copilot or any automation to run a reproducible sequence.
- Keeps secrets as environment variables (avoid embedding them into the repository).

## Prerequisites

- PHP CLI available (and needed PHP extensions for OpenEMR).
- composer in PATH (if `composer.json` exists).
- npm (Node.js) in PATH if `package.json` exists and the build step is required.
- MySQL reachable from the machine running the installer.

## Where to put it

- The script is intended to live at:
  `contrib/util/installScripts/auto_install.sh`
  Mark executable: `chmod +x contrib/util/installScripts/auto_install.sh`

## Basic environment variables (defaults shown)

- MYSQL_HOST (default: localhost)
- MYSQL_PORT (default: 3306)
- MYSQL_ROOT_USER (default: root)
- MYSQL_ROOT_PASS (default: empty)
- MYSQL_USER (default: openemr)
- MYSQL_PASS (default: openemr)
- MYSQL_DB (default: openemr)
- SITE (default: default)
- IUSER (default: admin)
- IUNAME (default: Administrator)
- IUSERPASS (default: pass)
- IGROUP (default: Default)
- SOURCE_SITE_ID (default: empty)
- CLONE_DATABASE (default: empty)
- NO_ROOT_DB_ACCESS (default: empty)
- DEVELOPMENT_TRANSLATIONS (default: empty)
- LOGINHOST (default: localhost)

## Example usage

- Local quick run with defaults:
  ```bash
  OPENEMR_ENABLE_INSTALLER_AUTO=1 contrib/util/installScripts/auto_install.sh
  ```

- Provide mysql root password and custom DB credentials:
  ```bash
  MYSQL_ROOT_PASS="rootpwd" MYSQL_USER="oemr" MYSQL_PASS="oemrpw" MYSQL_DB="openemr_test" \
  SITE="default" IUSER="admin" IUSERPASS="adminpwd" \
  OPENEMR_ENABLE_INSTALLER_AUTO=1 contrib/util/installScripts/auto_install.sh
  ```

- Create a duplicate site (no DB clone):
  ```bash
  SOURCE_SITE_ID="default" SITE="default2" \
  MYSQL_USER="openemr2" MYSQL_PASS="openemr2" MYSQL_DB="openemr2" \
  OPENEMR_ENABLE_INSTALLER_AUTO=1 contrib/util/installScripts/auto_install.sh
  ```

## Notes & security

- The script passes credentials on the php command line (InstallerAuto expects that).
  Avoid leaving command-line histories that contain secrets. Use ephemeral CI environment
  variables for automation.
- If you do not have root DB access (for example in a pre-created DB scenario), set
  NO_ROOT_DB_ACCESS=1 and provide MYSQL_USER/MYSQL_PASS/MYSQL_DB that are already created and configured.
- The script is intentionally small and portable. You can extend it with additional checks,
  tests, or a Docker Compose environment if you want to reproduce a full environment for CI.

## CI / automation ideas

- Use the script in a GitHub Actions workflow step that sets environment variables from
  repository secrets (keep secret values out of logs).
- Or build a minimal Docker image with MySQL and PHP, mount the repo, and run the script inside
  the container to create an ephemeral test instance.
