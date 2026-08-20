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

Install Infection:

```bash
composer require --dev infection/infection
composer config allow-plugins.infection/extension-installer true
```

Infection needs PHP 8.1 or later, and each Infection line supports a narrower PHP
range than this toolkit does. In a project that supports older PHP versions, pin the
mutation gate to one modern PHP version rather than narrowing the Composer
constraint: `composer require --dev "infection/infection:^0.26.19 || ^0.27 || ^0.28
|| ^0.29 || ^0.30 || ^0.31 || ^0.32 || ^0.33 || ^0.34 || ^0.35"` keeps every
supported PHP version installable while CI scores mutants on the newest one.

## Templates

Read the templates from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-infection/` and apply them to
the project root:

| Template | Target | Scope |
|----------|--------|-------|
| `infection.json5` | `infection.json5` | Whole source tree, run on the default branch |
| `infection-pr.json5` | `infection-pr.json5` | Changed lines only, run on pull requests |
| `mutation-job.yml` | merge into `.github/workflows/ci.yml` | CI job for both |

Pass the configuration file explicitly on every invocation
(`--configuration=infection.json5`). Infection otherwise picks the first file it
finds from `infection.json5`, `infection.json`, `infection.json5.dist`,
`infection.json.dist`, so an unrelated file dropped into the project root can
silently take over the gate.

Keep the two configurations identical apart from the thresholds and the reporting
each one needs. A mutator disabled in one and not the other means the pull request
gate and the branch gate measure different things.

## Why Two Thresholds

Infection reports two scores:

- **MSI** — killed mutants over all mutants, including mutants in code no test
  covers. It answers "how much of the source is verified".
- **Covered MSI** — killed mutants over the mutants in covered code only. It answers
  "where tests do run, do they assert anything".

A whole-tree threshold has to sit at whatever the project already reaches, or every
build fails until the entire backlog is paid off. Changed lines carry no such
backlog: code being written right now can be held to the bar the project wants, and
that is the only moment where enforcing it is cheap.

| Scope | Config | `minMsi` | `minCoveredMsi` | Why |
|-------|--------|----------|-----------------|-----|
| Whole source tree | `infection.json5` | measured score, rounded down | measured score, rounded down | Ratchet: no regression against what the branch already achieves |
| Changed lines | `infection-pr.json5` | measured score, stepped up | measured score, stepped up further | New code is expected to be verified, not merely executed |

## Setting the Thresholds

Measure before choosing numbers. Never copy the values from another project.

1. Run the gate with the thresholds neutralised:

   ```bash
   vendor/bin/infection --configuration=infection.json5 --threads=max --min-msi=0 --min-covered-msi=0
   ```

2. Read the `Mutation Score Indicator (MSI)` and `Covered Code MSI` lines from the
   summary and round each down to a whole number. Those are the whole-tree
   thresholds. Leave a further point of headroom when CI scores mutants on a
   different PHP or PHPUnit version than the measurement did: a threshold that sits
   exactly on the measured score turns red on a mutant that a different runtime
   classifies differently, and the ratchet still catches the real thing, because a
   suite that stops verifying loses mutants by the dozen, not by the one.
3. Set the changed-lines thresholds above the whole-tree ones — roughly ten points
   for `minMsi` and fifteen for `minCoveredMsi`, so that new code has to be both
   covered and asserted on. Pick a bar the project can actually hold: a suite
   scoring 82% on the whole tree will not hold 100% on a diff, and a gate that fails
   every honest pull request gets weakened rather than met. Note that this toolkit's
   `ForbiddenCommentRule` rejects `@infection-ignore-all`, so there is no annotation
   escape hatch: an equivalent mutant is a conversation with a human operator, not a
   comment.
4. Keep `ignoreMsiWithNoMutations: true` in the changed-lines configuration. A pull
   request that touches only documentation, tests, or configuration produces no
   mutants, and a scoreless run must not be reported as 0%.
5. Raise the whole-tree thresholds whenever the score rises. That is the whole point
   of the ratchet, and it is the only edit to these files that should be routine.

Lowering a threshold to make a red build green defeats the gate. So does disabling a
mutator, widening `source.excludes`, or pointing the gate at fewer directories. Fix
the tests, and ask a human operator when an exception is genuinely justified.

## Mutators

Keep `"@default": true`. The default profile is what the published mutation scores
of other projects mean, and a trimmed profile makes the number incomparable and
usually flatters the suite.

If a specific mutator produces only equivalent mutants for a project — a common case
is `Concat` on log strings, or arithmetic mutators inside a code generator — record
the reason next to the setting, disable exactly that mutator in both files, and
raise the thresholds to cover the mutants that are no longer generated.

## Analysis Scope

Mutate production source only:

```json5
"source": {
    "directories": ["src"]
}
```

`source.excludes` entries are relative to each source directory, not to the project
root: `"excludes": ["Generated"]` excludes `src/Generated`.

Exclude a directory only when its tests cannot run in the same job as the gate — for
example code exercised exclusively by a legacy PHPUnit configuration on an older PHP
version. Write the reason in the file. Do not exclude code because its mutants
survive.

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

## Recommended Composer Scripts

```json
{
    "scripts": {
        "infection": [
            "Composer\\Config::disableProcessTimeout",
            "@infection:coverage",
            "@php -d memory_limit=1G vendor/bin/infection --configuration=infection.json5 --threads=max --coverage=build/infection-coverage --skip-initial-tests"
        ],
        "infection:pr": [
            "Composer\\Config::disableProcessTimeout",
            "@infection:coverage",
            "@php -d memory_limit=1G vendor/bin/infection --configuration=infection-pr.json5 --threads=max --coverage=build/infection-coverage --skip-initial-tests --git-diff-lines --git-diff-base=origin/main"
        ],
        "infection:coverage": [
            "Composer\\Config::disableProcessTimeout",
            "@php -d memory_limit=1G -d xdebug.mode=coverage vendor/bin/phpunit --no-extensions --do-not-fail-on-risky --coverage-xml build/infection-coverage/coverage-xml --log-junit build/infection-coverage/junit.xml"
        ]
    }
}
```

`Composer\Config::disableProcessTimeout` is not optional. Composer kills a script
after 300 seconds by default, and a mutation run on a suite of any size takes
longer: without it the gate dies partway through with a process timeout instead of a
score.

Point `--git-diff-base` at the project's default branch. `--do-not-fail-on-risky`
belongs to the coverage run only: it relaxes the exit code of a run whose purpose is
to produce a coverage report, while `composer test` keeps enforcing the strict
PHPUnit settings. Drop the flag if the project's suite is green with coverage
enabled.

Do not add `infection` to `composer lint`. Mutation testing costs minutes, and the
lint gates are meant to be run on every save.

## CI Wiring

Merge `mutation-job.yml` into `.github/workflows/ci.yml` as a separate job. It needs
three things the other jobs do not:

- `fetch-depth: 0` on checkout, so the pull request can be diffed against its base.
- A coverage driver: `coverage: pcov` in `setup-php`.
- A branch on the event: `composer infection:pr` for `pull_request`, `composer
  infection` otherwise.

If pull requests target branches other than the default one, pass the base through
an environment variable rather than interpolating it into the shell:

```yaml
      - name: Mutation testing on changed lines
        if: github.event_name == 'pull_request'
        env:
          BASE_REF: ${{ github.event.pull_request.base.ref }}
        run: |
          composer infection:coverage
          vendor/bin/infection --configuration=infection-pr.json5 --threads=max \
            --coverage=build/infection-coverage --skip-initial-tests \
            --git-diff-lines --git-diff-base="origin/$BASE_REF"
