---
name: setup-toolkit-infection
description: >-
  Set up Infection mutation testing for a PHP project. Use when asked to configure
  Infection, infection.json5, mutation testing, mutation score (MSI) or covered MSI
  thresholds, a mutation score gate that differs between the default branch and pull
  requests, mutation testing of changed lines only, Composer scripts for Infection,
  CI jobs for mutation testing, or when asked why a test suite with high coverage
  still lets mutants escape.
---

# Setup Infection (Mutation Testing)

This skill configures Infection, the PHP mutation testing framework, as a quality
gate with two thresholds: one for the whole source tree and a stricter one for the
lines a pull request changes.

Line coverage says a line ran. Mutation testing says the tests noticed what the line
did. It is the check that catches the failure mode this toolkit exists for:
AI-generated tests that execute code and assert nothing meaningful about it.

## Prerequisites

Inspect the project before configuring:

- Confirm the project requires `k-kinzal/php-ai-toolkit` and has a working PHPUnit
  setup (`/setup-toolkit-phpunit`).
- Read `composer.json`: production autoload roots, the PHP floor, existing test and
  coverage scripts, and `config.allow-plugins`.
- Check for existing mutation config: `infection.json`, `infection.json5`, or either
  with a `.dist` suffix.
- Check that a coverage driver is available. Infection needs pcov or Xdebug.

Determine Infection's installation topology before choosing its version. Inspect
the target's PHP range, PHPUnit version, Composer locks, and the PHP runtime of the
mutation job. Then inspect current Composer metadata and Infection's release/schema
documentation and select the newest compatible release. Do not copy this toolkit
repository's historical multi-line constraint.

If root development dependencies install on every supported PHP minor, derive the
smallest constraint that lets each real graph resolve its newest compatible
Infection release. Prefer one current line; add an older line only for a supported
leg that proves it is necessary. If Infection runs from an isolated tool manifest,
container, or job, constrain it for that runtime instead of burdening every product
runtime. Verify every relevant lock or CI leg and diagnose an unexpected resolution
with `composer why-not`:

```bash
composer require --dev "infection/infection:<target-derived-constraint>" --dry-run
composer why-not infection/infection <newest-compatible-version>
composer config allow-plugins.infection/extension-installer true
```

Run the confirmed requirement without `--dry-run`, then enable the plugin only if
the resolved package requires it. Do not assume the minimum PHP version or supported
Infection line from this repository; those are release properties to verify at
application time.

## Templates

