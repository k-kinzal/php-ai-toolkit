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

Read the template from `vendor/k-kinzal/php-ai-toolkit/templates/phpunit.xml.dist` and apply it to the project root as `phpunit.xml.dist`.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,random"
         requireCoverageMetadata="true"
         beStrictAboutCoverageMetadata="true"
         beStrictAboutChangesToGlobalState="true"
         beStrictAboutOutputDuringTests="true"
         failOnAllIssues="true"
         failOnEmptyTestSuite="true"
         failOnRisky="true"
         failOnWarning="true"
         enforceTimeLimit="true"
         timeoutForSmallTests="1"
         timeoutForMediumTests="10"
         timeoutForLargeTests="60">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <extensions>
        <bootstrap class="PhpStanAiRules\TestReporter\AiTestReporterExtension"/>
    </extensions>
    <source restrictNotices="true"
            restrictWarnings="true"
            ignoreSuppressionOfDeprecations="true"
            ignoreSuppressionOfPhpDeprecations="true"
            ignoreSuppressionOfErrors="true"
            ignoreSuppressionOfNotices="true"
            ignoreSuppressionOfPhpNotices="true"
            ignoreSuppressionOfWarnings="true"
            ignoreSuppressionOfPhpWarnings="true">
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

## Setting Explanations

### Strict Flags

| Setting | Purpose |
|---------|---------|
| `requireCoverageMetadata` | Every test class MUST have `#[CoversClass(...)]` or `#[CoversNothing]` |
| `beStrictAboutCoverageMetadata` | Fails tests that lack coverage metadata |
| `beStrictAboutChangesToGlobalState` | Detects global state pollution between tests |
| `beStrictAboutOutputDuringTests` | Fails tests that produce unexpected stdout/stderr output |
| `failOnAllIssues` | Zero tolerance -- any issue is a test failure |
| `failOnEmptyTestSuite` | Fails if no tests are found (catches misconfigured paths) |
| `failOnRisky` | Fails risky tests (no assertions, output, global state) |
| `failOnWarning` | Fails on PHPUnit warnings |
| `enforceTimeLimit` | Enforces per-test time limits |
| `timeoutForSmallTests="1"` | Small tests must complete within 1 second |
| `timeoutForMediumTests="10"` | Medium tests within 10 seconds |
| `timeoutForLargeTests="60"` | Large tests within 60 seconds |
| `executionOrder="depends,random"` | Randomizes test order to catch hidden dependencies |

### Source Suppression Flags

All `ignoreSuppressionOf*` flags are set to `true`. This means `@` error suppression in source code will NOT hide issues during tests. Every notice, warning, and deprecation will surface.

### AI Test Reporter Extension

`PhpStanAiRules\TestReporter\AiTestReporterExtension` provides dual-mode output:
- **AI mode** (auto-detected when running under Claude Code, Cursor, etc.): Structured plain text with source location extraction -- points to the actual bug location, not just the test assertion line
- **Human mode**: Grouped by file with colored severity indicators

## Adaptation Guide

When applying this template to a project:

1. **Test directory**: Change `<directory>tests/Unit</directory>` to match the project's test directory structure. Add additional testsuites if needed:
   ```xml
   <testsuite name="integration">
       <directory>tests/Integration</directory>
   </testsuite>
   ```

2. **Source directory**: Change `<include><directory>src</directory></include>` to match the project's source directory.

3. **Existing config**: If the project already has `phpunit.xml.dist`, merge the strict flags rather than overwriting. Preserve existing testsuites and any custom bootstrap logic.

4. **Coverage metadata**: After applying, every test class needs `#[CoversClass(TargetClass::class)]`. Run tests to find missing attributes.

## Recommended Composer Scripts

Add to the target project's `composer.json`:

```json
{
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit tests/Unit/"
    }
}
```

For parallel execution (optional):
```json
{
    "require-dev": {
        "brianium/paratest": "^7"
    },
    "scripts": {
        "test": "paratest --processes=auto"
    }
}
```

## Verification

After applying:

```bash
vendor/bin/phpunit --list-tests   # Verify tests are discovered
composer test                      # Run the full suite
```
