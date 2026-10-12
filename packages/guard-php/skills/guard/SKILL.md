---
name: guard
description: >-
  Check PHP project policies with the Guard CLI (k-kinzal/guard-php). Use for
  guard commands, rule inspection, findings, configuration repairs, baselines,
  guard.yaml changes, or Guard checks in GitHub Actions.
---

# Guard

Run the installed CLI from the target project's root. Read its `guard.yaml` and
existing Composer command to identify the policy and paths in use. Use the
project's `composer guard` script for its usual check; invoke the CLI directly
when selecting commands or options:

```bash
vendor/bin/guard --help
vendor/bin/guard rules
vendor/bin/guard check
```

## Command rules

- `--config=FILE` selects a policy relative to the working directory. Project
  paths and baseline paths resolve relative to that policy's directory.
- Use `--format=ai` for compact diagnostics or `--format=json` for parsing.
  `--query`, `--fixable`, and `--level` on `check` filter display only; hidden
  errors still fail the command.
- Preview repairs with `guard fix --dry-run`, then use `guard fix` to apply them
  within the requested scope. Built-in repairs change configuration fields;
  metrics, directory structure, and Markdown findings need manual edits.
- Preserve the intended policy when resolving findings. Change limits, scope,
  imports, or baselines only when the task calls for that policy change.

After repairs, review the diff, rerun an unfiltered `guard check`, and run the
affected tool's checks. Exit `0` means no active required violations, `1` means
required violations remain, and `2` means an invalid command or input; read the
`Guard error:` diagnostic. Baseline suppression can leave existing violations
outside the result.

## References

Read only the reference needed for the current task:

- [CLI](references/cli.md): commands, options, reports, exit codes, and errors.
- [Use cases](references/use-cases.md): finding triage, repairs, baselines,
  `init`, policy scope, Composer commands, and monorepos.
- [GitHub Actions](references/github-actions.md): CI checks, package directories,
  and machine-readable reports.
- [Metrics](references/metrics.md): `metrics.*` findings, limits, and profiles.
- [Structure](references/structure.md): `structure.*` findings and path rules.
- [Documentation](references/documentation.md): `documentation.*` findings and
  Markdown declarations.
- [Configuration fields](references/configuration.md): field assertions and
  blocked repairs.
- [Extensions](references/extensions.md): extension findings and loading errors.
