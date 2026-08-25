---
name: setup-toolkit-scope-guard
description: >-
  Set up ScopeGuard namespace visibility checks for a PHP project. Use when
  asked to configure scope-guard, scope.yaml, the @visibility tag, package
  private or internal classes, pub(crate) or pub(super) style visibility in PHP,
  restricting a class to its own namespace, stopping other namespaces from
  reaching into a package's internals, exempting tests from visibility rules,
  ScopeGuard reporters, Composer scripts for ScopeGuard, or CI checks for PHP
  namespace visibility.
---

# Setup ScopeGuard (Namespace Visibility Guardrails)

This skill configures `scope-guard`, the php-ai-toolkit CLI that enforces the namespace visibility scopes declared with `@visibility`. It gives PHP the middle ground Rust spells `pub(crate)`, `pub(super)`, and `pub(in path)`.

## Prerequisites

Inspect `composer.json` before configuring:

- Confirm the target project requires `k-kinzal/php-ai-toolkit`.
- Read Composer production autoload roots. Usually this is `src/`, not `tests/`.
- Read the test autoload roots and their namespace prefix, usually `Tests\`.
- Check existing Composer scripts and CI jobs.

Install the toolkit if missing:

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-scope-guard/scope.yaml` and apply it to the project root as `scope.yaml`.

| Setting | Default | Meaning |
|---------|---------|---------|
| `paths` | `['src']` | Files and directories to scan. Both declarations and references are read from here. |
| `exclude` | `[]` | fnmatch globs of project-relative paths to skip, for generated sources. |
| `exempt_namespaces` | `[]` | Namespace prefixes whose scanned code may name any declaration regardless of its scope. |
| `report.reporter` | `ai` | `ai`, `text`, or `json`. |
| `report.order_by` | `['path', 'line']` | Violation ordering: `path`, `line`, `rule`, `symbol`. |

Set `paths` from the discovered autoload roots. The template scans production code
only, so its exemption list is empty: a namespace that is never scanned needs no
exemption. Add a test namespace only when test roots are intentionally present in
`paths`, and record that project-derived reason.

## Adapting to the Project

The command reports nothing until declarations carry a `@visibility` tag, so adoption is the work, not the configuration. Do not add tags in bulk. Find the boundaries the project already has and state them:

- A namespace with one obvious entry point and several collaborators behind it: tag the collaborators `@visibility namespace`, leave the entry point public.
- A package split across sibling namespaces that share internals: tag the shared parts `@visibility parent`, or name the owning namespace explicitly with `@visibility App\Billing`.
- A library whose whole implementation is off-limits to consumers: tag it `@visibility root`.
- A class that must not be constructed outside its namespace but stays usable: tag only `__construct`.

Report the boundaries found and ask before tagging anything whose ownership is unclear. A tag is a design decision, not a lint fix.

If `paths` includes test directories, add the test namespace prefix to `exempt_namespaces`. PHP has no counterpart to Rust's `#[cfg(test)] mod tests` declared inside the module it covers, so a unit test of a namespace-scoped class necessarily sits in another namespace.

## What Is Checked

ScopeGuard resolves written names, not inferred types. Instantiation, static calls, static property and constant access, `::class`, `instanceof`, `extends`, `implements`, `use <Trait>`, parameter, return and property types, catch types, and attributes are all checked. A member reached through a variable is not resolved; the class scope still holds, because the object had to be named somewhere.

`self`, `static`, and `parent` are never reported.

## Reporter

Keep `report.reporter: ai` by default for this toolkit. The AI reporter prints structured remediation guidance intended for coding agents.

## Recommended Composer Scripts

Add scripts that match the project:

```json
{
    "scripts": {
        "scope-guard": "scope-guard --config=scope.yaml",
        "lint": [
            "@format:check",
            "@phpstan",
            "@loc-guard",
            "@tree-guard",
            "@scope-guard",
            "@deptrac"
        ]
    }
}
```

If the project already has `lint` or `check`, merge `@scope-guard` into it before Deptrac. Do not remove existing lint steps.

## Verification

After applying:

```bash
vendor/bin/scope-guard --config=scope.yaml
```

Exit codes:

- `0`: no violations
- `1`: visibility violations found
- `2`: configuration or runtime error

Confirm the summary line reports the expected file count, then tag one declaration and confirm the violation appears before tagging more.

## Fixing Violations

Prefer moving the referencing code into the namespace that owns the declaration. When several namespaces need the same declaration, export one public entry point from the owning namespace instead of widening the scope. Widen the tag only when the scope was drawn too narrowly; each message names the narrowest scope that would admit the reference.

Deleting the tag is not a fix. It removes the boundary instead of respecting it.

## References

- [ScopeGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/scope-guard.md) — Tag semantics, checked references, and CLI behavior.
