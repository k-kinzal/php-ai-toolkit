---
name: setup-toolkit-phpunit
description: >-
  Set up PHPUnit with strict configuration and AI test reporter for a PHP project.
  Use when asked to configure PHPUnit, set up testing, or enable the AI test reporter.
---

# Setup PHPUnit (Strict + AI Reporter)

This skill configures PHPUnit with maximum strictness and enables the AI test reporter from php-ai-toolkit.

## Prerequisites

Choose PHPUnit from the target's PHP support range, existing test extensions,
Composer graph, and CI topology. Inspect current Composer metadata and PHPUnit's
support documentation at application time, then select the newest compatible
release. Do not copy this toolkit repository's multi-major constraint.

A single-runtime application normally needs one current major. A project that
installs development dependencies on several PHP minors may need a minimal union,
but include an older major only for an actual matrix leg that cannot install the
newest compatible line. Verify every maintained lock or CI leg and diagnose an
unexpected older resolution with `composer why-not`:

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
project root as `phpunit.xml.dist`. A project that genuinely installs multiple
PHPUnit majors must retain one configuration per major and select the matching
file in each CI leg.

The 10–12 templates track their maintained minor's current schema. Some strict
attributes were added in patch releases, so an older locked patch may reject the
template even within the same minor. Update it to the newest compatible patch as
required above, or derive a configuration from that exact installed XSD.

Validate every file with the PHPUnit version that consumes it. If a future schema
differs from the shipped PHPUnit 13 template, migrate the template to that
installed schema; do not downgrade PHPUnit merely to match an example file.

For a new configuration, replace `REPLACE_WITH_VENDOR_DIR` from `composer config
vendor-dir`, and replace `REPLACE_WITH_UNIT_TEST_PATH` and
`REPLACE_WITH_PRODUCTION_PATH` from the target's test layout and production
autoload roots. A remaining sentinel or a zero-test suite is a failed setup.

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
        <directory>tests/Unit</directory>         <!-- existing -->
    </testsuite>
    <testsuite name="integration">
        <directory>tests/Integration</directory>  <!-- existing -->
    </testsuite>
</testsuites>
```

### `<extensions>`

Use this section only for PHPUnit 10.5 or later.

Add the toolkit extension alongside existing extensions. Do not remove existing ones:
```xml
<extensions>
    <bootstrap class="Existing\Extension"/>                              <!-- keep -->
    <bootstrap class="Toolkit\PhpUnit\TestReporter\AiTestReporterExtension"/>  <!-- add -->
</extensions>
```

### `<listeners>` for PHPUnit 9.6

Use this section only for PHPUnit 9.6. Do not register `AiTestReporterExtension` in PHPUnit 9.6 because it depends on the PHPUnit 10+ event API.

Add the legacy listener alongside existing listeners:
```xml
<listeners>
    <listener class="Existing\Listener"/>                                                <!-- keep -->
    <listener class="Toolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener"/>  <!-- add -->
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

Add to the target project's `composer.json`:

```json
{
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite unit"
    }
}
```

For a real multi-major matrix, copy `run-tests.php` from this skill to the target
project as `tests/run.php`, along with one configuration per installed major in
the project root. The runner reads the
installed `phpunit/phpunit` version and selects the matching schema, including the
separate PHPUnit 10, 11, and 12 files. This keeps the public Composer command stable
instead of making CI reimplement dependency resolution as a matrix-to-script map:

```json
{
    "scripts": {
        "test:unit": "@php -d memory_limit=512M tests/run.php"
    }
}
```

Run `composer test:unit` under every maintained dependency graph and confirm the
selected file validates. Do not point the generic script at the newest schema when
an older supported runtime installs PHPUnit 10–12.

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

```json
{
    "scripts": {
        "test": "@php -d memory_limit=512M tests/run.php --parallel --processes=auto --max-processes=4",
        "test:unit": "@php -d memory_limit=512M tests/run.php"
    }
}
```

Three things to check before wiring it in:

- `ext-pcntl` must be available; it is what ParaTest forks workers with. Add it
  to the CI `extensions:` list.
- `tests/run.php` passes the selected configuration to ParaTest, so every supported
  dependency graph uses the schema for its installed PHPUnit major. It also passes
  the configured PHP memory limit to ParaTest workers rather than silently falling
  back to the runtime default.
- Keep the single-process script for local debugging and tools that invoke PHPUnit
  directly. CI should use the parallel `composer test` command in its PHP matrix;
  duplicating the same suite in a second CI job adds no gate.

## Verification

After applying:

```bash
vendor/bin/phpunit --list-tests   # Verify tests are discovered
composer test:unit                 # Run the full suite in one process
composer test                      # Run it again through the parallel runner
```

If the suite passes in one process and fails under the parallel runner, the
failure is real: it is test-to-test coupling that the single-process run was
hiding. Fix the coupling rather than reverting to one process.

## References

- [PHPUnit AI Reporter](vendor/k-kinzal/php-ai-toolkit/docs/phpunit-ai-reporter.md) — Reporter behavior and output contract
