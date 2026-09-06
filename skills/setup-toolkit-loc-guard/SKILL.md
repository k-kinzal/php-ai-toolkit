---
name: setup-toolkit-loc-guard
description: >-
  Set up LocGuard source metrics checks for a PHP project. Use when asked to
  configure loc-guard, loc.yaml, source line-count limits, NCLOC limits,
  class/trait/interface/enum length limits, function or method length limits,
  cyclomatic complexity limits, LocGuard reporters, AI-oriented metrics reports,
  Composer scripts for LocGuard, or CI checks for PHP source metrics without PHPMD.
---

# Setup LocGuard (Source Metrics Guardrails)

This skill configures `loc-guard`, the php-ai-toolkit CLI for source LOC, NCLOC, class-like length, function length, method length, cyclomatic complexity, and reporter output.

## Prerequisites

Inspect `composer.json` before configuring:

- Confirm the target project requires `k-kinzal/php-ai-toolkit`.
- Read Composer production autoload roots. Usually this is `src/`, not `tests/`.
- Check for existing metrics config such as `loc.yaml`, PHPMD rulesets, PhpMetrics config, or custom scripts.
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

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-loc-guard/loc.yaml` and apply it to the project root as `loc.yaml`.

Replace `REPLACE_WITH_PRODUCTION_AUTOLOAD_ROOT` with every production autoload root
the target actually ships. A remaining sentinel or a zero-file scan is a failed
setup.

The default toolkit metrics are:

| Setting | Recommended | Meaning |
|---------|------------:|---------|
| `file.lines` | `500` | Files with more than 500 physical lines fail. |
| `file.ncloc` | `350` | Files with more than 350 non-comment lines of code fail. |
| `class.lines` | `400` | Classes with more than 400 physical lines fail. |
| `trait.lines` | `300` | Traits with more than 300 physical lines fail. |
| `interface.lines` | `200` | Interfaces with more than 200 physical lines fail. |
| `enum.lines` | `200` | Enums with more than 200 physical lines fail. |
| `function.lines` | `50` | Functions with more than 50 physical lines fail. |
| `method.lines` | `50` | Methods with more than 50 physical lines fail. |
| `function.cyclomatic_complexity` | `20` | Functions with complexity greater than 20 fail. |
| `method.cyclomatic_complexity` | `20` | Methods with complexity greater than 20 fail. |

The limit value itself is allowed. For example, a 50-line method passes when `method.lines` is `50`.

LocGuard has no implicit metric defaults. Keep every intended standard limit explicit under `policies.standard.limits`; an omitted metric in a root policy is disabled.

## Reporter

Keep `report.reporter: ai` by default for this toolkit. The AI reporter prints structured remediation guidance intended for coding agents.

Use `text` for concise human output and `json` for CI or machine consumers:

```yaml
report:
  reporter: ai
  order_by:
    - path
    - line
    - rule
```

Supported `order_by` fields are `path`, `line`, `rule`, `actual`, and `limit`. Prefer `path`, `line`, `rule` unless the project has a reason to group by rule or severity-like values.

## Analysis Paths

Run LocGuard only on production source paths discovered from Composer autoload roots. Do not include `tests/` by default; test method and fixture length are intentionally out of scope.

Configure exact source directories under `scan.roots`. For standard projects:

```yaml
scan:
  roots:
    - src
```

For non-standard production roots:

```yaml
scan:
  roots:
    - app
    - packages/Core/src
```

Use `exclude` for generated production files only:

```yaml
scan:
  roots:
    - src
  exclude:
    - 'src/Generated/**'
```

Do not add broad excludes just to make violations pass. Fix the source or report the exact files that need a project-level decision.

## Multiple Policies

When code has a structurally different but legitimate source shape, define a named policy and assign the exact files. Do not create a second config file or exclude the source:

```yaml
policies:
  standard:
    limits:
      file: { lines: 500, ncloc: 350 }
      class: { lines: 400 }
      function: { lines: 50, cyclomatic_complexity: 20 }
      method: { lines: 50, cyclomatic_complexity: 20 }

  native-api-adapter:
    extends: standard
    limits:
      file: { lines: 900, ncloc: 650 }
      class: { lines: 800 }

apply:
  default: standard
  rules:
    - name: native-api-adapters
      match:
        paths:
          - 'src/ZtdMysqli.php'
          - 'src/ZtdMysqliStatement.php'
      policy: native-api-adapter
```

Omitted child limits inherit the parent. An explicit `null` disables a limit. Prefer retaining method length and complexity limits when only an inherited native API surface makes a file or class larger.

All path rules must be disjoint. LocGuard rejects overlapping rules, rules matching no scanned PHP files, unused policies, empty scans, inheritance cycles, missing policy references, and unknown configuration keys.

## Recommended Composer Scripts

Add scripts that match the project:

```json
{
    "scripts": {
        "loc-guard": "loc-guard --config=loc.yaml",
        "lint": [
            "@format:check",
            "@phpstan",
            "@loc-guard",
            "@deptrac"
        ]
    }
}
```

If the project already has `lint` or `check`, merge `@loc-guard` into it after PHPStan and before Deptrac when those scripts exist. Do not remove existing lint steps.

## Verification

After applying:

```bash
vendor/bin/loc-guard --config=loc.yaml
```

For every non-default policy rule, explain at least one matched file and verify its effective limits:

```bash
vendor/bin/loc-guard --config=loc.yaml --explain=src/ZtdMysqli.php
```

Exit codes:

- `0`: no violations
- `1`: metric violations found
- `2`: configuration or runtime error

## References

- [LocGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/loc-guard.md) — Settings and CLI behavior.
