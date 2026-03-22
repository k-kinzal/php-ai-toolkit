---
name: setup-toolkit-agents-md
description: >-
  Set up AGENTS.md with project conventions and AI agent guidelines for a PHP project.
  Use when asked to create AGENTS.md, set up AI agent guidelines, or configure project conventions for AI agents.
---

# Setup AGENTS.md

This skill creates an AGENTS.md file that provides context and rules for AI agents working on the project.

## What is AGENTS.md?

AGENTS.md is a convention for providing AI coding agents (Claude Code, Cursor, Codex, etc.) with project-specific context. It tells agents:
- What the project does
- What quality standards to follow
- How the codebase is organized
- Where to find documentation

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-agents-md/AGENTS.md`.

## Section Explanations

### NOTE Comment
The `<!-- NOTE -->` comment at the top tells AI agents that this file is human-managed and should not be modified by agents. This prevents accidental overwrites.

### Project Description
Replace `{{PROJECT_DESCRIPTION}}` with a description that covers:

1. What the project does (1-3 sentences)
2. The project's goal — why it exists and what it is trying to achieve
3. Core concepts — key ideas or domain terms that AI agents need to understand to work on the project

Write in prose, not bullet points. Example (from a dependency analysis CLI tool):
> A CLI tool that analyzes PHP code dependencies and visualizes the blast radius of changes. Its primary goal is impact analysis — automatically identifying what breaks when a class, method, or function changes. The tool builds a bidirectional graph model where nodes represent code elements (classes, methods, functions) and edges represent relationships (calls, extends, implements). Inverse edges are generated automatically, enabling traversal in both directions: what the target depends on, and what depends on the target. Output is available in two formats: a tree display for humans and structured data for AI agents.

### Tradeoff Sliders
These calibrate AI behavior. The defaults are set to HIGH scope/quality, LOW time/cost — meaning the agent should prioritize correctness over speed. Adjust if the project has different priorities:
- For prototypes/MVPs: reduce Quality to MEDIUM, increase Time to HIGH
- For production services: keep defaults (HIGH quality, LOW time)

### Supported Versions
Replace `{{SUPPORTED_VERSIONS}}` with the versions that the project guarantees to work on. Determine what to list from the project's own requirements — `composer.json`, CI matrix, documentation, etc. Only list what the project explicitly supports.

Example:
```markdown
- **PHP**: 8.1 / 8.2 / 8.3 / 8.4 / 8.5
```

### Architecture
Replace `{{ARCHITECTURE}}` with a description of the project's layering and responsibility boundaries. This is NOT a directory listing — it describes how the layers relate to each other and what each layer is responsible for.

Include:
1. A one-line pipeline or flow summary showing how data/control moves through the system
2. A table mapping each layer to its responsibility and key entry point

Example (from a CLI tool):
```markdown
Pipeline: `CLI input → Config stacking → Action → Analyzer → Graph → Traversal → Reporter → output`

| Layer | Responsibility | Key file |
|-------|---------------|----------|
| **Command** | IO only — parse arguments, delegate output | `src/Command/InspectCommand.php` |
| **Config** | Merge 4 layers: Default → Env → YAML → CLI | `src/Config/ConfigLoader.php` |
| **Action** | Orchestrate Analyzer and Reporter | `src/Action/Inspect/InspectAction.php` |
| **Analyzer** | Parse source code → build Graph | `src/Analyzer/` |
| **Reporter** | Format graph into output | `src/Reporter/` |

Dependencies between layers flow top-down only. Command never calls Analyzer directly.
```

After the architecture description, replace `{{DIRECTORY_STRUCTURE}}` with the actual project directory tree. This is the physical structure that corresponds to the layers above. Example:
```
src/
├── Command/    # CLI commands (IO only)
├── Action/     # Use-case orchestration
├── Analyzer/   # Analysis engine and graph model
├── Config/     # Layered configuration readers
└── Reporter/   # Output formatters
tests/          # Mirrors src/ namespaces
```

### Document References
Replace `{{DOCUMENT_REFERENCES}}` with links to project-specific documentation that AI agents should read. Example:
```markdown
- [API Specification](docs/api-spec.md)
- [Database Schema](docs/schema.md)
```

If the project has no documentation, remove this section entirely.

## Adaptation Workflow

When applying this template to a project, follow these steps:

1. **Read `composer.json`** to determine PHP version, framework, and dependencies
2. **Scan the directory structure** to understand project layout
3. **Look for existing documentation** in `docs/`, `README.md`, etc.
4. **Fill in all `{{PLACEHOLDER}}` values** with real project information
5. **Remove unused placeholders** (e.g., `{{ADDITIONAL_CODING_RULES}}` if no extra rules)
6. **Place as `AGENTS.md`** in the project root

## Protecting AGENTS.md

After creating AGENTS.md, recommend setting up `.claude/settings.json` to prevent AI agents from modifying it:

```json
{
    "permissions": {
        "deny": [
            "Write(AGENTS.md)",
            "Edit(AGENTS.md)",
            "Bash(*AGENTS.md*)"
        ]
    }
}
```

Also create `CLAUDE.md` in the project root that references AGENTS.md:

```markdown
<!-- NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you. -->
@AGENTS.md
```

## Verification

After creating AGENTS.md:
1. Read the file and confirm all `{{PLACEHOLDER}}` values have been replaced
2. Verify the supported versions match `composer.json` constraints
3. Verify the architecture describes actual layering and dependency direction
4. Verify the directory structure matches the actual project
