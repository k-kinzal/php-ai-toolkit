# NoHelperMethodInTestClassRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.testClassNonOverrideMethod` |
| Scope | Test classes in restricted namespaces |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports methods in test classes that are not:

- **Test methods**: methods starting with `test` or annotated with `#[Test]`
- **Data providers**: methods starting with `provider`
- **Framework overrides**: methods that override a parent class method (abstract implementations or `#[Override]`-annotated overrides)

```php
namespace Tests\Unit\Service;

final class UserServiceTest extends TestCase
{
    public function testCreate(): void { ... }

    public function providerInvalidEmails(): array { ... }

    #[Override]
    protected function setUp(): void { ... }

    // ERROR: Method buildUser() is not an override in Tests\Unit\Service\UserServiceTest.
    protected function buildUser(string $name = 'Alice'): User
    {
        return new User($name, 'alice@example.com', 30);
    }

    // ERROR: Method assertUserIsValid() is not an override.
    protected function assertUserIsValid(User $user): void
    {
        self::assertNotEmpty($user->name());
        self::assertNotEmpty($user->email());
    }
}
```

## Why This Is an Error

Test classes should have a flat, predictable structure: test methods, data providers, and framework lifecycle overrides. Any other method is a helper that introduces problems:

1. **Shared abstractions reduce readability**: A test should be a self-contained specification. When setup or assertions are hidden behind helper methods, the reader must navigate multiple methods to understand one test case.

2. **Helpers grow into frameworks**: Helper methods tend to accumulate parameters, default values, and conditional logic over time. Eventually the test class becomes a mini-framework that requires its own documentation.

3. **Hides what each test actually needs**: A `buildUser()` helper with defaults obscures which parameters matter for a specific test case. Inline construction makes each test's requirements explicit.

## How to Fix

### Option 1: Inline into test methods

```php
final class UserServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $user = new User('Alice', 'alice@example.com', 30);
        $service = new UserService();

        $service->create($user);

        self::assertNotEmpty($user->name());
        self::assertNotEmpty($user->email());
    }
}
```

### Option 2: Extract to a dedicated helper class

For genuinely reusable setup logic, create a helper class in a shared test support namespace:

```php
// tests/Support/UserFixture.php
namespace Tests\Support;

final class UserFixture
{
    public static function alice(): User
    {
        return new User('Alice', 'alice@example.com', 30);
    }
}
```

```php
// tests/Unit/Service/UserServiceTest.php
final class UserServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $user = UserFixture::alice();
        // ...
    }
}
```

### Option 3: Use data providers for parameterized assertions

```php
final class UserServiceTest extends TestCase
{
    public static function providerValidUsers(): array
    {
        return [
            'basic user' => [new User('Alice', 'alice@example.com', 30)],
            'minimal user' => [new User('B', 'b@b.com', 1)],
        ];
    }

    #[DataProvider('providerValidUsers')]
    public function testUserIsValid(User $user): void
    {
        self::assertNotEmpty($user->name());
        self::assertNotEmpty($user->email());
    }
}
```

## Configuration

Customize which namespaces are considered restricted:

```neon
parameters:
    customRules:
        restrictedTestNamespacePrefixes:
            - 'Tests\Unit'
            - 'Tests\Integration'
```
