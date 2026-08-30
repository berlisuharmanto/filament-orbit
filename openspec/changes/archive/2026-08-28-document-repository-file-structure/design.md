## Context

See `proposal.md` for motivation.

The project maintains a multi-tier repository consisting of the core PHP Filament plugin, a compiled Go DNS engine, an embedded Laravel 11 playground, and test/specification artifacts. To make the repository structure crystal clear for all developers, a dedicated `docs/repository-structure.md` will be created.

## Goals / Non-Goals

**Goals:**
* Author `docs/repository-structure.md` with complete directory maps, file inventories, and Git tracking boundaries.
* Clearly differentiate between:
  1. Files **Tracked in Git** (Version-controlled repository source).
  2. Files **Ignored in Git** (`.gitignore` runtime/environment files).
  3. Files **Excluded from Composer Releases** (`.gitattributes export-ignore`).
* Update `.gitattributes` to exclude `docs/` from downstream release archives.

**Non-Goals:**
* Altering the existing `.gitignore` or `.gitattributes` policies (they are already verified and correct).

## Decisions

### 1. Document Structure for `docs/repository-structure.md`
* **Section 1: Repository Overview Diagram**: Visual ASCII tree representing all top-level directories.
* **Section 2: Layer-by-Layer File Breakdown**:
  * Core PHP Plugin Layer (`src/`, `config/`, `database/migrations/`, `resources/views/`).
  * Compiled Go DNS Engine Layer (`engine/`, `bin/dist/`, `bin/dns-manager`).
  * Embedded Playground Sandbox (`playground/`).
  * Automated Test Suites (`tests/`, `playground/tests/`).
  * OpenSpec & Agent Planning (`openspec/`, `.agent/`).
* **Section 3: The 3-Tier Git Boundary Matrix**:
  * Comprehensive table detailing Tracked vs Ignored vs Export-Ignored files.
* **Section 4: Developer Setup & Hygiene Guide**:
  * Rules for committing playground modifications and maintaining a clean git tree.

### 2. Export Rules
* Add `/docs/** export-ignore` and `/docs export-ignore` to `.gitattributes`.

## Risks / Trade-offs

* **[Risk] Documentation drifting over time** → **Mitigation**: Base documentation on explicit glob patterns defined in `.gitignore` and `.gitattributes`.
