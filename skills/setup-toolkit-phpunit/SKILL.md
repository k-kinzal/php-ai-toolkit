---
name: setup-toolkit-phpunit
description: >-
  Set up PHPUnit with strict configuration and AI test reporter for a PHP project.
  Use when asked to configure PHPUnit, set up testing, or enable the AI test reporter.
---

# Setup PHPUnit (Strict + AI Reporter)

This skill configures PHPUnit with maximum strictness and enables the AI test reporter from php-ai-toolkit.

Keep generated and edited configuration files free of explanatory comments and
commented-out examples. Keep reusable explanations in this skill; report
project-specific rationale and measurements in the setup summary and, when
created, the commit message or PR description. Preserve existing ownership notices
and directives required by tools.

## Choose the Version Policy

Which PHPUnit majors a project installs is a project decision, not a toolkit
default. Decide it explicitly and state the decision in the setup summary, because
it determines how many configuration files this setup produces. Two policies are
valid:

**Pinned floor.** The project pins resolution to its oldest supported PHP —
normally `config.platform.php` in `composer.json` plus a committed
`composer.lock` — so every CI leg installs the same PHPUnit major whatever PHP
runs it. One configuration file, no runner script. Confirm the pin before
concluding this applies:

```bash
composer config platform.php            # empty output means there is no pin
composer show phpunit/phpunit --locked  # what the committed lock actually installs
```

**Per-runtime resolution.** No platform pin, or PHP-versioned locks such as
`composer.lock.php-8.1`, so each matrix leg resolves the newest PHPUnit its PHP
admits and the majors genuinely differ across CI.

This toolkit repository uses per-runtime resolution because validating every
template on every supported major is its purpose. A consuming project carries no
such obligation, and a pinned floor is the simpler default; do not copy this
repository's `^9.6 || ^10.5 || ^11 || ^12 || ^13` constraint reflexively.

Under either policy, `require-dev` must advertise only what some maintained lock or
CI leg actually installs. A constraint such as `^10.5 || ^11 || ^12 || ~13.0.0`
combined with a `config.platform.php` of `8.1` never resolves past 10.5: the extra
majors are untested surface, and the single configuration written for 10.5 is
invalid under them. Report that conflict and resolve it — narrow the constraint to
the pinned major, or drop the pin and add the per-major configurations below. Do
not leave a constraint that promises coverage no lock or CI leg provides.

## Prerequisites

Having fixed the policy, select releases from the target's PHP support range,
existing test extensions, Composer graph, and CI topology. Inspect current Composer
metadata and PHPUnit's support documentation at application time, then select the
newest release each supported graph admits. Verify every maintained lock or CI leg
and diagnose an unexpected older resolution with `composer why-not`:

```bash
composer require --dev "phpunit/phpunit:<target-derived-constraint>" k-kinzal/php-ai-toolkit --dry-run
composer why-not phpunit/phpunit <newest-compatible-version>
```

Run the confirmed requirement without `--dry-run`. Preserve an existing deliberate
pin unless changing it is in scope, but update the lock to the newest version that
the target constraint admits.

The toolkit requirement is unversioned only for a new install so Composer can select
its newest stable release compatible with the target graph. Preserve an intentional
existing toolkit pin and update its lock within that constraint.

## Template

Use the template for the installed PHPUnit major. The modern event API is shared,
but its XML schema is not: a lowest-common-denominator "10+" file omits stricter
settings added by later majors, while a PHPUnit 13 file is invalid on older ones.

| Installed PHPUnit | Template |
|-------------------|----------|
| 9.6 | `phpunit9.xml.dist` |
| 10.5 current maintenance release | `phpunit10.xml.dist` |
| 11.5 current maintenance release | `phpunit11.xml.dist` |
| 12.5 current maintenance release | `phpunit12.xml.dist` |
| 13.x | `phpunit.xml.dist` |

Read the selected file from
`vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpunit/` and apply it to the
project root as `phpunit.xml.dist`.

The set of configuration files must equal the set of majors that some maintained
lock actually installs — no more, no less. Under a pinned floor that is exactly one
file, named `phpunit.xml.dist`, written for the pinned major. Under per-runtime
resolution it is one file per resolved major, plus the runner that selects between
them (see Recommended Composer Scripts). A configuration for a major nothing
installs is dead weight; a major that installs with no matching configuration is a
broken CI leg.

