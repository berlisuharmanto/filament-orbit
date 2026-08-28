## Context

See `proposal.md` for motivation.

The repository contains both the core package (`src/`, `config/`, `database/`, `resources/`, `bin/dist/`) and developer test tooling (`playground/`, `tests/`, `openspec/`, `engine/`).

## Goals / Non-Goals

**Goals:**
* Ensure `playground/` commits only clean source files (`app/`, `bootstrap/`, `config/`, `database/seeders/`, `database/migrations/`, `routes/`, `public/`, `tests/`, `composer.json`, `.env.example`, `phpunit.xml`, `artisan`).
* Ignore all playground runtime artifacts (`/playground/vendor/`, `/playground/node_modules/`, `/playground/.env`, `/playground/bin/`, `/playground/database/*.sqlite`, `/playground/storage/`).
* Configure `.gitattributes` to exclude `/playground`, `/tests`, `/.agent`, `/openspec`, and `/engine` from release zipballs and `composer require` downloads.

**Non-Goals:**
* Deleting or moving the playground outside the workspace.

## Decisions

### 1. `.gitattributes` File Structure
* **Decision**: Create root `.gitattributes` with:
  ```gitattributes
  # Exclude dev tooling from composer distribution archives
  /.agent export-ignore
  /.github export-ignore
  /engine export-ignore
  /openspec export-ignore
  /playground export-ignore
  /tests export-ignore
  .gitattributes export-ignore
  .gitignore export-ignore
  phpunit.xml export-ignore
  ```
* **Rationale**: When Composer or GitHub creates an archive of a release tag (e.g. `v1.0.0.zip`), git respects these attributes and strips out all development overhead.

### 2. Explicit `.gitignore` Boundaries
* **Decision**: Refine `.gitignore` to include `/playground/bin/` and recursive `/playground/storage/**` patterns.

## Risks / Trade-offs

* **[Risk] Missing `.env` on fresh clone** → **Mitigation**: `composer playground:install` copies `.env.example` to `.env` and generates the application key automatically.
