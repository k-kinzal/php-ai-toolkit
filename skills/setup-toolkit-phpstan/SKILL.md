---
name: setup-toolkit-phpstan
description: >-
  Set up PHPStan with strict configuration and AI error formatter for a PHP project.
  Use when asked to configure PHPStan, set up static analysis, or enable the AI error formatter.
---

# Setup PHPStan (Strict + AI Error Formatter)

This skill configures PHPStan at maximum strictness with the AI error formatter from php-ai-toolkit.

## Prerequisites

Run in the target project:

```bash
composer require --dev phpstan/phpstan phpstan/phpstan-strict-rules k-kinzal/php-ai-toolkit
```

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/templates/phpstan.neon` and apply it to the project root as `phpstan.neon`.

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

## Setting Explanations

### Level

`level: max` is the strictest analysis level. It enables all type checks including union types, intersection types, generics, and strict mixed-type handling.

### Included Extensions

| Include | Purpose |
|---------|---------|
| `phpstan-strict-rules` | Enforces strict comparisons (`===`), disallows empty(), requires explicit boolean conditions, and more |
| `extension.neon` (php-ai-toolkit) | Registers 17 AI-specific rules: forbidden comments, test class restrictions, src/test pairing, naming conventions, etc. |
| `error-formatter.neon` (php-ai-toolkit) | Registers the `aiRules` error formatter for dual-mode output (AI-optimized vs human-readable) |

### AI Error Formatter

The `aiRules` formatter provides:
- **AI mode**: Flat format with `path:line` leading, deduplication of repeated errors, plain text — optimized for token efficiency
- **Human mode**: Grouped by file, code context with caret pointers, colored output

The formatter auto-detects the execution environment (Claude Code, Cursor, Gemini CLI, etc.).

### AI Rules (17 Total)

The extension.neon registers these rules (all auto-enabled):

**General Rules:**
- ForbiddenCommentRule — Bans `@phpstan-ignore`, `@infection-ignore-all`, `// ` comments
- ForbiddenMagicMethodCallRule — Reports direct calls to magic methods
- OverrideMustHaveAttributeRule — Requires `#[Override]` on overriding methods
- SrcUnitTestPairRule — Every src/ class must have a test, and vice versa
- RequirePhpDocOnPublicApiRule — PHPDoc required on all public API
- ForbidNonDocCommentRule — Bans `//`, `/* */`, `#` comments (use PHPDoc)
- ForbidSingleLinePhpDocRule — Bans single-line `/** */` on public API

**Test Class Rules:**
- NoPropertyInTestClassRule, NoClassConstantInTestClassRule
- NoPrivateMethodInTestClassRule, NoHelperMethodInTestClassRule
- NoControlFlowInTestMethodRule, NoTraitUseInTestClassRule
- NoReflectionInTestClassRule, PhpUnitMockApiRule
- ForbidDescriptivePhpDocInTestClassRule, TestNamingConventionRule

## Customizable Parameters

The extension accepts these parameters for project-specific tuning:

```neon
parameters:
    customRules:
        # Namespace prefixes that identify test classes (default: ['Tests'])
        testNamespacePrefixes:
            - 'Tests'

        # Subset of test namespaces where stricter rules apply (default: ['Tests\Unit', 'Tests\Integration'])
        restrictedTestNamespacePrefixes:
            - 'Tests\Unit'
            - 'Tests\Integration'

        # Glob patterns for classes excluded from src/test pair checks
        srcUnitTestPairExcludePatterns: []

        # Path markers for matching src classes to test classes
        srcMarker: '/src/'
        unitTestMarker: '/tests/Unit/'
```

## Adaptation Guide

When applying this template to a project:

1. **Paths**: Change `paths` to match the project's actual source and test directories.

2. **Exclude paths**: Change `excludePaths` to match fixture/stub directories.

3. **Test namespace prefixes**: If the project uses a different test namespace (e.g., `App\Tests`), configure:
   ```neon
   parameters:
       customRules:
           testNamespacePrefixes:
               - 'App\Tests'
           restrictedTestNamespacePrefixes:
               - 'App\Tests\Unit'
               - 'App\Tests\Integration'
   ```

4. **Src/test markers**: If the project has a non-standard directory layout:
   ```neon
   parameters:
       customRules:
           srcMarker: '/app/'
           unitTestMarker: '/tests/Unit/'
   ```

5. **Exclude patterns**: To exclude certain classes from src/test pairing:
   ```neon
   parameters:
       customRules:
           srcUnitTestPairExcludePatterns:
               - '#/Migration/#'
               - '#/DataFixtures/#'
   ```

6. **ignoreErrors**: Add project-specific error ignores ONLY when the violation is unavoidable and well-documented:
   ```neon
   parameters:
       ignoreErrors:
           -
               identifier: some.specific.identifier
               path: path/to/specific/file.php
   ```

## Recommended Composer Scripts

Add to the target project's `composer.json`:

```json
{
    "scripts": {
        "lint": [
            "@format:check",
            "phpstan analyse --error-format=aiRules --memory-limit=512M"
        ]
    }
}
```

The `--error-format=aiRules` flag activates the dual-mode formatter. When run by an AI agent, output is optimized for token efficiency; when run by a human, output includes code context and colors.

## Verification

After applying:

```bash
vendor/bin/phpstan analyse --error-format=aiRules --memory-limit=512M
```

Fix all reported errors before committing. Every error message includes specific fix instructions.