The 10–12 templates track their maintained minor's current schema. Some strict
attributes were added in patch releases, so an older locked patch may reject the
template even within the same minor. Update it to the newest compatible patch as
required above, or derive a configuration from that exact installed XSD.

Validate every file against the XSD of the PHPUnit version that consumes it:

```bash
xmllint --noout --schema vendor/phpunit/phpunit/phpunit.xsd phpunit.xml.dist
```

The schemas are not interchangeable in either direction. A PHPUnit 10 file fails
PHPUnit 11+ validation on `restrictDeprecations`, which PHPUnit 11 removed; a
PHPUnit 13 file fails on older majors. Validating a configuration only under the
single locally installed PHPUnit proves nothing about the other legs of a
per-runtime matrix. If a future schema differs from the shipped PHPUnit 13
template, migrate the template to that installed schema; do not downgrade PHPUnit
merely to match an example file.

For a new configuration, replace `REPLACE_WITH_VENDOR_DIR` from `composer config
vendor-dir`, and replace `REPLACE_WITH_UNIT_TEST_PATH` and
`REPLACE_WITH_PRODUCTION_PATH` from the target's test layout and production
autoload roots. A remaining sentinel or a zero-test suite is a failed setup.

## Required PHP Extensions

Every runtime that installs the dev graph or runs the suite needs these. Name each
one explicitly in the CI `extensions:` list rather than relying on what the
runner image happens to preinstall. Report the reasons in the setup summary.

| Extension | Required by | Failure without it |
|-----------|-------------|--------------------|
| `mbstring` | `phpunit/phpunit` itself, plus `phpunit/php-code-coverage`, `sebastian/comparator`, `sebastian/exporter`, and `infection/infection` — all list it under `require`, not `suggest` | `composer install` fails the platform check; nothing runs |
| `pcntl` | `phpunit/php-invoker`, which is what implements the template's `enforceTimeLimit="true"` via `pcntl_alarm()`, `pcntl_signal()`, and `pcntl_async_signals()` | Time limits are silently not enforced. PHPUnit 10+ raises the test-runner warning `The pcntl extension is required for enforcing time limits`, and because the template also sets `failOnAllIssues="true"` the run exits 1 |

ParaTest does **not** need `pcntl`. It starts workers through `symfony/process`, not
`pcntl_fork()`, and counts cores through `fidry/cpu-core-counter`; the `ext-pcntl`
entry in its own `composer.json` is a `require-dev` for its own test suite. Its
runtime platform requirements are `ext-dom`, `ext-pcre`, `ext-reflection`, and
`ext-simplexml`, all of which are compiled in by default on a normal PHP build.

Derive any further entries from the resolved graph rather than from this list:

```bash
composer check-platform-reqs   # every ext-* the installed graph demands
```

Add the resulting list to every job that installs the dev graph or executes tests —
including a mutation-testing job, which runs the same `phpunit.xml.dist` through
Infection and so hits the same `enforceTimeLimit` requirement. Adding an extension
to the test job alone leaves the other jobs failing for a reason the diff does not
explain.

## Merging with Existing Configuration

If the project already has `phpunit.xml.dist`, merge as follows rather than overwriting.

### PHPUnit 10.5–13 `<phpunit>` attributes — common strict flags

All common modern strict flags must have these values. Override a weaker existing
value:

| Attribute | Required value | If existing is weaker |
|-----------|---------------|----------------------|
| `executionOrder` | `depends,random` | Override. Fixed order hides test dependencies. |
| `requireCoverageMetadata` | `true` | Override. Without it, coverage numbers are inaccurate. |
| `beStrictAboutCoverageMetadata` | `true` | Override. |
| `beStrictAboutChangesToGlobalState` | `true` | Override. |
| `beStrictAboutOutputDuringTests` | `true` | Override. |
| `beStrictAboutTestsThatDoNotTestAnything` | `true` | Override. Useless tests must be risky. |
| `failOnAllIssues` | `true` | Override. Without it, warnings pass silently. |
| `displayDetailsOnAllIssues` | `true` | Override. Every issue needs actionable diagnostics. |
| `enforceTimeLimit` | `true` | Override. |

`failOnAllIssues="true"` already includes the fine-grained `failOn*` settings and
intentionally opts into issue types added by later PHPUnit releases. Do not add
redundant `failOnEmptyTestSuite`, `failOnRisky`, or `failOnWarning` attributes.

