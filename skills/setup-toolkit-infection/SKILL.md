---
name: setup-toolkit-infection
description: >-
  Set up Infection mutation testing for a PHP project. Use when asked to configure
  Infection, infection.json5, mutation testing, mutation score (MSI) or covered MSI
  thresholds, a scheduled mutation workflow on the default branch that reports a
  low score as an issue, Composer scripts for Infection, CI jobs for mutation
  testing, or when asked why a test suite with high coverage still lets mutants
  escape.
---

# Setup Infection (Mutation Testing)

This skill configures Infection, the PHP mutation testing framework, as a
scheduled measurement of the default branch: the whole source tree is mutated
every day, the score is compared with a fixed threshold, and a score below it is
reported as an issue rather than as a red build.

Line coverage says a line ran. Mutation testing says the tests noticed what the line
did. It is the check that catches the failure mode this toolkit exists for:
AI-generated tests that execute code and assert nothing meaningful about it.

## Prerequisites

Inspect the project before configuring:

- Confirm the project requires `k-kinzal/php-ai-toolkit` and has a working PHPUnit
  setup (`/setup-toolkit-phpunit`).
- Read `composer.json`: production autoload roots, the PHP floor, existing test and
  coverage scripts, and `config.allow-plugins`.
- Read `phpunit.xml.dist`: which test suites hold behavioral tests and which hold
  documentation examples or other suites that must not score mutants.
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
leg that proves it is necessary. If the package graph forces an unsuitable tool
release, prefer a pinned Infection PHAR on the mutation job's supported runtime.
Do not create a nested `tools/infection/composer.json` and lock just to isolate one
executable. Preserve an established separate toolchain only when the project
deliberately maintains it. Verify every relevant lock or CI leg and diagnose an
unexpected resolution with `composer why-not`:

```bash
composer require --dev "infection/infection:<target-derived-constraint>" --dry-run
composer why-not infection/infection <newest-compatible-version>
```

Run the confirmed requirement without `--dry-run`, then enable the plugin only if
the resolved package requires it. Do not assume the minimum PHP version or supported
Infection line from this repository; those are release properties to verify at
application time.

For a PHAR installation, `setup-php` can install `infection:<exact-version>` in
its `tools` input. Resolve that version from the mutation runtime, verify
`infection --version`, point `$schema` at the published `resources/schema.json`
URL of that exact release, and replace `vendor/bin/infection` in every command
with the installed executable. When passing PHP options, invoke
`php -d memory_limit=4G "$(command -v infection)"`. The product still installs
its own locked dependencies and PHPUnit, which is why the shipped configuration
names `vendor/bin/phpunit` explicitly; a PHAR does not make an incompatible test
graph supported. No Infection Composer plugin permission is needed for this
installation mode.

## Templates

