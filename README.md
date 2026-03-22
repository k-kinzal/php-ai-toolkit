# php-ai-toolkit

A PHPStan extension that detects anti-patterns commonly introduced by AI code generation, plus output formatters optimized for both AI agents and humans.

## Requirements

- PHP ^8.0
- PHPStan ^1.12 || ^2.0
- PHPUnit ^10.5 || ^11 || ^12 || ^13 (for Test Reporter)

## Quick Start

### 1. Install

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

PHPStan rules are automatically enabled via the extension installer.

### 2. Set up phpstan.neon

Use `templates/phpstan.neon` as a starting point:

```neon
includes:
    - vendor/phpstan/phpstan-strict-rules/rules.neon
    - vendor/k-kinzal/php-ai-toolkit/extension.neon
    - vendor/k-kinzal/php-ai-toolkit/error-formatter.neon

parameters:
    level: max
    paths:
        - src
        - tests
    excludePaths:
        - tests/Fixture
```

> **Note:** These rules are designed for `level: max` with `phpstan-strict-rules`. Lower levels may reduce effectiveness, as some anti-patterns would already be caught by PHPStan itself.

### 3. Set up PHPUnit Test Reporter (optional)

Add the extension to your `phpunit.xml.dist`:

```xml
<extensions>
    <bootstrap class="PhpStanAiRules\TestReporter\AiTestReporterExtension"/>
</extensions>
```

### 4. Install AI Agent Skills (optional)

```bash
vendor/bin/php-ai-toolkit install
```

Auto-detects AI agent directories (`.claude`, `.cursor`, `.continue`, etc.) in your project root and installs skills. Use `--force` to overwrite, `--copy` to copy instead of symlinking.

## Usage

```bash
# PHPStan analysis (format switches automatically based on environment)
vendor/bin/phpstan analyse --error-format aiRules

# PHPUnit (Test Reporter switches automatically if enabled)
vendor/bin/phpunit
```

The Error Formatter and Test Reporter auto-detect AI agent environments and output structured plain text optimized for LLM context windows. For human runs, they produce rich colored output with code context. For unlisted agents, set `AI_AGENT=1`.

Details: [Error Formatter](docs/error-formatter.md) / [Test Reporter](docs/test-reporter.md)

## Rules

### General

| Rule | Description |
|------|-------------|
| [ForbiddenCommentRule](docs/rules/ForbiddenCommentRule.md) | Forbids `@phpstan-ignore` and `@infection-ignore-all` comments |
| [ForbiddenMagicMethodCallRule](docs/rules/ForbiddenMagicMethodCallRule.md) | Forbids direct calls to magic methods |
| [OverrideMustHaveAttributeRule](docs/rules/OverrideMustHaveAttributeRule.md) | Requires `#[Override]` attribute on overridden methods |
| [SrcUnitTestPairRule](docs/rules/SrcUnitTestPairRule.md) | Enforces 1:1 pairing between source and test files |
| [RequirePhpDocOnPublicApiRule](docs/rules/RequirePhpDocOnPublicApiRule.md) | Requires PHPDoc on all public API elements |
| [ForbidNonDocCommentRule](docs/rules/ForbidNonDocCommentRule.md) | Forbids non-PHPDoc comments (`//`, `/* */`, `#`) |
| [ForbidSingleLinePhpDocRule](docs/rules/ForbidSingleLinePhpDocRule.md) | Forbids single-line PHPDoc on public elements |

### Test Class

Applied to `Tests\Unit` / `Tests\Integration` namespaces. Rules marked with `*` apply to all `Tests\` namespaces.

| Rule | Description |
|------|-------------|
| [NoPropertyInTestClassRule](docs/rules/NoPropertyInTestClassRule.md) | Forbids property declarations |
| [NoClassConstantInTestClassRule](docs/rules/NoClassConstantInTestClassRule.md) | Forbids class constants |
| [NoPrivateMethodInTestClassRule](docs/rules/NoPrivateMethodInTestClassRule.md) | Forbids private methods |
| [NoHelperMethodInTestClassRule](docs/rules/NoHelperMethodInTestClassRule.md) | Forbids methods other than test/provider/override |
| [NoControlFlowInTestMethodRule](docs/rules/NoControlFlowInTestMethodRule.md) | Forbids control flow statements in test methods |
| [NoTraitUseInTestClassRule](docs/rules/NoTraitUseInTestClassRule.md) | Forbids trait usage |
| [NoReflectionInTestClassRule](docs/rules/NoReflectionInTestClassRule.md) `*` | Forbids Reflection usage |
| [PhpUnitMockApiRule](docs/rules/PhpUnitMockApiRule.md) `*` | Restricts mock API and enforces interface-only mocking |
| [ForbidDescriptivePhpDocInTestClassRule](docs/rules/ForbidDescriptivePhpDocInTestClassRule.md) | Forbids descriptive PHPDoc text |
| [TestNamingConventionRule](docs/rules/TestNamingConventionRule.md) | Enforces naming conventions for test methods and data providers |

See each rule's documentation for error identifiers and examples.

## Configuration

To disable specific rules, suppress their error identifiers:

```neon
parameters:
    ignoreErrors:
        - identifier: customRules.infectionIgnoreAllComment
```

For other parameters (`testNamespacePrefixes`, `srcMarker`, etc.), see [extension.neon](extension.neon).

## License

MIT