Do not copy these names into PHPUnit 9: that schema uses different
coverage-metadata names and does not support `failOnAllIssues` or modern issue
detail attributes.

### Major-specific modern settings

Apply only the row for the installed major:

| PHPUnit | Required setting | Reason |
|---------|------------------|--------|
| 10.5 | `<source restrictDeprecations="true">` | PHPUnit 10's supported way to exclude third-party-only deprecations; removed in PHPUnit 11. |
| 11–13 | `<source ignoreIndirectDeprecations="true">` | Keeps self and direct deprecations actionable while ignoring deprecations triggered only inside third-party code. |
| 11–13 | `shortenArraysForExportThreshold="0"` | Keeps complete arrays in failure output instead of hiding elements. |
| 13 | `requireSealedMockObjects="true"` | Marks mock objects that can still accept unplanned calls as risky. |

When enabling sealed mocks in a suite also executed on PHPUnit 9–12, prefer a
stub or a small fake where possible. PHPUnit 13's `seal()` API does not exist on
older majors, so unconditional calls to it make otherwise cross-version test code
invalid.

Do not carry `cacheResult` into PHPUnit 13: it is deprecated there. Omitting it
keeps the enabled-by-default test-run history without making the configuration
invalid on early PHPUnit 13 releases; use `recordTestRunHistory` only when the
installed schema supports it and the default must be overridden.

Do not force `requireCoverageContribution="true"`. It is useful for a homogeneous
suite, but interface, enum, subprocess, and contract tests can legitimately have
no executable target line. PHPUnit 12 deprecates method-level `#[CoversNothing]`,
so such exceptions must be split into their own `#[CoversNothing]` test classes
before enabling this setting without introducing deprecated metadata.

Do not force `warnWhenPhpIsNotConfiguredForDevelopment="true"`. PHPUnit's
development profile requires `memory_limit=-1`; a deliberate finite test-process
limit is safer for CI and should not become a failing runner warning.

### PHPUnit 9.6 strict equivalents

Start from `phpunit9.xml.dist`. In addition to the flags shared with modern
PHPUnit, its fixed baseline uses:

| Attribute | Required value | Modern equivalent |
|-----------|----------------|-------------------|
| `forceCoversAnnotation` | `true` | `requireCoverageMetadata` |
| `beStrictAboutCoversAnnotation` | `true` | `beStrictAboutCoverageMetadata` |
| `convertDeprecationsToExceptions` | `true` | covered by `failOnAllIssues` and modern issue handling |
| `convertErrorsToExceptions` | `true` | covered by modern error handling |
| `convertNoticesToExceptions` | `true` | covered by modern issue handling |
| `convertWarningsToExceptions` | `true` | covered by modern issue handling |
| `beStrictAboutResourceUsageDuringSmallTests` | `true` | covered by modern strict issue handling |
| `beStrictAboutTestsThatDoNotTestAnything` | `true` | same |
| `failOnIncomplete` | `true` | covered by `failOnAllIssues` |
| `failOnSkipped` | `true` | covered by `failOnAllIssues` |
| `beStrictAboutTodoAnnotatedTests` | `true` | covered by modern strict issue handling |
| `enforceTimeLimit` | `true` | same |
| `timeoutForSmallTests` | `1` | same |
| `timeoutForMediumTests` | `10` | same |
| `timeoutForLargeTests` | `60` | same |

PHPUnit 9.6 requires `@covers`/`@uses` metadata. It does not understand the
PHPUnit 10+ coverage attributes. A suite exercised under both generations must
carry the PHPDoc metadata for PHPUnit 9 and the attributes for modern PHPUnit;
neither configuration should silently run without intentional coverage scope.

The PHPUnit 9 template also uses `cacheResultFile=".phpunit.result.cache"` and
`verbose="true"`; neither attribute belongs in a modern configuration.

### PHPUnit 9.6–13 `<phpunit>` attributes — timeouts

| Attribute | Toolkit value | If existing is stricter (lower) | If existing is weaker (higher) |
|-----------|--------------|-------------------------------|-------------------------------|
| `timeoutForSmallTests` | `1` | Keep existing. | Override to `1`. |
| `timeoutForMediumTests` | `10` | Keep existing. | Override to `10`. |
| `timeoutForLargeTests` | `60` | Keep existing. | Override to `60`. |

