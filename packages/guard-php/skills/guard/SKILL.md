---
name: guard
description: >-
  Work in a PHP project governed by Guard (k-kinzal/guard-php). Use when
  `guard check` or `guard apply` reports findings, when a change must keep
  passing Guard, or when writing or reviewing rules in guard.yaml: source
  metrics (`metrics.*`), directory structure (`structure.*`), Markdown
  documents (`documentation.*`), configuration fields, and extension policies.
  Use the setup-toolkit-*-guard skills for first adoption instead.
---

# Guard

Guard checks that a project has not drifted from what was decided in `guard.yaml`: how large and complex PHP code may be, what each directory may contain, which sections each Markdown document has, and which values tool configuration files hold. Every finding names a rule ID, a path and how to comply. Read [the policy overview](../../docs/policies.md) for the model and [the command line](../../docs/cli.md) for commands, options and exit codes.

## The rule that applies to every finding

Fix the project, not the policy. `guard.yaml` and the presets it imports record decisions made by a human. A finding means the change departs from one of them, so the change is what has to move.

Do not, unless a human asks for that exact change:

- edit `guard.yaml`, an imported preset or a file under `vendor/k-kinzal/guard-php/rules/`;
- raise a limit, add an `exclude`, add an assignment or profile, narrow `collect`, or drop an import;
- set a configuration rule to `level: recommended` or rewrite its `assert`;
- rename, move or reformat a file only so that a pattern stops matching it.

When a finding reflects a deliberate decision, stop, explain the finding and the proposed policy change, and let a human make it. The human changes `guard.yaml` in the same change as the code it permits.

## Workflow

1. Run `vendor/bin/guard check`. Use `--format=json` when parsing the result. Use `--config=FILE` when the policy is not `guard.yaml` in the working directory, such as a root policy in a monorepo.
2. Read the exit code: `0` passed, recommendations may remain; `1` a required rule is violated; `2` the command line, policy, an input document or an extension is invalid and nothing was checked. For `2`, read the `Guard error:` line and fix the cause it names.
3. Group the findings by rule ID and open the reference for each group:

   | Rule ID | Section | Reference |
   |---------|---------|-----------|
   | `metrics.*` | `metrics` | [Source metrics](references/metrics.md) |
   | `structure.*` | `structure` | [Directory structure](references/structure.md) |
   | `documentation.*` | `documentation` | [Markdown documents](references/documentation.md) |
   | the rule's own `id`, such as `phpstan.level` | `configuration` | [Configuration fields](references/configuration.md) |
   | an ID chosen by the extension, such as `app.strict-types` | `extensions` | [Extensions](references/extensions.md) |

4. Run `vendor/bin/guard apply --dry-run`, then `vendor/bin/guard apply`, when configuration findings remain. `apply` writes configuration fields only; metric, structure and document findings always need a manual edit.
5. Fix the remaining findings in code and documents, following the reference, and run `guard check` again until it exits `0`.
6. Run the project's other gates afterwards. A split method or a moved class must still pass the tests, PHPStan and Deptrac.

Before finishing any change in a Guard project, run `guard check` even if no finding was reported earlier: a new file, directory or heading is itself a finding.

## Writing rules

When a human asks for a new or changed rule, read the reference of its section first, then [the configuration reference](../../docs/configuration.md) for imports and overrides. These hold for every section:

- Override instead of copying. Import the preset and set only what the project does differently; an override keeps every imported value it does not mention.
- Keep the scope wide. Guard checks only what it reads, so every exclusion, assignment and narrowed scan is a place where drift goes unreported. Each one needs a reason that names the files and why they differ.
- Do not calibrate to the current state. A limit set to the largest existing method, or a heading list regenerated from the document an agent just edited, records the drift instead of preventing it.
- Run `guard check` after the edit. Unknown keys, overlapping assignments, unused profiles and stale patterns are configuration errors with exit code `2`.

## Collection scope

`collect.include` and `collect.exclude` limit which files every policy reads, built-in or extension. A declared document or configuration file outside the scope is skipped silently, not reported as missing. Treat `collect` as part of the policy: never narrow it to make a finding disappear, and when a finding unexpectedly does not appear, check whether the file lies outside it. See [Collection scope](../../docs/configuration.md#collection-scope).
