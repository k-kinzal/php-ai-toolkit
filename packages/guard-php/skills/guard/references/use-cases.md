# Guard use cases

## Inspect and resolve findings

```bash
vendor/bin/guard rules --query=phpstan --format=ai
vendor/bin/guard check --query=phpstan --format=json
vendor/bin/guard check --fixable
vendor/bin/guard check --level=warning
```

`rules` explains configured expectations even for compliant or missing files.
Use `check` to inspect actual findings. An empty filtered report can still exit
`1`; finish with an unfiltered check.

For configuration repairs:

```bash
vendor/bin/guard fix --dry-run
vendor/bin/guard fix
vendor/bin/guard check
```

Review all proposed files: `fix` has no query filter and includes baselined
violations. An unsatisfied required configuration rule blocks the planned
writes. Resolve missing files, conflicting assertions, and manual fields before
retrying. Source, structure, and Markdown findings require manual changes and
do not block configuration repairs.

## Record existing violations

Use a baseline when the task calls for accepting existing findings while
detecting new ones:

```bash
vendor/bin/guard baseline
vendor/bin/guard check
vendor/bin/guard check --no-baseline
```

Review and commit `guard-baseline.json` with the policy. Entries match exact
paths, rule IDs, severity, messages, and occurrence counts. Changed metric
values, including improvements still above a limit, produce new findings.

To refresh after fixing findings, inspect `check --no-baseline` first, then
regenerate and review the diff. Regeneration records all current violations,
including new ones. Stale entries are reported as `unmatched` without failing.
Choose a location allowed by the project's structure policy. For an existing
`baselines/` directory:

```bash
vendor/bin/guard baseline --output=baselines/guard.json
vendor/bin/guard check --baseline=baselines/guard.json
```

## Create or inspect a policy

When a new policy is requested and none exists:

```bash
vendor/bin/guard init
```

`init` detects tool presets, production roots, and README headings, and migrates
`loc.yaml`, `tree.yaml`, and `doc-guard.yaml` settings when present. If no root
`docs/` directory exists, detection includes `disable-doc`, which forbids creating
that directory. Review the generated imports and declarations before using them.

To select only named presets instead of detection:

```bash
vendor/bin/guard init --import=metrics,structure
```

For an existing policy, edit within the requested scope and use `guard rules`
and `guard check` to verify it. Imports are relative to the file declaring them;
target paths are relative to the main policy. Later imports and project values
override earlier values while retaining unspecified fields.

`collect` applies to every policy. When present, it replaces section reading
scopes such as `metrics.source` and `structure.paths`; metrics then read every
PHP file in that collection. Declared documents and configuration files outside
it are silently skipped, including missing ones. Inspect it when expected
findings disappear. See the [policy schema](../../../docs/configuration.md)
for merge rules and scope syntax.

## Composer and monorepos

When adding a reusable command is requested, merge this into existing Composer
scripts and retain other gates:

```json
{
  "scripts": {"guard": "guard check"},
  "scripts-descriptions": {"guard": "Check project policies with Guard."}
}
```

Include `@guard` in `lint` when it is a project gate. In a monorepo, use the owning
package's command, or select its policy explicitly from the repository root:

```bash
composer --working-dir=packages/example guard
vendor/bin/guard check --config=packages/example/guard.yaml
```

The second form assumes Guard is installed at the repository root. `--config`
selects the policy and base for target paths; it does not change the executable.
