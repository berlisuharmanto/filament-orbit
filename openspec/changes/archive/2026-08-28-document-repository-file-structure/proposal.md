## Why

To provide transparent technical documentation for contributors and package maintainers on repository layout, version-controlled source files, runtime ignore rules, and package distribution export boundaries, the project requires a dedicated reference document `docs/repository-structure.md`.

## What Changes

* **`docs/repository-structure.md`**: A comprehensive technical breakdown covering:
  1. Complete Directory Tree Overview.
  2. Files Tracked in Git (Source code, migrations, seeders, test suites, static distribution binaries).
  3. Files Ignored in Git (`.gitignore` rules for `/playground/vendor/`, `/playground/.env`, `/playground/bin/`, SQLite databases, logs, and caches).
  4. Files Excluded from Composer Release Archives (`.gitattributes export-ignore` rules for `/playground/**`, `/engine/**`, `/tests/**`, and `openspec/**`).
  5. The architectural rationale behind every boundary.

## Capabilities

### New Capabilities
- `repository-structure-documentation`: Technical reference documentation specifying tracked, ignored, and export-ignored file and directory structures.

### Modified Capabilities
<!-- None -->

## Impact

* **Documentation**: Adds `docs/repository-structure.md`.
* **Export Rules**: Marked with `export-ignore` in `.gitattributes` to ensure it does not bloat downstream Composer installs.
