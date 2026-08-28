## Context

See `proposal.md` for motivation.

The project is a hybrid Go + PHP Filament plugin for custom domain and DNS management. To enable LLM analysis in Google NotebookLM and provide an executive technical reference, a comprehensive `summary.md` will be created.

## Goals / Non-Goals

**Goals:**
* Author a self-contained, high-density `summary.md` document at the repository root.
* Include ASCII system diagrams, data flow schemas, Go engine internals, PHP bridge mechanics, and Filament resource structures.
* Include an objective, transparent evaluation of project strengths ("The Good Side"), limitations/risks ("The Bad Side"), and the evolutionary roadmap.
* Ensure `summary.md` is excluded from distribution tarballs via `.gitattributes`.

**Non-Goals:**
* Modifying package runtime code or dependencies.

## Decisions

### 1. Document Structure for NotebookLM Optimization
* **Decision**: Organize `summary.md` with explicit, searchable sections:
  1. Executive Summary & Core Value Proposition
  2. End-to-End Architectural Diagram & Data Flow
  3. Go DNS Engine Internals (`engine/`)
  4. PHP Subprocess Bridge & Typed DTOs (`src/`)
  5. Filament Admin Panel Integration (`src/Resources/DomainResource.php`)
  6. Embedded Laravel 11 Playground (`playground/`)
  7. OpenSpec Capability Matrix
  8. The Good Side: 5 Architectural Wins
  9. The Bad Side: 5 Known Limitations & Technical Gaps
  10. Future Evolution & Roadmap (OAuth, Queue Polling, ACME SSL, Pure PHP Fallback)
* **Rationale**: NotebookLM parses high-density structured markdown exceptionally well, generating rich audio overviews and accurate citations.

### 2. Distribution Cleanliness
* **Decision**: Add `summary.md export-ignore` to `.gitattributes`.

## Risks / Trade-offs

* **[Risk] Summary getting out of sync with code changes** → **Mitigation**: Base the summary directly on verified OpenSpec capabilities and active test suites.
