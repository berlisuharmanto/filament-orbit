## Purpose

Defines technical documentation requirements for specifying repository file structures, tracked source files, gitignore runtime boundaries, and composer export-ignore rules.

## Requirements

### Requirement: Tracked vs Ignored File Structure Matrix
The repository documentation SHALL provide a comprehensive, visual tree diagram and tabular classification of all project directories, distinguishing between tracked source files, ignored runtime assets, and distribution export-ignored directories.

#### Scenario: Reading repository directory guide in docs/repository-structure.md
- **WHEN** a contributor or developer inspects `docs/repository-structure.md`
- **THEN** the document clearly illustrates which directories are version-controlled, which are ignored by `.gitignore`, and which are excluded by `.gitattributes`

### Requirement: Clean Packaging and Sandbox Isolation Documentation
The documentation SHALL detail the architectural rationale for isolating the embedded Laravel 11 sandbox (`playground/`) and excluding developer tooling (`engine/`, `tests/`, `openspec/`) from downstream Composer installs.

#### Scenario: Auditing repository distribution footprint
- **WHEN** reviewing release packaging rules
- **THEN** the documentation provides exact glob patterns and rationale for `.gitignore` and `.gitattributes` entries
