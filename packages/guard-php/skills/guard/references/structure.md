# Directory structure

Use `guard rules --query=structure` to inspect directory constraints and
`guard check --query=structure` to find violations. All built-in structure
findings are required; Guard does not move, rename, create, or delete entries.

| Rule ID | Required edit |
|---------|---------------|
| `structure.denied_file`, `structure.disallowed_file` | Follow the reported filename or allowed-file constraint. |
| `structure.denied_dir`, `structure.disallowed_dir` | Follow the reported directory constraint; `disable-doc` forbids root `docs/`. |
| `structure.max_files`, `structure.max_dirs`, `structure.max_total_files` | Reduce the reported count, grouping related responsibilities where appropriate. |
| `structure.max_depth` | Reduce directory nesting. |
| `structure.missing_required_file` | Add the named file with the required content. |
| `structure.empty_directory` | Populate the intended directory or remove it. |
| `structure.file_case`, `structure.dir_case` | Rename to the configured convention. |

For PHP moves, update PSR-4 namespaces and callers, then verify autoloading,
tests, and any architecture checks. Use the effective rule's message: filename
restrictions come from project policy and imported presets.

For requested policy changes:

- Import `vendor/k-kinzal/guard-php/rules/structure.yaml` for maintained defaults.
- `structure.paths` sets scan roots; `collect` replaces section reading scopes
  when present. Keep intended directories inside the scan.
- Every matching directory rule applies independently. A narrower rule cannot
  relax a broader one. Imports merge declarations with the same `path`.
- Patterns match the whole project-relative path; `src/**` includes `src` itself.
- `allow: []` forbids files; omitting `allow` imposes no filename restriction.
  Combine `require` and `allow` when only specific files may be present.

See the [structure schema](../../../docs/structure.md)
for path patterns, naming conventions, and count limits.