### `bootstrap`

Keep existing. The project may have a custom bootstrap file. Only set to `vendor/autoload.php` if no bootstrap is configured.

### `<testsuites>`

Keep existing testsuites. If the existing config already defines test directories,
preserve them. When no testsuite exists, derive its paths from the target's
autoload-dev mapping and test layout; do not assume `tests/Unit`. Example merge of
an existing layout:

```xml
<testsuites>
    <testsuite name="unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="integration">
        <directory>tests/Integration</directory>
    </testsuite>
</testsuites>
```

### `<extensions>`

Use this section only for PHPUnit 10.5 or later.

Add the toolkit extension alongside existing extensions. Do not remove existing ones:
```xml
<extensions>
    <bootstrap class="Existing\Extension"/>
    <bootstrap class="Toolkit\PhpUnit\TestReporter\AiTestReporterExtension"/>
</extensions>
```

### `<listeners>` for PHPUnit 9.6

Use this section only for PHPUnit 9.6. Do not register `AiTestReporterExtension` in PHPUnit 9.6 because it depends on the PHPUnit 10+ event API.

Add the legacy listener alongside existing listeners:
```xml
<listeners>
    <listener class="Existing\Listener"/>
    <listener class="Toolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener"/>
</listeners>
```

### `<source>` — issue scope and suppression

All supported `ignoreSuppression*` attributes must be `true`. Set
`restrictNotices="true"` and `restrictWarnings="true"`. For deprecations, use
`restrictDeprecations="true"` only on PHPUnit 10 and
`ignoreIndirectDeprecations="true"` only on PHPUnit 11–13. There is no case where
the PHP error-suppression operator should hide an issue in first-party code.

### `<source> > <include>`

Keep existing source directories. When no include exists, derive all production
paths from Composer autoload roots; do not assume `src`.

### Coverage metadata

After merging, every modern unit-test class needs the appropriate
`#[CoversClass]`, `#[CoversFunction]`, or `#[CoversNothing]` attribute. A project
that runs PHPUnit 9.6 also needs the equivalent `@covers`, `@uses`, or
`@coversNothing` tags. Run both configuration files to find missing metadata.

Set `includeUncoveredFiles="true"` and `disableCodeCoverageIgnore="true"` on
`<coverage>` for every major. The first prevents untouched source files from
disappearing from coverage reports; the second prevents ignore metadata from
silently inflating coverage.

## Recommended Composer Scripts

Under a pinned floor there is one configuration, so the scripts call the runner
directly:

```json
{
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite unit"
    }
}
```

Under per-runtime resolution, copy `run-tests.php` from this skill to the target
project as `tests/run.php`, along with one configuration per resolved major in the
project root. The runner reads the installed `phpunit/phpunit` version and selects
the matching schema, including the separate PHPUnit 10, 11, and 12 files. This
keeps the public Composer command stable instead of making CI reimplement
dependency resolution as a matrix-to-script map:

```json
{
    "scripts": {
        "test:unit": "@php tests/run.php"
    }
}
```

Run `composer test:unit` under every maintained dependency graph and confirm the
selected file validates. Do not point the generic script at the newest schema when
an older supported runtime installs PHPUnit 10–12.

### Memory limit

Do not put `-d memory_limit=...` in these scripts by default. An unexplained limit
is either a no-op or a cap someone has to debug later, and it is not part of this
setup. Add one only for a measured failure.

PHPUnit reports the peak of every run in its footer (`Time: 00:00.123, Memory:
26.00 MB`). Compare that against what the target runtime actually allows —
`php -r 'echo ini_get("memory_limit"), PHP_EOL;'` — remembering that PHP CLI
frequently ships `memory_limit=-1`, in which case adding a finite value only makes
the suite fail sooner than it does today. If the suite fits, ship no limit.

This repository is the counterexample that justifies one: its PHPStan rule tests
boot a PHPStan DI container, and the suite dies with `Allowed memory size of
167772160 bytes exhausted` at 160M, so `composer test:unit` sets 512M. That number
comes from a reproduced failure, not from habit — derive the target's number the
same way.