Read the templates from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-infection/` and apply them to
the project root:

| Template | Target | Scope |
|----------|--------|-------|
| `infection.json5` | `infection.json5` | The only configuration file |
| `mutation-job.yml` | merge into `.github/workflows/ci.yml` | CI job for both gates |

Write one configuration file, not one per gate. Infection reads a single file and
takes per-run thresholds from `--min-msi` and `--min-covered-msi`, which is how its
own documentation drives CI. A second file duplicates the scope, the mutators, and
the exclusions, and the day one copy is edited the two gates start measuring
different things.

Pass the configuration file explicitly on every invocation
(`--configuration=infection.json5`). Infection otherwise picks the first file it
finds from `infection.json5`, `infection.json`, `infection.json5.dist`,
`infection.json.dist`, so an unrelated file dropped into the project root can
silently take over the gate.

`mutation-job.yml` contains `REPLACE_WITH_*` sentinels for the target's selected
tool runtime, extensions, and lock policy. Replace all of them before merging the
job; none is a default supplied by this repository.

Replace `REPLACE_WITH_PRODUCTION_SOURCE_ROOT` in `infection.json5` with all target
production roots that should be mutated. Confirm the run generates mutants from
each root.

Put the whole-tree baseline in the file and the changed-lines bar on the command
line. Pass `--ignore-msi-with-no-mutations` with the changed-lines flags rather than
setting `ignoreMsiWithNoMutations` in the file: a change that mutates nothing must
not score 0%, but a whole-tree run that generates nothing is a misconfiguration and
should fail rather than pass silently.

Check `vendor/bin/infection --version` and validate the shipped configuration
against that installed release before copying version-specific keys. Configure the
newest resolved line first. When a target genuinely resolves an older line, consult
that line's schema and apply its documented spelling or limitation deliberately; a
schema-invalid file is not a cross-version configuration, and the template's own
lock resolution is not evidence about the target.

## Why Two Thresholds

Infection reports two scores:

- **MSI** — killed mutants over all mutants, including mutants in code no test
  covers. It answers "how much of the source is verified".
- **Covered MSI** — killed mutants over the mutants in covered code only. It answers
  "where tests do run, do they assert anything".

A measured score describes the current suite; it does not define an acceptable
suite. The toolkit therefore supplies fixed minimums. Adoption includes paying
down enough weak tests and design debt to reach them rather than lowering the gate
to the score that happened to be measured.

| Scope | Where the numbers live | `minMsi` | `minCoveredMsi` | Why |
|-------|------------------------|----------|-----------------|-----|
| Whole source tree | `infection.json5` | 80 | 80 | Fixed toolkit policy for the complete production tree |
| Changed lines | `--min-msi` / `--min-covered-msi` in CI | 85 | 85 | Fixed toolkit policy for new code |

## Setting the Thresholds

The shipped 80/80 whole-tree and 85/85 changed-lines values are fixed policy, not
placeholders or measurements.

1. Run the gate with the thresholds neutralised:

   ```bash
   vendor/bin/infection --configuration=infection.json5 --threads=max --min-msi=0 --min-covered-msi=0
   ```

2. If either whole-tree score is below 80, keep the template unchanged and fix the
   suite and production design until both pass. The adoption is incomplete while
   the default branch cannot meet the baseline.
3. Read `build/infection/per-mutator.md` and
   `build/infection/escaped.log`. Add assertions for observable behavior first.
   When equivalent mutants cluster around an implementation idiom, improve that
   design instead of treating every survivor as permanent. Optional constructor
   injection such as `$this->x = $x ?? new X()` is a common example: prefer explicit
   construction or required dependencies when the fallback exists only for test
   convenience.
4. If the project already exceeds a floor, keep the shipped threshold. Record the
   measured result as evidence that the gate is feasible, not as a new policy.
   Tighten a threshold only when a human explicitly chooses that quality policy;
   do not derive it by rounding a single run.
5. Pass `--ignore-msi-with-no-mutations` on the changed-lines run. A pull request
   that touches only documentation, tests, or configuration produces no mutants, and
   a scoreless run must not be reported as 0%.
6. Re-measure after changes, but never rewrite policy from the measurement. A
   later threshold change remains an explicit human decision.

Lowering a threshold to make a red build green defeats the gate. So does disabling a
mutator, widening `source.excludes`, or pointing the gate at fewer directories. Fix
the tests, and ask a human operator when an exception is genuinely justified.

## Mutators

Keep `"@default": true`. The default profile is what the published mutation scores
of other projects mean, and a trimmed profile makes the number incomparable and
usually flatters the suite.

Resist disabling a mutator that produces equivalent mutants when it also kills real
ones. `Coalesce` still catches genuine missing tests on `??` over data even when a
particular construction idiom produces equivalent survivors. Improve that idiom;
disable a mutator only when it produces nothing but equivalent mutants for the
project, with human approval and the reason recorded next to the setting.

## Analysis Scope

Mutate production source only:

```json5
"source": {
    "directories": ["REPLACE_WITH_PRODUCTION_SOURCE_ROOT"]
}
```

`source.excludes` entries are relative to each source directory, not to the project
root. Resolve every path from the target's selected source directories.

Exclude a directory only when its tests cannot run in the same job as the gate — for
example code exercised exclusively by a legacy PHPUnit configuration on an older PHP
version. Write the reason in the file. Do not exclude code because its mutants
survive.

## Timeouts

`timeout` is an operational starting point, not a quality threshold. Measure the
slowest relevant mutant test and set it above that duration with enough runner
headroom; do not keep `10` merely because the first run was green. A timeout must
not count as a killed mutant:

- Infection 0.32.3 and later must set `timeoutsAsEscaped: true` and
  `maxTimeouts: 0`.
- Older supported Infection lines cannot express that policy. Prefer running the
  isolated mutation job on a current Infection line. If the target is genuinely
  pinned older, report the limitation and choose a timeout above the measured test
  duration; do not pretend timeout classification is enforced.

## Running Against a Pre-generated Coverage Report

The templates run Infection with `--coverage` and `--skip-initial-tests`, and a
separate Composer script produces the coverage report. That is not an optimisation.
Infection's own initial test run is unusable against a strict toolkit PHPUnit
configuration for two reasons:

- Infection stops the initial test process at the first byte written to STDERR.
  This toolkit's AI test reporter writes issues to STDERR, so a single risky or
  failing test aborts the run.
- Infection adds `stopOnDefect="true"` to the PHPUnit configuration it generates.
  With `beStrictAboutCoverageMetadata="true"`, every test that touches code it does
  not declare as covered is risky, so the run stops after the first such test — and
  if the exit code is relaxed with `--do-not-fail-on-risky`, Infection accepts that
  near-empty coverage report and scores a handful of mutants as if it had scored
  them all.

Generating coverage with the project's own PHPUnit configuration avoids both. Keep
`"testFrameworkExtraArgs": "--no-extensions"` in both configurations as well: in AI
mode the reporter replaces PHPUnit's result output, and Infection reads that output
to tell a killed mutant from an escaped one. Without the flag, an agent running the
gate locally scores every mutant as killed.

`testFrameworkExtraArgs` arrived in Infection 0.34. On an older line the same value
goes under `testFrameworkOptions`, which every version from 0.26 accepts and 0.34
and later still honour; setting both is an error.

The gate is only as honest as the coverage report it is handed. Regenerate coverage
in the same command that runs Infection — the Composer scripts below do — so a stale
report can never be scored.

## Do Not Wrap It in Composer Scripts

Write the commands into the workflow. This gate runs in CI, so a Composer script
would add a layer to look through, a second place for the flags to drift from the
job that actually runs them, and Composer's 300-second process timeout to work
around — a mutation run on a suite of any size takes longer than that, and the
script dies partway through with a process timeout instead of a score.

Composer scripts earn their place when a command is run by hand on every save, like
`composer lint`. Mutation testing is not that command, and it does not belong inside
`composer lint` either: those gates are seconds, this one is minutes.

Do not explain this tooling in the target project's product `docs/` or rewrite its
README or `AGENTS.md`. The workflow and this vendor skill are the development
documentation unless the user explicitly names another developer-owned location.

## CI Wiring

Merge `mutation-job.yml` into `.github/workflows/ci.yml` as a separate job. It needs
four things the other jobs do not:

- `fetch-depth: 0` on checkout, so the pull request can be diffed against its base.
- A coverage driver: `coverage: pcov` in `setup-php`.
- A step that produces the coverage report before Infection reads it.
- A branch on the event: a changed-lines step guarded by
  `if: github.event_name == 'pull_request'`, a whole-tree step guarded by the
  negation.

Use the highest PHP version in the target's supported matrix for this one-off job,
not the template's literal version. Derive its extensions from Composer platform
requirements and the commands in the job, require the selected lock file to exist,
and run `composer check-platform-reqs` after installation. Treat job timeout,
memory limits, and `--threads` as measured operational values: size them from the
observed run and runner capacity without altering mutation scope or thresholds.

Take the base branch from `origin/${GITHUB_BASE_REF}` rather than hardcoding the
default branch. GitHub sets that variable on pull request events, so a pull request
against a release branch is scored against that branch. Read it as a shell
environment variable rather than through `${{ github.event.pull_request.base.ref }}`
interpolation: a branch name pasted into the shell as literal text is a script
injection waiting to happen.

## Protecting the Configuration

Mutation thresholds are the first thing an agent lowers when a build goes red, so
treat these files the way the project treats its other non-negotiable configuration:

- Recommend protecting `infection.json5` in the agent permission system. Do not
  edit `.claude/settings.json`, `AGENTS.md`, or another agent-owned policy file
  unless the user explicitly asks for that change.
- Add the file to `.gitattributes` with `export-ignore` if the project excludes dev
  configuration from its distributed archive.
- Keep `ForbiddenCommentRule` enabled. It rejects `@infection-ignore-all`, which is
  the other way to make a mutant disappear without writing a test.

## Verification

Run the two local commands above, or push the branch and read the job. Exit codes:

- `0`: every threshold met
- Non-zero: a threshold was missed, or Infection could not run

Read `build/infection/escaped.log` for the mutants that survived and
`build/infection/per-mutator.md` for the mutators they came from. Each escaped
mutant is a diff showing a change to production code that no test objected to;
write the test that objects.

## References

- [Infection documentation](https://infection.github.io/guide/) — Mutators, loggers, and CLI options.
- [ForbiddenCommentRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/ForbiddenCommentRule.md) — Why `@infection-ignore-all` is rejected.
