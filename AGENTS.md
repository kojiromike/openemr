# Agent guide

This file is the shared entry point for AI coding agents (Codex, Cursor, Aider,
Claude Code via `@AGENTS.md` import, etc.) working in this repository. For the
full developer guide, see `CLAUDE.md`. For project history and code conventions,
see `CONTRIBUTING.md`.

## Getting a running instance

The repository ships a devcontainer that wraps the existing
`docker/development-easy/` compose stack. The same configuration works in two
places:

- **GitHub Codespaces** — open the repo in a codespace; the devcontainer builds
  automatically. Once healthy, click the forwarded HTTPS port to open OpenEMR.
- **Local VS Code** — "Dev Containers: Reopen in Container". Same result, but
  using the local Docker daemon. For multiple concurrent local checkouts,
  prefer `openemr-cmd worktree` (see `CLAUDE.md`) over running the devcontainer
  in each — devcontainer pins the OAuth-relevant HTTPS port and will collide.

Default credentials: `admin` / `pass`.

The compose stack auto-installs OpenEMR on first boot (database initialization,
admin user creation) via the image's bootstrap. No manual install step is
needed.

## What not to commit

The `development-easy` image bootstrap writes generated files into the working
tree on first boot. Expect them to appear dirty in `git status` — leave them
that way:

- `sites/default/sqlconf.php` — flipped to `$config = 1` after install. The
  repo version lives at `$config = 0` by design; committing the installed
  version would ship pre-baked DB credentials and skip the installer for
  downstream users.
- `sites/default/documents/` runtime contents (smarty caches, log files). The
  README placeholders that keep these directories tracked must remain.
- `vendor/`, `node_modules/` — already gitignored.

Before staging, `git status` should show only your intentional edits.

## Where things live

```
src/         Modern PSR-4 code (OpenEMR\ namespace)
library/     Legacy procedural PHP
interface/   Web UI controllers and templates
tests/       PHPUnit + Jest suites
sql/         Schema and migrations
docker/      Compose stacks (development-easy is the canonical dev env)
.devcontainer/  Devcontainer wrapping development-easy
```

## Running tests

Inside the devcontainer:

```bash
composer test          # full PHP test suite
composer phpstan       # static analysis (level 10)
composer phpcs         # PSR-12 check
npm test               # JS unit tests
```

Outside (with `openemr-cmd` installed), see `CLAUDE.md` for the per-suite
aliases.

## Commit conventions

- [Conventional Commits](https://www.conventionalcommits.org/) for messages.
- Add an `Assisted-by:` trailer naming the AI tool when one helped:
  `Assisted-by: Claude Code`, `Assisted-by: GitHub Copilot`, etc.
- See `CLAUDE.md` for the full coding standards (strict types, PSR-3 logging,
  PHPStan level 10, etc.).
