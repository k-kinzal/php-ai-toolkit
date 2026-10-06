# Loc policy

`Guard\Policy\MetricLimits` declares PHP inputs and checks their shared `php.metrics` structures. Configure it in the `metrics` section of [guard.yaml](guard.md); run `vendor/bin/guard check` or `vendor/bin/guard check --format=json`. Findings use the `metrics.` rule prefix. Source repairs remain manual.

## Configuration

Import `vendor/k-kinzal/guard-php/rules/metrics.yaml` for the standard limits and override the selected roots:

```yaml
version: 1
imports:
  - vendor/k-kinzal/guard-php/rules/metrics.yaml
metrics:
  source: [src]
  exclude: []
```

Alternatively, define `metrics.profiles`, `metrics.default` and `metrics.assignments` directly without an import. `guard init` migrates an existing `loc.yaml` without changing its thresholds.

## Source Discovery

`metrics.source` (default `[src]`) is a list of existing source directories relative to the config directory. Absolute directory paths are also accepted. Files under an absolute root outside the config directory retain their normalized absolute paths in matching and reports. Scan production source roots only; do not include `tests/` by default because test method and fixture length are intentionally out of scope.

`metrics.exclude` removes generated, vendored, or otherwise unmanaged paths from analysis. An exclusion that matches a directory also excludes its descendants:

```yaml
metrics:
  source:
    - src
  exclude:
    - 'src/Generated/**'
```

A scan that finds no PHP files is a configuration error. Files that require a different threshold must remain in the scan and receive another policy through `metrics.assignments`; do not exclude them merely to make violations pass.

## Pattern Semantics

`metrics.exclude` and `metrics.assignments[].match.paths` use the same anchored, segment-aware path patterns:

- `*` matches within exactly one path segment and never crosses `/`.
- `**` matches zero or more complete path segments.
- Patterns match complete config-relative paths with normalized `/` separators.

For example, `src/*.php` matches `src/Example.php` but not `src/Nested/Example.php`. The pattern `src/**/*.php` matches both.

## Policies and Limits

`metrics.profiles` is a required, non-empty mapping of policy names to metric limits. There are no implicit runtime thresholds: a metric omitted from a root policy is disabled. The setup template writes all recommended limits explicitly.

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
metrics:
  profiles:
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

`metrics.default` names the policy used when no path rule matches. Each optional rule has a unique name, one or more path patterns, and a policy:

```yaml
metrics:
  default: standard
  assignments:
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

- No rule match: use `metrics.default`.
- One rule match: use that rule's policy.
- Multiple rule matches: fail and identify the conflicting rule names.

Rule order never establishes precedence, and MetricLimits never guesses which glob is more specific. Rule patterns must be disjoint. A rule that matches no scanned PHP files and a policy that is never referenced are configuration errors because both usually indicate stale configuration.

For native API adapters, relax only the file or class-like limits caused by the inherited surface. Function and method length or complexity remain inherited from the standard policy unless explicitly changed.

## Complexity

The PHP metric parser starts each function or method at complexity `1` and increments for branch points such as `if`, `elseif`, loops, `case`, `catch`, boolean operators, null coalescing, ternary branches, and `match` arms.

Function and method complexity have separate limits, allowing a policy to express different thresholds without coupling them.
