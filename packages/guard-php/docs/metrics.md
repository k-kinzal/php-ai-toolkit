# Metrics

The `metrics` section limits the size and complexity of production PHP code. Every scanned file gets exactly one profile of limits, and each element that exceeds a limit is a required finding. Guard does not shorten code; the finding stays until the code is split.

## Example

```yaml
version: 1
metrics:
  source: [src]
  exclude: ['src/Generated/**']
  profiles:
    standard:
      limits:
        file: {lines: 500, ncloc: 350}
        class: {lines: 400}
        trait: {lines: 300}
        interface: {lines: 200}
        enum: {lines: 200}
        function: {lines: 50, cyclomatic_complexity: 20}
        method: {lines: 50, cyclomatic_complexity: 20}
    adapter:
      extends: standard
      limits:
        file: {lines: 900, ncloc: 650}
        class: {lines: 800}
  default: standard
  assignments:
    - name: native-adapters
      match:
        paths: ['src/Adapter/Pdo*.php']
      policy: adapter
```

These are the limits of the shipped `rules/metrics.yaml`. The adapters that mirror a large native API get longer files and classes, but keep the method limits of `standard`.

## Fields

| Key | Default | Description |
|-----|---------|-------------|
| `source` | `[src]` | Directories to scan for `.php` files. Each must exist. |
| `exclude` | `[]` | Paths to leave out, such as generated code. Excluding a directory excludes everything below it. |
| `profiles` | | Required. Named sets of limits. See [Limits](#limits). |
| `profiles.<name>.extends` | | Another profile to inherit limits from. See [Inheritance](#inheritance). |
| `default` | `standard` | The profile of files that no assignment matches. |
| `assignments` | `[]` | Rules that give matching files another profile, each `{name, match: {paths}, policy}`. See [Assignments](#assignments). |

Scan production code only. Tests and fixtures are long on purpose and do not belong in `source`. A scan that finds no PHP file is a configuration error. When [`collect`](configuration.md#collection-scope) is set, it replaces `source` and `exclude`.

`exclude` and `match.paths` use the same patterns: `*` matches within one path segment, `**` matches any number of segments, and a pattern matches the whole path relative to `guard.yaml`. `src/*.php` matches `src/Example.php` but not `src/Nested/Example.php`; `src/**/*.php` matches both.

## Limits

| Limit | Shipped | Measures | Rule ID |
|-------|--------:|----------|---------|
| `file.lines` | 500 | Physical lines of the file. | `metrics.file_lines` |
| `file.ncloc` | 350 | Lines that hold PHP code, not counting comments, blank lines and the opening tag. | `metrics.file_ncloc` |
| `class.lines` | 400 | Lines from the class declaration to its closing brace. | `metrics.class_lines` |
| `trait.lines` | 300 | The same for a trait. | `metrics.trait_lines` |
| `interface.lines` | 200 | The same for an interface. | `metrics.interface_lines` |
| `enum.lines` | 200 | The same for an enum. | `metrics.enum_lines` |
| `function.lines` | 50 | Lines of a function. | `metrics.function_lines` |
| `method.lines` | 50 | Lines of a method. | `metrics.method_lines` |
| `function.cyclomatic_complexity` | 20 | Cyclomatic complexity of a function. | `metrics.cyclomatic_complexity` |
| `method.cyclomatic_complexity` | 20 | Cyclomatic complexity of a method. | `metrics.cyclomatic_complexity` |

A limit is a positive integer and is itself allowed: with `method.lines: 50`, a 50-line method passes and a 51-line method fails. A metric that a profile does not set is not checked; there are no built-in defaults. Each profile must check at least one metric.

The finding is reported on the file and names the element, such as `method Big::m has 52 physical lines; maximum is 50.`

### Inheritance

A profile with `extends` starts from the effective limits of its parent. A value it sets replaces the parent's, and `null` turns the metric off. Declaration order does not matter. An unknown parent or a cycle is a configuration error.

### Complexity

Cyclomatic complexity starts at `1` for each function or method and adds one for each `if`, `elseif`, `for`, `foreach`, `while`, `do`, `case`, `catch`, `&&`, `||`, `??`, `?` and `match` arm. A closure or arrow function is checked on its own, as `function {closure}`, against the function limits. Its branches do not add to the complexity of the function around it, but its lines count toward that function's length.

## Assignments

| Matching assignments | Profile of the file |
|----------------------|---------------------|
| none | `default` |
| one | its `policy` |
| several | configuration error that names the assignments |

Order never decides between assignments, and Guard does not guess which pattern is more specific: the patterns must not overlap. An assignment that matches no scanned file and a profile that nothing uses are configuration errors, because both usually mean the configuration is stale.

Relax only the limits that the reason for the assignment explains. A file that wraps a large native API needs a longer file and class, not longer or more complex methods.
