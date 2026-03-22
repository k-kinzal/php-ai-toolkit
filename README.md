# php-ai-toolkit

A comprehensive toolkit for AI-assisted PHP development — PHPStan rules, PHPUnit reporters, config templates, and more.

Detects anti-patterns commonly introduced by AI code generation, such as suppressing static analysis, bloated test classes, and direct magic method calls.

## Installation

```bash
composer require --dev k-kinzal/php-ai-toolkit
```

Rules are automatically enabled via PHPStan's extension installer.

> **Note:** These rules are designed to complement PHPStan at `level: max` with `phpstan-strict-rules` enabled. Using a lower level or disabling strict rules may reduce their effectiveness, as some anti-patterns would already be caught by PHPStan itself.

## Rules

### General

| Rule | Identifier | Description |
|------|-----------|-------------|
| [ForbiddenCommentRule](docs/rules/ForbiddenCommentRule.md) | `customRules.phpstanIgnoreComment` / `customRules.infectionIgnoreAllComment` | Forbids `@phpstan-ignore` and `@infection-ignore-all` comments |
| [ForbiddenMagicMethodCallRule](docs/rules/ForbiddenMagicMethodCallRule.md) | `customRules.forbiddenMagicMethodCall` | Forbids direct calls to magic methods |
| [OverrideMustHaveAttributeRule](docs/rules/OverrideMustHaveAttributeRule.md) | `customRules.overrideMustHaveAttribute` | Requires `#[Override]` attribute on overridden methods |
| [SrcUnitTestPairRule](docs/rules/SrcUnitTestPairRule.md) | `customRules.srcWithoutUnitTest` / `customRules.unitTestWithoutSource` | Enforces 1:1 pairing between source and test files |
| [RequirePhpDocOnPublicApiRule](docs/rules/RequirePhpDocOnPublicApiRule.md) | `customRules.requirePhpDocOnClass` / `customRules.requirePhpDocOnMethod` / `customRules.requirePhpDocOnProperty` / `customRules.requirePhpDocOnConstant` | Requires PHPDoc on all public API elements |
| [ForbidNonDocCommentRule](docs/rules/ForbidNonDocCommentRule.md) | `customRules.nonDocComment` | Forbids all non-PHPDoc comments (`//`, `/* */`, `#`) |
| [ForbidSingleLinePhpDocRule](docs/rules/ForbidSingleLinePhpDocRule.md) | `customRules.singleLinePhpDoc` | Forbids single-line PHPDoc comments on public elements |

### Test Class

Rules applied to test classes. Most rules target `Tests\Unit` / `Tests\Integration` namespaces; rules marked with `*` apply to all `Tests\` namespaces.

| Rule | Identifier | Description |
|------|-----------|-------------|
| [NoPropertyInTestClassRule](docs/rules/NoPropertyInTestClassRule.md) | `customRules.testClassProperty` | Forbids property declarations in test classes |
| [NoClassConstantInTestClassRule](docs/rules/NoClassConstantInTestClassRule.md) | `customRules.testClassConstant` | Forbids class constants in test classes |
| [NoPrivateMethodInTestClassRule](docs/rules/NoPrivateMethodInTestClassRule.md) | `customRules.testClassPrivateMethod` | Forbids private methods in test classes |
| [NoHelperMethodInTestClassRule](docs/rules/NoHelperMethodInTestClassRule.md) | `customRules.testClassNonOverrideMethod` | Forbids methods other than test/provider/override in test classes |
| [NoControlFlowInTestMethodRule](docs/rules/NoControlFlowInTestMethodRule.md) | `customRules.testMethodControlFlow` | Forbids control flow statements in test methods |
| [NoTraitUseInTestClassRule](docs/rules/NoTraitUseInTestClassRule.md) | `customRules.testClassTraitUse` | Forbids trait usage in test classes |
| [NoReflectionInTestClassRule](docs/rules/NoReflectionInTestClassRule.md) `*` | `customRules.noReflectionInTestClass` | Forbids Reflection usage in test classes |
| [PhpUnitMockApiRule](docs/rules/PhpUnitMockApiRule.md) `*` | `customRules.testClassPhpUnitMockProhibitedApi` / `customRules.testClassPhpUnitMockRequiresInterface` / `customRules.testClassPhpUnitMockRequiresLiteralInterface` / `customRules.testClassPhpUnitMockProhibitedInstantiation` | Restricts PHPUnit mock API usage and enforces interface-only mocking |
| [ForbidDescriptivePhpDocInTestClassRule](docs/rules/ForbidDescriptivePhpDocInTestClassRule.md) | `customRules.testClassDescriptivePhpDoc` | Forbids descriptive PHPDoc text in test classes |
| [TestNamingConventionRule](docs/rules/TestNamingConventionRule.md) | `customRules.testMethodNamingConvention` / `customRules.providerNamingConvention` / `customRules.testMethodProhibitedConstructorDestructor` / `customRules.publicMethodWithoutTest` | Enforces PascalCase naming for test methods and data providers, prohibits constructor/destructor tests, and requires test coverage for public methods |

## Error Formatter

This package includes an optional dual-mode error formatter that automatically switches between human-readable and AI-readable output.

To enable it, add `error-formatter.neon` to your `phpstan.neon`:

```neon
includes:
    - vendor/k-kinzal/php-ai-toolkit/error-formatter.neon
