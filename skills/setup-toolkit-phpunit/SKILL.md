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

## Verification

After applying:

```bash
vendor/bin/phpunit --list-tests   # Verify tests are discovered
composer test                      # Run the full suite
```

## References

- [PHPUnit Configuration](vendor/k-kinzal/php-ai-toolkit/docs/phpunit.md) — Settings and why each is needed
