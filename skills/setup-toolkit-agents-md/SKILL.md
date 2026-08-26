---
name: setup-toolkit-agents-md
description: >-
  Set up AGENTS.md with project conventions and AI agent guidelines for a PHP project.
  Use when asked to create AGENTS.md, set up AI agent guidelines, or configure project conventions for AI agents.
---

# Setup AGENTS.md

This skill creates an AGENTS.md file that provides context and rules for AI agents working on the project.

Use this skill only when the user explicitly asks to create or change
`AGENTS.md`. Applying php-ai-toolkit, adding quality gates, or repairing project
architecture is not permission to edit agent instructions. If `AGENTS.md`
already exists, treat it as human-owned: inspect it for constraints, then ask
before changing even an outdated section.

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

### Supported Versions
Replace `{{SUPPORTED_VERSIONS}}` with the compatibility guarantees that agents
need while changing the project. Determine them from `composer.json`, the CI
matrix, and project documentation, and list only versions the project explicitly
supports.

Keep this section focused on the primary runtimes, platforms, frameworks, and
development tools whose versions materially affect project-wide changes. Do not
copy every direct or transitive package constraint from `composer.json`, and do not
list internal parser, adapter, or helper libraries merely because the project
supports multiple versions of them. Composer remains the source of truth for those
implementation dependencies.

Write the target's real constraint or enumerated CI-supported minors. Do not copy
a version list from this repository. Shape example:
```markdown
- **PHP**: <versions guaranteed by this project>
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

### Document References
Replace `{{DOCUMENT_REFERENCES}}` with links to project-specific documentation
directly under `docs/`. Keep this section as a short index of top-level entry
points; do not list files from nested directories such as `docs/rules/`. Detailed
pages should be linked from the relevant top-level document instead. Example:
```markdown
- [API Specification](docs/api-spec.md)
- [Database Schema](docs/schema.md)
```

If the project has no documentation directly under `docs/`, remove this section
entirely.

## Adaptation Workflow

When applying this template to a project, follow these steps:

1. **Read `composer.json`** to determine the primary supported runtimes and tools
2. **Scan the directory structure** to understand project layout
3. **Look for top-level documentation** directly under `docs/`
4. **Fill in all `{{PLACEHOLDER}}` values** with real project information
5. **Remove unused placeholders and sections**
6. **Place as `AGENTS.md`** in the project root only after confirming that no
   existing file will be overwritten

## Protecting AGENTS.md

After creating AGENTS.md, recommend setting up `.claude/settings.json` to prevent AI agents from modifying it. Do not edit an existing settings file unless the user explicitly asks:

```json
{
    "permissions": {
        "deny": [
            "Edit(/AGENTS.md)",
            "Bash(*AGENTS.md*)"
        ]
    }
}
```

Create `CLAUDE.md` only when the user explicitly asks for Claude-specific setup:

```markdown
<!-- NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you. -->
@AGENTS.md
```
