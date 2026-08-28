## Purpose

Defines repository ignore rules and `.gitattributes` export-ignore directives to ensure the package repository tracks only essential playground source files and excludes non-runtime dev assets from Composer downstream installs.

## Requirements

### Requirement: Comprehensive Playground Runtime Ignore Rules
The repository `.gitignore` SHALL ignore all runtime-generated assets, vendor dependencies, local environment secrets, dynamic binary builds, temporary SQLite databases, and storage logs/caches within `playground/`, while preserving lightweight application source files in version control.

#### Scenario: Git status ignores playground runtime files
- **WHEN** `git status` is executed after running playground tests and web servers
- **THEN** `playground/vendor/`, `playground/.env`, `playground/bin/`, `playground/database/*.sqlite`, and `playground/storage/` files do not appear in untracked files

### Requirement: Composer Export-Ignore Configuration
The repository SHALL maintain a `.gitattributes` configuration specifying `export-ignore` for non-distribution developer directories and toolchains, ensuring that downstream package consumers do not download development playgrounds, tests, or planning directories.

#### Scenario: Creating distribution archive via git archive
- **WHEN** Composer or GitHub builds a release archive from the repository
- **THEN** `/playground`, `/tests`, `/.agent`, `/openspec`, and `/engine` are omitted from the resulting archive
