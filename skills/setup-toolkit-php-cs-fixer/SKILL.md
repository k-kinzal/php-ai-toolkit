---
name: setup-toolkit-php-cs-fixer
description: >-
  Set up PHP-CS-Fixer with strict coding standards for a PHP project.
  Use when asked to configure code formatting, coding standards, or PHP-CS-Fixer.
---

# Setup PHP-CS-Fixer (Strict Standards)

This skill configures PHP-CS-Fixer with strict coding standards optimized for AI-assisted PHP development.

## Prerequisites

Run in the target project:

```bash
composer require --dev friendsofphp/php-cs-fixer
```

## Template

Read the template from `vendor/k-kinzal/php-ai-toolkit/skills/setup-toolkit-php-cs-fixer/.php-cs-fixer.dist.php` and apply it to the project root as `.php-cs-fixer.dist.php`.

## Merging with Existing Configuration

If the project already has `.php-cs-fixer.dist.php`, merge as follows rather than overwriting.

### `setRiskyAllowed`

Must be `true`. If the existing config has `setRiskyAllowed(false)`, override to `true`. Without it, `declare_strict_types`, `strict_param`, `strict_comparison`, `void_return`, and `no_alias_functions` cannot be applied.

### Rules — toolkit rules always win

Merge `setRules()` arrays. When the same rule key exists in both, the toolkit value takes precedence. The toolkit rules are the minimum bar and must not be weakened.

| Conflict type | Example | Resolution |
|--------------|---------|------------|
| Existing disables a toolkit rule | `'strict_comparison' => false` | Override to `true`. There is no use case for `==` in PHP. |
| Existing has a weaker setting | `'ordered_imports' => ['sort_algorithm' => 'none']` | Override to `['sort_algorithm' => 'alpha']`. |
| Existing has a different setting | `'ordered_imports' => ['sort_algorithm' => 'length']` | Override to `alpha`. `alpha` is deterministic and AI agents can always compute the correct insertion position. |
| Existing has rules not in toolkit | `'no_trailing_whitespace' => true` | Keep. Additional rules are fine. |
| Existing uses a preset that conflicts | `'@PhpCsFixer:risky' => true` | Keep the preset, but add toolkit rules after it so they override any conflicting preset values. |

Example merge:
```php
return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer:risky' => true,       // existing preset — keep
        'no_trailing_whitespace' => true,   // existing extra rule — keep
        // toolkit rules below — these override any conflicting preset values
        '@PSR12' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        // ... all other toolkit rules
    ])
    ->setFinder($finder);
```

### Finder

Keep existing Finder configuration. The project may have specific `->in()` paths and `->exclude()` directories. Only use the toolkit's default Finder if no existing config exists. If needed, add additional excludes:
```php
->exclude(['vendor', 'var', 'cache', 'build'])
```

### Framework-specific rules

For Laravel or Symfony projects, their rule sets can be added alongside toolkit rules. Place toolkit rules after framework presets so they take precedence on conflicts.

### Initial application

The first run will likely fix many files. Run `composer format` and commit all changes in a single "Apply strict coding standards" commit before doing other work.

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

## References

- [PHP-CS-Fixer Configuration](vendor/k-kinzal/php-ai-toolkit/docs/php-cs-fixer.md) — Settings and why each is needed
