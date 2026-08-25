---
name: setup-toolkit-phpunit
description: >-
  Set up PHPUnit with strict configuration and AI test reporter for a PHP project.
  Use when asked to configure PHPUnit, set up testing, or enable the AI test reporter.
---

# Setup PHPUnit (Strict + AI Reporter)

This skill configures PHPUnit with maximum strictness and enables the AI test reporter from php-ai-toolkit.

## Prerequisites

Derive the PHPUnit constraint before installing it. A single-runtime application
may resolve one compatible major. A project supporting multiple PHP minors must use
a union that resolves for all of them and verify every maintained lock. For this
toolkit's PHP 8.0+ matrix the union is shown below; derive a different one when the
target's matrix differs:

```bash
composer require --dev "phpunit/phpunit:^9.6 || ^10.5 || ^11 || ^12 || ^13" k-kinzal/php-ai-toolkit
```

## Template

For PHPUnit 10.5 or later, read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpunit/phpunit.xml.dist` and apply it to the project root as `phpunit.xml.dist`.

For PHPUnit 9.6, read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpunit/phpunit9.xml.dist` and apply it to the project root as `phpunit.xml.dist`.

## Merging with Existing Configuration

If the project already has `phpunit.xml.dist`, merge as follows rather than overwriting.

### PHPUnit 10.5+ `<phpunit>` attributes — strict flags

All modern strict flags must be `true`. If the existing PHPUnit 10.5+ config has
any of these set to `false` or missing, override to `true`:

| Attribute | Required value | If existing is weaker |
|-----------|---------------|----------------------|
| `executionOrder` | `depends,random` | Override. Fixed order hides test dependencies. |
| `requireCoverageMetadata` | `true` | Override. Without it, coverage numbers are inaccurate. |
| `beStrictAboutCoverageMetadata` | `true` | Override. |
| `beStrictAboutChangesToGlobalState` | `true` | Override. |
| `beStrictAboutOutputDuringTests` | `true` | Override. |
| `failOnAllIssues` | `true` | Override. Without it, warnings pass silently. |
| `failOnEmptyTestSuite` | `true` | Override. |
| `failOnRisky` | `true` | Override. |
| `failOnWarning` | `true` | Override. |
| `enforceTimeLimit` | `true` | Override. |

Do not copy these names into a PHPUnit 9 configuration: that schema uses
different coverage-metadata names and does not support `failOnAllIssues`.

### PHPUnit 9.6 strict equivalents

Start from `phpunit9.xml.dist`. In addition to the flags shared with modern
PHPUnit, its fixed baseline uses:

| Attribute | Required value | Modern equivalent |
|-----------|----------------|-------------------|
| `forceCoversAnnotation` | `true` | `requireCoverageMetadata` |
| `beStrictAboutCoversAnnotation` | `true` | `beStrictAboutCoverageMetadata` |
| `convertDeprecationsToExceptions` | `true` | covered by `failOnAllIssues` and modern issue handling |
| `beStrictAboutResourceUsageDuringSmallTests` | `true` | covered by modern strict issue handling |
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

### PHPUnit 10.5+ `<phpunit>` attributes — timeouts

| Attribute | Toolkit value | If existing is stricter (lower) | If existing is weaker (higher) |
|-----------|--------------|-------------------------------|-------------------------------|
| `timeoutForSmallTests` | `1` | Keep existing. | Override to `1`. |
| `timeoutForMediumTests` | `10` | Keep existing. | Override to `10`. |
| `timeoutForLargeTests` | `60` | Keep existing. | Override to `60`. |

### `bootstrap`

Keep existing. The project may have a custom bootstrap file. Only set to `vendor/autoload.php` if no bootstrap is configured.

### `<testsuites>`

Keep existing testsuites. If the existing config already defines test directories, preserve them. Only add `tests/Unit` if no testsuite is configured. Example merge:
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
    <bootstrap class="PhpAiToolkit\PhpUnit\TestReporter\AiTestReporterExtension"/>  <!-- add -->
</extensions>
```

### `<listeners>` for PHPUnit 9.6

Use this section only for PHPUnit 9.6. Do not register `AiTestReporterExtension` in PHPUnit 9.6 because it depends on the PHPUnit 10+ event API.

Add the legacy listener alongside existing listeners:
```xml
<listeners>
    <listener class="Existing\Listener"/>                                                <!-- keep -->
    <listener class="PhpAiToolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener"/>  <!-- add -->
</listeners>
```

### `<source>` — `ignoreSuppression*` and `restrict*` attributes

All `ignoreSuppression*` attributes must be `true`. All `restrict*` attributes must be `true`. If any existing value is `false`, override to `true`. There is no case where error suppression should be honored.

### `<source> > <include>`

Keep existing source directories. Only set to `src` if no include is configured.

### Coverage metadata

After merging, every modern unit-test class needs the appropriate
`#[CoversClass]`, `#[CoversFunction]`, or `#[CoversNothing]` attribute. A project
that runs PHPUnit 9.6 also needs the equivalent `@covers`, `@uses`, or
`@coversNothing` tags. Run both configuration files to find missing metadata.

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
composer require --dev "brianium/paratest:^6.11 || ^7"
```

```json
{
    "scripts": {
        "test": "paratest --processes=auto",
        "test:unit": "@php -d memory_limit=512M vendor/bin/phpunit --configuration phpunit.xml.dist"
    }
}
```

Three things to check before wiring it in:

- `ext-pcntl` must be available; it is what ParaTest forks workers with. Add it
  to the CI `extensions:` list.
- `paratest` takes no `--configuration` above, so it reads `phpunit.xml.dist`.
  A project that keeps a separate PHPUnit 9 config for an older PHP floor must
  keep a `phpunit` script naming that file, and must not point the parallel job
  at the old floor.
- Keep the single-process script. The two runners answer different questions and
  both belong in CI: the version matrix on the single-process one, and one job
  on the parallel one. See the `/setup-toolkit-github-actions` skill.

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

- [PHPUnit Configuration](vendor/k-kinzal/php-ai-toolkit/docs/phpunit.md) — Settings and why each is needed