When a limit is genuinely needed, make sure it reaches the ParaTest workers.
`-d memory_limit=...` applies to the parent process only; workers are separate PHP
processes and fall back to the runtime default. `tests/run.php` forwards the
active limit for you; without it, pass
`--passthru-php="'-d' 'memory_limit=512M'"` explicitly. Each worker receives the
full limit, so the machine's real ceiling is processes × limit — check that
product against the runner before raising either.

## Parallel Execution with ParaTest

Offer [ParaTest](https://github.com/paratestphp/paratest) once the suite is long
enough that developers start narrowing it with `--filter` — but explain what it
buys beyond speed, because that is the reason to prefer it as the default `test`
script rather than an optional extra.

Each ParaTest worker is a separate PHP process, so the suite has to hold up
without a shared runtime: no test reading a static property another test set, no
two tests writing the same fixture path, no class assuming it runs after some
other class. `executionOrder="depends,random"` already looks for that coupling
inside one process; splitting the suite checks it across the process boundary,
which no PHPUnit setting reaches. This matters most for AI-written tests, where
shared temporary paths and static caches are a routine shortcut.

```bash
composer require --dev "brianium/paratest:<target-derived-constraint>" --dry-run
```

Select that constraint with the same target-first process as PHPUnit: use the
newest ParaTest release compatible with the resolved PHPUnit version and the PHP
runtime of the test jobs. Run the command without `--dry-run` after confirming
the resolution. Add an older ParaTest line only when a real supported graph needs
it; do not copy this repository's union.

Under a pinned floor:

```json
{
    "scripts": {
        "test": "paratest --testsuite unit",
        "test:unit": "phpunit --testsuite unit"
    }
}
```

Under per-runtime resolution:

```json
{
    "scripts": {
        "test": "@php tests/run.php --parallel",
        "test:unit": "@php tests/run.php"
    }
}
```

### Process count

Leave the process count to ParaTest. `--processes` already defaults to `auto` in
every release this skill can select — 6.11 and the whole 7.x line — and `auto`
resolves through `fidry/cpu-core-counter` on the machine that runs the suite. No
supported version requires a fixed number, so there is no version-compatibility
reason to write one.

Never hardcode a count. `--processes=4` under-uses a larger runner, oversubscribes
a smaller one, and stops matching the hardware silently the first time CI changes
its runner size. Writing `--processes=auto` explicitly is redundant but acceptable
as a statement of intent.

Cap the count only for a demonstrated resource problem, and cap it with
`--max-processes`, which bounds `auto` rather than replacing it. That option exists
only from ParaTest 7.17.0; on an older line there is no way to bound `auto`, so
leave the count alone instead of reverting to a fixed number.

### Before wiring it in

- Check ParaTest's extensions, not folklore: it needs `ext-dom`, `ext-pcre`,
  `ext-reflection`, and `ext-simplexml`, and it does not use `pcntl`. The suite
  still needs `pcntl` for `enforceTimeLimit`, for the reason given in Required PHP
  Extensions.
- `tests/run.php` passes the selected configuration to ParaTest, so every supported
  dependency graph uses the schema for its installed PHPUnit major. It also
  forwards the active PHP memory limit to the workers, which otherwise fall back to
  the runtime default.
- Keep the single-process script for local debugging and tools that invoke PHPUnit
  directly. CI should use the parallel `composer test` command in its PHP matrix;
  duplicating the same suite in a second CI job adds no gate.

## Verification

After applying:

```bash
composer check-platform-reqs                                          # Extensions the graph demands
xmllint --noout --schema vendor/phpunit/phpunit/phpunit.xsd phpunit.xml.dist
vendor/bin/phpunit --list-tests   # Verify tests are discovered
composer test:unit                 # Run the full suite in one process
composer test                      # Run it again through the parallel runner
```

Repeat the schema check for every configuration file under per-runtime resolution,
each against the XSD of the PHPUnit its own leg installs. Confirm that every CI job
which installs the dev graph or runs tests lists the extensions from Required PHP
Extensions, and that `enforceTimeLimit` is not silently inert: a run with
`pcntl` missing reports `The pcntl extension is required for enforcing time limits`
rather than passing quietly.

If the suite passes in one process and fails under the parallel runner, the
failure is real: it is test-to-test coupling that the single-process run was
hiding. Fix the coupling rather than reverting to one process.

## References

- [PHPUnit AI Reporter](vendor/k-kinzal/php-ai-toolkit/docs/phpunit-ai-reporter.md) — Reporter behavior and output contract
