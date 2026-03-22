# RequirePhpDocOnPublicApiRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.requirePhpDocOnClass`, `customRules.requirePhpDocOnMethod`, `customRules.requirePhpDocOnProperty`, `customRules.requirePhpDocOnConstant` |
| Scope | All classes, interfaces, traits, enums (excluding restricted test namespaces) |
| Configurable | Via `restrictedTestNamespacePrefixes` |

## What It Detects

Reports public API elements that are missing PHPDoc comments:

```php
class UserService  // ERROR: Class UserService is missing a PHPDoc comment.
{
    public const VERSION = '1.0';  // ERROR: Public constant is missing a PHPDoc comment.

    public string $name = '';  // ERROR: Public property is missing a PHPDoc comment.

    public function findUser(int $id): ?User  // ERROR: Public method is missing a PHPDoc comment.
    {
        // ...
    }

    public function __toString(): string  // ERROR: Magic methods are also checked.
    {
        return $this->name;
    }
}
```

### Not reported

- Classes in restricted test namespaces (`Tests\Unit`, `Tests\Integration` by default)
- Anonymous classes
- Private and protected methods, properties, and constants
- Constructor promoted properties (covered by the constructor's `@param` tags)
- Enum cases

## Why This Is an Error

Public API elements form the contract between a class and its consumers. Without PHPDoc:

1. **IDE support degrades**: Type hints, parameter descriptions, and return value documentation are unavailable in autocomplete and hover tooltips.

2. **AI generators skip documentation**: AI code generators frequently produce undocumented public APIs, creating technical debt that accumulates across the codebase.

3. **Intent is unclear**: Method names alone rarely communicate preconditions, postconditions, side effects, or the meaning of parameters and return values.

## How to Fix

Add a multi-line PHPDoc block to each public element:

```php
/**
 * Manages user lifecycle operations.
 */
class UserService
{
    /**
     * Current API version.
     */
    public const VERSION = '1.0';

    /**
     * Display name of the service.
     */
    public string $name = '';

    /**
     * Finds a user by their unique identifier.
     *
     * @param int $id the user ID to search for
     * @return User|null the user, or null if not found
     */
    public function findUser(int $id): ?User
    {
        // ...
    }

    /**
     * Returns a string representation of the service.
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
```

For interfaces, all methods are implicitly public and require PHPDoc:

```php
/**
 * Contract for user repository implementations.
 */
interface UserRepositoryInterface
{
    /**
     * Finds a user by ID.
     *
     * @param int $id the user ID
     * @return User|null the user, or null if not found
     */
    public function find(int $id): ?User;
}
```
