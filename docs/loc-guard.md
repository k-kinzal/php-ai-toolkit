# LocGuard

## Purpose

LocGuard is a first-party CLI for enforcing source metrics without PHPMD. It checks PHP files for physical file length, file NCLOC, class-like length, function length, method length, and cyclomatic complexity.

LocGuard separates source discovery, metric policies, and file-to-policy assignment. A project can therefore apply a different, named quality policy to code whose required shape differs, such as adapters that inherit large native APIs, without excluding that code from analysis.

## Command

Run:

```bash
vendor/bin/loc-guard --config=loc.yaml
```

Exit codes:

- `0`: no violations
- `1`: metric violations found
- `2`: configuration or runtime error

## Configuration

Example `loc.yaml`:

```yaml
scan:
  roots:
    - src
  exclude: []

policies:
  standard:
    limits:
      file:
        lines: 500
        ncloc: 350
      class:
        lines: 400
      trait:
        lines: 300
      interface:
        lines: 200
      enum:
        lines: 200
      function:
        lines: 50
        cyclomatic_complexity: 20
      method:
        lines: 50
        cyclomatic_complexity: 20

apply:
  default: standard
  rules: []

report:
  reporter: ai
  order_by:
    - path
    - line
    - rule
```

Every top-level and nested key is validated. Unknown keys are configuration errors so that a typo cannot silently disable a metric.

## Source Discovery

`scan.roots` is a required, non-empty list of existing source directories relative to the config directory. Absolute directory paths are also accepted. Files under an absolute root outside the config directory retain their normalized absolute paths in matching and reports. Scan production source roots only; do not include `tests/` by default because test method and fixture length are intentionally out of scope.

`scan.exclude` removes generated, vendored, or otherwise unmanaged paths from analysis. An exclusion that matches a directory also excludes its descendants:

```yaml
scan:
  roots:
    - src
  exclude:
    - 'src/Generated/**'
```

A scan that finds no PHP files is a configuration error. Files that require a different threshold must remain in the scan and receive another policy through `apply`; do not exclude them merely to make violations pass.

## Pattern Semantics

`scan.exclude` and `apply.rules[].match.paths` use the same anchored, segment-aware path patterns:

- `*` matches within exactly one path segment and never crosses `/`.
- `**` matches zero or more complete path segments.
- Patterns match complete config-relative paths with normalized `/` separators.

For example, `src/*.php` matches `src/Example.php` but not `src/Nested/Example.php`. The pattern `src/**/*.php` matches both.

## Policies and Limits

`policies` is a required, non-empty mapping of policy names to metric limits. There are no implicit runtime thresholds: a metric omitted from a root policy is disabled. The setup template writes all recommended limits explicitly.

| Limit | Recommended | Checks |
|-------|------------:|--------|
| `file.lines` | 500 | Physical lines in the complete file. |
| `file.ncloc` | 350 | Non-comment lines of PHP code. |
| `class.lines` | 400 | Physical lines from a class declaration through its closing brace. |
| `trait.lines` | 300 | Physical lines from a trait declaration through its closing brace. |
| `interface.lines` | 200 | Physical lines from an interface declaration through its closing brace. |
| `enum.lines` | 200 | Physical lines from an enum declaration through its closing brace. |
| `function.lines` | 50 | Physical lines in a function. |
| `method.lines` | 50 | Physical lines in a method. |
| `function.cyclomatic_complexity` | 20 | Cyclomatic complexity of a function. |
| `method.cyclomatic_complexity` | 20 | Cyclomatic complexity of a method. |

Limit values must be positive integers. The configured value itself is allowed: a 50-line method passes with `method.lines: 50`, while a 51-line method fails.

### Policy inheritance

A policy can inherit effective limits from another policy with `extends`. Omitted child values inherit the parent value, a positive integer replaces it, and an explicit `null` disables that metric:

```yaml
policies:
  standard:
    limits:
      file: { lines: 500, ncloc: 350 }
      function: { lines: 50, cyclomatic_complexity: 20 }
      method: { lines: 50, cyclomatic_complexity: 20 }

  native-api-adapter:
    extends: standard
    limits:
      file:
        lines: 900
        ncloc: 650
      class:
        lines: 800
```

Inheritance is independent of declaration order. Missing parents and inheritance cycles are configuration errors. Every effective policy must enable at least one metric.

## Policy Assignment

`apply.default` names the policy used when no path rule matches. Each optional rule has a unique name, one or more path patterns, and a policy:

```yaml
apply:
  default: standard
  rules:
    - name: native-api-adapters
      match:
        paths:
          - 'src/ZtdMysqli.php'
          - 'src/ZtdMysqliStatement.php'
          - 'src/ZtdPdo.php'
          - 'src/ZtdPdoStatement.php'
      policy: native-api-adapter
```

Every scanned file receives exactly one policy:

- No rule match: use `apply.default`.
- One rule match: use that rule's policy.
- Multiple rule matches: fail and identify the conflicting rule names.

Rule order never establishes precedence, and LocGuard never guesses which glob is more specific. Rule patterns must be disjoint. A rule that matches no scanned PHP files and a policy that is never referenced are configuration errors because both usually indicate stale configuration.

For native API adapters, relax only the file or class-like limits caused by the inherited surface. Function and method length or complexity remain inherited from the standard policy unless explicitly changed.

## Explain Effective Policy

Inspect the policy and effective limits selected for one scanned file:

```bash
vendor/bin/loc-guard --config=loc.yaml --explain=src/ZtdMysqli.php
```

Example output:

```text
Path: src/ZtdMysqli.php
Matched rule: native-api-adapters
Policy: native-api-adapter
Extends: standard

Effective limits:
  file.lines: 900
  file.ncloc: 650
  class.lines: 800
  method.lines: 50
  method.cyclomatic_complexity: 20
```

Disabled metrics are printed as `disabled`. Explaining a path outside `scan.roots`, excluded by `scan.exclude`, or not ending in `.php` returns exit code `2`.

## Complexity

LocGuard starts each function or method at complexity `1` and increments for branch points such as `if`, `elseif`, loops, `case`, `catch`, boolean operators, null coalescing, ternary branches, and `match` arms.

Function and method complexity have separate limits, allowing a policy to express different thresholds without coupling them.

## Reporting

Configure the reporter with `report.reporter`:

- `ai`: structured text with remediation guidance for coding agents.
- `text`: concise human-readable output.
- `json`: machine-readable JSON for CI and tooling.

Override the configured reporter from the CLI:

```bash
vendor/bin/loc-guard --config=loc.yaml --reporter=json
vendor/bin/loc-guard --config=loc.yaml --format=text
```

Each violation includes the effective policy name. JSON violations expose it in the `policy` field, and text or AI reports print it next to the violated limit.

Configure violation ordering with `report.order_by`. Supported fields are `path`, `line`, `rule`, `actual`, and `limit`.
