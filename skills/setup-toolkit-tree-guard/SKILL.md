---
name: setup-toolkit-tree-guard
description: >-
  Set up TreeGuard directory and file structure checks for a PHP project. Use
  when asked to configure tree-guard, tree.yaml, directory file-count limits,
  subdirectory-count limits, nesting depth limits, file or directory naming
  conventions, allowed or denied file patterns, required files per directory,
  empty directory detection, TreeGuard reporters, Composer scripts for
  TreeGuard, or CI checks for PHP project structure.
---

# Setup TreeGuard (Directory Structure Guardrails)

This skill configures `tree-guard`, the php-ai-toolkit CLI for directory file counts, subdirectory counts, subtree totals, nesting depth, naming conventions, required files, empty directories, and reporter output.

## Prerequisites

Inspect `composer.json` before configuring:

- Confirm the target project requires `k-kinzal/php-ai-toolkit`.
- Read Composer production autoload roots. Usually this is `src/`, not `tests/`.
- Check for existing structure config such as `tree.yaml` or custom scripts.
- Check existing Composer scripts and CI jobs.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-tree-guard/tree.yaml` and apply it to the project root as `tree.yaml`.

The starter template scans the project root (`paths: ['.']`) so that structure rules reach every directory of the repository, including the root itself and dotted directories such as `.github`. Keep `exclude` limited to generated and vendored directories, and add the ones the project actually has.

The starter template forbids everywhere:

| Setting | Default | Meaning |
|---------|---------|---------|
| `deny_dirs` | `['scripts', 'Scripts']` | No directory named `scripts` anywhere, including the project root and `.github/`. Put automation in Composer scripts, a Makefile, or a workflow step instead of a loose script directory that AI agents fill with one-off files. |

The starter template enforces on `src/`:

| Setting | Default | Meaning |
|---------|---------|---------|
| `forbid_empty` | `true` | Empty directories fail; delete leftovers or add intended contents. |
| `allow` | `['*.php']` | Only PHP files are allowed in source directories. |
| `file_case` | `pascal` | File stems must be PascalCase (PSR-4 class-per-file naming). |
| `dir_case` | `pascal` | Directory names must be PascalCase (PSR-4 namespace segments). |
| `max_files` | `25` | Directories with more than 25 direct files fail. |
| `max_dirs` | `20` | Directories with more than 20 direct subdirectories fail. |

The limit value itself is allowed. For example, a directory with exactly 25 files passes when `max_files` is `25`.

## Pattern Semantics

`rules[].path` patterns match whole relative directory paths segment by segment. A `**` segment matches zero or more segments, so `src/**` also matches `src` itself. Other segments match exactly one path segment with fnmatch, so `*` never crosses `/`. The project root is the path `.` and carries no segment, so only `.` and `**` match it. See the reference documentation for details.

## Adapting to the Project

Adapt rules to the discovered layout instead of copying blindly:

- Scan production roots discovered from Composer autoload. Add `tests/Unit` with `allow: ['*Test.php']` when the project pairs tests with sources.
- Add `max_depth` (for example `3`) on the root rule when the project keeps namespaces shallow.
- Add `deny` globs for forbidden name patterns such as `'*Helper.php'` when the project bans them, and `deny_dirs` globs on `path: '**'` for directory names the project bans everywhere.
- Add `require: ['README.md']` style rules for directories that must carry a specific file.
- Use `exclude` for generated directories only. Do not add broad excludes just to make violations pass; fix the structure or report the exact directories that need a project-level decision.

## Reporter

Keep `report.reporter: ai` by default for this toolkit. The AI reporter prints structured remediation guidance intended for coding agents.

Use `text` for concise human output and `json` for CI or machine consumers. Supported `order_by` fields are `path`, `rule`, `actual`, and `limit`.

## Recommended Composer Scripts

Add scripts that match the project:

```json
{
    "scripts": {
        "tree-guard": "tree-guard --config=tree.yaml",
        "lint": [
            "@format:check",
            "@phpstan",
            "@loc-guard",
            "@tree-guard",
            "@deptrac"
        ]
    }
}
```

If the project already has `lint` or `check`, merge `@tree-guard` into it after LocGuard and before Deptrac when those scripts exist. Do not remove existing lint steps.

## Verification

After applying:

```bash
vendor/bin/tree-guard --config=tree.yaml
```

Exit codes:

- `0`: no violations
- `1`: structure violations found
- `2`: configuration or runtime error

## References

- [TreeGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/tree-guard.md) — Settings, pattern semantics, and CLI behavior.
