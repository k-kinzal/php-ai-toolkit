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

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-loc-guard/loc.yaml` and apply it to the project root as `loc.yaml`.

The default toolkit metrics are:

| Setting | Default | Meaning |
|---------|---------|---------|
| `limits.max_file_lines` | `500` | Files with more than 500 physical lines fail. |
| `limits.max_file_ncloc` | `350` | Files with more than 350 non-comment lines of code fail. |
| `limits.max_class_lines` | `400` | Classes with more than 400 physical lines fail. |
| `limits.max_trait_lines` | `300` | Traits with more than 300 physical lines fail. |
| `limits.max_interface_lines` | `200` | Interfaces with more than 200 physical lines fail. |
| `limits.max_enum_lines` | `200` | Enums with more than 200 physical lines fail. |
| `limits.max_function_lines` | `50` | Functions with more than 50 physical lines fail. |
| `limits.max_method_lines` | `50` | Methods with more than 50 physical lines fail. |
| `limits.max_cyclomatic_complexity` | `20` | Functions or methods with complexity greater than 20 fail. |

The limit value itself is allowed. For example, a 50-line method passes when `max_method_lines` is `50`.

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

For standard projects:

```yaml
paths:
  - src
```

For non-standard production roots:

```yaml
paths:
  - app
  - packages/Core/src
```

Use `exclude` for generated production files only:

```yaml
exclude:
  - 'src/Generated/*'
```

Do not add broad excludes just to make violations pass. Fix the source or report the exact files that need a project-level decision.

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

Exit codes:

- `0`: no violations
- `1`: metric violations found
- `2`: configuration or runtime error

## References

- [LocGuard Configuration](vendor/k-kinzal/php-ai-toolkit/docs/loc-guard.md) — Settings and CLI behavior.
