---
name: setup-toolkit-php-cs-fixer
description: >-
  Set up PHP-CS-Fixer with strict coding standards for a PHP project.
  Use when asked to configure code formatting, coding standards, or PHP-CS-Fixer.
---

# Setup PHP-CS-Fixer (Strict Standards)

This skill configures PHP-CS-Fixer with strict coding standards optimized for quality-focused PHP projects.

## Prerequisites

Run in the target project:

```bash
composer require --dev friendsofphp/php-cs-fixer
```

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/templates/.php-cs-fixer.dist.php` and apply it to the project root as `.php-cs-fixer.dist.php`.

```php
<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('vendor');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        'no_empty_statement' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'void_return' => true,
        'no_alias_functions' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'fully_qualified_strict_types' => true,
    ])
    ->setFinder($finder);
```

## Rule Explanations

### Base Standard

| Rule | Purpose |
|------|---------|
| `@PSR12` | Full PSR-12 coding standard compliance |

### Type Safety

| Rule | Purpose |
|------|---------|
| `declare_strict_types` | Adds `declare(strict_types=1);` to every PHP file |
| `strict_param` | Native PHP functions use strict parameter types |
| `strict_comparison` | Forces `===` and `!==` instead of `==` and `!=` |

### Import Hygiene

| Rule | Purpose |
|------|---------|
| `no_unused_imports` | Removes unused `use` statements |
| `ordered_imports` | Alphabetically sorts `use` statements |
| `global_namespace_import` | Converts `\strlen()` to `use function strlen;` — explicit imports for all global symbols |
| `fully_qualified_strict_types` | Uses short names in code when the symbol is imported |

### Style Consistency

| Rule | Purpose |
|------|---------|
| `single_quote` | Uses single quotes for strings without interpolation |
| `trailing_comma_in_multiline` | Adds trailing commas in multiline arrays/arguments — cleaner git diffs |
| `array_syntax` | Uses short array syntax `[]` instead of `array()` |

### Dead Code Removal

| Rule | Purpose |
|------|---------|
| `no_empty_statement` | Removes stray semicolons (e.g., `;;`) |
| `no_superfluous_elseif` | Converts `elseif` after `return`/`throw`/`continue` to `if` |
| `no_useless_else` | Removes `else` after `return`/`throw`/`continue` |
| `no_alias_functions` | Replaces aliases (`sizeof` → `count`, `join` → `implode`) |
| `no_mixed_echo_print` | Enforces consistent `echo` usage (no `print`) |

### Explicitness

| Rule | Purpose |
|------|---------|
| `void_return` | Adds explicit `: void` return type to functions that return nothing |

## Adaptation Guide

When applying this template to a project:

1. **Source directories**: If the project has source in a subdirectory:
   ```php
   $finder = (new PhpCsFixer\Finder())
       ->in([__DIR__ . '/src', __DIR__ . '/tests'])
       ->exclude('vendor');
   ```

2. **Additional excludes**: Add directories to exclude:
   ```php
   ->exclude(['vendor', 'var', 'cache', 'build'])
   ```

3. **Framework-specific rules**: For Laravel or Symfony projects, consider adding their rule sets. However, do NOT weaken the strict rules above.

4. **Initial application**: The first run will likely fix many files. Run `composer format` and commit all changes in a single "Apply strict coding standards" commit before doing other work.

## Recommended Composer Scripts

Add to the target project's `composer.json`:

```json
{
    "scripts": {
        "format": "php-cs-fixer fix --allow-risky=yes",
        "format:check": "php-cs-fixer fix --dry-run --diff --allow-risky=yes"
    }
}
```

## .gitignore

Add to the project's `.gitignore`:

```
/.php-cs-fixer.cache
```

## Verification

After applying:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
```

To auto-fix all violations:

```bash
vendor/bin/php-cs-fixer fix --allow-risky=yes
```
