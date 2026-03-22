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

Read the template from `vendor/k-kinzal/php-ai-toolkit/templates/AGENTS.md`.

```markdown
<!-- NOTE: You do not have permission to overwrite this file. Please ask a human operator to perform the changes for you. -->

# AGENTS

{{PROJECT_DESCRIPTION}}

## Project Tradeoff Sliders

- Scope     ●————————→ HIGH — Deliver the full intended scope; do not cut corners or skip requirements.
- Quality   ●————————→ HIGH — Quality is the top priority. Correctness, test coverage, and strict static analysis come first.
- Time      ←————————● LOW — There is no deadline pressure. Take the time needed to get it right.
- Cost      ←————————● LOW — Resource constraints are not a concern. Invest in doing things properly.

When in doubt, prioritize quality over everything else. It is better to ship less with confidence than to ship more with uncertainty.

## Tech Stack

{{TECH_STACK}}

## Coding Rules

- All code MUST pass PHPStan at level max with strict rules before committing
- All code MUST pass PHP-CS-Fixer checks before committing
- All test classes MUST have `#[CoversClass(...)]` attribute
- When lint errors include fix instructions (Tip:), follow them exactly
- Every public class in `src/` must have a corresponding test in `tests/Unit/`
- No `@phpstan-ignore` comments — fix the underlying type issue instead
- No `//` inline comments — use PHPDoc comments when documentation is needed
- No suppression of errors, warnings, or notices with `@` operator

{{ADDITIONAL_CODING_RULES}}

## Directory Structure

\`\`\`
{{DIRECTORY_STRUCTURE}}
\`\`\`

## Document References

{{DOCUMENT_REFERENCES}}
```

## Section Explanations

### NOTE Comment
The `<!-- NOTE -->` comment at the top tells AI agents that this file is human-managed and should not be modified by agents. This prevents accidental overwrites.

### Project Description
Replace `{{PROJECT_DESCRIPTION}}` with a 1-3 sentence summary of what the project does. Example:
> A REST API for managing inventory data. Built on Laravel 11 with PostgreSQL. Serves the mobile app and admin dashboard.

### Tradeoff Sliders
These calibrate AI behavior. The defaults are set to HIGH scope/quality, LOW time/cost — meaning the agent should prioritize correctness over speed. Adjust if the project has different priorities:
- For prototypes/MVPs: reduce Quality to MEDIUM, increase Time to HIGH
- For production services: keep defaults (HIGH quality, LOW time)

### Tech Stack
Replace `{{TECH_STACK}}` with a bulleted list. Derive this from `composer.json`. Example:
```markdown
- PHP 8.3
- Laravel 11
- PostgreSQL 16
- PHPStan (Level Max)
- PHP-CS-Fixer
- PHPUnit
```

### Coding Rules
The preset rules enforce php-ai-toolkit standards. Replace `{{ADDITIONAL_CODING_RULES}}` with project-specific rules or remove the placeholder if none. Examples of project-specific rules:
- "All database queries must use Eloquent; no raw SQL"
- "API responses must follow JSON:API specification"
- "Service classes must implement an interface"

### Directory Structure
Replace `{{DIRECTORY_STRUCTURE}}` with the actual tree. Generate it by scanning the project:
```bash
find . -type d -not -path '*/vendor/*' -not -path '*/.git/*' -not -path '*/node_modules/*' | head -30 | sort
```

Include descriptions for key directories. Example:
```
src/
  Controller/     # HTTP request handlers
  Service/        # Business logic
  Repository/     # Data access layer
  Entity/         # Domain models
tests/
  Unit/           # Unit tests (mirrors src/ structure)
  Integration/    # Integration tests with database
```

### Document References
Replace `{{DOCUMENT_REFERENCES}}` with links to key docs. Example:
```markdown
- [API Specification](docs/api-spec.md)
- [Database Schema](docs/schema.md)
- [Deployment Guide](docs/deploy.md)
```

If the project has no docs, remove this section entirely.

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
2. Verify the directory structure matches the actual project
3. Verify the tech stack matches `composer.json`
