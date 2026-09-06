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

The unversioned requirement is intentional for a new install: Composer should
select the newest stable toolkit release compatible with the target project. Check
current package metadata and the target's PHP/dependency graph first. If the toolkit
is already constrained, update its lock to the newest admitted release and preserve
an intentional pin unless changing that policy is in scope. Never copy this
repository's root constraint or lock resolution.

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-tree-guard/tree.yaml` and apply it to the project root as `tree.yaml`.

Replace `REPLACE_WITH_PRODUCTION_ROOT` and `REPLACE_WITH_UNIT_TEST_ROOT` with every
maintained target-project root to which the corresponding policy applies. Remove a
rule only when that category truly does not exist; never leave a sentinel or point
it at an incidental example directory.

The starter template scans the project root (`paths: ['.']`) so the global
forbidden-directory rule reaches the whole repository. The file and directory
count limits apply to maintained production and unit-test roots, not to the
repository root where conventional package manifests and tool configuration
coexist. Keep `exclude` limited to generated and vendored directories, and add
the ones the project actually has.

The starter template forbids everywhere:

| Setting | Default | Meaning |
|---------|---------|---------|
| `deny_dirs` | `['scripts', 'Scripts']` | No directory named `scripts` anywhere, including the project root and `.github/`. Put automation in Composer scripts, a Makefile, or a workflow step instead of a loose script directory that AI agents fill with one-off files. |

After its path sentinels are replaced, the starter template enforces on every
selected production and unit-test root:

| Setting | Default | Meaning |
|---------|---------|---------|
| `forbid_empty` | `true` | Empty directories fail; delete leftovers or add intended contents. |
| `allow` | `['*.php']` | Only PHP files are allowed in source directories. |
| `file_case` | `pascal` | File stems must be PascalCase (PSR-4 class-per-file naming). |
| `dir_case` | `pascal` | Directory names must be PascalCase (PSR-4 namespace segments). |
| `max_files` | `15` | A maintained code or unit-test directory may contain at most 15 direct files. Split responsibilities before adding a sixteenth. |
| `max_dirs` | `20` | A maintained code or unit-test directory may contain at most 20 direct subdirectories. Introduce a meaningful grouping before adding a twenty-first. |
The limit value itself is allowed. A directory with exactly 15 files passes.

## Pattern Semantics

`rules[].path` patterns match whole relative directory paths segment by segment. A
`**` segment matches zero or more segments, so `<root>/**` also matches `<root>`
itself. Other segments match exactly one path segment with fnmatch, so `*` never
crosses `/`. The project root is the path `.` and carries no segment, so only `.`
and `**` match it. See the reference documentation for details.

## Adapting to the Project

Adapt paths and naming rules to the discovered layout; keep the limits on every
maintained production and unit-test root:

- Scan production roots discovered from Composer autoload and the maintained
  unit-test roots that exercise them. Replace the template's `src/**` and
  `tests/Unit/**` patterns when the project uses different roots; do not merely
  leave real code outside the limited paths.
- Add `max_depth` (for example `3`) on the root rule when the project keeps namespaces shallow.
- Add `deny` globs for forbidden name patterns such as `'*Helper.php'` when the project bans them, and `deny_dirs` globs on `path: '**'` for directory names the project bans everywhere.
- Add `require: ['README.md']` style rules for directories that must carry a specific file.
- Use `exclude` for generated directories only. Do not add broad excludes just to make violations pass; fix the structure or report the exact directories that need a project-level decision.

Do not apply the count limits to the repository root simply to claim broader
coverage: package manifests and independent tool configs do not form one source
responsibility. Conversely, do not narrow the scan to a hand-picked subset of
maintained code.

Do not raise `max_files` above 15 or `max_dirs` above 20 to fit the existing
production or unit-test tree. The existing tree is evidence to redesign, not the
policy. Group files by responsibility, move internal implementation behind
meaningful namespaces, and keep released public facades at their existing names.
Introducing TreeGuard does not authorize a public API change.

Do not invent incidental buckets such as `Common`, `Shared`, `Helpers`, or a
numbered split. The new directories must express stable ownership or dependency
boundaries that Deptrac can enforce.

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
