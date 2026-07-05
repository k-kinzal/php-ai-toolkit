# NoReflectionInTestClassRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.noReflectionInTestClass` |
| Scope | All test classes |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports instantiation of Reflection API classes (`ReflectionClass`, `ReflectionMethod`, `ReflectionProperty`, etc.) in test classes.

```php
namespace Tests\Unit\Service;

final class UserServiceTest extends TestCase
{
    public function testInternalState(): void
    {
        $service = new UserService();
        $service->register('Alice');

        // ERROR: Replace ReflectionProperty usage with assertions against public behavior.
        $ref = new ReflectionProperty($service, 'users');
        $ref->setAccessible(true);
        $users = $ref->getValue($service);

        self::assertCount(1, $users);
    }

    public function testPrivateMethod(): void
    {
        $service = new UserService();

        // ERROR: Replace ReflectionMethod usage with assertions against public behavior.
        $method = new ReflectionMethod($service, 'normalize');
        $method->setAccessible(true);
        $result = $method->invoke($service, 'Alice');

        self::assertSame('alice', $result);
    }
}
```

## Why This Is an Error

Reflection in tests is a sign that the test is verifying internal implementation rather than observable behavior. This leads to:

1. **Brittle tests**: Renaming a private property or method breaks the test, even though the class's behavior is unchanged. Tests become a maintenance burden that resists refactoring.

2. **Testing the wrong thing**: If a value is only accessible via Reflection, it is not part of the class's public contract. Asserting on it proves nothing about correctness from the caller's perspective.

3. **Masking design problems**: Needing Reflection often signals that the class under test has too many responsibilities or lacks a proper public API. Rather than working around the design with Reflection, the design itself should be improved.

## How to Fix

Test observable behavior through the public API:

```php
namespace Tests\Unit\Service;

final class UserServiceTest extends TestCase
{
    public function testRegisterAddsUser(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(
                static fn (User $user): bool => $user->name() === 'Alice',
            ));

        $service = new UserService($repository);
        $service->register('Alice');
    }

    public function testNormalizeThroughPublicBehavior(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('save')
            ->with(self::callback(
                static fn (User $user): bool => $user->name() === 'alice',
            ));

        $service = new UserService($repository);
        $service->register('Alice'); // normalize is called internally
    }
}
```

If the internal logic is complex enough to warrant direct testing, extract it into its own class with a public API and test that class separately.

## Configuration

Customize which namespaces are considered test classes:

```neon
parameters:
    customRules:
        testNamespacePrefixes:
            - 'Tests'
```
