# ForbidSingleLinePhpDocRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.singleLinePhpDoc` |
| Scope | Public elements of classes, interfaces, traits, enums |
| Configurable | No |

## What It Detects

Reports single-line PHPDoc comments on public API elements:

```php
/** User service. */  // ERROR: Rewrite PHPDoc as a multi-line block.
class UserService
{
    /** The user name. */  // ERROR
    public string $name = '';

    /** Finds a user. */  // ERROR
    public function findUser(int $id): ?User
    {
        // ...
    }
}
```

### Not reported

- Multi-line PHPDoc comments (the correct format)
- Regular comments (`//` and `/* ... */`)
- PHPDoc on private and protected members

## Why This Is an Error

Single-line PHPDoc comments tend to be terse and unhelpful. The multi-line format:

1. **Encourages detailed documentation**: The expanded format naturally leads to more thorough descriptions, parameter annotations, and return value documentation.

2. **Enables structured tags**: `@param`, `@return`, `@throws`, and other PHPDoc tags require separate lines, which single-line format cannot accommodate.

3. **Maintains consistency**: A uniform multi-line format across the codebase makes documentation predictable and easier to scan.

## How to Fix

Convert single-line PHPDoc to multi-line format:

```php
// Bad
/** User service. */
class UserService {}

// Good
/**
 * User service.
 */
class UserService {}
```

```php
// Bad
/** Finds a user by ID. */
public function findUser(int $id): ?User;

// Good
/**
 * Finds a user by ID.
 *
 * @param int $id the user ID
 * @return User|null the user, or null if not found
 */
public function findUser(int $id): ?User;
```
