# NoPropertyInTestClassRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.testClassProperty` |
| Scope | Test classes in restricted namespaces |
| Configurable | Yes (namespace prefixes) |

## What It Detects

Reports property declarations in test classes within `Tests\Unit` and `Tests\Integration` namespaces.

```php
namespace Tests\Unit\Service;

final class UserServiceTest extends TestCase
{
    // ERROR: Property $service is prohibited in Tests\Unit and Tests\Integration classes.
    private UserService $service;

    // ERROR: Property $logger is prohibited in Tests\Unit and Tests\Integration classes.
    private MockObject&LoggerInterface $logger;

    #[Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->service = new UserService($this->logger);
    }

    public function testRegister(): void
    {
        $this->service->register('Alice');
        // ...
    }
}
```

## Why This Is an Error

Properties in test classes create shared mutable state between test methods. This leads to:

1. **Hidden dependencies between tests**: Test A may set a property value that test B relies on. When tests run in a different order or in isolation, failures appear seemingly at random.

2. **Obscured test setup**: When a property is initialized in `setUp()` and used across multiple test methods, each test's preconditions are split between two locations. Reading a single test method no longer tells you everything you need to understand it.

3. **Accumulating complexity**: AI code generators tend to add new properties for each new dependency, growing `setUp()` into a large initialization block that makes every test depend on every dependency, even when individual tests only use a subset.

## How to Fix

Declare values as local variables inside each test method:

```php
namespace Tests\Unit\Service;

final class UserServiceTest extends TestCase
{
    public function testRegister(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new UserService($logger);

        $service->register('Alice');

        $logger->expects(self::once())
            ->method('info')
            ->with('User registered: Alice');
    }

    public function testRegisterDuplicate(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('exists')->willReturn(true);

        $service = new UserService(
            $this->createMock(LoggerInterface::class),
            $repository,
        );

        $this->expectException(DuplicateUserException::class);
        $service->register('Alice');
    }
}
```

Each test method is now self-contained: its setup, action, and assertions are all visible in one place.

## Configuration

Customize which namespaces are considered restricted:

```neon
parameters:
    customRules:
        restrictedTestNamespacePrefixes:
            - 'Tests\Unit'
            - 'Tests\Integration'
```
