# Source metrics

Use `guard rules --query=metrics --format=json` to inspect effective limits and
`guard check --query=metrics --format=ai` to locate violations. Built-in metric
findings are required and need manual code changes.

| Rule ID | Measured element |
|---------|------------------|
| `metrics.file_lines`, `metrics.file_ncloc` | Physical lines or lines containing PHP code in a file. |
| `metrics.class_lines`, `metrics.trait_lines`, `metrics.interface_lines`, `metrics.enum_lines` | Lines in a type declaration. |
| `metrics.method_lines`, `metrics.function_lines` | Lines in a method, function, or closure. |
| `metrics.cyclomatic_complexity` | Branching complexity of a method or function. |

Follow the reported symbol and limit. Split responsibilities or simplify control
flow while preserving behavior; removing comments or compressing statements only
changes the measurement. Rerun the full check and affected tests after extraction.

For requested policy changes:

- Import `vendor/k-kinzal/guard-php/rules/metrics.yaml` for maintained defaults.
- Set `metrics.source` from production autoload roots. Without `collect`, tests
  and fixtures outside those roots are not measured; a scan finding no PHP files
  is an error. `collect`, when present, replaces `source` and `exclude`.
- Profiles inherit through `extends`. Omitted limits in a child stay inherited;
  omitted limits in a root profile are disabled. Explicit `null` disables a limit.
- Assignments use `{name, match: {paths: [...]}, policy}`. They must match scanned
  files and must not overlap. Use a named profile for an intentional exception.

See the [metrics schema](../../../docs/metrics.md)
for limits, counting rules, and assignment syntax.
