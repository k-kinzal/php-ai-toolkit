# OverrideMustHaveAttributeRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.overrideMustHaveAttribute` |
| Scope | All classes |
| Configurable | No |

## What It Detects

Reports methods that override a non-abstract parent method without the `#[Override]` attribute.

```php
class BaseRepository
{
    public function findAll(): array
    {
        return [];
    }
}

class UserRepository extends BaseRepository
{
    // ERROR: Override method findAll() must have the #[\Override] attribute.
    public function findAll(): array
    {
        return $this->query('SELECT * FROM users');
    }
}
```

### Not reported

- Methods that implement an abstract parent method (these are implementations, not overrides)
- Methods that do not exist in the parent class
- Methods already annotated with `#[Override]`

## Why This Is an Error

Without `#[Override]`, there is no explicit signal that a method intentionally overrides a parent. This creates two problems:

1. **Silent breakage on rename**: If the parent method is renamed, the child method silently becomes a standalone method instead of an override. With `#[Override]`, PHP throws a fatal error, catching the mismatch immediately.

2. **Unclear intent**: When reading the code, it is not obvious whether a method overrides a parent or is a new method. AI code generators are especially prone to accidentally shadowing parent methods without realizing it.

The `#[Override]` attribute, introduced in PHP 8.3, serves as a contract: "this method must exist in a parent." It turns silent failures into loud errors and makes the override relationship explicit in the source code.

## How to Fix

Add the `#[Override]` attribute to the method:

```php
class UserRepository extends BaseRepository
{
    #[\Override]
    public function findAll(): array
    {
        return $this->query('SELECT * FROM users');
    }
}
```

Common places where this rule triggers:

```php
class AppTestCase extends TestCase
{
    // Bad
    protected function setUp(): void { ... }
    protected function tearDown(): void { ... }

    // Good
    #[\Override]
    protected function setUp(): void { ... }

    #[\Override]
    protected function tearDown(): void { ... }
}
```

If the method is not intended to override the parent, rename it to avoid the collision:

```php
class UserRepository extends BaseRepository
{
    // If this is NOT meant to override BaseRepository::findAll()
    public function findAllActive(): array
    {
        return $this->query('SELECT * FROM users WHERE active = 1');
    }
}
```