```

Then use it with:

```bash
vendor/bin/phpstan analyse --error-format aiRules
```

When run inside an AI agent (Claude Code, Cursor, Devin, etc.), it outputs structured plain text optimized for LLM context windows — with deduplication, self-contained error blocks, and no decorative formatting. When run by a human, it outputs rich, grouped output with code context, caret pointers, and color.

Agent detection is automatic via environment variables and filesystem markers. For unlisted agents, set `AI_AGENT=1`.

See [docs/error-formatter.md](docs/error-formatter.md) for full details.

## Test Reporter

This package includes an optional PHPUnit extension that provides dual-mode test result reporting with the same auto-detection as the error formatter.

To enable it, add the extension to your `phpunit.xml.dist`:

```xml
<extensions>
    <bootstrap class="PhpStanAiRules\TestReporter\AiTestReporterExtension"/>
</extensions>
```

When run inside an AI agent, it outputs structured plain text optimized for LLM context windows — with self-contained failure blocks, code context, and source locations pointing to where the bug is (not just where the test failed). When run by a human, it outputs rich, grouped output with code context, caret pointers, and color.

See [docs/test-reporter.md](docs/test-reporter.md) for full details.

## CLI

The package provides a CLI tool for installing skills into your project:

```bash
vendor/bin/php-ai-toolkit install
```

The `install` command auto-detects AI agent directories (`.claude`, `.agents`, `.continue`, `.openhands`, `.windsurf`, `.factory`) in your project root and installs skills from the package into the corresponding skills directories.

Options:

| Flag | Description |
|------|-------------|
| `--force`, `-f` | Overwrite existing skills |
| `--copy` | Copy files instead of creating symlinks |
| `--help`, `-h` | Show help message |
| `--version`, `-V` | Show version |

## Configuration

Customize parameters in your `phpstan.neon`:

```neon
parameters:
    customRules:
        # Namespace prefixes for identifying test classes
        testNamespacePrefixes:
            - 'Tests'

        # Namespace prefixes for restricted test classes
        restrictedTestNamespacePrefixes:
            - 'Tests\Unit'
            - 'Tests\Integration'

        # File patterns to exclude from SrcUnitTestPairRule
        srcUnitTestPairExcludePatterns:
            - '*.generated.php'

        # Path marker for source directory
        srcMarker: '/src/'

        # Path marker for unit test directory
        unitTestMarker: '/tests/Unit/'
```

### Disabling Specific Rules

To disable an entire rule, avoid including `extension.neon` and register only the rules you need manually.

Alternatively, suppress specific error identifiers:

```neon
parameters:
    ignoreErrors:
        - identifier: customRules.infectionIgnoreAllComment
```

## Requirements

- PHP ^8.0
- PHPStan ^1.12 || ^2.0

## License

MIT
