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

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-phpstan/phpstan.neon` and apply it to the project root as `phpstan.neon`.

## Merging with Existing Configuration

If the project already has `phpstan.neon`, merge as follows rather than overwriting.

### `includes`

Union both lists. Never remove existing includes. Add the toolkit's three includes:
```neon
includes:
    - existing/extension.neon          # keep
    - vendor/phpstan/phpstan-strict-rules/rules.neon  # add
    - vendor/k-kinzal/php-ai-toolkit/extension.neon   # add
    - vendor/k-kinzal/php-ai-toolkit/error-formatter.neon  # add
```

### `level`

Always set to `max`. If the existing level is lower, override it. There is no case where a lower level is acceptable — raising the level mid-project is harder than starting at max.

### `paths`

Keep the existing paths. If the existing config already lists `src` and `tests` (or equivalent), leave them. Only adjust if the project uses different directory names.

### `excludePaths`

Keep existing excludes as-is. These are project-specific (fixture directories, generated code, etc.) and the toolkit has no opinion on them.

### `ignoreErrors`

Keep existing ignores as-is. These are project-specific suppressions for unavoidable violations. Do not add new ignores unless the violation is genuinely unfixable and well-documented:
```neon
parameters:
    ignoreErrors:
        -
            identifier: some.specific.identifier
            path: path/to/specific/file.php
```

### `parameters.customRules`

Only add if the project uses non-standard conventions:

- **Test namespace prefixes**: If the project uses `App\Tests` instead of `Tests`:
  ```neon
  parameters:
      customRules:
          testNamespacePrefixes:
              - 'App\Tests'
          restrictedTestNamespacePrefixes:
              - 'App\Tests\Unit'
              - 'App\Tests\Integration'
  ```

- **Src/test markers**: If the project has a non-standard directory layout:
  ```neon
  parameters:
      customRules:
          srcMarker: '/app/'
          unitTestMarker: '/tests/Unit/'
  ```

- **Exclude patterns**: To exclude certain classes from src/test pairing:
  ```neon
  parameters:
      customRules:
          srcUnitTestPairExcludePatterns:
              - '#/Migration/#'
              - '#/DataFixtures/#'
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

## References

- [PHPStan Configuration](vendor/k-kinzal/php-ai-toolkit/docs/phpstan.md) — Settings and why each is needed
- [PHPStan Rules](vendor/k-kinzal/php-ai-toolkit/docs/phpstan-rules.md) — Custom rules and their error identifiers
