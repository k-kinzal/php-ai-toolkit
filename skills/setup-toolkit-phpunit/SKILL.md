---
name: setup-toolkit-phpunit
description: >-
  Set up PHPUnit with strict configuration and AI test reporter for a PHP project.
  Use when asked to configure PHPUnit, set up testing, or enable the AI test reporter.
---

# Setup PHPUnit (Strict + AI Reporter)

This skill configures PHPUnit with maximum strictness and enables the AI test reporter from php-ai-toolkit.

## Prerequisites

Run in the target project:

```bash
composer require --dev phpunit/phpunit k-kinzal/php-ai-toolkit
```

## Template

For PHPUnit 10.5 or later, read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpunit/phpunit.xml.dist` and apply it to the project root as `phpunit.xml.dist`.

For PHPUnit 9.6, read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpunit/phpunit9.xml.dist` and apply it to the project root as `phpunit.xml.dist`.

## Merging with Existing Configuration

If the project already has `phpunit.xml.dist`, merge as follows rather than overwriting.

### `<phpunit>` attributes — strict flags

All strict flags must be `true`. If the existing config has any of these set to `false` or missing, override to `true`:

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

There is no case where any of these should be `false`. The toolkit is stricter than the default and always wins.

### `<phpunit>` attributes — timeouts

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

After merging, every test class needs `#[CoversClass(TargetClass::class)]`. Run tests to find missing attributes.

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
