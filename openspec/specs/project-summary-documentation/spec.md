## Purpose

Generates a high-density, structured summary document (`summary.md`) detailing the system architecture, component breakdown, pros and cons, and technical specification map for LLM ingestion and executive review.

## Requirements

### Requirement: Comprehensive Architectural Synthesis
The `summary.md` document SHALL provide a self-contained, high-density synthesis of the entire repository, detailing the Go native DNS engine, the PHP process bridge, the Filament v3–v5 plugin integration, the driver-based DNS connector model, and the embedded playground testbed.

#### Scenario: Ingesting repository knowledge into an LLM or NotebookLM
- **WHEN** `summary.md` is uploaded or provided as context to an LLM
- **THEN** the model accurately answers architectural questions, data flow queries, and implementation details without needing multiple source files

### Requirement: Objective Technical Evaluation and Trade-offs
The `summary.md` document SHALL explicitly evaluate both architectural strengths ("The Good Side") and technical limitations/trade-offs ("The Bad Side"), providing actionable mitigations and future roadmap milestones.

#### Scenario: Evaluating project readiness and roadmap
- **WHEN** an engineer or stakeholder reviews the technical pros and cons in `summary.md`
- **THEN** the document clearly explains binary host dependencies, API token friction, background polling gaps, and future OAuth/queue solutions