Read the templates from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-infection/` and apply them:

| Template | Target | Scope |
|----------|--------|-------|
| `infection.json5` | `infection.json5` at the project or package root | The only configuration file |
| `mutation.yml` | `.github/workflows/mutation.yml` | The scheduled workflow, separate from `ci.yml` |

Write one configuration file. Infection reads a single file and takes per-run
thresholds from `--min-msi` and `--min-covered-msi`, which is how the workflow
neutralises the gate while the file keeps the policy. A second file duplicates the
scope, the mutators, and the exclusions, and the day one copy is edited the two
start measuring different things.

Pass the configuration file explicitly on every invocation
(`--configuration=infection.json5`). Infection otherwise picks the first file it
finds from `infection.json5`, `infection.json`, `infection.json5.dist`,
`infection.json.dist`, so an unrelated file dropped into the project root can
silently take over the measurement.

Both templates contain `REPLACE_WITH_*` sentinels. Replace all of them from the
target before installing; none is a default supplied by this repository:

- `REPLACE_WITH_PRODUCTION_SOURCE_ROOT`: every production root that should be
  mutated. Confirm the run generates mutants from each root.
- `REPLACE_WITH_BEHAVIORAL_TEST_SUITES`: the comma-separated names of the PHPUnit
  suites whose tests are meant to kill mutants, normally `unit`, or
  `unit,integration` when integration tests run in the mutation job. Never
  include the doctest suite: documentation examples demonstrate usage, they do
  not object to a changed line, and counting them flatters the score.
- The workflow's runtime, extensions, lock policy, working directory, default
  branch, schedule, and timeout.

Check `vendor/bin/infection --version` and validate the shipped configuration
against that installed release before copying version-specific keys. Configure the
newest resolved line first. When a target genuinely resolves an older line, consult
that line's schema and apply its documented spelling or limitation deliberately; a
schema-invalid file is not a cross-version configuration, and the template's own
lock resolution is not evidence about the target.

## Why a Scheduled Measurement and Not a Pull-Request Gate

Infection reports two scores:

- **MSI** — detected mutants over all mutants, including mutants in code no test
  covers. Infection's default detected count includes killed, errored, and
  timed-out mutants. It answers "how much of the source is verified".
- **Covered MSI** — detected mutants over the mutants in covered code only. It answers
  "where tests do run, do they assert anything".

A whole-tree run takes minutes to an hour, and a changed-lines run on a pull
request is only as fast as the slowest covering test set. Neither belongs in the
feedback loop of a pull request, where the other gates answer in seconds. The
score also moves for reasons a pull request cannot see: a timeout tuned for one
suite size, a mutator release, a test that became slow. A red pull request for a
score is exactly the situation in which an agent lowers the threshold, disables a
mutator, or widens an exclusion to get green.

So the measurement runs on the default branch, on a schedule and on demand, and
it never fails on the score. The workflow passes `--min-msi=0 --min-covered-msi=0`
so Infection exits zero after any complete run, then compares the reported score
with the thresholds and creates or updates one issue while the score stays below
them. The run is red only when Infection could not measure: the initial test run
failed, mutants were skipped before execution, or the report is missing or
invalid. A red mutation run therefore always means "fix the measurement", the
same rule the fuzz workflow follows, and the issue is the queue of weak tests.

| Scope | Where the numbers live | `minMsi` | `minCoveredMsi` | Role |
|-------|------------------------|----------|-----------------|------|
| Whole source tree | `infection.json5` | 80 | 80 | Fixed toolkit policy; the file documents it and local runs enforce it |
| Whole source tree | `MIN_MSI` and `MIN_COVERED_MSI` in `mutation.yml` | 80 | 80 | The same policy, read by the issue step |

Keep the two places aligned. A measured score describes the current suite; it does
not define an acceptable suite, so adoption includes paying down enough weak tests
and design debt to reach the threshold rather than lowering it to the score that
happened to be measured.

## Setting the Thresholds

The shipped 80/80 values are fixed policy, not placeholders or measurements.

1. Run the measurement locally with the thresholds neutralised:

   ```bash
   vendor/bin/infection --configuration=infection.json5 --with-uncovered --threads=max --only-covering-test-cases --min-msi=0 --min-covered-msi=0
   ```

2. If either whole-tree score is below 80, keep the template unchanged and fix the
   suite and production design until both pass. The adoption is incomplete while
   the default branch cannot meet the baseline, and the scheduled run will say so
   in an issue until it does.
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
5. Re-measure after changes, but never rewrite policy from the measurement. A
   later threshold change remains an explicit human decision.

Lowering a threshold to close the issue defeats the measurement. So does disabling
a mutator, widening `source.excludes`, or pointing the run at fewer directories.
Fix the tests, and ask a human operator when an exception is genuinely justified.

## Mutators

Keep `"@default": true`. The default profile is what the published mutation scores
of other projects mean, and a trimmed profile makes the number incomparable and
usually flatters the suite.

Resist disabling a mutator that produces equivalent mutants when it also kills real
ones. `Coalesce` still catches genuine missing tests on `??` over data even when a
particular construction idiom produces equivalent survivors. Improve that idiom;
disable a mutator only when it produces nothing but equivalent mutants for the
project, with human approval.

## Analysis Scope

Mutate production source only:

```json5
"source": {
    "directories": ["REPLACE_WITH_PRODUCTION_SOURCE_ROOT"]
}
```

`source.excludes` entries are relative to each source directory, not to the project
root. Resolve every path from the target's selected source directories.

An exclusion needs a specific scope reason: for example, code tested only under a
different supported runtime, or reference declarations whose correctness is
established against an independent native parser and whose unit tests would merely
duplicate the declarations. Keep algorithms that interpret that data in scope.
Identify the exact files and independent check; a large array or surviving mutants
alone do not justify exclusion. Preserve an already authorized exception and ask
only when a new mutation-scope decision remains unresolved.

## Timeouts

Use the toolkit's fixed `"timeoutsAsEscaped": true` policy: a timed-out mutant
does not count as detected and cannot improve the mutation score. This is
stricter than Infection's default. Loop-condition mutations can create infinite
loops, so this policy can also leave those mutants classified as escaped; inspect
the report rather than changing the classification to raise the score. Do not add
`maxTimeouts` or a zero-timeout post-check without an explicit project decision.

`timeout` is an operational value. Measure the covered initial suite and the
largest selected test set, then allow runner headroom. Infection may skip mutants
before execution when their estimated test duration exceeds the timeout; those
skips are distinct from mutants that ran and timed out. A green score with skipped
whole-tree mutants does not establish the baseline, so the workflow reads
`stats.skippedCount` from the JSON report and fails when it is non-zero.
Re-measure after suite growth rather than retaining the first working timeout.

Bound the PHP memory available to mutant test processes so warning floods or
runaway allocation terminate. Setting `php -d` on Infection's controller alone
does not establish the worker limit; configure the job's PHP INI and verify the
child processes. Size the controller/report reader separately for large reports.

## Coverage Collection

Keep `tmpDir` separate from the report directory in `logs`: Infection removes its
temporary directory when the run finishes.

Let Infection run its normal initial test phase and generate the coverage it needs.
With pcov or Xdebug enabled, no separate PHPUnit coverage command is required.
`--coverage` means reuse an existing XML and JUnit report; it is not a prerequisite
for mutation testing. `--skip-initial-tests` is only valid when that existing report
is supplied and the suite was already proved green.

Use `--only-covering-test-cases` when the installed release supports it. This
selects the tests covering each mutant without narrowing mutation scope. Pass
`--with-uncovered` so the uncovered mutants that MSI already counts are listed in
the logs; a score below threshold is then diagnosable from the artifact alone.

Do not add a `Generate coverage for mutation testing` step solely for Infection,
and do not pass `--coverage` or `--skip-initial-tests` in the standard workflow.
Reuse a pre-generated report only when the workflow already creates it for another
independent consumer and the saved runtime justifies the extra coupling.

Keep `--no-extensions` in `testFrameworkExtraArgs` so the toolkit's AI reporter
cannot replace the PHPUnit result output Infection reads to distinguish killed from
escaped mutants, and keep `--testsuite=` beside it so only behavioral suites
score. A risky or failing initial test should fail the mutation run; do not
weaken PHPUnit with `--do-not-fail-on-risky`.

`testFrameworkExtraArgs` arrived in Infection 0.34. On an older line the same value
goes under `testFrameworkOptions`, which every version from 0.26 accepts and 0.34
and later still honour; setting both is an error.

## Do Not Wrap It in Composer Scripts

Write the commands into the workflow. This measurement runs in CI, so a Composer
script would add a layer to look through, a second place for the flags to drift
from the job that actually runs them, and Composer's 300-second process timeout
to work around — a mutation run on a suite of any size takes longer than that,
and the script dies partway through with a process timeout instead of a score.

Composer scripts earn their place when a command is run by hand on every save, like
`composer lint`. Mutation testing is not that command, and it does not belong inside
`composer lint` either: those gates are seconds, this one is minutes.

Do not explain this tooling in the target project's product `docs/` or rewrite its
README or `AGENTS.md`. The workflow and this vendor skill are the development
documentation unless the user explicitly names another developer-owned location.

## The Scheduled Workflow

Install `mutation.yml` as its own workflow, never as a job of `ci.yml`. In a
monorepo, install one workflow per package, named `Mutation (<package>)`, with
`defaults.run.working-directory` set to the package; the issue marker and title
derive from the workflow name, so each package gets its own issue.

The workflow:

- runs on a schedule and on `workflow_dispatch`, and its job carries
  `if: github.ref == 'refs/heads/<default>'` so a dispatch from another branch
  does nothing;
- checks out `github.sha` explicitly, so a rerun measures the commit the run was
  created for even after the branch moved;
- uses a concurrency group without `cancel-in-progress`, so a dispatch queues
  behind the scheduled run instead of killing it;
- uses the highest PHP version in the target's supported matrix, `coverage: pcov`,
  the extensions the test job needs, a worker memory limit in `ini-values`, and a
  controller limit on the `php -d` invocation;
- validates the manifest, installs the locked graph, checks platform
  requirements, and prints `infection --version`;
- runs the whole-tree measurement with the thresholds neutralised, then rejects
  skipped mutants;
- creates or updates the score issue with `if: success()` and
  `continue-on-error: true`, writes the score to the job summary on every
  successful run, and needs `issues: write` for that step alone; and
- uploads `build/infection/` whether the run succeeded or not.

Treat job timeout, memory limits, and `--threads` as measured operational values:
size them from the observed run and runner capacity without altering mutation
scope or thresholds. Set artifact and report paths relative to the repository
root; `defaults.run.working-directory` does not change an action's paths.
Choose a schedule minute away from the start of the hour, and stagger the
packages of a monorepo so their runs do not compete for runners.

The issue step looks for an existing issue by marker or title in every state.
When the score recovers, close the issue by hand or with the fixing pull request;
the next run below threshold updates the closed issue's body rather than opening
a second one, so the history of the score stays in one place.

## Protecting the Configuration

Mutation thresholds are the first thing an agent lowers when an issue stays open,
so treat these files the way the project treats its other non-negotiable
configuration:

- Recommend protecting `infection.json5` in the agent permission system. Do not
  edit `.claude/settings.json`, `AGENTS.md`, or another agent-owned policy file
  unless the user explicitly asks for that change.
- Add the file to `.gitattributes` with `export-ignore` if the project excludes dev
  configuration from its distributed archive.
- Keep `ForbiddenCommentRule` enabled. It rejects `@infection-ignore-all`, which is
  the other way to make a mutant disappear without writing a test.

## Verification

Run the local command from Setting the Thresholds, or dispatch the workflow on
the default branch and read the run. Exit codes of the local command:

- `0`: Infection measured the whole tree; the score is in the summary log
- Non-zero: Infection could not run, or the initial test suite failed

In the workflow, a green run with the score in the job summary is the normal
outcome. Confirm that a score below threshold produces the issue by reading the
step against `build/infection/infection.json` from the artifact; a red run must
point at a measurement failure, never at the score.

Read `build/infection/escaped.log` for the mutants that survived and
`build/infection/per-mutator.md` for the mutators they came from. Each escaped
mutant is a diff showing a change to production code that no test objected to;
write the test that objects.

Run `git diff --check` and validate `.github/workflows/mutation.yml` with
actionlint. Search installed files for `REPLACE_WITH`; a remaining sentinel is a
failed setup.

## References

- [Infection documentation](https://infection.github.io/guide/) — Mutators, loggers, and CLI options.
- [ForbiddenCommentRule](vendor/k-kinzal/phpstan-guard-rules/docs/rules/ForbiddenCommentRule.md) — Why `@infection-ignore-all` is rejected.