```

Calling Infection directly means the coverage step has to be called with it, and
the flags in the workflow have to stay in step with the Composer script. Prefer
`composer infection:pr` and a fixed base branch when the project has one.

## Protecting the Configuration

Mutation thresholds are the first thing an agent lowers when a build goes red, so
treat these files the way the project treats its other non-negotiable configuration:

- Add `infection.json5` and `infection-pr.json5` to the `permissions.deny` list of
  `.claude/settings.json`, alongside the PHPUnit and PHPStan configuration.
- Add them to `.gitattributes` with `export-ignore` if the project excludes dev
  configuration from its distributed archive.
- Keep `ForbiddenCommentRule` enabled. It rejects `@infection-ignore-all`, which is
  the other way to make a mutant disappear without writing a test.

## Verification

```bash
composer infection
```

Exit codes:

- `0`: every threshold met
- Non-zero: a threshold was missed, or Infection could not run

Read `build/infection/escaped.log` for the mutants that survived and
`build/infection/per-mutator.md` for the mutators they came from. Each escaped
mutant is a diff showing a change to production code that no test objected to;
write the test that objects.

## References

- [Infection Configuration](vendor/k-kinzal/php-ai-toolkit/docs/infection.md) — Settings, thresholds, and CI behavior.
- [Infection documentation](https://infection.github.io/guide/) — Mutators, loggers, and CLI options.
- [ForbiddenCommentRule](vendor/k-kinzal/php-ai-toolkit/docs/rules/ForbiddenCommentRule.md) — Why `@infection-ignore-all` is rejected.
